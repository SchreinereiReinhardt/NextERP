<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Listener;
use OCA\ReinhardtERP\Service\DocumentInboxService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Events\TaskFailedEvent;
final class DocumentOcrTaskListener implements IEventListener {
 public function __construct(private DocumentInboxService $documents){}
 public function handle(Event $event):void{
  if(!$event instanceof TaskSuccessfulEvent && !$event instanceof TaskFailedEvent)return;
  $task=$event->getTask();
  if($task->getAppId()!=='reinhardterp'||$task->getTaskTypeId()!=='core:image2text:ocr')return;
  if(!preg_match('/^document:(\d+)$/',$task->getCustomId(),$m))return;
  $id=(int)$m[1];
  if($event instanceof TaskFailedEvent){$this->documents->markOcrFailed($id);return;}
  $output=$task->getOutput();$texts=is_array($output)?($output['output']??[]):[];
  if(is_string($texts))$texts=[$texts];
  $text=is_array($texts)?trim(implode("\n\n",array_map('strval',$texts))):'';
  if($text===''){$this->documents->markOcrFailed($id);return;}
  $this->documents->storeOcrText($id,$text);
  try{$this->documents->analyse($id);}catch(\Throwable){}
 }
}
