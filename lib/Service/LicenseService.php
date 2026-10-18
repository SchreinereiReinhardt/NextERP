<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IConfig;
use OCP\Http\Client\IClientService;

final class LicenseService {
 private const ENDPOINT='https://kassel-net.de/betrio/api/v1/license/activate';
 private const LICENSE_V2_PUBLIC_KEY=<<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEArdtufwq69OxT7WDfiexk
ptfvNq+vjTcx7iLn5kljjsKpipQXjfIqkOtwqP+6FoPpu6I4ZBAF5pS5R7SoiCY7
EPGJlTDkYXpI/AslSyU/eRQ1UHS030Q1NBeEEP5xLQPNk/6VDKnaXKfPM2TW/u7c
ohgDPnMXqMdA9rBeT3OeIJ5h6N3fFitxtEuKDKfUAb3iqo+M+JxNFEKSDxAnwn0Z
b41v5eWJ6V3qH6GTgZusUt8cQNqxQebSjuZ0mRB8XRcfkNatlBSHLBKtwPwdb1Qw
aiPMXgVRasmH1/fEEJX/mGi/wQo+Cx1ATOcXEnLjO2v9EzP/XXq9lq3KmfO7WDkl
fkrCbj9OIL8s0wu16/G0IpaLi4frN1Nlxc2tAMTY7XzqMmlJ3TSddQlOgZwmkkVA
Hy7Xp1UnE2q8bfeB5ru0VTjX9N8unk88PpzpWm1rGRPqZlRAGtfej2nfF/HDaVpu
D3wtTa9n2q8JoekS6I1/7xD4TPywwCM8dK68e+0VrCgdAgMBAAE=
-----END PUBLIC KEY-----
PEM;
 public function __construct(private IClientService $clients,private IConfig $config,private EditionService $edition){}
 public function restoreStoredPro():array{
  $key=$this->edition->licenseKey();
  if($key==='')throw new \RuntimeException('Keine gespeicherte Betrio-Pro-Lizenz vorhanden.');
  return $this->verifyAndStore($key);
 }
 public function activate(string $key):array{
  $key=trim($key);if($key==='')throw new \InvalidArgumentException('Bitte einen Lizenzschlüssel eingeben.');
  return $this->verifyAndStore($key);
 }
 public function scheduledCheck():void{
  if($this->edition->licenseKey()==='')return;
  try{$this->restoreStoredPro();}catch(\Throwable){/* Status und Meldung werden im Prüfpfad gespeichert; Cron darf nicht abbrechen. */}
 }
 private function verifyAndStore(string $key):array{
  $instance=(string)$this->config->getSystemValue('instanceid','');
  $payload=['product'=>'betrio','license_key'=>$key,'instance_hash'=>hash('sha256',$instance),'betrio_version'=>$this->installedVersion(),'nextcloud_version'=>(string)$this->config->getSystemValue('version',''),'php_version'=>PHP_VERSION];
  try{
   try{
    $response=$this->clients->newClient()->post(self::ENDPOINT,['timeout'=>15,'headers'=>['Accept'=>'application/json','Content-Type'=>'application/json'],'body'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
   }catch(\Throwable $e){
    $message='Lizenzserver vorübergehend nicht erreichbar. Ihre bestehende Pro-Lizenz bleibt unverändert aktiv.';
    $this->edition->markLicenseCheck($message);
    throw new \RuntimeException($message,0,$e);
   }
   $data=json_decode($response->getBody(),true,512,JSON_THROW_ON_ERROR);
   if(!is_array($data))throw new \RuntimeException('Der Kassel-NET-Lizenzserver hat keine gültige Antwort geliefert.');
   if(($data['valid']??false)!==true||(string)($data['edition']??'')!==EditionService::PRO){
    $status=(string)($data['status']??'invalid');
    $this->edition->storeRejectedLicenseStatus($status,(string)($data['expires_at']??''));
    throw new \InvalidArgumentException($this->statusMessage($status));
   }
   $this->verifyLicenseV2($data, (string)$payload['instance_hash']);
   $this->edition->storeLicense($key,$data);
   foreach(['pro_release_url','pro_release_sha256','pro_release_version','auto_pro_restore_attempt_version'] as $legacyKey)$this->config->deleteAppValue('reinhardterp',$legacyKey);
   $this->edition->markLicenseCheck((string)($data['message']??'Betrio Pro Lizenz gültig.'));
   return $data;
  }catch(\InvalidArgumentException $e){$this->edition->markLicenseCheck($e->getMessage());throw $e;}
  catch(\RuntimeException $e){$this->edition->markLicenseCheck($e->getMessage());throw $e;}
  catch(\Throwable $e){$this->edition->markLicenseCheck('Lizenzserver nicht erreichbar. Installierter Editionsstatus bleibt unverändert.');throw new \RuntimeException('Kassel-NET-Lizenzserver nicht erreichbar. Der bisherige Betrio-Status wurde nicht verändert.');}
 }
 private function verifyLicenseV2(array $response,string $expectedInstanceHash):void{
  $envelope=$response['license_v2']??null;
  if(!is_array($envelope))throw new \RuntimeException('Der Lizenzserver hat keine signierte License-2.0-Bestätigung geliefert.');
  if((int)($envelope['version']??0)!==2||(string)($envelope['algorithm']??'')!=='RS256')throw new \RuntimeException('Die License-2.0-Bestätigung verwendet ein unbekanntes Format.');
  $payloadEncoded=(string)($envelope['payload']??'');$signatureEncoded=(string)($envelope['signature']??'');
  $payloadJson=$this->base64UrlDecode($payloadEncoded);$signature=$this->base64UrlDecode($signatureEncoded);
  if($payloadJson===null||$signature===null)throw new \RuntimeException('Die License-2.0-Bestätigung ist beschädigt.');
  $verified=openssl_verify($payloadJson,$signature,self::LICENSE_V2_PUBLIC_KEY,OPENSSL_ALGO_SHA256);
  if($verified!==1)throw new \RuntimeException('Die kryptografische Signatur der Betrio-Pro-Lizenz ist ungültig.');
  try{$signed=json_decode($payloadJson,true,512,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new \RuntimeException('Der signierte License-2.0-Inhalt ist ungültig.',0,$e);}
  if(!is_array($signed)||(int)($signed['v']??0)!==2||(string)($signed['iss']??'')!=='kassel-net.de'||(string)($signed['product']??'')!=='betrio')throw new \RuntimeException('Die signierte Lizenz gehört nicht zu Betrio License 2.0.');
  if(!hash_equals(strtolower($expectedInstanceHash),strtolower((string)($signed['instance_hash']??''))))throw new \RuntimeException('Die signierte Betrio-Pro-Lizenz gehört zu einer anderen Installation.');
  if((string)($signed['edition']??'')!==EditionService::PRO||(string)($signed['status']??'')!=='active')throw new \RuntimeException('Die signierte Betrio-Pro-Lizenz ist nicht aktiv.');
  $features=$signed['features']??[];if(!is_array($features)||!in_array('pro',$features,true))throw new \RuntimeException('Die signierte Lizenz enthält keine Betrio-Pro-Berechtigung.');
  $signedExpires=(string)($signed['expires_at']??'');$responseExpires=trim((string)($response['expires_at']??''));
  if($responseExpires!==''&&($signedExpires===''||strtotime($signedExpires)!==strtotime($responseExpires)))throw new \RuntimeException('Das signierte Ablaufdatum der Betrio-Pro-Lizenz stimmt nicht mit der Serverantwort überein.');
 }
 private function base64UrlDecode(string $value):?string{
  if($value===''||preg_match('/[^A-Za-z0-9_-]/',$value))return null;
  $padding=(4-(strlen($value)%4))%4;$decoded=base64_decode(strtr($value,'-_','+/').str_repeat('=',$padding),true);return $decoded===false?null:$decoded;
 }
 private function statusMessage(string $status):string{return match($status){'expired'=>'Die Betrio-Pro-Lizenz ist abgelaufen.','blocked'=>'Die Betrio-Pro-Lizenz wurde gesperrt.','bound_to_other_installation'=>'Die Betrio-Pro-Lizenz ist an eine andere Installation gebunden.',default=>'Der Kassel-NET-Lizenzserver hat keine gültige Betrio-Pro-Lizenz bestätigt.'};}
 private function installedVersion():string{return trim((string)$this->config->getAppValue('reinhardterp','installed_version',''));}
}
