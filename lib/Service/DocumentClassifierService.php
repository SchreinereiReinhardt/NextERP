<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

/**
 * Deterministic document classifier. Combines filename, extracted PDF/OCR text,
 * master data and project supplier references. No external service is used.
 */
final class DocumentClassifierService {
    /**
     * @param array<int,array<string,mixed>> $customers
     * @param array<int,array<string,mixed>> $projects
     * @param array<int,array<string,mixed>> $suppliers
     * @param array<int,array<string,mixed>> $projectSuppliers
     * @return array<string,mixed>
     */
    public function classify(string $filename, array $customers = [], array $projects = [], array $suppliers = [], string $content = '', array $projectSuppliers = []): array {
        $filePlain = $this->normalise(pathinfo($filename, PATHINFO_FILENAME));
        $contentPlain = $this->normalise($content);
        $searchPlain = trim($filePlain.' '.$contentPlain);
        $compact = $this->compact($searchPlain);
        $signals = [];

        [$type,$typeScore,$typeReason] = $this->detectType($filePlain,$contentPlain);
        if ($typeScore > 0) $signals[] = $typeReason;

        $documentNo = $this->documentNumber($filename,$content);
        $documentDate = $this->documentDate($filename,$content);

        $customer = $this->bestEntityMatch($searchPlain,$compact,$customers,['customer_no'=>42,'name'=>28,'street'=>14,'city'=>8]);
        $project = $this->bestEntityMatch($searchPlain,$compact,$projects,['project_no'=>58,'title'=>26]);
        $supplier = $this->bestEntityMatch($searchPlain,$compact,$suppliers,['supplier_no'=>42,'name'=>32,'street'=>12,'city'=>8]);

        $customerId=$customer['id']; $projectId=$project['id']; $supplierId=$supplier['id'];
        $entityScore=max($customer['score'],$project['score'],$supplier['score']);
        if($project['score']>=45)$signals[]='Projektnummer eindeutig erkannt';
        elseif($project['score']>0)$signals[]='Projektbezeichnung erkannt';
        if($customer['score']>0)$signals[]='Kundendaten erkannt';
        if($supplier['score']>0)$signals[]='Lieferant erkannt';

        // Strongest link: supplier cockpit references (purchase/AB numbers + supplier relation).
        $cockpitBest=['score'=>0,'project_id'=>null,'supplier_id'=>null,'reason'=>''];
        foreach($projectSuppliers as $row){
            $score=0;$why=[];
            foreach(['purchase_no'=>'Bestellnummer','confirmation_no'=>'AB-Nummer'] as $field=>$label){
                $v=trim((string)($row[$field]??''));
                if($v!=='' && $this->containsFlexible($searchPlain,$compact,$v)){$score+=70;$why[]=$label;}
            }
            $sname=trim((string)($row['supplier_name']??''));
            if($sname!=='' && $this->containsFlexible($searchPlain,$compact,$sname)){$score+=24;$why[]='Lieferant';}
            if($score>$cockpitBest['score'])$cockpitBest=['score'=>$score,'project_id'=>(int)($row['project_id']??0)?:null,'supplier_id'=>(int)($row['supplier_id']??0)?:null,'reason'=>implode(' + ',$why)];
        }
        if($cockpitBest['score']>=70){
            $projectId=$cockpitBest['project_id'] ?: $projectId;
            $supplierId=$cockpitBest['supplier_id'] ?: $supplierId;
            $entityScore=max($entityScore,min(100,$cockpitBest['score']));
            $signals[]='Lieferanten-Cockpit: '.$cockpitBest['reason'];
        }

        // A matched project determines its customer when available.
        if($projectId){
            foreach($projects as $p){if((int)($p['id']??0)===$projectId && (int)($p['customer_id']??0)>0){$customerId=(int)$p['customer_id'];break;}}
        }

        // Confidence reflects independent evidence instead of one keyword.
        $confidence=max($typeScore,$entityScore);
        if($typeScore>=70 && $entityScore>=25)$confidence=min(99,max($confidence,82)+8);
        if($projectId && $supplierId)$confidence=min(99,max($confidence,88));
        if($cockpitBest['score']>=94)$confidence=99;
        elseif($cockpitBest['score']>=70)$confidence=max($confidence,96);
        if($type==='unassigned' && $entityScore>0)$confidence=max(35,min(78,$entityScore));

        $reason=$signals ? implode(' · ',array_values(array_unique($signals))) : 'Keine eindeutige Regel gefunden.';
        return [
            'suggested_type'=>$type,
            'suggested_document_no'=>$documentNo,
            'suggested_document_date'=>$documentDate,
            'suggested_customer_id'=>$customerId,
            'suggested_project_id'=>$projectId,
            'suggested_supplier_id'=>$supplierId,
            'suggestion_confidence'=>$confidence,
            'suggestion_reason'=>$reason,
        ];
    }

    private function detectType(string $file,string $content):array{
        $all=trim($file.' '.$content);
        $rules=[
            'delivery_note'=>[['lieferschein','delivery note','warenbegleitschein'],94,'Lieferschein erkannt'],
            'credit_note'=>[['gutschrift','credit note','stornorechnung'],94,'Gutschrift/Storno erkannt'],
            'bank_statement'=>[['kontoauszug','bank statement','kontoumsaetze','kontoauszuge'],94,'Kontoauszug erkannt'],
            'cash'=>[['kassenbeleg','kassenbon','quittung'],90,'Kassenbeleg erkannt'],
            'tax'=>[['steuerbescheid','umsatzsteuer','lohnsteuer','finanzamt'],90,'Steuerdokument erkannt'],
            'offer'=>[['angebot','offerte','quotation'],90,'Angebot erkannt'],
            'order'=>[['auftragsbestaetigung','auftragsbestatigung','order confirmation'],94,'Auftragsbestätigung erkannt'],
            'report'=>[['rapport','arbeitsbericht','servicebericht'],90,'Rapport erkannt'],
            'drawing'=>[['zeichnung','cad plan','werkplan'],86,'Zeichnung/Plan erkannt'],
            'incoming_invoice'=>[['eingangsrechnung','lieferantenrechnung','rechnungseingang','supplier invoice'],96,'Eingangsrechnung erkannt'],
        ];
        if(preg_match('/^(rechnung|rg|re)\s*(nr|nummer)?\s*20\d{6,}/u',$file)||str_contains($file,'ausgangsrechnung')||str_contains($file,'kundenrechnung'))return ['outgoing_invoice',96,'Eigene Ausgangsrechnung im Dateinamen erkannt'];
        foreach($rules as [$type,$words,$score,$reason])foreach($words as $w)if($this->containsFlexible($all,$this->compact($all),$w))return [$type,$score,$reason];
        if(preg_match('/\b(rechnung|invoice|rechnungsnummer|rechnungs nr)\b/u',$content))return ['incoming_invoice',74,'Dokumentinhalt als Rechnung erkannt; Richtung bitte prüfen'];
        if(str_contains($all,'rechnung'))return ['incoming_invoice',58,'Allgemeines Wort „Rechnung“ erkannt; Richtung bitte prüfen'];
        return ['unassigned',0,''];
    }

    private function documentNumber(string $filename,string $content):?string{
        foreach([
            '/(?:rechnungs(?:nummer|nr\.?)|beleg(?:nummer|nr\.?)|lieferschein(?:nummer|nr\.?)|gutschrift(?:nummer|nr\.?)|angebots(?:nummer|nr\.?)|auftragsbestaetigungs(?:nummer|nr\.?)|ab(?:nummer|nr\.?)|bestell(?:nummer|nr\.?))\s*[:#]?\s*([A-Z0-9][A-Z0-9._\/-]{2,})/iu',
            '/(?:nr|nummer|re|rg|ls|an|au)[-_ .]*(\d{4,})/iu'
        ] as $p){if(preg_match($p,$content.'\n'.$filename,$m))return trim($m[1]);}
        if(preg_match('/\b(20\d{6,})\b/u',$filename,$m))return $m[1];
        return null;
    }
    private function documentDate(string $filename,string $content):?string{
        $sources=[
            ['/(?:rechnungsdatum|belegdatum|lieferscheindatum|datum)\s*:?\s*(\d{1,2})[.\/-](\d{1,2})[.\/-](20\d{2})/iu',$content,true],
            ['/(?<!\d)(20\d{2})[-_.](0?[1-9]|1[0-2])[-_.]([0-2]?\d|3[01])(?!\d)/u',$filename,false],
            ['/(?<!\d)([0-2]?\d|3[01])[-_.](0?[1-9]|1[0-2])[-_.](20\d{2})(?!\d)/u',$filename,true],
        ];
        foreach($sources as [$p,$s,$dmy])if(preg_match($p,$s,$m))return $dmy?sprintf('%04d-%02d-%02d',(int)$m[3],(int)$m[2],(int)$m[1]):sprintf('%04d-%02d-%02d',(int)$m[1],(int)$m[2],(int)$m[3]);
        return null;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function bestEntityMatch(string $haystack,string $compact,array $rows,array $fields):array{
        $best=['id'=>null,'score'=>0];
        foreach($rows as $row){$score=0;
            foreach($fields as $field=>$weight){$raw=trim((string)($row[$field]??''));if(mb_strlen($raw)<3)continue;if($this->containsFlexible($haystack,$compact,$raw))$score+=$weight;}
            // Multiple independent fields on the same entity are especially meaningful.
            if($score>40)$score+=12;
            if($score>$best['score'])$best=['id'=>(int)($row['id']??0)?:null,'score'=>min(100,$score)];
        }
        return $best;
    }
    private function containsFlexible(string $haystack,string $compact,string $needle):bool{
        $n=$this->normalise($needle);if(mb_strlen($n)<3)return false;
        if(str_contains(' '.$haystack.' ',' '.$n.' '))return true;
        $c=$this->compact($n);return strlen($c)>=4 && str_contains($compact,$c);
    }
    private function compact(string $value):string{return preg_replace('/[^a-z0-9]+/','',$this->normalise($value))??'';}
    private function normalise(string $value):string{
        $value=strtr($value,['Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','+'=>' ']);
        $value=strtolower($value);
        // Common OCR confusions around separators are neutralised by compact matching.
        return trim((string)preg_replace('/[^a-z0-9]+/u',' ',$value));
    }
}
