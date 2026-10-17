<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IConfig;
use OCP\Http\Client\IClientService;

final class LicenseService {
 private const ENDPOINT='https://kassel-net.de/betrio/api/v1/license/activate';
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
 private function verifyAndStore(string $key):array{
  $instance=(string)$this->config->getSystemValue('instanceid','');
  $payload=['product'=>'betrio','license_key'=>$key,'instance_hash'=>hash('sha256',$instance),'betrio_version'=>$this->installedVersion(),'nextcloud_version'=>(string)$this->config->getSystemValue('version',''),'php_version'=>PHP_VERSION];
  try{
   $response=$this->clients->newClient()->post(self::ENDPOINT,['timeout'=>15,'headers'=>['Accept'=>'application/json','Content-Type'=>'application/json'],'body'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
   $data=json_decode($response->getBody(),true,512,JSON_THROW_ON_ERROR);
   if(!is_array($data)||($data['valid']??false)!==true||(string)($data['edition']??'')!==EditionService::PRO)throw new \RuntimeException('Der Kassel-NET-Lizenzserver hat keine gültige Betrio-Pro-Lizenz bestätigt.');
   $this->edition->storeLicense($key,$data);
   foreach(['pro_release_url','pro_release_sha256','pro_release_version','auto_pro_restore_attempt_version'] as $legacyKey)$this->config->deleteAppValue('reinhardterp',$legacyKey);
   $this->edition->markLicenseCheck('Betrio Pro ist aktiviert. Alle Pro-Funktionen sind Bestandteil der installierten Betrio-App.');
   return $data;
  }catch(\InvalidArgumentException|\RuntimeException $e){$this->edition->markLicenseCheck($e->getMessage());throw $e;}
  catch(\Throwable $e){$this->edition->markLicenseCheck('Lizenzserver nicht erreichbar. Installierter Editionsstatus bleibt unverändert.');throw new \RuntimeException('Kassel-NET-Lizenzserver nicht erreichbar. Der bisherige Betrio-Status wurde nicht verändert.');}
 }
 private function installedVersion():string{return trim((string)$this->config->getAppValue('reinhardterp','installed_version',''));}
}
