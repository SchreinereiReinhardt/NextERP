<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\IUserSession;

final class ProjectFileActivityListener implements IEventListener {
 public function __construct(private IDBConnection $db, private IUserSession $session) {}

 public function handle(Event $event): void {
  $class=get_class($event);
  $action='';$title='';$path='';$oldPath='';
  if(str_ends_with($class,'NodeCreatedEvent')){$action='file_created';$title='Datei/Ordner hinzugefügt';$path=$this->nodePath($event,'getNode');}
  elseif(str_ends_with($class,'NodeWrittenEvent')){$action='file_changed';$title='Datei geändert';$path=$this->nodePath($event,'getNode');}
  elseif(str_ends_with($class,'NodeDeletedEvent')){$action='file_deleted';$title='Datei/Ordner gelöscht';$path=$this->nodePath($event,'getNode');}
  elseif(str_ends_with($class,'NodeRenamedEvent')){$action='file_moved';$title='Datei/Ordner verschoben';$oldPath=$this->nodePath($event,'getSource');$path=$this->nodePath($event,'getTarget');if($path==='')$path=$this->nodePath($event,'getNode');}
  else{return;}
  if($path==='')return;
  $project=$this->matchProject($path);if($project===null)return;
  $relative=$this->relativePath($path,(string)$project['folder_path']);
  if($relative==='')return;
  if($action==='file_changed' && $this->recentCreateExists((int)$project['id'],$relative))return;
  $details=$relative;
  if($oldPath!==''){$old=$this->relativePath($oldPath,(string)$project['folder_path']);if($old!==''&&$old!==$relative)$details=$old.' → '.$relative;}
  $this->record($project,$action,$title,$details);
 }
 private function nodePath(object $event,string $method): string {try{if(!method_exists($event,$method))return '';$node=$event->{$method}();return is_object($node)&&method_exists($node,'getPath')?trim((string)$node->getPath(),'/'):'';}catch(\Throwable){return '';}}
 private function matchProject(string $path): ?array {
  $norm='/'.trim($path,'/');$qb=$this->db->getQueryBuilder();$qb->select('id','customer_id','folder_path','created_by')->from('re_erp_projects')->where($qb->expr()->isNotNull('folder_path'));
  $res=$qb->executeQuery();while($row=$res->fetch()){$base=trim((string)($row['folder_path']??''),'/');if($base==='')continue;$owner=trim((string)($row['created_by']??''));$needle='/'.($owner!==''?$owner.'/files/':'').$base;if($norm===$needle||str_starts_with($norm,$needle.'/'))return $row;if(str_ends_with($norm,'/'.$base)||str_contains($norm,'/'.$base.'/'))return $row;}return null;
 }
 private function relativePath(string $path,string $base): string {$path='/'.trim($path,'/');$base=trim($base,'/');$pos=strpos($path,'/'.$base);if($pos===false)return trim($path,'/');return trim(substr($path,$pos+strlen($base)+1),'/');}
 private function recentCreateExists(int $projectId,string $details): bool {$qb=$this->db->getQueryBuilder();$qb->select('id')->from('re_erp_activities')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->andWhere($qb->expr()->eq('action',$qb->createNamedParameter('file_created')))->andWhere($qb->expr()->eq('details',$qb->createNamedParameter($details)))->andWhere($qb->expr()->gte('created_at',$qb->createNamedParameter(date('Y-m-d H:i:s',time()-10))))->setMaxResults(1);return (bool)$qb->executeQuery()->fetch();}
 private function record(array $project,string $action,string $title,string $details): void {$qb=$this->db->getQueryBuilder();$qb->insert('re_erp_activities')->values(['customer_id'=>$qb->createNamedParameter((int)$project['customer_id']),'project_id'=>$qb->createNamedParameter((int)$project['id']),'entity_type'=>$qb->createNamedParameter('project_file'),'entity_id'=>$qb->createNamedParameter(null),'action'=>$qb->createNamedParameter($action),'title'=>$qb->createNamedParameter($title),'details'=>$qb->createNamedParameter($details),'created_by'=>$qb->createNamedParameter($this->session->getUser()?->getUID()??'system'),'created_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();}
}
