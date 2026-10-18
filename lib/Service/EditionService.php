<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IConfig;
use OCP\IDBConnection;

final class EditionService {
 public const COMMUNITY='community';
 public const LEGACY='legacy';
 public const PRO='pro';
 private const LIMITS=['active_projects'=>10,'invoices_per_month'=>5,'reports_per_month'=>10,'employees'=>3,'inventory_articles'=>50];
 private const LEGACY_LIMITS=['projects'=>5,'offers'=>10,'invoices'=>10];
 private const PRO_URL='https://kassel-net.de/betrio/';
 public function __construct(private IConfig $config,private IDBConnection $db){}
 public function edition():string{
  $stored=$this->config->getAppValue('reinhardterp','edition','');
  // A former installation may still carry the old local "pro" marker although
  // no licence key and no matching physical Pro module exist anymore. That is
  // exactly the Bestandsschutz migration case: keep all existing data/functions
  // accessible and move the installation to Legacy instead of pretending Pro is active.
  if($stored===self::PRO && $this->licenseKey()===''){
   $this->config->setAppValue('reinhardterp','edition',self::LEGACY);
   $this->config->setAppValue('reinhardterp','edition_initialized_at',date('c'));
   $this->config->setAppValue('reinhardterp','legacy_migrated_from','pro-without-license');
   return self::LEGACY;
  }
  if(in_array($stored,[self::COMMUNITY,self::LEGACY,self::PRO],true))return $stored;
  $edition=$this->hasExistingBusinessData()?self::LEGACY:self::COMMUNITY;
  $this->config->setAppValue('reinhardterp','edition',$edition);
  $this->config->setAppValue('reinhardterp','edition_initialized_at',date('c'));
  return $edition;
 }
 public function isCommunity():bool{return $this->edition()===self::COMMUNITY;}
 public function isPro():bool{return $this->edition()===self::PRO;}
 public function isLegacy():bool{return $this->edition()===self::LEGACY;}
 public function limit(string $key):?int{return self::LIMITS[$key]??null;}
 public function status():array{
  $expires=$this->config->getAppValue('reinhardterp','license_expires_at','');$remaining=null;if($expires!==''){try{$end=new \DateTimeImmutable($expires);$today=new \DateTimeImmutable('today');$remaining=max(0,(int)$today->diff($end)->format('%r%a'));}catch(\Throwable){}}
  return ['edition'=>$this->edition(),'proInstalled'=>$this->isPro(),'limits'=>self::LIMITS,'legacyLimits'=>self::LEGACY_LIMITS,'proUrl'=>self::PRO_URL,'licenseKey'=>$this->maskedKey(),'licenseCheckedAt'=>$this->config->getAppValue('reinhardterp','license_checked_at',''),'licenseMessage'=>$this->config->getAppValue('reinhardterp','license_message',''),'licenseExpiresAt'=>$expires,'licenseRemainingDays'=>$remaining,'licenseRemoteStatus'=>$this->config->getAppValue('reinhardterp','license_remote_status','')];
 }
 public function assertCanCreateProject():void{if($this->isLegacy()){$this->assertLegacyProjectSlot();return;}$this->assertCount('active_projects',$this->countWhere('re_erp_projects','is_archived',0),'aktive Projekte');}
 public function assertCanRestoreProject(int $projectId):void{if($this->isLegacy()){$cutoff=$this->legacyProjectCutoffId();if($projectId>$cutoff)$this->assertLegacyProjectSlot();return;}$this->assertCanCreateProject();}
 public function assertCanCreateOffer():void{if($this->isLegacy())$this->assertLegacyAllowance('offers','re_erp_offers','neue Angebote');}
 public function assertCanCreateInvoice():void{if($this->isLegacy()){$this->assertLegacyAllowance('invoices','re_erp_invoices','neue Rechnungen');return;}$this->assertCount('invoices_per_month',$this->countMonth('re_erp_invoices','created_at'),'Rechnungen in diesem Monat');}
 public function assertCanCreateReport():void{$this->assertCount('reports_per_month',$this->countMonth('re_erp_reports','created_at'),'Rapporte in diesem Monat');}
 public function assertCanEnableEmployee(?string $uid=null):void{
  if(!$this->isCommunity())return;
  $q=$this->db->getQueryBuilder();$q->select('user_id')->from('re_erp_user_roles')->where($q->expr()->eq('enabled',$q->createNamedParameter(1)));
  $result=$q->executeQuery();$ids=[];while($r=$result->fetch()){$id=(string)($r['user_id']??'');if($id!==''&&$id!==$uid)$ids[$id]=true;}$result->closeCursor();
  if(count($ids)>=self::LIMITS['employees'])$this->fail('Mitarbeiter',self::LIMITS['employees']);
 }
 public function assertCanCreateMaterial(int $additional=1):void{
  if(!$this->isCommunity())return;$current=$this->countWhere('re_erp_materials','active',1);if($current+$additional>self::LIMITS['inventory_articles'])$this->fail('aktive Lager-/Materialartikel',self::LIMITS['inventory_articles']);
 }
 public function assertPro(string $feature):void{if(!$this->isPro() && !$this->isLegacy())throw new \InvalidArgumentException($feature.' ist Bestandteil von Betrio Pro. Bestehende Daten bleiben selbstverständlich zugänglich.');}
 public function assertLicensedPro(string $feature):void{if(!$this->isPro())throw new \InvalidArgumentException($feature.' ist eine Betrio Pro-Funktion. Bitte aktivieren Sie Betrio Pro, um diese Funktion zu verwenden.');}
 public function storeLicense(string $key,array $response):void{
  $edition=(string)($response['edition']??'');if(($response['valid']??false)!==true||$edition!==self::PRO)throw new \InvalidArgumentException('Der Kassel-NET-Lizenzserver hat keine gültige Betrio-Pro-Lizenz bestätigt.');
  $current=$this->edition();if($current!==self::PRO)$this->config->setAppValue('reinhardterp','license_fallback_edition',$current);
  $this->config->setAppValue('reinhardterp','license_key',trim($key));$this->config->setAppValue('reinhardterp','edition',self::PRO);$this->config->setAppValue('reinhardterp','license_checked_at',date('c'));$this->config->setAppValue('reinhardterp','license_message',(string)($response['message']??'Betrio Pro aktiviert'));$this->config->setAppValue('reinhardterp','license_remote_status',(string)($response['status']??'active'));$this->config->setAppValue('reinhardterp','license_expires_at',trim((string)($response['expires_at']??'')));
 }
 public function storeRejectedLicenseStatus(string $status,string $expiresAt=''):void{
  $this->config->setAppValue('reinhardterp','license_remote_status',$status);if($expiresAt!=='')$this->config->setAppValue('reinhardterp','license_expires_at',$expiresAt);
  if(in_array($status,['expired','blocked','invalid','bound_to_other_installation'],true)){$fallback=$this->config->getAppValue('reinhardterp','license_fallback_edition',self::COMMUNITY);if(!in_array($fallback,[self::COMMUNITY,self::LEGACY],true))$fallback=self::COMMUNITY;$this->config->setAppValue('reinhardterp','edition',$fallback);}
 }
 public function markLicenseCheck(string $message):void{$this->config->setAppValue('reinhardterp','license_checked_at',date('c'));$this->config->setAppValue('reinhardterp','license_message',$message);}
 public function licenseKey():string{return trim($this->config->getAppValue('reinhardterp','license_key',''));}
 private function installedVersion():string{return trim((string)$this->config->getAppValue('reinhardterp','installed_version',''));}
 private function maskedKey():string{$k=$this->licenseKey();return $k===''?'':str_repeat('•',max(4,strlen($k)-4)).substr($k,-4);}
 private function assertCount(string $key,int $current,string $label):void{if(!$this->isCommunity())return;$limit=self::LIMITS[$key];if($current>=$limit)$this->fail($label,$limit);}
 private function assertLegacyProjectSlot():void{
  $limit=self::LEGACY_LIMITS['projects'];$cutoff=$this->legacyProjectCutoffId();
  $q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','cnt'))->from('re_erp_projects')->where($q->expr()->gt('id',$q->createNamedParameter($cutoff)))->andWhere($q->expr()->eq('is_archived',$q->createNamedParameter(0)));$r=$q->executeQuery()->fetch();$active=(int)($r['cnt']??0);
  if($active>=$limit)throw new \InvalidArgumentException('Betrio Legacy: Es können maximal '.$limit.' zusätzliche aktive Projekte gleichzeitig geführt werden. Archivieren Sie ein Legacy-Projekt, wird wieder ein Platz frei. Vorhandene Daten bleiben vollständig verfügbar. Betrio Pro hebt diese Begrenzung auf: '.self::PRO_URL);
 }
 private function legacyProjectCutoffId():int{
  $key='legacy_project_cutoff_id';$raw=$this->config->getAppValue('reinhardterp',$key,'');if($raw!=='')return (int)$raw;
  // 2.13.8-2.13.10 stored a project-count baseline. Convert that baseline into
  // an ID cutoff so projects created during those tests remain post-migration projects.
  $old=$this->config->getAppValue('reinhardterp','legacy_baseline_projects','');
  if($old!=='' && (int)$old>0){$q=$this->db->getQueryBuilder();$q->select('id')->from('re_erp_projects')->orderBy('id','ASC')->setFirstResult(max(0,(int)$old-1))->setMaxResults(1);$row=$q->executeQuery()->fetch();$cutoff=(int)($row['id']??0);}
  else{$q=$this->db->getQueryBuilder();$q->select($q->func()->max('id','max_id'))->from('re_erp_projects');$row=$q->executeQuery()->fetch();$cutoff=(int)($row['max_id']??0);}
  $this->config->setAppValue('reinhardterp',$key,(string)$cutoff);$this->config->setAppValue('reinhardterp','legacy_project_slots_initialized_at',date('c'));return $cutoff;
 }
 private function assertLegacyAllowance(string $key,string $table,string $label):void{
  $limit=self::LEGACY_LIMITS[$key];$baselineKey='legacy_baseline_'.$key;$raw=$this->config->getAppValue('reinhardterp',$baselineKey,'');$current=$this->countTable($table);
  if($raw===''){$this->config->setAppValue('reinhardterp',$baselineKey,(string)$current);$this->config->setAppValue('reinhardterp','legacy_limits_initialized_at',date('c'));$baseline=$current;}else{$baseline=(int)$raw;}
  if(max(0,$current-$baseline)>=$limit)throw new \InvalidArgumentException('Betrio Legacy: Das Kontingent von '.$limit.' '.$label.' nach dem Update ist aufgebraucht. Vorhandene Daten bleiben vollständig verfügbar. Betrio Pro hebt diese Begrenzung auf: '.self::PRO_URL);
 }
 private function countTable(string $table):int{$q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','cnt'))->from($table);$r=$q->executeQuery()->fetch();return (int)($r['cnt']??0);}
 private function fail(string $label,int $limit):never{throw new \InvalidArgumentException('Betrio Community: Das Limit von '.$limit.' '.$label.' ist erreicht. Vorhandene Daten bleiben vollständig verfügbar. Mit Betrio Pro entfällt dieses Limit: '.self::PRO_URL);}
 private function countWhere(string $table,string $field,mixed $value):int{$q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','cnt'))->from($table)->where($q->expr()->eq($field,$q->createNamedParameter($value)));$r=$q->executeQuery()->fetch();return (int)($r['cnt']??0);}
 private function countMonth(string $table,string $field):int{$from=date('Y-m-01 00:00:00');$to=date('Y-m-t 23:59:59');$q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','cnt'))->from($table)->where($q->expr()->gte($field,$q->createNamedParameter($from)))->andWhere($q->expr()->lte($field,$q->createNamedParameter($to)));$r=$q->executeQuery()->fetch();return (int)($r['cnt']??0);}
 private function hasExistingBusinessData():bool{foreach(['re_erp_customers','re_erp_projects','re_erp_offers','re_erp_orders','re_erp_invoices','re_erp_reports'] as $table){try{$q=$this->db->getQueryBuilder();$q->select('id')->from($table)->setMaxResults(1);if($q->executeQuery()->fetch())return true;}catch(\Throwable){}}return false;}
}
