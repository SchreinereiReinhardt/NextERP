<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

/**
 * Lightweight, deterministic classifier for newly uploaded documents.
 * It deliberately uses only metadata and filenames. OCR/text extraction can
 * be added later without changing the inbox workflow.
 */
final class DocumentClassifierService {
    /**
     * @param array<int,array<string,mixed>> $customers
     * @param array<int,array<string,mixed>> $projects
     * @param array<int,array<string,mixed>> $suppliers
     * @return array<string,mixed>
     */
    public function classify(string $filename, array $customers = [], array $projects = [], array $suppliers = [], string $content = ''): array {
        $plain = $this->normalise(pathinfo($filename, PATHINFO_FILENAME));
        $contentPlain = $this->normalise($content);
        $searchPlain = trim($plain.' '.$contentPlain);
        $type = 'unassigned';
        $confidence = 0;
        $reason = 'Keine eindeutige Regel gefunden.';

        // Strong patterns are evaluated before generic invoice words.
        if (preg_match('/^(rechnung|rg|re)[ _.-]*(nr|nummer)?[ _.-]*20\d{6,}/u', $plain)
            || str_contains($plain, 'rechnung nr ')
            || str_contains($plain, 'ausgangsrechnung')
            || str_contains($plain, 'kundenrechnung')) {
            $type = 'outgoing_invoice';
            $confidence = 96;
            $reason = 'Dateiname entspricht dem Muster einer eigenen Ausgangsrechnung.';
        }

        $rules = [
            'incoming_invoice' => ['eingangsrechnung', 'lieferantenrechnung', 'rechnungseingang', 'supplier invoice'],
            'delivery_note' => ['lieferschein', 'delivery note', 'delivery_note', 'ls '],
            'credit_note' => ['gutschrift', 'credit note', 'credit_note'],
            'bank_statement' => ['kontoauszug', 'bank statement', 'umsatzanzeige'],
            'cash' => ['kassenbeleg', 'kassenbon', 'quittung', 'receipt'],
            'tax' => ['steuerbescheid', 'umsatzsteuer', 'lohnsteuer', 'finanzamt'],
            'accounting_other' => ['buchungsbeleg', 'buchhaltungsbeleg', 'zahlungsbeleg', 'abrechnungsbeleg'],
            'offer' => ['angebot', 'offerte', 'quotation'],
            'order' => ['auftrag', 'auftragsbestaetigung', 'auftragsbestatigung', 'order confirmation'],
            'report' => ['rapport', 'arbeitsbericht', 'servicebericht'],
            'drawing' => ['zeichnung', 'plan', 'cad', 'dwg'],
        ];

        if ($type === 'unassigned') {
        foreach ($rules as $candidate => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains(' '.$searchPlain.' ', ' '.$this->normalise($keyword).' ')
                    || str_contains($searchPlain, $this->normalise($keyword))) {
                    $type = $candidate;
                    $confidence = 92;
                    $reason = 'Dateiname enthält das eindeutige Schlüsselwort „'.$keyword.'“.';
                    break 2;
                }
            }
        }
        }

        if ($contentPlain !== '') {
            if (preg_match('/\b(lieferschein|lieferschein nr|delivery note)\b/u',$contentPlain)) {$type='delivery_note';$confidence=max($confidence,94);$reason='Dokumentinhalt als Lieferschein erkannt.';}
            elseif (preg_match('/\b(gutschrift|credit note)\b/u',$contentPlain)) {$type='credit_note';$confidence=max($confidence,94);$reason='Dokumentinhalt als Gutschrift erkannt.';}
            elseif (preg_match('/\b(kontoauszug|bank statement)\b/u',$contentPlain)) {$type='bank_statement';$confidence=max($confidence,94);$reason='Dokumentinhalt als Kontoauszug erkannt.';}
            elseif (preg_match('/\b(angebot|offerte|quotation)\b/u',$contentPlain)) {$type='offer';$confidence=max($confidence,90);$reason='Dokumentinhalt als Angebot erkannt.';}
            elseif (preg_match('/\b(auftragsbestaetigung|auftragsbestatigung|order confirmation)\b/u',$contentPlain)) {$type='order';$confidence=max($confidence,92);$reason='Dokumentinhalt als Auftragsbestätigung erkannt.';}
            elseif (preg_match('/\b(rechnung|invoice|rechnungsnummer|rechnungs nr)\b/u',$contentPlain) && $type==='unassigned') {$type='incoming_invoice';$confidence=78;$reason='Dokumentinhalt als Rechnung erkannt; Richtung bitte prüfen.';}
        }

        // Generic 'Rechnung' stays deliberately uncertain and must be reviewed.
        if ($type === 'unassigned' && str_contains($searchPlain, 'rechnung')) {
            $type = 'incoming_invoice';
            $confidence = 60;
            $reason = 'Nur das allgemeine Wort „Rechnung“ wurde erkannt; Richtung bitte prüfen.';
        }

        $documentNo = null;
        if (preg_match('/(?:nr|nummer|re|rg|ls|an|au)[-_ .]*(\d{4,})/iu', $filename, $match)) {
            $documentNo = $match[1];
        } elseif (preg_match('/\b(20\d{6,})\b/u', $filename, $match)) {
            $documentNo = $match[1];
        }

        if ($documentNo === null && $content !== '') {
            if (preg_match('/(?:rechnungs(?:nummer|nr\.?)|beleg(?:nummer|nr\.?)|lieferschein(?:nummer|nr\.?)|gutschrift(?:nummer|nr\.?)|angebots(?:nummer|nr\.?))\s*[:#]?\s*([A-Z0-9][A-Z0-9._\/-]{2,})/iu',$content,$match)) $documentNo=$match[1];
        }

        $documentDate = null;
        foreach ([
            '/(?<!\d)(20\d{2})[-_.](0?[1-9]|1[0-2])[-_.]([0-2]?\d|3[01])(?!\d)/u',
            '/(?<!\d)([0-2]?\d|3[01])[-_.](0?[1-9]|1[0-2])[-_.](20\d{2})(?!\d)/u',
        ] as $index => $pattern) {
            if (preg_match($pattern, $filename, $match)) {
                $documentDate = $index === 0
                    ? sprintf('%04d-%02d-%02d', (int)$match[1], (int)$match[2], (int)$match[3])
                    : sprintf('%04d-%02d-%02d', (int)$match[3], (int)$match[2], (int)$match[1]);
                break;
            }
        }

        if ($documentDate === null && $content !== '') {
            if (preg_match('/(?:rechnungsdatum|belegdatum|lieferscheindatum|datum)\s*:?\s*(\d{1,2})[.\/-](\d{1,2})[.\/-](20\d{2})/iu',$content,$match)) {
                $documentDate=sprintf('%04d-%02d-%02d',(int)$match[3],(int)$match[2],(int)$match[1]);
            }
        }

        $customerId = $this->bestEntityMatch($searchPlain, $customers, ['name', 'customer_no']);
        $projectId = $this->bestEntityMatch($searchPlain, $projects, ['title', 'project_no']);
        $supplierId = $this->bestEntityMatch($searchPlain, $suppliers, ['name', 'supplier_no']);

        if ($type === 'unassigned' && ($customerId || $projectId || $supplierId)) {
            $confidence = 35;
        }

        return [
            'suggested_type' => $type,
            'suggested_document_no' => $documentNo,
            'suggested_document_date' => $documentDate,
            'suggested_customer_id' => $customerId,
            'suggested_project_id' => $projectId,
            'suggested_supplier_id' => $supplierId,
            'suggestion_confidence' => $confidence,
            'suggestion_reason' => $reason,
        ];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function bestEntityMatch(string $haystack, array $rows, array $fields): ?int {
        $bestId = null;
        $bestLength = 0;
        foreach ($rows as $row) {
            foreach ($fields as $field) {
                $value = $this->normalise((string)($row[$field] ?? ''));
                if (strlen($value) < 4) {
                    continue;
                }
                if (str_contains($haystack, $value) && strlen($value) > $bestLength) {
                    $bestId = (int)($row['id'] ?? 0) ?: null;
                    $bestLength = strlen($value);
                }
            }
        }
        return $bestId;
    }

    private function normalise(string $value): string {
        $value = strtr($value, ['Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', '+' => ' ']);
        $value = strtolower($value);
        return trim((string)preg_replace('/[^a-z0-9]+/u', ' ', $value));
    }
}
