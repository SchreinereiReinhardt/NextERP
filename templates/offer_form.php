<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
use OCP\Util;
$url=\OCP\Server::get(IURLGenerator::class);
Util::addScript('reinhardterp','offers');
$o=$_['offer']??null;$editing=is_array($o);$items=$_['items']??[];
$action=$editing?$url->linkToRoute('reinhardterp.business.updateOffer',['id'=>$o['id']]):$url->linkToRoute('reinhardterp.business.saveOffer');
if(!$items)$items=[['description'=>'','quantity'=>1,'unit'=>'Stk.','unit_price'=>0,'is_alternative'=>0]];
?>
<div id="app-content"><div class="erp-page erp-offers-v2 erp-commercial-form-v3">
 <div class="erp-head"><div><span class="erp-eyebrow">VERKAUF</span><h1><?php p($editing?'Angebot bearbeiten':'Neues Angebot');?></h1><p class="erp-sub"><?php p($editing?'Angebot '.$o['offer_no'].' ändern. Die Angebotsnummer bleibt unverändert.':'Kunde, Positionen und Konditionen erfassen.');?></p></div><div class="erp-actions"><a class="button" href="<?php p($editing?$url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$o['id']]):$url->linkToRoute('reinhardterp.business.offers'));?>">Abbrechen</a></div></div>
 <div class="erp-form-card erp-offer-create">
  <form enctype="multipart/form-data" method="post" action="<?php p($action);?>" id="offerCreateForm">
   <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
   <div class="erp-form-grid">
    <div><label>Kunde</label><select name="customerId" id="offerCustomerId" required><?php foreach($_['customers'] as $c):?><option value="<?php p($c['id']);?>" <?php if($editing&&(int)$o['customer_id']===(int)$c['id']):?>selected<?php endif;?>><?php p($c['name']);?></option><?php endforeach;?></select></div>
    <div><label>Projekt</label><select name="projectId" id="offerProjectId"><option value="">ohne Projekt</option><?php foreach($_['projects'] as $p):?><option value="<?php p($p['id']);?>" data-customer-id="<?php p($p['customer_id']??'');?>" <?php if($editing&&(int)($o['project_id']??0)===(int)$p['id']):?>selected<?php endif;?>><?php p($p['project_no'].' · '.$p['title']);?></option><?php endforeach;?></select></div>
    <div class="erp-span-2"><label>Angebotstitel</label><input name="title" required value="<?php p($editing?(string)$o['title']:'');?>" placeholder="z. B. Einbauschrank Schlafzimmer"></div>
    <div><label>Gültig bis</label><input type="date" name="validUntil" value="<?php p($editing?(string)($o['valid_until']??''):'');?>"></div>
    <div><label>USt.</label><div class="erp-input-suffix"><input type="number" step="0.01" min="0" max="100" name="vatRate" id="offerVatRate" value="<?php p($editing?(string)$o['vat_rate']:'19');?>"><span>%</span></div></div>
   </div>
   <div class="erp-form-grid"><div style="grid-column:1/-1"><label>Betreff</label><input name="subject" value="<?php p($editing?(string)($o['subject']??''):'');?>" placeholder="z. B. Angebot Reparaturarbeiten Fensterrahmen"></div></div>
   <div><label>Einleitungstext</label><textarea name="introText" rows="4" placeholder="Individueller Einleitungstext"><?php p($editing?(string)($o['intro_text']??''):'');?></textarea></div>
   <div><label>Schlusstext</label><textarea name="outroText" rows="4" placeholder="Individueller Schlusstext"><?php p($editing?(string)($o['outro_text']??''):'');?></textarea></div>
   <div class="erp-offer-items-head"><div><h2>Positionen</h2><p class="erp-muted">Leistungen und Produkte einzeln aufführen.</p></div><button type="button" class="button" id="offerAddItem">+ Position</button></div>
   <div class="erp-offer-items" id="offerItems">
   <?php foreach($items as $idx=>$row):?>
    <div class="erp-offer-item" data-offer-item>
     <div class="erp-offer-pos" data-position><?php p($idx+1);?></div>
     <div class="erp-offer-description"><label>Beschreibung</label><textarea name="descriptions[]" rows="2" required placeholder="Leistung oder Produkt"><?php p((string)($row['description']??''));?></textarea><div class="erp-item-image"><label class="button erp-image-button">Bild hinzufügen<input type="file" name="itemImages[]" accept="image/jpeg,image/png" hidden></label><?php if($editing&&!empty($row['image_data'])):?><span class="erp-image-existing">Bild vorhanden</span><input type="hidden" name="keepImages[]" value="1"><?php else:?><input type="hidden" name="keepImages[]" value="0"><?php endif;?><span class="erp-image-name" data-image-name></span></div></div>
     <div class="erp-item-qty"><label>Menge</label><input type="number" name="quantities[]" min="0.01" step="0.01" value="<?php p((string)($row['quantity']??1));?>" required data-qty></div>
     <div class="erp-item-unit"><label>Einheit</label><input name="units[]" value="<?php p((string)($row['unit']??'Stk.'));?>" required></div>
     <div class="erp-alt-position"><label>Alternative</label><label class="erp-check"><input type="checkbox" name="alternatives[<?php p($idx);?>]" value="1" data-alternative <?php if(!empty($row['is_alternative'])):?>checked<?php endif;?>> Alternativposition</label></div>
     <div class="erp-item-price"><label>EP netto</label><input type="number" name="unitPrices[]" min="0" step="0.01" value="<?php p((string)($row['unit_price']??0));?>" required data-price></div>
     <div class="erp-offer-line-total"><label>Gesamt</label><strong data-line-total>0,00 €</strong></div>
     <button type="button" class="erp-icon-button erp-offer-remove" data-remove-item aria-label="Position entfernen">×</button>
    </div>
   <?php endforeach;?>
   </div>
   <div class="erp-offer-bottom"><div><label>Notizen / Angebotsbedingungen</label><textarea name="notes" rows="4" placeholder="Optionale Hinweise, Ausführungsbedingungen oder Zahlungsbedingungen"><?php p($editing?(string)($o['notes']??''):'');?></textarea></div><div class="erp-offer-totals" aria-live="polite"><div><span>Netto</span><strong id="offerNet">0,00 €</strong></div><div><span>USt.</span><strong id="offerVat">0,00 €</strong></div><div class="erp-offer-grand"><span>Brutto</span><strong id="offerGross">0,00 €</strong></div></div></div>
   <div class="erp-actions"><button class="button primary"><?php p($editing?'Änderungen speichern':'Angebot anlegen');?></button></div>
  </form>
 </div>
</div></div>
