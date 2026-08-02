<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
final class ProjectMapper extends QBMapper {
 public function __construct(IDBConnection $db){parent::__construct($db,'re_erp_projects',Project::class);}
 public function find(int $id): Project { $q=$this->db->getQueryBuilder(); $q->select('*')->from($this->getTableName())->where($q->expr()->eq('id',$q->createNamedParameter($id))); return $this->findEntity($q); }
 public function findAllActive(): array { $q=$this->db->getQueryBuilder(); $q->select('*')->from($this->getTableName())->where($q->expr()->eq('is_archived',$q->createNamedParameter(false,$q::PARAM_BOOL)))->orderBy('id','DESC'); return $this->findEntities($q); }
 public function countActive(): int { $q=$this->db->getQueryBuilder(); $q->selectAlias($q->createFunction('COUNT(*)'),'cnt')->from($this->getTableName())->where($q->expr()->eq('is_archived',$q->createNamedParameter(false,$q::PARAM_BOOL))); return (int)$q->executeQuery()->fetchOne(); }
}
