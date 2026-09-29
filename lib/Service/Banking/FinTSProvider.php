<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service\Banking;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\IDBConnection;
final class FinTSProvider implements BankingProviderInterface {
 private const APP='reinhardterp'; private const P='banking.fints.';
 public function __construct(private IConfig $config,private ICrypto $crypto,private IDBConnection $db){}
 private function set(string $k,string $v):void{$this->config->setAppValue(self::APP,self::P.$k,$v);}
 private function get(string $k,string $d=''):string{return (string)$this->config->getAppValue(self::APP,self::P.$k,$d);}
 private function secretSet(string $k,string $v):void{$this->set($k,$v===''?'':base64_encode($this->crypto->encrypt($v)));}
 private function secretGet(string $k):string{$v=$this->get($k);if($v==='')return '';try{return $this->crypto->decrypt((string)base64_decode($v,true));}catch(\Throwable){return '';}}
 public function saveConfiguration(array $d):void{
  foreach(['url','bankCode','username','productName'] as $k){$v=trim((string)($d[$k]??''));if($v==='')throw new \InvalidArgumentException('FinTS-Konfiguration unvollständig: '.$k);$this->set($k,$v);}
  if(isset($d['pin'])&&trim((string)$d['pin'])!=='')$this->secretSet('pin',trim((string)$d['pin']));
  if($this->secretGet('pin')==='')throw new \InvalidArgumentException('Bitte die Online-Banking-PIN angeben.');
 }
 public function configuration():array{return ['url'=>$this->get('url','https://banking-hs3.s-fints-pt-hs.de/fints30'),'bankCode'=>$this->get('bankCode','52050353'),'username'=>$this->get('username'),'productName'=>$this->get('productName'),'hasPin'=>$this->secretGet('pin')!=='','pending'=>$this->get('pending')!==''];}
 private function boot(?string $persisted=null):\Fhp\FinTs{
  $autoload=dirname(__DIR__,3).'/vendor/autoload.php';if(!is_file($autoload))throw new \RuntimeException('FinTS-Bibliothek fehlt.');require_once $autoload;
  $o=new \Fhp\Options\FinTsOptions();$o->url=$this->get('url');$o->bankCode=$this->get('bankCode');$o->productName=$this->get('productName');$o->productVersion='2.5.2';
  return \Fhp\FinTs::new($o,\Fhp\Options\Credentials::create($this->get('username'),$this->secretGet('pin')),$persisted);
 }
 private function pending(\Fhp\FinTs $f,\Fhp\BaseAction $a,array $ctx):array{
  $state=serialize(['f'=>$f->persist(),'a'=>serialize($a),'c'=>$ctx]);$this->secretSet('pending',$state);$r=$a->getTanRequest();$m=$f->getSelectedTanMode();return ['status'=>'sca','decoupled'=>$m->isDecoupled(),'challenge'=>$r?->getChallenge(),'medium'=>$r?->getTanMediumName()];
 }
 private function clearPending():void{$this->set('pending','');}
 public function startSync(int $days=90):array{$this->clearPending();$f=$this->boot();$a=\Fhp\Action\GetSEPAAccounts::create();$f->execute($a);if($a->needsTan())return $this->pending($f,$a,['stage'=>'accounts','days'=>$days]);return $this->afterAccounts($f,$a,$days);}
 public function continueAuthentication(?string $tan=null):array{
  $raw=$this->secretGet('pending');if($raw==='')throw new \RuntimeException('Keine offene FinTS-Freigabe vorhanden.');$s=unserialize($raw,['allowed_classes'=>true]);$f=$this->boot($s['f']);$a=unserialize($s['a'],['allowed_classes'=>true]);$ctx=$s['c'];
  if($f->getSelectedTanMode()->isDecoupled()){if(!$f->checkDecoupledSubmission($a))return $this->pending($f,$a,$ctx);}else{if(trim((string)$tan)==='')return $this->pending($f,$a,$ctx);$f->submitTan($a,trim((string)$tan));}
  $this->clearPending();if(($ctx['stage']??'')==='accounts')return $this->afterAccounts($f,$a,(int)($ctx['days']??90));if(($ctx['stage']??'')==='statement')return $this->afterStatement($f,$a,(int)$ctx['accountId'],(int)($ctx['added']??0),(int)($ctx['matched']??0));return ['status'=>'ok'];
 }
 private function afterAccounts(\Fhp\FinTs $f,\Fhp\Action\GetSEPAAccounts $a,int $days):array{
  $accounts=$a->getAccounts();$added=0;$matched=0;foreach($accounts as $acc){$id=$this->upsertAccount($acc);$from=(new \DateTimeImmutable())->modify('-'.max(1,min(365,$days)).' days');$to=new \DateTimeImmutable();$st=\Fhp\Action\GetStatementOfAccount::create($acc,\DateTime::createFromImmutable($from),\DateTime::createFromImmutable($to),false,true);$f->execute($st);if($st->needsTan())return $this->pending($f,$st,['stage'=>'statement','accountId'=>$id,'added'=>$added,'matched'=>$matched]);$x=$this->importStatement($st,$id);$added+=$x[0];$matched+=$x[1];$this->updateBalance($f,$acc,$id);}return ['status'=>'ok','accounts'=>count($accounts),'added'=>$added,'matched'=>$matched];
 }
 private function afterStatement(\Fhp\FinTs $f,\Fhp\Action\GetStatementOfAccount $a,int $id,int $added,int $matched):array{$x=$this->importStatement($a,$id);return ['status'=>'ok','accounts'=>1,'added'=>$added+$x[0],'matched'=>$matched+$x[1],'note'=>'Weitere Konten können mit erneutem Abruf synchronisiert werden.'];}
 private function upsertAccount(\Fhp\Model\SEPAAccount $a):int{$iban=(string)$a->getIban();$q=$this->db->getQueryBuilder();$q->select('id')->from('re_erp_bank_accounts')->where($q->expr()->eq('iban',$q->createNamedParameter($iban)));$r=$q->executeQuery()->fetch();if($r)return (int)$r['id'];$i=$this->db->getQueryBuilder();$i->insert('re_erp_bank_accounts')->values(['name'=>$i->createNamedParameter('Geschäftskonto'),'iban'=>$i->createNamedParameter($iban?:null),'bic'=>$i->createNamedParameter($a->getBic()),'bank_name'=>$i->createNamedParameter('Kasseler Sparkasse'),'provider'=>$i->createNamedParameter('fints'),'active'=>$i->createNamedParameter(1),'created_at'=>$i->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();return (int)$this->db->lastInsertId('*PREFIX*re_erp_bank_accounts');}
 private function importStatement(\Fhp\Action\GetStatementOfAccount $a,int $accountId):array{$added=0;$matched=0;foreach($a->getStatement()->getStatements() as $s)foreach($s->getTransactions() as $t){if(!$t->getBooked()||!$t->getBookingDate())continue;$amount=$t->getAmount()*($t->getCreditDebit()===\Fhp\Model\StatementOfAccount\Transaction::CD_DEBIT?-1:1);$purpose=trim($t->getMainDescription().' '.$t->getBookingText());$party=trim($t->getName());$date=$t->getBookingDate()->format('Y-m-d');$ext=hash('sha256',$accountId.'|'.$date.'|'.number_format($amount,2,'.','').'|'.$party.'|'.$purpose.'|'.$t->getEndToEndID());$q=$this->db->getQueryBuilder();$q->select('id')->from('re_erp_bank_transactions')->where($q->expr()->eq('external_id',$q->createNamedParameter($ext)));if($q->executeQuery()->fetch())continue;$invoice=$amount>0?$this->suggest($amount,$purpose,$party):null;$i=$this->db->getQueryBuilder();$i->insert('re_erp_bank_transactions')->values(['account_id'=>$i->createNamedParameter($accountId),'booking_date'=>$i->createNamedParameter($date),'value_date'=>$i->createNamedParameter($t->getValutaDate()?->format('Y-m-d')),'amount'=>$i->createNamedParameter(round($amount,2)),'currency'=>$i->createNamedParameter('EUR'),'counterparty'=>$i->createNamedParameter($party?:null),'counterparty_iban'=>$i->createNamedParameter(null),'purpose'=>$i->createNamedParameter($purpose?:null),'external_id'=>$i->createNamedParameter($ext),'invoice_id'=>$i->createNamedParameter($invoice),'match_status'=>$i->createNamedParameter($invoice?'suggested':'open'),'import_source'=>$i->createNamedParameter('fints'),'created_at'=>$i->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();$added++;if($invoice)$matched++;}return [$added,$matched];}
 private function suggest(float $amount,string $purpose,string $party):?int{$q=$this->db->getQueryBuilder();$q->select('i.id','i.invoice_no','i.gross_amount','c.name')->from('re_erp_invoices','i')->leftJoin('i','re_erp_customers','c',$q->expr()->eq('c.id','i.customer_id'))->where($q->expr()->neq('i.status',$q->createNamedParameter('draft')))->andWhere($q->expr()->neq('i.status',$q->createNamedParameter('cancelled')))->andWhere($q->expr()->neq('i.status',$q->createNamedParameter('paid')));foreach($q->executeQuery()->fetchAll() as $i){if(abs((float)$i['gross_amount']-$amount)>.02)continue;$hay=mb_strtolower($purpose.' '.$party);if(str_contains($hay,mb_strtolower((string)$i['invoice_no']))||(!empty($i['name'])&&str_contains($hay,mb_strtolower((string)$i['name']))))return (int)$i['id'];}return null;}
 private function updateBalance(\Fhp\FinTs $f,\Fhp\Model\SEPAAccount $acc,int $id):void{try{$a=\Fhp\Action\GetBalance::create($acc,false);$f->execute($a);if($a->needsTan())return;foreach($a->getBalances() as $b){$bal=$b->getGebuchterSaldo();$this->set('balance.'.$id,(string)$bal->getAmount());$this->set('balance_currency.'.$id,(string)$bal->getCurrency());break;}}catch(\Throwable){}}
 public function balance(int $id):?array{$v=$this->get('balance.'.$id);return $v===''?null:['amount'=>(float)$v,'currency'=>$this->get('balance_currency.'.$id,'EUR')];}
}
