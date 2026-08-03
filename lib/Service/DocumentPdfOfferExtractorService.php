<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

final class DocumentPdfOfferExtractorService {
    public function __construct(private FolderService $folders) {
    }

    /**
     * Extracts offer header data and simple position tables from a digital PDF.
     * Scanned PDFs deliberately return a clear notice; OCR is a separate step.
     *
     * @param array<string,mixed> $document
     * @param array<int,array<string,mixed>> $customers
     * @param array<int,array<string,mixed>> $projects
     * @return array<string,mixed>
     */
    public function extract(array $document, array $customers, array $projects): array {
        $result = [
            'available' => false,
            'has_text' => false,
            'error' => null,
            'title' => '',
            'description' => '',
            'document_no' => '',
            'offer_date' => '',
            'valid_until' => '',
            'net_amount' => null,
            'vat_amount' => null,
            'gross_amount' => null,
            'vat_rate' => 19.0,
            'customer_id' => null,
            'project_id' => null,
            'positions' => [],
            'text_excerpt' => '',
        ];

        $path = (string)($document['file_path'] ?? '');
        $name = (string)($document['file_name'] ?? $document['original_name'] ?? '');
        if ($path === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
            $result['error'] = 'Die automatische Angebotsauslesung unterstützt derzeit digitale PDF-Dateien.';
            return $result;
        }

        if (!$this->commandExists('pdftotext')) {
            $result['error'] = 'Das Serverprogramm „pdftotext“ fehlt. Bitte das Paket poppler-utils installieren.';
            return $result;
        }

        try {
            $file = $this->folders->readFile($path);
        } catch (\Throwable $e) {
            $result['error'] = 'Die PDF-Datei konnte nicht gelesen werden: '.$e->getMessage();
            return $result;
        }

        $base = tempnam(sys_get_temp_dir(), 'nexterp_offer_');
        if ($base === false) {
            $result['error'] = 'Temporäre Datei konnte nicht erstellt werden.';
            return $result;
        }
        $pdfPath = $base.'.pdf';
        $txtPath = $base.'.txt';
        @unlink($base);

        try {
            if (file_put_contents($pdfPath, (string)$file['content']) === false) {
                $result['error'] = 'PDF konnte nicht temporär gespeichert werden.';
                return $result;
            }
            $command = ['pdftotext', '-layout', '-enc', 'UTF-8', $pdfPath, $txtPath];
            $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $process = proc_open($command, $descriptor, $pipes);
            if (!is_resource($process)) {
                $result['error'] = 'PDF-Textauslesung konnte nicht gestartet werden.';
                return $result;
            }
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            if ($exit !== 0 || !is_file($txtPath)) {
                $result['error'] = 'PDF-Textauslesung fehlgeschlagen'.($stderr !== '' ? ': '.trim($stderr) : '.');
                return $result;
            }
            $text = (string)file_get_contents($txtPath);
            $text = str_replace(["\r\n", "\r", "\f"], ["\n", "\n", "\n"], $text);
            $text = preg_replace('/[\x{00A0}\t]+/u', ' ', $text) ?? $text;
            $text = trim($text);
            $result['available'] = true;
            if (mb_strlen(preg_replace('/\s+/u', '', $text) ?? '') < 30) {
                $result['error'] = 'Die PDF enthält keinen ausreichend lesbaren Text. Bei einem Scan wird später OCR benötigt.';
                return $result;
            }

            $result['has_text'] = true;
            $result['text_excerpt'] = mb_substr(preg_replace('/\s+/u', ' ', $text) ?? $text, 0, 900);
            $result['document_no'] = $this->firstMatch($text, [
                '/(?:Angebots?(?:nummer|nr\.?|\s*Nr\.?)|Angebot\s+Nr\.?)\s*[:#]?\s*([A-Z0-9][A-Z0-9._\/-]{2,})/iu',
                '/\bAN[-\s]?[0-9]{3,}[A-Z0-9._\/-]*\b/iu',
            ]);
            $result['offer_date'] = $this->extractDate($text, [
                '/(?:Angebotsdatum|Datum)\s*:?\s*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/iu',
            ]);
            $result['valid_until'] = $this->extractDate($text, [
                '/(?:gültig\s+bis|Gültigkeit)\s*:?\s*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/iu',
            ]);
            $result['title'] = $this->cleanLine($this->firstMatch($text, [
                '/(?:Bauvorhaben|Projekt|Betreff|BV)\s*:?\s*([^\n]{4,160})/iu',
                '/Angebot\s+(?:für|über)\s*:?\s*([^\n]{4,160})/iu',
            ]));
            if ($result['title'] === '') {
                $result['title'] = trim(pathinfo((string)($document['original_name'] ?? 'Angebot'), PATHINFO_FILENAME));
            }

            $result['net_amount'] = $this->extractAmount($text, [
                '/(?:Nettosumme|Summe\s+netto|Nettobetrag|Netto)\s*:?\s*(?:EUR|€)?\s*([0-9][0-9.\s]*,[0-9]{2})/iu',
            ]);
            $result['gross_amount'] = $this->extractAmount($text, [
                '/(?:Angebotssumme|Gesamtbetrag|Gesamtsumme|Summe\s+brutto|Bruttobetrag|Brutto)\s*:?\s*(?:EUR|€)?\s*([0-9][0-9.\s]*,[0-9]{2})/iu',
            ]);
            $vatMatch = $this->firstMatches($text, [
                '/(?:MwSt\.?|USt\.?|Umsatzsteuer|Mehrwertsteuer)\s*(?:von\s+[^\n]*?)?\s*([0-9]{1,2}(?:[,.][0-9]+)?)\s*%[^\n]*?([0-9][0-9.\s]*,[0-9]{2})/iu',
                '/([0-9]{1,2}(?:[,.][0-9]+)?)\s*%\s*(?:MwSt\.?|USt\.?)\s*:?\s*([0-9][0-9.\s]*,[0-9]{2})/iu',
            ]);
            if ($vatMatch !== []) {
                $result['vat_rate'] = (float)str_replace(',', '.', (string)$vatMatch[1]);
                $result['vat_amount'] = $this->parseAmount((string)$vatMatch[2]);
            }
            if ($result['net_amount'] === null && $result['gross_amount'] !== null && $result['vat_rate'] > 0) {
                $result['net_amount'] = round($result['gross_amount'] / (1 + $result['vat_rate'] / 100), 2);
            }
            if ($result['gross_amount'] === null && $result['net_amount'] !== null) {
                $result['gross_amount'] = round($result['net_amount'] * (1 + $result['vat_rate'] / 100), 2);
            }
            if ($result['vat_amount'] === null && $result['net_amount'] !== null && $result['gross_amount'] !== null) {
                $result['vat_amount'] = round($result['gross_amount'] - $result['net_amount'], 2);
            }

            $normalText = $this->normalise($text);
            foreach ($customers as $customer) {
                $candidate = trim((string)($customer['name'] ?? ''));
                if (mb_strlen($candidate) >= 3 && str_contains($normalText, $this->normalise($candidate))) {
                    $result['customer_id'] = (int)$customer['id'];
                    break;
                }
            }
            foreach ($projects as $project) {
                $number = trim((string)($project['project_no'] ?? ''));
                $title = trim((string)($project['title'] ?? ''));
                if (($number !== '' && str_contains($normalText, $this->normalise($number))) ||
                    (mb_strlen($title) >= 4 && str_contains($normalText, $this->normalise($title)))) {
                    $result['project_id'] = (int)$project['id'];
                    if ($result['customer_id'] === null && !empty($project['customer_id'])) {
                        $result['customer_id'] = (int)$project['customer_id'];
                    }
                    break;
                }
            }

            $result['positions'] = $this->extractPositions($text);
            if ($result['positions'] !== []) {
                $result['description'] = implode("\n", array_map(
                    static fn(array $position): string => trim((string)$position['description']),
                    array_slice($result['positions'], 0, 12),
                ));
            }
            if ($result['description'] === '') {
                $result['description'] = 'Importiert aus '.(string)($document['original_name'] ?? 'PDF-Angebot');
            }
            return $result;
        } finally {
            @unlink($pdfPath);
            @unlink($txtPath);
        }
    }

    private function commandExists(string $command): bool {
        $process = proc_open(['sh', '-lc', 'command -v '.escapeshellarg($command)], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }
        stream_get_contents($pipes[1]); fclose($pipes[1]);
        stream_get_contents($pipes[2]); fclose($pipes[2]);
        return proc_close($process) === 0;
    }

    /** @param list<string> $patterns */
    private function firstMatch(string $text, array $patterns): string {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return trim((string)($matches[1] ?? $matches[0] ?? ''));
            }
        }
        return '';
    }

    /** @param list<string> $patterns @return array<int,string> */
    private function firstMatches(string $text, array $patterns): array {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return $matches;
            }
        }
        return [];
    }

    /** @param list<string> $patterns */
    private function extractDate(string $text, array $patterns): string {
        $raw = $this->firstMatch($text, $patterns);
        if ($raw === '') {
            return '';
        }
        $raw = str_replace('/', '.', $raw);
        foreach (['d.m.Y', 'd.m.y', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $raw);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }
        return '';
    }

    /** @param list<string> $patterns */
    private function extractAmount(string $text, array $patterns): ?float {
        $raw = $this->firstMatch($text, $patterns);
        return $raw === '' ? null : $this->parseAmount($raw);
    }

    private function parseAmount(string $value): float {
        $value = preg_replace('/[^0-9,.-]/u', '', $value) ?? '';
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return round((float)$value, 2);
    }

    /** @return list<array{position_no:int,description:string,quantity:float,unit:string,unit_price:float,total_price:float}> */
    private function extractPositions(string $text): array {
        $positions = [];
        $lines = preg_split('/\n/u', $text) ?: [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s{2,}/u', '  ', $line) ?? $line);
            if ($line === '' || mb_strlen($line) > 350) {
                continue;
            }
            $patterns = [
                '/^(\d{1,4})[.)]?\s+(.+?)\s{2,}(\d+(?:[.,]\d+)?)\s*(Stk\.?|Stück|m²|m2|m|lfm|Std\.?|h|Psch\.?|Pauschal)\s{1,}([0-9][0-9.]*,[0-9]{2})\s{1,}([0-9][0-9.]*,[0-9]{2})(?:\s*€)?$/iu',
                '/^(\d{1,4})[.)]?\s+(.+?)\s{2,}(\d+(?:[.,]\d+)?)\s*(Stk\.?|Stück|m²|m2|m|lfm|Std\.?|h|Psch\.?|Pauschal)\s{1,}([0-9][0-9.]*,[0-9]{2})(?:\s*€)?$/iu',
            ];
            foreach ($patterns as $index => $pattern) {
                if (preg_match($pattern, $line, $m) !== 1) {
                    continue;
                }
                $quantity = (float)str_replace(',', '.', $m[3]);
                $total = $this->parseAmount($index === 0 ? $m[6] : $m[5]);
                $unitPrice = $index === 0 ? $this->parseAmount($m[5]) : ($quantity > 0 ? round($total / $quantity, 2) : $total);
                $positions[] = [
                    'position_no' => (int)$m[1],
                    'description' => $this->cleanLine($m[2]),
                    'quantity' => max(0.01, $quantity),
                    'unit' => $this->normaliseUnit($m[4]),
                    'unit_price' => $unitPrice,
                    'total_price' => $total,
                ];
                break;
            }
        }
        return array_slice($positions, 0, 100);
    }

    private function normaliseUnit(string $unit): string {
        $unit = trim($unit);
        return match (mb_strtolower($unit)) {
            'stück', 'stk', 'stk.' => 'Stk.',
            'std', 'std.', 'h' => 'Std.',
            'psch', 'psch.', 'pauschal' => 'Pauschal',
            default => $unit,
        };
    }

    private function cleanLine(string $value): string {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value, " \t\n\r\0\x0B:-");
    }

    private function normalise(string $value): string {
        $value = mb_strtolower($value);
        $value = strtr($value, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss']);
        return preg_replace('/[^a-z0-9]+/u', '', $value) ?? '';
    }
}
