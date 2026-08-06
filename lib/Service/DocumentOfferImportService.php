<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;
use OCP\IUserSession;

final class DocumentOfferImportService {
    public function __construct(
        private IDBConnection $db,
        private NumberService $numbers,
        private FolderService $folders,
        private IUserSession $session,
    ) {
    }

    /**
     * @return array{offer_id:int,project_id:?int}
     */
    public function importOffer(int $documentId, array $data): array {
        $document = $this->one('re_erp_documents', $documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Dokument nicht gefunden.');
        }
        if (($document['status'] ?? '') !== 'assigned') {
            throw new \InvalidArgumentException('Bitte das Dokument zuerst vollständig zuordnen.');
        }
        if (($document['document_type'] ?? '') !== 'offer') {
            throw new \InvalidArgumentException('Nur ein als Angebot zugeordnetes Dokument kann übernommen werden.');
        }
        $existingOfferId = (int)($document['offer_id'] ?? 0);
        if ($existingOfferId > 0) {
            return ['offer_id' => $existingOfferId, 'project_id' => $this->nullableInt($document['project_id'] ?? null)];
        }

        $customerId = (int)($data['customer_id'] ?? $document['customer_id'] ?? 0);
        if ($customerId <= 0 || !$this->one('re_erp_customers', $customerId)) {
            throw new \InvalidArgumentException('Für den Angebotsimport muss ein vorhandener Kunde ausgewählt sein.');
        }

        $projectId = (int)($data['project_id'] ?? $document['project_id'] ?? 0);
        if ($projectId > 0) {
            $project = $this->one('re_erp_projects', $projectId);
            if (!$project) {
                throw new \InvalidArgumentException('Das ausgewählte Projekt wurde nicht gefunden.');
            }
            if ((int)$project['customer_id'] !== $customerId) {
                throw new \InvalidArgumentException('Das ausgewählte Projekt gehört nicht zum Kunden.');
            }
        } elseif (!empty($data['create_project'])) {
            $projectId = $this->createProject($customerId, (string)($data['project_title'] ?? ''));
        } else {
            $projectId = 0;
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $title = trim(pathinfo((string)($document['original_name'] ?? 'Importiertes Angebot'), PATHINFO_FILENAME));
        }
        if ($title === '') {
            $title = 'Importiertes Angebot';
        }

        $description = trim((string)($data['description'] ?? ''));
        if ($description === '') {
            $description = 'Importiert aus Dokument: '.(string)($document['original_name'] ?? '');
        }

        $net = $this->floatOrNull($data['net_amount'] ?? $document['net_amount'] ?? null);
        $vatAmount = $this->floatOrNull($data['vat_amount'] ?? $document['vat_amount'] ?? null);
        $gross = $this->floatOrNull($data['gross_amount'] ?? $document['gross_amount'] ?? null);
        $vatRate = $this->inferVatRate((float)($net ?? 0), (float)($gross ?? 0), $this->floatOrNull($data['vat_rate'] ?? null));
        [$net, $vatAmount, $gross] = $this->validateAmounts($net, $vatAmount, $gross, $vatRate);

        $offerDate = $this->normaliseDate((string)($data['offer_date'] ?? $document['document_date'] ?? '')) ?? date('Y-m-d');
        $validUntil = $this->normaliseDate((string)($data['valid_until'] ?? ''));
        $sourceNo = trim((string)($document['document_no'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $sourceNote = 'Quelldokument #'.$documentId.($sourceNo !== '' ? ' · Belegnummer '.$sourceNo : '');
        $notes = $notes === '' ? $sourceNote : $notes."\n\n".$sourceNote;
        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $offerId = $this->insert('re_erp_offers', [
                'offer_no' => $this->numbers->next('offer'),
                'customer_id' => $customerId,
                'project_id' => $projectId > 0 ? $projectId : null,
                'title' => $title,
                'offer_date' => $offerDate,
                'valid_until' => $validUntil,
                'status' => 'draft',
                'notes' => $notes,
                'net_amount' => $net,
                'vat_rate' => $vatRate,
                'gross_amount' => $gross,
                'source_document_id' => $documentId,
                'created_by' => $this->uid(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $positions = is_array($data['positions'] ?? null) ? $data['positions'] : [];
            if ($positions === []) {
                $positions[] = [
                    'description' => $description,
                    'quantity' => 1,
                    'unit' => 'Pauschal',
                    'unit_price' => $net,
                    'total_price' => $net,
                ];
            }
            $calculatedNet = 0.0;
            foreach ($positions as $index => $position) {
                $quantity = max(0.01, (float)($position['quantity'] ?? 1));
                $unitPrice = (float)($position['unit_price'] ?? 0);
                $totalPrice = (float)($position['total_price'] ?? 0);
                if ($totalPrice <= 0) {
                    $totalPrice = round($quantity * $unitPrice, 2);
                }
                if ($unitPrice <= 0 && $quantity > 0) {
                    $unitPrice = round($totalPrice / $quantity, 2);
                }
                $calculatedNet += $totalPrice;
                $this->insert('re_erp_offer_items', [
                    'offer_id' => $offerId,
                    'position_no' => $index + 1,
                    'description' => trim((string)($position['description'] ?? 'Position '.($index + 1))),
                    'quantity' => $quantity,
                    'unit' => trim((string)($position['unit'] ?? 'Stk.')) ?: 'Stk.',
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
            if ($net <= 0 && $calculatedNet > 0) {
                $net = round($calculatedNet, 2);
                $gross = round($net * (1 + ($vatRate / 100)), 2);
                $this->update('re_erp_offers', $offerId, [
                    'net_amount' => $net,
                    'gross_amount' => $gross,
                    'updated_at' => $now,
                ]);
            }
            $this->update('re_erp_documents', $documentId, [
                'offer_id' => $offerId,
                'project_id' => $projectId > 0 ? $projectId : null,
                'imported_at' => $now,
                'updated_at' => $now,
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['offer_id' => $offerId, 'project_id' => $projectId > 0 ? $projectId : null];
    }

    private function createProject(int $customerId, string $title): int {
        $title = trim($title);
        if ($title === '') {
            throw new \InvalidArgumentException('Bitte einen Projektnamen eingeben oder ein vorhandenes Projekt auswählen.');
        }
        $customer = $this->one('re_erp_customers', $customerId);
        if (!$customer) {
            throw new \InvalidArgumentException('Kunde wurde nicht gefunden.');
        }
        $projectNo = $this->numbers->next('project');
        $customerFolder = trim((string)($customer['folder_path'] ?? ''));
        if ($customerFolder === '') {
            $customerFolder = $this->folders->ensureCustomerFolder((string)($customer['customer_no'] ?? $customerId), (string)$customer['name']);
        }
        $folderPath = $this->folders->ensureProjectFolder($customerFolder, $projectNo, $title);
        $now = date('Y-m-d H:i:s');
        return $this->insert('re_erp_projects', [
            'customer_id' => $customerId,
            'project_no' => $projectNo,
            'title' => $title,
            'status' => 'Angebot',
            'start_date' => null,
            'due_date' => null,
            'description' => 'Automatisch beim Angebotsimport angelegt.',
            'folder_path' => $folderPath,
            'is_archived' => false,
            'created_by' => $this->uid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function inferVatRate(float $net, float $gross, ?float $requested): float {
        if ($requested !== null && $requested >= 0 && $requested <= 100) {
            return round($requested, 2);
        }
        if ($net > 0 && $gross >= $net) {
            return round((($gross / $net) - 1) * 100, 2);
        }
        return 19.0;
    }

    private function normaliseDate(string $value): ?string {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $time = strtotime($value);
        return $time === false ? null : date('Y-m-d', $time);
    }

    /** @return array{0:float,1:float,2:float} */
    private function validateAmounts(?float $net, ?float $vat, ?float $gross, float $rate): array {
        if ($net === null && $gross === null) {
            throw new \InvalidArgumentException('Bitte mindestens Netto oder Brutto angeben.');
        }
        if ($net === null && $gross !== null) {
            $net = round($gross / (1 + $rate / 100), 2);
        }
        if ($gross === null && $net !== null) {
            $gross = round($net * (1 + $rate / 100), 2);
        }
        $net = round((float)$net, 2);
        $gross = round((float)$gross, 2);
        $vat = $vat === null ? round($gross - $net, 2) : round($vat, 2);
        if ($net < 0 || $vat < 0 || $gross < 0) {
            throw new \InvalidArgumentException('Netto, USt. und Brutto dürfen nicht negativ sein.');
        }
        if (abs(round($net + $vat - $gross, 2)) > 0.02) {
            throw new \InvalidArgumentException('Die Summen sind nicht plausibel: Netto + USt.-Betrag muss Brutto ergeben.');
        }
        $expectedVat = round($net * $rate / 100, 2);
        if ($net > 0 && abs($expectedVat - $vat) > 0.05) {
            throw new \InvalidArgumentException('Der USt.-Betrag passt nicht zum angegebenen USt.-Satz. Bitte die Werte kontrollieren.');
        }
        return [$net, $vat, $gross];
    }

    private function floatOrNull(mixed $value): ?float {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return round((float)$value, 2);
        }
        $value = preg_replace('/[^0-9,.-]/u', '', trim((string)$value)) ?? '';
        if ($value === '') {
            return null;
        }
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) ? round((float)$value, 2) : null;
    }

    private function nullableInt(mixed $value): ?int {
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }

    private function uid(): string {
        return $this->session->getUser()?->getUID() ?? 'system';
    }

    private function one(string $table, int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ?: null;
    }

    private function insert(string $table, array $data): int {
        $qb = $this->db->getQueryBuilder();
        $qb->insert($table);
        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = $qb->createNamedParameter($value);
        }
        $qb->values($values)->executeStatement();
        return (int)$this->db->lastInsertId($table);
    }

    private function update(string $table, int $id, array $data): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update($table);
        foreach ($data as $key => $value) {
            $qb->set($key, $qb->createNamedParameter($value));
        }
        $qb->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeStatement();
    }
}
