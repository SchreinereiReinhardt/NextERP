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
    protected ?float $calcValueNet = null;
    protected ?float $calcHours = null;
    protected ?float $calcMaterial = null;
    protected ?float $calcExternal = null;
    protected ?\DateTime $calcLockedAt = null;
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
        $this->addType('calcValueNet', 'float');
        $this->addType('calcHours', 'float');
        $this->addType('calcMaterial', 'float');
        $this->addType('calcExternal', 'float');
        $this->addType('calcLockedAt', 'datetime');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }
}
