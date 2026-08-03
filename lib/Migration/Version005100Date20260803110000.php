<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version005100Date20260803110000 extends SimpleMigrationStep {
    public function __construct(private IDBConnection $db) {}
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema=$schemaClosure();
        if(!$schema->hasTable('re_erp_customers')) return $schema;
        $t=$schema->getTable('re_erp_customers');
        foreach ([
            'street'=>['length'=>255],
            'postal_code'=>['length'=>32],
            'city'=>['length'=>160],
            'country'=>['length'=>100],
        ] as $name=>$opts) {
            if(!$t->hasColumn($name)) $t->addColumn($name,'string',['notnull'=>false]+$opts);
        }
        if(!$t->hasIndex('re_erp_customer_postal_city')) $t->addIndex(['postal_code','city'],'re_erp_customer_postal_city');
        return $schema;
    }
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $qb=$this->db->getQueryBuilder();
        $rows=$qb->select('id','address')->from('re_erp_customers')->where($qb->expr()->isNotNull('address'))->executeQuery()->fetchAllAssociative();
        foreach($rows as $row){
            $address=trim((string)$row['address']); if($address==='') continue;
            $lines=array_values(array_filter(array_map('trim',preg_split('/\R+/',$address)?:[])));
            $street=$lines[0]??''; $postal=''; $city=''; $country='';
            if(isset($lines[1])){
                if(preg_match('/^(?:[A-Z]{1,3}-)?([0-9]{4,6})\s+(.+)$/u',$lines[1],$m)){ $postal=$m[1]; $city=$m[2]; }
                else { $city=$lines[1]; }
            }
            if(isset($lines[2])) $country=$lines[2];
            $u=$this->db->getQueryBuilder();
            $u->update('re_erp_customers')->set('street',$u->createNamedParameter($street?:null))->set('postal_code',$u->createNamedParameter($postal?:null))->set('city',$u->createNamedParameter($city?:null))->set('country',$u->createNamedParameter($country?:null))->where($u->expr()->eq('id',$u->createNamedParameter((int)$row['id'])))->executeStatement();
        }
    }
}
