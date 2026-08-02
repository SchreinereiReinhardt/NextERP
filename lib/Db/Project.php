<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Db;

use OCP\AppFramework\Db\Entity;

final class Project extends Entity {
    protected int $customerId = 0;
    protected string $projectNo = '';
    protected string $title = '';
    protected string $status = 'offen';
    protected ?\DateTime $startDate = null;
    protected ?\DateTime $dueDate = null;
    protected ?string $description = null;
    protected ?string $folderPath = null;
    protected ?float $specialHourlyRate = null;
    protected bool $isArchived = false;
    protected string $createdBy = '';
    protected ?\DateTime $createdAt = null;
    protected ?\DateTime $updatedAt = null;

    public function __construct() {
        $this->addType('id', 'int');
        $this->addType('customerId', 'int');
        $this->addType('startDate', 'date');
        $this->addType('dueDate', 'date');
        $this->addType('isArchived', 'bool');
        $this->addType('specialHourlyRate', 'float');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }
}
