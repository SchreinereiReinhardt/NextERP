<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\IConfig;

final class DocumentInboxService {
    public const INBOX = 'ERP/00_Dokumenteneingang/01_Unbearbeitet';

    public function __construct(
        private FolderService $folders,
        private IDBConnection $db,
        private IUserSession $session,
        private DocumentClassifierService $classifier,
        private DocumentRuleService $rules,
        private IConfig $config,
    ) {
    }

    public function ensureStructure(): void {
        $uid = $this->session->getUser()?->getUID();
        if ($uid !== null && $this->config->getAppValue('reinhardterp', 'document_inbox_owner', '') === '') {
            $this->config->setAppValue('reinhardterp', 'document_inbox_owner', $uid);
        }
        $this->folders->ensureFolderPath('ERP', '00_Dokumenteneingang/01_Unbearbeitet');
        $this->folders->ensureFolderPath('ERP', '00_Dokumenteneingang/02_In_Pruefung');
        $this->folders->ensureFolderPath('ERP', '00_Dokumenteneingang/03_Nicht_zugeordnet');
        $this->folders->ensureFolderPath('ERP', '00_Dokumenteneingang/99_Fehler');
        $this->folders->ensureFolderPath('ERP', '20_Lieferanten');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Eingangsrechnungen');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Ausgangsrechnungen');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Kontoauszuege');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Kasse');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Steuern');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Gutschriften');
        $this->folders->ensureFolderPath('ERP', '30_Finanzen/Sonstige_Belege');
        $this->folders->ensureFolderPath('ERP', '90_Archiv');
    }

    public function syncInbox(): int {
        $this->ensureStructure();
        $added = 0;
        foreach ($this->folders->listFiles(self::INBOX, 1000, 0) as $file) {
            if ($this->findByPath((string)$file['path'])) {
                continue;
            }
            $this->insertNewDocument([
                'file_id' => $file['id'] ?? null,
                'file_name' => $file['name'],
                'original_name' => $file['name'],
                'file_path' => $file['path'],
                'mime_type' => $file['mime'] ?? null,
                'file_size' => (int)($file['size'] ?? 0),
                'checksum' => $file['checksum'] ?? null,
                'source' => 'scan_folder',
            ]);
            $added++;
        }
        return $added;
    }

    public function upload(array $file): int {
        $this->ensureStructure();
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Bitte eine Datei auswählen.');
        }
        if ((int)($file['size'] ?? 0) > 100 * 1024 * 1024) {
            throw new \InvalidArgumentException('Die Datei darf maximal 100 MB groß sein.');
        }

        $name = $this->uniqueName(self::INBOX, (string)($file['name'] ?? 'Dokument.pdf'));
        $path = $this->folders->writeFromLocalFile(self::INBOX, $name, (string)$file['tmp_name']);
        $info = $this->folders->fileInfo($path);

        return $this->insertNewDocument([
            'file_id' => $info['id'] ?? null,
            'file_name' => $name,
            'original_name' => (string)($file['name'] ?? $name),
            'file_path' => $path,
            'mime_type' => $info['mime'] ?? ($file['type'] ?? null),
            'file_size' => (int)($info['size'] ?? ($file['size'] ?? 0)),
            'checksum' => $info['checksum'] ?? null,
            'source' => 'upload',
        ]);
    }

    public function analyse(int $id): array {
        $document = $this->one($id);
        if (!$document) {
            throw new \InvalidArgumentException('Dokument nicht gefunden.');
        }
        $content = $this->extractDocumentText($document);
        $suggestion = $this->classifier->classify(
            (string)$document['original_name'],
            $this->tableRows('re_erp_customers', 'name'),
            $this->tableRows('re_erp_projects', 'project_no'),
            $this->tableRows('re_erp_suppliers', 'name'),
            $content,
        );
        $suggestion = array_replace($suggestion, $this->rules->apply((string)$document['original_name']));
        $suggestion['analyzed_at'] = date('Y-m-d H:i:s');
        $this->update($id, $suggestion);
        return array_merge($document, $suggestion);
    }

    public function assign(int $id, array $data): array {
        $doc = $this->one($id);
        if (!$doc) {
            throw new \InvalidArgumentException('Dokument nicht gefunden.');
        }
        if (($doc['status'] ?? '') === 'assigned') {
            throw new \InvalidArgumentException('Das Dokument wurde bereits zugeordnet.');
        }

        $allowedTypes = [
            'incoming_invoice', 'outgoing_invoice', 'delivery_note', 'credit_note',
            'bank_statement', 'cash', 'tax', 'accounting_other', 'offer', 'order', 'report', 'drawing', 'other',
        ];
        $type = (string)($data['document_type'] ?? 'unassigned');
        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException('Bitte eine gültige Dokumentart auswählen.');
        }

        $customerId = (int)($data['customer_id'] ?? 0);
        $projectId = (int)($data['project_id'] ?? 0);
        $supplierId = (int)($data['supplier_id'] ?? 0);
        $orderId = (int)($data['order_id'] ?? 0);
        if ($projectId > 0) {
            $project = $this->tableOne('re_erp_projects', $projectId);
            if (!$project) {
                throw new \InvalidArgumentException('Das ausgewählte Projekt wurde nicht gefunden.');
            }
            $projectCustomerId = (int)($project['customer_id'] ?? 0);
            if ($customerId > 0 && $projectCustomerId > 0 && $customerId !== $projectCustomerId) {
                throw new \InvalidArgumentException('Das ausgewählte Projekt gehört nicht zum ausgewählten Kunden.');
            }
            if ($customerId === 0 && $projectCustomerId > 0) {
                $customerId = $projectCustomerId;
            }
        }
        if ($customerId > 0 && !$this->tableOne('re_erp_customers', $customerId)) {
            throw new \InvalidArgumentException('Der ausgewählte Kunde wurde nicht gefunden.');
        }
        if ($orderId > 0) {
            $order = $this->tableOne('re_erp_orders', $orderId);
            if (!$order) { throw new \InvalidArgumentException('Der ausgewählte Auftrag wurde nicht gefunden.'); }
            if ($projectId > 0 && (int)($order['project_id'] ?? 0) > 0 && (int)$order['project_id'] !== $projectId) { throw new \InvalidArgumentException('Der ausgewählte Auftrag gehört nicht zum ausgewählten Projekt.'); }
        }
        if ($supplierId > 0 && !$this->tableOne('re_erp_suppliers', $supplierId)) {
            throw new \InvalidArgumentException('Der ausgewählte Lieferant wurde nicht gefunden.');
        }

        $date = $this->date((string)($data['document_date'] ?? '')) ?? date('Y-m-d');
        $year = substr($date, 0, 4);
        $month = (int)substr($date, 5, 2);
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Das Belegdatum ist ungültig.');
        }

        $target = $this->targetFolder($type, $year, $month, $projectId, $supplierId);
        $name = $this->buildName(
            $doc,
            $type,
            $date,
            (string)($data['document_no'] ?? ''),
            (float)($data['gross_amount'] ?? 0),
        );
        $name = $this->uniqueName($target, $name);

        $sourcePath = (string)($doc['file_path'] ?? '');
        if ($sourcePath === '' || !$this->folders->exists($sourcePath)) {
            throw new \RuntimeException('Die Quelldatei ist im Dokumenteneingang nicht mehr vorhanden.');
        }

        $newPath = $this->folders->moveFile($sourcePath, $target, $name);
        $now = date('Y-m-d H:i:s');
        $update = [
            'file_name' => $name,
            'file_path' => $newPath,
            'document_type' => $type,
            'status' => 'assigned',
            'processing_status' => 'assigned',
            'customer_id' => $this->nullableInt($customerId),
            'project_id' => $this->nullableInt($projectId),
            'order_id' => $this->nullableInt($orderId),
            'supplier_id' => $this->nullableInt($supplierId),
            'document_no' => $this->nullableString($data['document_no'] ?? null),
            'document_date' => $date,
            'due_date' => $this->date((string)($data['due_date'] ?? '')),
            'net_amount' => $this->nullableFloat($data['net_amount'] ?? null),
            'vat_amount' => $this->nullableFloat($data['vat_amount'] ?? null),
            'gross_amount' => $this->nullableFloat($data['gross_amount'] ?? null),
            'currency' => strtoupper(substr(trim((string)($data['currency'] ?? 'EUR')) ?: 'EUR', 0, 3)),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'assigned_by' => $this->uid(),
            'assigned_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $this->update($id, $update);
        } catch (\Throwable $e) {
            try {
                $sourceFolder = trim(str_replace('\\', '/', dirname($sourcePath)), './');
                $sourceName = basename($sourcePath);
                if ($sourceFolder !== '' && $this->folders->exists($newPath)) {
                    $this->folders->moveFile($newPath, $sourceFolder, $sourceName);
                }
            } catch (\Throwable) {
                // Die ursprüngliche Exception bleibt maßgeblich; ein fehlgeschlagener Rollback wird geloggt.
            }
            throw $e;
        }

        return array_merge($doc, $update);
    }

    public function rows(string $status = 'all', string $type = 'all', string $query = '', string $processing = 'all', string $year = '', string $month = '', int $supplierId = 0, int $customerId = 0, int $projectId = 0): array {
        $this->syncInbox();
        $this->normaliseProcessingStates();
        $qb = $this->db->getQueryBuilder();
        $qb->select(
            'd.*',
            'c.name AS customer_name',
            'p.project_no',
            'p.title AS project_title',
            's.name AS supplier_name',
        )
            ->from('re_erp_documents', 'd')
            ->leftJoin('d', 're_erp_customers', 'c', $qb->expr()->eq('c.id', 'd.customer_id'))
            ->leftJoin('d', 're_erp_projects', 'p', $qb->expr()->eq('p.id', 'd.project_id'))
            ->leftJoin('d', 're_erp_suppliers', 's', $qb->expr()->eq('s.id', 'd.supplier_id'));

        $conditions = [];
        if ($processing !== 'all') {
            $conditions[] = $qb->expr()->eq('d.processing_status', $qb->createNamedParameter($processing));
        }
        if ($status !== 'all') {
            $conditions[] = $qb->expr()->eq('d.status', $qb->createNamedParameter($status));
        }
        if ($type !== 'all') {
            $conditions[] = $qb->expr()->orX(
                $qb->expr()->eq('d.document_type', $qb->createNamedParameter($type)),
                $qb->expr()->andX(
                    $qb->expr()->eq('d.document_type', $qb->createNamedParameter('unassigned')),
                    $qb->expr()->eq('d.suggested_type', $qb->createNamedParameter($type)),
                ),
            );
        }
        if ($year !== '') {
            $conditions[] = $qb->expr()->orX(
                $qb->expr()->like('d.document_date',$qb->createNamedParameter($year.'-%')),
                $qb->expr()->andX($qb->expr()->isNull('d.document_date'),$qb->expr()->like('d.suggested_document_date',$qb->createNamedParameter($year.'-%')))
            );
        }
        if ($month !== '') {
            $mm=str_pad($month,2,'0',STR_PAD_LEFT);
            $conditions[] = $qb->expr()->orX(
                $qb->expr()->like('d.document_date',$qb->createNamedParameter('____-'.$mm.'-%')),
                $qb->expr()->andX($qb->expr()->isNull('d.document_date'),$qb->expr()->like('d.suggested_document_date',$qb->createNamedParameter('____-'.$mm.'-%')))
            );
        }
        if($supplierId>0)$conditions[]=$qb->expr()->eq('d.supplier_id',$qb->createNamedParameter($supplierId));
        if($customerId>0)$conditions[]=$qb->expr()->eq('d.customer_id',$qb->createNamedParameter($customerId));
        if($projectId>0)$conditions[]=$qb->expr()->eq('d.project_id',$qb->createNamedParameter($projectId));
        $query = trim($query);
        if ($query !== '') {
            $needle = '%' . $this->db->escapeLikeParameter($query) . '%';
            $conditions[] = $qb->expr()->orX(
                $qb->expr()->iLike('d.original_name', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('d.file_name', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('d.document_no', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('d.suggested_document_no', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('c.name', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('p.title', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('p.project_no', $qb->createNamedParameter($needle)),
                $qb->expr()->iLike('s.name', $qb->createNamedParameter($needle)),
            );
        }
        if ($conditions !== []) {
            $qb->where($qb->expr()->andX(...$conditions));
        }

        $qb->orderBy('d.created_at', 'DESC')->setMaxResults(500);
        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function get(int $id): ?array {
        $document = $this->one($id);
        if ($document && ($document['processing_status'] ?? 'new') === 'new') {
            $this->update($id, ['processing_status' => 'review', 'updated_at' => date('Y-m-d H:i:s')]);
            $document['processing_status'] = 'review';
        }
        if ($document && empty($document['analyzed_at'])) {
            return $this->analyse($id);
        }
        return $document;
    }

    public function counts(): array {
        $out = ['unassigned'=>0,'assigned'=>0,'error'=>0,'all'=>0,'new'=>0,'review'=>0];
        $qb=$this->db->getQueryBuilder();
        $qb->select('status',$qb->func()->count('*','c'))->from('re_erp_documents')->groupBy('status');
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) { $out[$row['status']]=(int)$row['c']; $out['all']+=(int)$row['c']; }
        $qb=$this->db->getQueryBuilder();
        $qb->select('processing_status',$qb->func()->count('*','c'))->from('re_erp_documents')->groupBy('processing_status');
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) { $out[$row['processing_status']]=(int)$row['c']; }
        return $out;
    }

    public function scanInfo(): array {
        return [
            'owner' => $this->config->getAppValue('reinhardterp','document_inbox_owner',''),
            'last_at' => $this->config->getAppValue('reinhardterp','document_last_scan_at',''),
            'last_count' => (int)$this->config->getAppValue('reinhardterp','document_last_scan_count','0'),
        ];
    }

    public function duplicateWarning(string $checksum, ?int $excludeId = null): bool {
        if ($checksum === '') {
            return false;
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('re_erp_documents')
            ->where($qb->expr()->eq('checksum', $qb->createNamedParameter($checksum)));
        if ($excludeId) {
            $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId)));
        }
        $qb->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    public function lookupRows(string $table, string $order): array {
        $allowed=['re_erp_suppliers','re_erp_customers','re_erp_projects'];
        if(!in_array($table,$allowed,true)){return [];}
        return $this->tableRows($table,$order);
    }

    public function financeRows(array $filters=[]): array {
        $financeTypes=['incoming_invoice','outgoing_invoice','bank_statement','cash','tax','credit_note','accounting_other'];
        $rows=$this->rows('all','all',(string)($filters['q']??''),'all');
        return array_values(array_filter($rows,static function(array $row)use($filters,$financeTypes):bool{
            $actual=(string)($row['document_type']??'unassigned');
            $suggested=(string)($row['suggested_type']??'unassigned');
            $effective=$actual!=='unassigned'?$actual:$suggested;
            if(!in_array($effective,$financeTypes,true))return false;
            $type=(string)($filters['type']??'all');
            if($type!=='all'&&$effective!==$type)return false;
            $date=(string)($row['document_date']??$row['suggested_document_date']??$row['created_at']??'');
            $year=(string)($filters['year']??'');
            $month=(string)($filters['month']??'');
            if($year!==''&&substr($date,0,4)!==$year)return false;
            if($month!==''&&substr($date,5,2)!==str_pad($month,2,'0',STR_PAD_LEFT))return false;
            foreach(['supplier_id','customer_id','project_id'] as $key){
                $want=(int)($filters[$key]??0);
                if($want>0&&(int)($row[$key]??0)!==$want)return false;
            }
            return true;
        }));
    }

    public function financeCounts(array $filters=[]): array {
        $counts=['incoming_invoice'=>0,'outgoing_invoice'=>0,'bank_statement'=>0,'cash'=>0,'tax'=>0,'credit_note'=>0,'accounting_other'=>0];
        $base=$filters;$base['type']='all';
        foreach($this->financeRows($base) as $row){
            $actual=(string)($row['document_type']??'unassigned');
            $effective=$actual!=='unassigned'?$actual:(string)($row['suggested_type']??'unassigned');
            if(isset($counts[$effective]))$counts[$effective]++;
        }
        return $counts;
    }

    public function financeExport(array $filters=[]): array {
        if(!class_exists(\ZipArchive::class)){throw new \RuntimeException('PHP-Erweiterung zip fehlt. Bitte php-zip installieren.');}
        $rows=$this->financeRows($filters);
        if($rows===[]){throw new \RuntimeException('Für die gewählten Filter sind keine Belege vorhanden.');}
        $tmp=tempnam(sys_get_temp_dir(),'nexterp_fin_');
        if($tmp===false){throw new \RuntimeException('Temporäre Exportdatei konnte nicht erstellt werden.');}
        $zip=new \ZipArchive();
        if($zip->open($tmp,\ZipArchive::OVERWRITE)!==true){@unlink($tmp);throw new \RuntimeException('ZIP-Export konnte nicht erstellt werden.');}
        $labels=['incoming_invoice'=>'Eingangsrechnungen','outgoing_invoice'=>'Ausgangsrechnungen','bank_statement'=>'Kontoauszuege','cash'=>'Kasse','tax'=>'Steuern','credit_note'=>'Gutschriften','accounting_other'=>'Sonstige_Belege'];
        $csv=fopen('php://temp','w+');
        fwrite($csv,"\xEF\xBB\xBF");
        fputcsv($csv,['Belegart','Belegdatum','Belegnummer','Lieferant','Kunde','Projekt','Netto','MwSt','Brutto','Waehrung','Datei'],';');
        foreach($rows as $row){
            $actual=(string)($row['document_type']??'unassigned');
            $type=$actual!=='unassigned'?$actual:(string)($row['suggested_type']??'unassigned');
            $folder=$labels[$type]??'Sonstige_Belege';
            $path=(string)($row['file_path']??'');
            try{$file=$this->folders->readFile($path);$content=(string)($file['content']??'');}catch(\Throwable){continue;}
            $name=basename((string)($row['file_name']??$row['original_name']??'Beleg.pdf'));
            $zip->addFromString($folder.'/'.$name,$content);
            fputcsv($csv,[
                $folder,(string)($row['document_date']??$row['suggested_document_date']??''),
                (string)($row['document_no']??$row['suggested_document_no']??''),
                (string)($row['supplier_name']??''),(string)($row['customer_name']??''),
                trim((string)($row['project_no']??'').' '.(string)($row['project_title']??'')),
                (string)($row['net_amount']??''),(string)($row['vat_amount']??''),(string)($row['gross_amount']??''),
                (string)($row['currency']??'EUR'),$name
            ],';');
        }
        rewind($csv);$zip->addFromString('Beleguebersicht.csv',(string)stream_get_contents($csv));fclose($csv);
        $zip->close();$content=(string)file_get_contents($tmp);@unlink($tmp);
        $period=(string)($filters['year']??'');
        if($period==='')$period='Auswahl';
        if((string)($filters['month']??'')!=='')$period.='-'.str_pad((string)$filters['month'],2,'0',STR_PAD_LEFT);
        return ['name'=>'NextERP-Steuerbuero-'.$this->safe($period).'.zip','content'=>$content];
    }

    private function extractDocumentText(array $document): string {
        $name=(string)($document['file_name']??$document['original_name']??'');
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if(!in_array($ext,['pdf','png','jpg','jpeg','webp'],true))return '';
        try{$file=$this->folders->readFile((string)$document['file_path']);}catch(\Throwable){return '';}
        $base=tempnam(sys_get_temp_dir(),'nexterp_doc_');if($base===false)return '';
        @unlink($base);$source=$base.'.'.$ext;
        if(file_put_contents($source,(string)$file['content'])===false)return '';
        $text='';
        try{
            if($ext==='pdf'&&$this->commandExists('pdftotext')){
                $txt=$base.'.txt';$this->runCommand(['pdftotext','-layout','-enc','UTF-8',$source,$txt]);
                if(is_file($txt))$text=(string)file_get_contents($txt);
                @unlink($txt);
            }
            if(mb_strlen(preg_replace('/\s+/u','',$text)??'')<30&&$this->commandExists('tesseract')){
                if($ext==='pdf'&&$this->commandExists('pdftoppm')){
                    $prefix=$base.'_page';$this->runCommand(['pdftoppm','-jpeg','-r','180',$source,$prefix]);
                    $pages=glob($prefix.'-*.jpg')?:[];natsort($pages);
                    foreach($pages as $page){$out=$page.'_ocr';$this->runCommand(['tesseract',$page,$out,'-l','deu+eng','--psm','6']);if(is_file($out.'.txt'))$text.="\n".file_get_contents($out.'.txt');@unlink($out.'.txt');@unlink($page);}
                }else{
                    $out=$base.'_ocr';$this->runCommand(['tesseract',$source,$out,'-l','deu+eng','--psm','6']);if(is_file($out.'.txt')){$text=(string)file_get_contents($out.'.txt');@unlink($out.'.txt');}
                }
            }
        }finally{@unlink($source);}
        return trim((string)(preg_replace('/[\x{00A0}\t]+/u',' ',$text)??$text));
    }
    private function commandExists(string $command):bool{
        $p=proc_open(['sh','-lc','command -v '.escapeshellarg($command)], [1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($p))return false;stream_get_contents($pipes[1]);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);return proc_close($p)===0;
    }
    private function runCommand(array $command):void{
        $p=proc_open($command,[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))return;
        stream_get_contents($pipes[1]);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);proc_close($p);
    }

    private function normaliseProcessingStates(): void {
        $qb=$this->db->getQueryBuilder();
        $qb->update('re_erp_documents')->set('processing_status',$qb->createNamedParameter('assigned'))
            ->where($qb->expr()->eq('status',$qb->createNamedParameter('assigned')))
            ->andWhere($qb->expr()->neq('processing_status',$qb->createNamedParameter('assigned')))->executeStatement();
    }

    private function insertNewDocument(array $fileData): int {
        $checksum = (string)($fileData['checksum'] ?? '');
        $duplicateOf = $checksum !== '' ? $this->findDuplicateId($checksum) : null;
        $suggestion = $this->classifier->classify(
            (string)$fileData['original_name'],
            $this->tableRows('re_erp_customers', 'name'),
            $this->tableRows('re_erp_projects', 'project_no'),
            $this->tableRows('re_erp_suppliers', 'name'),
        );
        $now = date('Y-m-d H:i:s');
        return $this->insert(array_merge($fileData, $suggestion, [
            'document_type' => 'unassigned',
            'status' => 'unassigned',
            'processing_status' => 'new',
            'detected_at' => $now,
            'last_seen_at' => $now,
            'currency' => 'EUR',
            'duplicate_of' => $duplicateOf,
            'analyzed_at' => $now,
            'created_by' => $this->uid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    private function findDuplicateId(string $checksum): ?int {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('re_erp_documents')
            ->where($qb->expr()->eq('checksum', $qb->createNamedParameter($checksum)))
            ->orderBy('id', 'ASC')
            ->setMaxResults(1);
        $id = $qb->executeQuery()->fetchOne();
        return $id === false ? null : (int)$id;
    }

    private function targetFolder(string $type, string $year, int $month, int $projectId, int $supplierId): string {
        $monthFolder = sprintf('%02d_%s', $month, $this->monthName($month));

        // Buchhaltungsbelege haben genau ein physisches Original in 30_Finanzen.
        // Projekt, Kunde und Lieferant bleiben Verknüpfungen in der Datenbank.
        $financeTarget = match ($type) {
            'incoming_invoice' => 'ERP/30_Finanzen/Eingangsrechnungen',
            'outgoing_invoice' => 'ERP/30_Finanzen/Ausgangsrechnungen',
            'bank_statement' => 'ERP/30_Finanzen/Kontoauszuege',
            'cash' => 'ERP/30_Finanzen/Kasse',
            'tax' => 'ERP/30_Finanzen/Steuern',
            'credit_note' => 'ERP/30_Finanzen/Gutschriften',
            'accounting_other' => 'ERP/30_Finanzen/Sonstige_Belege',
            default => null,
        };
        if ($financeTarget !== null) {
            return $this->folders->ensureFolderPath($financeTarget, $year.'/'.$monthFolder);
        }

        if ($projectId > 0) {
            $project = $this->tableOne('re_erp_projects', $projectId);
            if ($project && trim((string)($project['folder_path'] ?? ''), '/') !== '') {
                $base = trim((string)$project['folder_path'], '/');
                return match ($type) {
                    'delivery_note' => $this->folders->ensureFolderPath($base, '05_Bestellungen/Lieferscheine/'.$year.'/'.$monthFolder),
                    'incoming_invoice' => $this->folders->ensureFolderPath($base, '09_Rechnung/Eingangsrechnungen/'.$year.'/'.$monthFolder),
                    'outgoing_invoice' => $this->folders->ensureFolderPath($base, '09_Rechnung/Ausgangsrechnungen/'.$year.'/'.$monthFolder),
                    'credit_note' => $this->folders->ensureFolderPath($base, '09_Rechnung/Gutschriften/'.$year.'/'.$monthFolder),
                    'offer' => $this->folders->ensureFolderPath($base, '10_Angebote/'.$year.'/'.$monthFolder),
                    'order' => $this->folders->ensureFolderPath($base, '11_Auftraege/'.$year.'/'.$monthFolder),
                    'drawing' => $this->folders->ensureFolderPath($base, '03_Zeichnungen/'.$year.'/'.$monthFolder),
                    'report' => $this->folders->ensureFolderPath($base, '06_Rapporte/'.$year.'/'.$monthFolder),
                    default => $this->folders->ensureFolderPath($base, '12_Sonstiges/'.$year.'/'.$monthFolder),
                };
            }
        }

        if ($supplierId > 0) {
            $supplierRow = $this->tableOne('re_erp_suppliers', $supplierId);
            $supplier = $this->safe((string)($supplierRow['name'] ?? ('Lieferant_'.$supplierId)));
            $base = $this->folders->ensureFolderPath('ERP/20_Lieferanten', $supplier);
            return match ($type) {
                'delivery_note' => $this->folders->ensureFolderPath($base, 'Lieferscheine/'.$year.'/'.$monthFolder),
                'incoming_invoice' => $this->folders->ensureFolderPath($base, 'Eingangsrechnungen/'.$year.'/'.$monthFolder),
                'credit_note' => $this->folders->ensureFolderPath($base, 'Gutschriften/'.$year.'/'.$monthFolder),
                default => $this->folders->ensureFolderPath($base, 'Dokumente/'.$year.'/'.$monthFolder),
            };
        }

        return match ($type) {
            'incoming_invoice' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Eingangsrechnungen', $year.'/'.$monthFolder),
            'outgoing_invoice' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Ausgangsrechnungen', $year.'/'.$monthFolder),
            'bank_statement' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Kontoauszuege', $year.'/'.$monthFolder),
            'cash' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Kasse', $year.'/'.$monthFolder),
            'tax' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Steuern', $year.'/'.$monthFolder),
            'credit_note' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Gutschriften', $year.'/'.$monthFolder),
            'accounting_other' => $this->folders->ensureFolderPath('ERP/30_Finanzen/Sonstige_Belege', $year.'/'.$monthFolder),
            default => $this->folders->ensureFolderPath('ERP/90_Archiv', $year.'/'.$monthFolder),
        };
    }

    private function buildName(array $doc, string $type, string $date, string $number, float $gross): string {
        $labels = [
            'incoming_invoice' => 'ER', 'outgoing_invoice' => 'AR', 'delivery_note' => 'LS',
            'bank_statement' => 'KA', 'credit_note' => 'GS', 'offer' => 'AN', 'order' => 'AU',
            'report' => 'RAP', 'drawing' => 'PLAN', 'cash' => 'KASSE', 'tax' => 'STEUER',
            'accounting_other' => 'BUCH', 'other' => 'DOK',
        ];
        $parts = [$date, $labels[$type] ?? 'DOK'];
        if (trim($number) !== '') {
            $parts[] = $this->safe($number);
        }
        if ($gross > 0) {
            $parts[] = number_format($gross, 2, ',', '').'-EUR';
        }
        $extension = strtolower(pathinfo((string)$doc['original_name'], PATHINFO_EXTENSION)) ?: 'pdf';
        return implode('_', $parts).'.'.$extension;
    }

    private function uniqueName(string $folder, string $name): string {
        $name = preg_replace('/[^\pL\pN._ ,()-]+/u', '_', trim($name)) ?: 'Dokument.pdf';
        $candidate = $name;
        $index = 2;
        while ($this->folders->exists($folder.'/'.$candidate)) {
            $path = pathinfo($name);
            $candidate = ($path['filename'] ?? 'Dokument').'_'.$index.'.'.($path['extension'] ?? 'pdf');
            $index++;
        }
        return $candidate;
    }

    private function monthName(int $month): string {
        return [1 => 'Januar', 2 => 'Februar', 3 => 'Maerz', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'][$month] ?? 'Monat';
    }

    private function safe(string $value): string {
        return trim(preg_replace('/[^\pL\pN._-]+/u', '_', trim($value)) ?? 'Eintrag', '._-') ?: 'Eintrag';
    }

    private function uid(): string {
        return $this->session->getUser()?->getUID() ?? 'system';
    }

    private function nullableInt(mixed $value): ?int {
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    private function nullableString(mixed $value): ?string {
        $string = trim((string)$value);
        return $string !== '' ? $string : null;
    }

    private function nullableFloat(mixed $value): ?float {
        return $value === '' || $value === null ? null : (float)str_replace(',', '.', (string)$value);
    }

    private function date(string $value): ?string {
        if (trim($value) === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function one(int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_documents')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ?: null;
    }

    private function findByPath(string $path): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_documents')->where($qb->expr()->eq('file_path', $qb->createNamedParameter($path)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ?: null;
    }

    private function tableOne(string $table, int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ?: null;
    }

    private function tableRows(string $table, string $order): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)->orderBy($order, 'ASC');
        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function insert(array $data): int {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_documents');
        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = $qb->createNamedParameter($value);
        }
        $qb->values($values)->executeStatement();
        return (int)$this->db->lastInsertId('re_erp_documents');
    }

    private function update(int $id, array $data): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('re_erp_documents');
        foreach ($data as $key => $value) {
            $qb->set($key, $qb->createNamedParameter($value));
        }
        $qb->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeStatement();
    }
}
