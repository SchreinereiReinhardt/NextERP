<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Db\Customer;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
final class CustomerController extends Controller {
 public function __construct(string $appName,IRequest $request,private CustomerMapper $mapper,private IUserSession $users,private IURLGenerator $url,private FolderService $folders,private NumberService $numbers,private PermissionService $permissions){parent::__construct($appName,$request);}
 #[NoAdminRequired]
 public function save(?int $id,string $name,?string $customerNo=null,?string $contactName=null,?string $phone=null,?string $email=null,?string $address=null,?string $notes=null): RedirectResponse {$this->permissions->assert('customers');
  $name=trim($name); if($name==='') throw new \InvalidArgumentException('Name ist Pflicht.'); $now=new \DateTime();
  $c=$id!==null?$this->mapper->find($id):new Customer(); $c->setName($name); $number=$id===null?$this->numbers->next('customer'):(string)$c->getCustomerNo(); $c->setCustomerNo($number);
  $c->setContactName($this->n($contactName)); $c->setPhone($this->n($phone)); $c->setEmail($this->n($email)); $c->setAddress($this->n($address)); $c->setNotes($this->n($notes)); $c->setUpdatedAt($now);
  $c->setFolderPath($this->folders->ensureCustomerFolder($number,$name));
  if($id===null){$c->setCreatedAt($now);$c->setCreatedBy($this->users->getUser()?->getUID()??'system');$this->mapper->insert($c);}else{$this->mapper->update($c);} return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers'));
 }
 #[NoAdminRequired] public function archive(int $id): RedirectResponse {$this->permissions->assert('customers');$c=$this->mapper->find($id);$c->setIsArchived(true);$c->setUpdatedAt(new \DateTime());$this->mapper->update($c);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers'));}
 private function n(?string $v):?string {if($v===null)return null;$v=trim($v);return $v===''?null:$v;}
}
