<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\IAppData;
use OCP\IDBConnection;
use OCP\IConfig;

final class BackupService {
    private const APP = 'reinhardterp';
    private const FORMAT = 1;
    private const TABLES = [
        're_erp_absences','re_erp_activities','re_erp_attendance','re_erp_attendance_audit','re_erp_bank_accounts','re_erp_bank_transactions',
        're_erp_billing_batch_items','re_erp_billing_batches','re_erp_billing_checks','re_erp_cash_entries','re_erp_checklist','re_erp_communications',
        're_erp_customer_contacts','re_erp_customer_reminders','re_erp_customers','re_erp_delivery_items','re_erp_delivery_notes','re_erp_document_rules',
        're_erp_documents','re_erp_hourly_rates','re_erp_invoice_audit','re_erp_invoice_items','re_erp_invoice_payments','re_erp_invoices','re_erp_material_groups',
        're_erp_materials','re_erp_mobile_sync_receipts','re_erp_mobile_tokens','re_erp_offer_items','re_erp_offer_text_blocks','re_erp_offers','re_erp_order_items',
        're_erp_order_notes','re_erp_orders','re_erp_project_documents','re_erp_project_phases','re_erp_project_suppliers','re_erp_project_users','re_erp_projects',
        're_erp_purchase_items','re_erp_report_files','re_erp_report_hours','re_erp_report_items','re_erp_reports','re_erp_sequences','re_erp_stock_movements',
        're_erp_suppliers','re_erp_team_event_users','re_erp_team_events','re_erp_time_timers','re_erp_user_roles','re_erp_workday_entries','re_erp_workday_materials','re_erp_workdays'
    ];
    public function __construct(private IDBConnection $db, private IAppData $appData, private IConfig $config) {}

    public function create(string $kind='manual'): array {
        $tables=[];$total=0;
        foreach(self::TABLES as $table){
            try{$q=$this->db->getQueryBuilder();$q->select('*')->from($table);$r=$q->executeQuery();$rows=[];while(($row=$r->fetch())!==false){$rows[]=$row;}$r->closeCursor();$tables[$table]=$rows;$total+=count($rows);}catch(\Throwable $e){/* table may not exist on older installs */}
        }
        $payload=['manifest'=>['format'=>self::FORMAT,'app'=>'reinhardterp','version'=>$this->config->getAppValue(self::APP,'installed_version','2.15.0'),'created_at'=>date(DATE_ATOM),'kind'=>$kind,'tables'=>count($tables),'rows'=>$total],'tables'=>$tables];
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $content=function_exists('gzencode')?gzencode($json,6):$json;
        $ext=function_exists('gzencode')?'.json.gz':'.json';
        $name='betrio-backup-'.date('Ymd-His').'-'.$kind.$ext;
        $folder=$this->folder();
        try{$folder->getFile($name);$name='betrio-backup-'.date('Ymd-His').'-'.$kind.'-'.substr(bin2hex(random_bytes(3)),0,6).$ext;}catch(\Throwable){}
        $file=$folder->newFile($name);$file->putContent($content);
        $this->prune();
        return ['name'=>$name,'rows'=>$total,'tables'=>count($tables),'size'=>strlen($content)];
    }
    public function list(): array {
        $out=[];foreach($this->folder()->getDirectoryListing() as $node){if(!$node instanceof ISimpleFile||!str_starts_with($node->getName(),'betrio-backup-'))continue;$out[]=['name'=>$node->getName(),'size'=>$node->getSize(),'mtime'=>$node->getMTime()];}
        usort($out,fn($a,$b)=>$b['mtime']<=>$a['mtime']);return $out;
    }
    public function content(string $name): string {$this->assertName($name);$n=$this->folder()->getFile($name);if(!$n instanceof ISimpleFile)throw new \RuntimeException('Backup nicht gefunden.');return $n->getContent();}
    public function delete(string $name): void {$this->assertName($name);$n=$this->folder()->getFile($name);if($n instanceof ISimpleFile)$n->delete();}
    public function inspectContent(string $content,string $name='backup'): array {
        $p=$this->decode($content,$name);
        $m=$p['manifest'];
        $actualTables=count($p['tables']);$actualRows=0;foreach($p['tables'] as $rows){$actualRows+=is_array($rows)?count($rows):0;}
        if((int)($m['tables']??-1)!==$actualTables||(int)($m['rows']??-1)!==$actualRows)throw new \InvalidArgumentException('Das Backup ist unvollständig: Manifest und Inhalt stimmen nicht überein.');
        $installed=$this->config->getAppValue(self::APP,'installed_version','');
        $backup=(string)($m['version']??'');
        if($installed===''||$backup==='')throw new \InvalidArgumentException('Die Betrio-Version des Backups konnte nicht geprüft werden.');
        if($backup!==$installed)throw new \InvalidArgumentException('Versionskonflikt: Backup '.$backup.' kann nur mit derselben installierten Betrio-Version wiederhergestellt werden. Installiert ist '.$installed.'.');
        $m['actual_tables']=$actualTables;$m['actual_rows']=$actualRows;$m['installed_version']=$installed;
        return $m;
    }
    public function restoreUploaded(string $content,string $name): array {
        $this->inspectContent($content,$name);$payload=$this->decode($content,$name);$this->create('safety');
        $tables=$payload['tables'];$this->db->beginTransaction();
        try{
            foreach(array_reverse(self::TABLES) as $table){if(!array_key_exists($table,$tables))continue;$q=$this->db->getQueryBuilder();$q->delete($table)->executeStatement();}
            foreach(self::TABLES as $table){if(!isset($tables[$table])||!is_array($tables[$table]))continue;foreach($tables[$table] as $row){if(!is_array($row)||$row===[])continue;$q=$this->db->getQueryBuilder();$q->insert($table);$vals=[];foreach($row as $col=>$value){$vals[$col]=$q->createNamedParameter($value);}$q->values($vals)->executeStatement();}}
            $this->db->commit();
        }catch(\Throwable $e){$this->db->rollBack();throw $e;}
        return $payload['manifest'];
    }
    public function settings(): array {return ['schedule'=>$this->config->getAppValue(self::APP,'backup_schedule','off'),'retention'=>(int)$this->config->getAppValue(self::APP,'backup_retention','7'),'last'=>$this->config->getAppValue(self::APP,'backup_last','')];}
    public function saveSettings(string $schedule,int $retention): void {if(!in_array($schedule,['off','daily','weekly'],true))$schedule='off';if(!in_array($retention,[7,14,30],true))$retention=7;$this->config->setAppValue(self::APP,'backup_schedule',$schedule);$this->config->setAppValue(self::APP,'backup_retention',(string)$retention);$this->prune();}
    public function runAutomaticIfDue(): void {$s=$this->settings();if($s['schedule']==='off')return;$last=$s['last']!==''?strtotime($s['last']):false;$due=$s['schedule']==='daily'?86400:604800;if($last!==false&&time()-$last<$due)return;$this->create('automatic');$this->config->setAppValue(self::APP,'backup_last',date(DATE_ATOM));}
    private function decode(string $content,string $name): array {
        if(str_ends_with(strtolower($name),'.gz')){$d=@gzdecode($content);if($d===false)throw new \InvalidArgumentException('Das komprimierte Backup ist beschädigt.');$content=$d;}
        try{$p=json_decode($content,true,512,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new \InvalidArgumentException('Die Backup-Datei enthält kein gültiges JSON.');}
        if(!is_array($p)||($p['manifest']['app']??'')!=='reinhardterp'||(int)($p['manifest']['format']??0)!==self::FORMAT||!is_array($p['tables']??null))throw new \InvalidArgumentException('Keine gültige Betrio-Backup-Datei.');
        foreach(array_keys($p['tables']) as $table){if(!in_array($table,self::TABLES,true))throw new \InvalidArgumentException('Das Backup enthält eine unbekannte Tabelle: '.$table);}
        return $p;
    }
    private function folder(): ISimpleFolder {try{return $this->appData->getFolder('backups');}catch(\Throwable){}return $this->appData->newFolder('backups');}
    private function assertName(string $name): void {if(!preg_match('/^betrio-backup-[A-Za-z0-9._-]+$/',$name))throw new \InvalidArgumentException('Ungültiger Backup-Name.');}
    private function prune(): void {$keep=(int)$this->config->getAppValue(self::APP,'backup_retention','7');$files=$this->list();$auto=array_values(array_filter($files,fn($f)=>str_contains($f['name'],'-automatic.')));foreach(array_slice($auto,$keep) as $f){try{$this->delete($f['name']);}catch(\Throwable){}}}
}
