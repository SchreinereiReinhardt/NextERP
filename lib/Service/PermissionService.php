<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserSession;
final class PermissionService {
 public const ADMIN='admin'; public const OFFICE='office'; public const MANAGER='manager'; public const EMPLOYEE='employee'; public const TIME='time';
 public function __construct(private IDBConnection $db,private IUserSession $session,private IGroupManager $groups){}
 public function uid():string{return $this->session->getUser()?->getUID()??'';}
 public function isNextcloudAdmin(?string $uid=null):bool{$uid=$uid??$this->uid();return $uid!==''&&$this->groups->isAdmin($uid);}
 public function isEnabled(?string $uid=null):bool{
  $uid=$uid??$this->uid(); if($uid==='')return false;
  if($this->isNextcloudAdmin($uid))return true;
  $qb=$this->db->getQueryBuilder();$qb->select('enabled')->from('re_erp_user_roles')->where($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)));$r=$qb->executeQuery()->fetchOne();
  return $r===false ? false : (bool)$r;
 }
 public function role(?string $uid=null):string{
  $uid=$uid??$this->uid(); if($uid==='')return self::TIME;
  if($this->isNextcloudAdmin($uid))return self::ADMIN;
  $qb=$this->db->getQueryBuilder();$qb->select('role')->from('re_erp_user_roles')->where($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)));$r=$qb->executeQuery()->fetchOne();
  return is_string($r)&&$r!==''?$r:self::TIME;
 }
 public function can(string $permission):bool{
  if(!$this->isEnabled())return false;
  $role=$this->role(); if($role===self::ADMIN)return true;
  $map=[
   self::OFFICE=>['dashboard','customers','projects','reports','report_edit','time','time_all','time_billing','invoices','materials','calendar','users_view','crm','offers','orders','inventory','mobile','documents'],
   self::MANAGER=>['dashboard','projects','reports','report_edit','time','time_all','materials_use','calendar','crm','offers','orders','inventory','mobile','documents'],
   self::EMPLOYEE=>['dashboard','projects','reports','report_edit','time','materials_use','mobile'],
   self::TIME=>['time','mobile'],
  ];
  return in_array($permission,$map[$role]??[],true);
 }
 public function assert(string $permission):void{if(!$this->isEnabled())throw new \OCP\AppFramework\Http\ForbiddenException('Dieser Benutzer ist für das ERP gesperrt.');if(!$this->can($permission))throw new \OCP\AppFramework\Http\ForbiddenException('Keine Berechtigung für diesen ERP-Bereich.');}

 public const PROJECT_FOLDERS=['00_Eingang','01_Aufmass','02_Planung','03_Zeichnungen','04_Material','05_Bestellungen','06_Rapporte','07_Fotos','08_Abnahme','09_Rechnung','10_Angebote','11_Auftraege','12_Sonstiges'];
 public const EMPLOYEE_DEFAULT_FOLDERS=['01_Aufmass','02_Planung','03_Zeichnungen','04_Material','05_Bestellungen','06_Rapporte','07_Fotos','08_Abnahme','12_Sonstiges'];
 public function isProjectSupervisor(?string $uid=null):bool{return in_array($this->role($uid),[self::ADMIN,self::OFFICE,self::MANAGER],true);}
 public function assertProjectManager():void{if(!$this->isEnabled())throw new \OCP\AppFramework\Http\ForbiddenException('Dieser Benutzer ist für das ERP gesperrt.');if(!$this->isProjectSupervisor())throw new \OCP\AppFramework\Http\ForbiddenException('Nur Administration, Büro oder Projektleitung dürfen Projektfreigaben und Projektdaten verwalten.');}
 public function canAccessProject(int $projectId,?string $uid=null):bool{
  $uid=$uid??$this->uid();if($uid===''||!$this->isEnabled($uid))return false;if($this->isProjectSupervisor($uid))return true;
  $qb=$this->db->getQueryBuilder();$qb->select('id')->from('re_erp_project_users')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->andWhere($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)));return (bool)$qb->executeQuery()->fetchOne();
 }
 public function assertProjectAccess(int $projectId):void{$this->assert('projects');if(!$this->canAccessProject($projectId))throw new \OCP\AppFramework\Http\ForbiddenException('Dieses Projekt ist für deinen Benutzer nicht freigegeben.');}
 public function projectFolders(int $projectId,?string $uid=null):array{
  $uid=$uid??$this->uid();if($this->isProjectSupervisor($uid))return self::PROJECT_FOLDERS;
  $qb=$this->db->getQueryBuilder();$qb->select('folder_permissions')->from('re_erp_project_users')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->andWhere($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)));$raw=$qb->executeQuery()->fetchOne();
  if(!is_string($raw)||trim($raw)==='')return self::EMPLOYEE_DEFAULT_FOLDERS;$decoded=json_decode($raw,true);if(!is_array($decoded))return self::EMPLOYEE_DEFAULT_FOLDERS;return array_values(array_intersect(self::PROJECT_FOLDERS,array_map('strval',$decoded)));
 }
 public function canAccessProjectFolder(int $projectId,string $folder,?string $uid=null):bool{if(!$this->canAccessProject($projectId,$uid))return false;if($this->isProjectSupervisor($uid))return true;$folder=trim(explode('/',trim($folder,'/'))[0]??'');return $folder!==''&&in_array($folder,$this->projectFolders($projectId,$uid),true);}
 public function saveRole(string $uid,string $role,bool $enabled,string $updatedBy,?int $hourlyRateId=null,?float $individualHourlyRate=null):void{
  if(!in_array($role,[self::ADMIN,self::OFFICE,self::MANAGER,self::EMPLOYEE,self::TIME],true))throw new \InvalidArgumentException('Ungültige Rolle.');
  if($this->groups->isAdmin($uid))$enabled=true;
  $qb=$this->db->getQueryBuilder();$qb->select('id')->from('re_erp_user_roles')->where($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)));$id=$qb->executeQuery()->fetchOne();
  $values=['role'=>$role,'enabled'=>$enabled?1:0,'hourly_rate_id'=>$hourlyRateId&&$hourlyRateId>0?$hourlyRateId:null,'individual_hourly_rate'=>$individualHourlyRate&&$individualHourlyRate>0?$individualHourlyRate:null,'updated_by'=>$updatedBy,'updated_at'=>date('Y-m-d H:i:s')];
  if($id){$q=$this->db->getQueryBuilder();$q->update('re_erp_user_roles');foreach($values as $k=>$v)$q->set($k,$q->createNamedParameter($v));$q->where($q->expr()->eq('id',$q->createNamedParameter((int)$id)))->executeStatement();}
  else{$q=$this->db->getQueryBuilder();$q->insert('re_erp_user_roles')->values(['user_id'=>$q->createNamedParameter($uid),'role'=>$q->createNamedParameter($role),'enabled'=>$q->createNamedParameter($enabled?1:0),'hourly_rate_id'=>$q->createNamedParameter($hourlyRateId&&$hourlyRateId>0?$hourlyRateId:null),'individual_hourly_rate'=>$q->createNamedParameter($individualHourlyRate&&$individualHourlyRate>0?$individualHourlyRate:null),'updated_by'=>$q->createNamedParameter($updatedBy),'updated_at'=>$q->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();}
 }
 public function roles():array{$qb=$this->db->getQueryBuilder();$qb->select('user_id','role','enabled','hourly_rate_id','individual_hourly_rate')->from('re_erp_user_roles');$out=[];foreach($qb->executeQuery()->fetchAllAssociative() as $r)$out[$r['user_id']]=['role'=>$r['role'],'enabled'=>(bool)$r['enabled'],'hourly_rate_id'=>$r['hourly_rate_id'],'individual_hourly_rate'=>$r['individual_hourly_rate']];return $out;}
}
