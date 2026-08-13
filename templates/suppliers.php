<?php require __DIR__.'/_nav.php';$url=\OC::$server->get(\OCP\IURLGenerator::class);$s=$_['selected']??[];?>
<div id="app-content"><div class="erp-page">
<div class="erp-head"><div><h1>Lieferanten</h1><p class="erp-sub">Lieferanten, Konditionen, Materialien und zugeordnete Belege zentral verwalten.</p></div><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.materials'));?>">Materialstamm</a></div>
<?php if(!empty($_['message'])):?><div class="erp-notice"><?php p($_['message']);?></div><?php endif;?>
<section class="erp-card erp-wide"><form method="get"><div class="erp-actions"><input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Lieferant, Kundennummer, Ansprechpartner …"><button class="button primary">Suchen</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.suppliers'));?>">Neu / Zurücksetzen</a></div></form></section>
<div class="erp-two-column">
<section class="erp-card"><h2><?php p(!empty($s['id'])?'Lieferant bearbeiten':'Neuer Lieferant');?></h2>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveSupplierMaster'));?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><?php if(!empty($s['id'])):?><input type="hidden" name="id" value="<?php p($s['id']);?>"><?php endif;?>
<div class="erp-form-grid">
<div class="erp-span-2"><label>Firma / Name *</label><input name="name" required value="<?php p($s['name']??'');?>"></div>
<div><label>Ansprechpartner</label><input name="contactPerson" value="<?php p($s['contact_person']??'');?>"></div><div><label>Kundennummer bei Lieferant</label><input name="customerNo" value="<?php p($s['customer_no']??'');?>"></div>
<div><label>Straße</label><input name="street" value="<?php p($s['street']??'');?>"></div><div><label>PLZ</label><input name="postalCode" value="<?php p($s['postal_code']??'');?>"></div>
<div><label>Ort</label><input name="city" value="<?php p($s['city']??'');?>"></div><div><label>Land</label><input name="country" value="<?php p($s['country']??'');?>"></div>
<div><label>E-Mail</label><input type="email" name="email" value="<?php p($s['email']??'');?>"></div><div><label>Telefon</label><input name="phone" value="<?php p($s['phone']??'');?>"></div>
<div><label>Website</label><input name="website" value="<?php p($s['website']??'');?>"></div><div><label>Zahlungsziel</label><input name="paymentTerms" value="<?php p($s['payment_terms']??'');?>" placeholder="z. B. 14 Tage 2 %, 30 Tage netto"></div>
<div><label>IBAN</label><input name="iban" value="<?php p($s['iban']??'');?>"></div><div><label>BIC</label><input name="bic" value="<?php p($s['bic']??'');?>"></div>
<div class="erp-span-2"><label>Notizen</label><textarea name="notes" rows="4"><?php p($s['notes']??'');?></textarea></div>
<div><label>Status</label><select name="active"><option value="1" <?php if(($s['active']??1))p('selected');?>>Aktiv</option><option value="0" <?php if(isset($s['active'])&&!$s['active'])p('selected');?>>Inaktiv</option></select></div>
</div><button class="button primary">Lieferant speichern</button></form></section>
<section class="erp-card"><h2>Lieferantenstamm</h2><?php if(empty($_['rows'])):?><p class="erp-muted">Keine Lieferanten vorhanden.</p><?php else:?><div class="erp-dms-list"><?php foreach($_['rows'] as $x):?>
<a class="erp-dms-row" href="<?php p($url->linkToRoute('reinhardterp.module.suppliers',['id'=>$x['id']]));?>"><span class="erp-dms-row-main"><strong><?php p($x['name']);?></strong><small><?php p(trim(($x['customer_no']?'Kdnr. '.$x['customer_no'].' · ':'').($x['contact_person']??'')));?></small></span><span><strong><?php p((int)$x['material_count']);?></strong><small> Materialien</small></span><span><strong><?php p((int)$x['document_count']);?></strong><small> Belege</small></span><span>›</span></a>
<?php endforeach;?></div><?php endif;?></section></div>
</div></div>