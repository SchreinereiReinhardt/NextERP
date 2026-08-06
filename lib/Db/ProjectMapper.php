<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
final class ProjectMapper extends QBMapper {
 public function __construct(IDBConnection $db){parent::__construct($db,'re_erp_projects',Project::class);}
 public function find(int $id): Project { $q=$this->db->getQueryBuilder(); $q->select('*')->from($this->getTableName())->where($q->expr()->eq('id',$q->createNamedParameter($id))); return $this->findEntity($q); }
 public function findAllActive(?string $search=null): array { return $this->findByArchiveState(false,$search); }
 public function findAllArchived(?string $search=null): array { return $this->findByArchiveState(true,$search); }
 public function countActive(): int { return $this->countByArchiveState(false); }
 public function countArchived(): int { return $this->countByArchiveState(true); }
 private function findByArchiveState(bool $archived,?string $search=null):array {
  $q=$this->db->getQueryBuilder();$q->select('*')->from($this->getTableName())->where($q->expr()->eq('is_archived',$q->createNamedParameter($archived,$q::PARAM_BOOL)));
  $search=trim((string)$search);if($search!==''){$like=$q->createNamedParameter('%'.strtolower($search).'%');$q->andWhere($q->expr()->orX($q->expr()->like($q->createFunction('LOWER(project_no)'),$like),$q->expr()->like($q->createFunction('LOWER(title)'),$like),$q->expr()->like($q->createFunction('LOWER(status)'),$like)));}
  $q->orderBy('updated_at','DESC')->addOrderBy('id','DESC');return $this->findEntities($q);
 }
 private function countByArchiveState(bool $archived):int{$q=$this->db->getQueryBuilder();$q->selectAlias($q->createFunction('COUNT(*)'),'cnt')->from($this->getTableName())->where($q->expr()->eq('is_archived',$q->createNamedParameter($archived,$q::PARAM_BOOL)));return (int)$q->executeQuery()->fetchOne();}
}
