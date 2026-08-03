<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Db;

use OCP\AppFramework\Db\Entity;

final class Customer extends Entity {
    protected string $name = '';
    protected ?string $customerNo = null;
    protected ?string $folderPath = null;
    protected ?string $contactName = null;
    protected ?string $phone = null;
    protected ?string $mobile = null;
    protected ?string $email = null;
    protected ?string $address = null;
    protected ?string $street = null;
    protected ?string $postalCode = null;
    protected ?string $city = null;
    protected ?string $country = null;
    protected ?string $notes = null;
    protected ?string $ncAddressbookKey = null;
    protected ?string $ncContactId = null;
    protected ?string $ncContactUid = null;
    protected ?string $ncContactLabel = null;
    protected ?\DateTime $ncContactSyncedAt = null;
    protected bool $isArchived = false;
    protected string $createdBy = '';
    protected ?\DateTime $createdAt = null;
    protected ?\DateTime $updatedAt = null;

    public function __construct() {
        $this->addType('id', 'int');
        $this->addType('isArchived', 'bool');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
        $this->addType('ncContactSyncedAt', 'datetime');
    }
}
