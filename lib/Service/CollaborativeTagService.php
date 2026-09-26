<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

/** Read-only bridge from Betrio project files to Nextcloud Collaborative Tags. */
final class CollaborativeTagService {
 public function __construct(private ISystemTagManager $tags,private ISystemTagObjectMapper $mapper,private IUserSession $users){}

 /** @param array<int,array<string,mixed>> $files */
 public function enrichFiles(array $files):array{
  $ids=[];foreach($files as $file){$id=(int)($file['id']??0);if($id>0)$ids[]=(string)$id;}
  if($ids===[])return $files;
  try{$mapped=$this->mapper->getTagIdsForObjects(array_values(array_unique($ids)),'files');}catch(\Throwable){return $files;}
  $allTagIds=[];foreach($mapped as $tagIds){foreach((array)$tagIds as $tagId)$allTagIds[(string)$tagId]=true;}
  $tagObjects=[];
  if($allTagIds!==[]){try{$tagObjects=$this->tags->getTagsByIds(array_keys($allTagIds),$this->users->getUser());}catch(\Throwable){try{$tagObjects=$this->tags->getTagsByIds(array_keys($allTagIds));}catch(\Throwable){$tagObjects=[];}}}
  foreach($files as &$file){$id=(string)((int)($file['id']??0));$names=[];foreach((array)($mapped[$id]??[]) as $tagId){$tag=$tagObjects[(string)$tagId]??$tagObjects[(int)$tagId]??null;if($tag!==null){try{$name=trim((string)$tag->getName());if($name!=='')$names[]=$name;}catch(\Throwable){}}}$names=array_values(array_unique($names));natcasesort($names);$file['collaborativeTags']=array_values($names);}unset($file);
  return $files;
 }
 /** @param array<int,array<string,mixed>> $files */
 public function availableTags(array $files):array{$out=[];foreach($files as $file){foreach((array)($file['collaborativeTags']??[]) as $tag){$tag=trim((string)$tag);if($tag!=='')$out[$tag]=$tag;}}natcasesort($out);return array_values($out);}
 /** @param array<int,array<string,mixed>> $files */
 public function filter(array $files,string $tag):array{$tag=trim($tag);if($tag==='')return $files;return array_values(array_filter($files,static fn(array $file):bool=>in_array($tag,(array)($file['collaborativeTags']??[]),true)));}
}
