<?php
require __DIR__.'/_nav.php'; use OCP\IURLGenerator; use OCP\Util;
$url=\OCP\Server::get(IURLGenerator::class);Util::addScript('reinhardterp','invoices');$i=$_['invoice'];
?>
<div id="app-content"><div class="erp-page erp-offers-v2 erp-commercial-form-v3">
<div class="erp-head"><div><span class="erp-eyebrow">RECHNUNGSENTWURF</span><h1>Entwurf bearbeiten</h1><p class="erp-sub">Bis zur Finalisierung können alle Rechnungsdaten geändert werden.</p></div></div>
<form class="erp-form-card erp-offer-create" enctype="multipart/form-data" method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateInvoice',['id'=>$i['id']]));?>" id="invoiceCreateForm">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
<div class="erp-form-grid">
 <div><label>Sachbearbeiter</label><input name="clerkName" value="<?php p($_['clerkName']??'');?>"><small class="erp-muted">Standard: angemeldeter Betrio-/Nextcloud-Benutzer; hier manuell überschreibbar.</small></div>
<div><label>Kunde</label><select name="customerId" id="invoiceCustomerId"><?php foreach($_['customers'] as $c):?><option value="<?php p($c['id']);?>" <?php if((int)$c['id']===(int)$i['customer_id']):?>selected<?php endif;?>><?php p($c['name']);?></option><?php endforeach;?></select></div>
<div><label>Projekt</label><select name="projectId" id="invoiceProjectId"><option value="">ohne Projekt</option><?php foreach($_['projects'] as $p):?><option value="<?php p($p['id']);?>" data-customer-id="<?php p($p['customer_id']??'');?>" <?php if((int)$p['id']===(int)($i['project_id']??0)):?>selected<?php endif;?>><?php p($p['project_no'].' · '.$p['title']);?></option><?php endforeach;?></select></div>
<div><label>Rechnungsart</label><select name="invoiceType"><?php foreach(['invoice'=>'Rechnung','advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift'] as $v=>$l):?><option value="<?php p($v);?>" <?php if(($i['invoice_type']??'invoice')===$v):?>selected<?php endif;?>><?php p($l);?></option><?php endforeach;?></select></div>
<div><label>Rechnungsdatum</label><input type="date" name="invoiceDate" value="<?php p($i['invoice_date']);?>"></div>
<div><label>Leistungsdatum</label><input type="date" name="serviceDate" value="<?php p($i['service_date']??'');?>"></div>
<div><label>Fällig am</label><input type="date" name="dueDate" id="invoiceDueDate" value="<?php p($i['due_date']??'');?>"></div>
<div><label>Besteuerungsart</label><?php $taxMode=$i['tax_mode']??(((float)$i['vat_rate']===7.0)?'standard7':(((float)$i['vat_rate']===19.0)?'standard19':'custom'));?><select name="taxMode" id="invoiceTaxMode"><option value="standard19" <?php if($taxMode==='standard19'):?>selected<?php endif;?>>19 % MwSt. (Standard)</option><option value="standard7" <?php if($taxMode==='standard7'):?>selected<?php endif;?>>7 % MwSt.</option><option value="small_business" <?php if($taxMode==='small_business'):?>selected<?php endif;?>>Kleinunternehmer § 19 UStG</option><option value="reverse_charge_13b" <?php if($taxMode==='reverse_charge_13b'):?>selected<?php endif;?>>Bauleistung § 13b – Steuerschuldner Leistungsempfänger</option><option value="custom" <?php if($taxMode==='custom'):?>selected<?php endif;?>>Individueller Steuersatz</option></select><small class="erp-muted" id="invoiceTaxHint"></small></div>
<div id="invoiceCustomVatWrap"><label>USt.-Satz</label><input type="number" step="0.01" min="0" max="100" name="vatRate" id="invoiceVatRate" value="<?php p($i['vat_rate']);?>"></div>
</div>

<div class="erp-form-grid">
 <div style="grid-column:1/-1"><label>Betreff</label><input name="subject" value="<?php p($i['subject']??'');?>"></div>
</div>
<div><label>Einleitungstext</label><textarea name="introText" rows="4"><?php p($i['intro_text']??'');?></textarea></div>
<div><label>Schlusstext</label><textarea name="outroText" rows="4"><?php p($i['outro_text']??'');?></textarea></div>
<div class="erp-offer-items-head"><h2>Positionen</h2><button type="button" class="button" id="invoiceAddItem">+ Position</button></div>
<div class="erp-offer-items" id="invoiceItems"><?php foreach($_['items'] as $idx=>$x):?>
<div class="erp-offer-item" data-invoice-item><div class="erp-offer-pos" data-position><?php p($idx+1);?></div>
<div class="erp-offer-description"><label>Beschreibung</label><textarea name="descriptions[]" rows="2"><?php p($x['description']);?></textarea><div class="erp-item-image"><label class="button erp-image-button">Bild hinzufügen<input type="file" name="itemImages[]" accept="image/jpeg,image/png" hidden></label><?php if(!empty($x['image_data'])):?><span class="erp-image-existing">Bild vorhanden</span><input type="hidden" name="keepImages[]" value="1"><?php else:?><input type="hidden" name="keepImages[]" value="0"><?php endif;?><span class="erp-image-name" data-image-name></span></div></div>
<div class="erp-item-qty"><label>Menge</label><input type="number" step="0.01" min="0.01" name="quantities[]" value="<?php p($x['quantity']);?>" data-qty></div>
<div class="erp-item-unit"><label>Einheit</label><input name="units[]" value="<?php p($x['unit']);?>"></div><div class="erp-alt-position"><label>Alternative</label><label class="erp-check"><input type="checkbox" name="alternatives[<?php p($idx);?>]" value="1" data-alternative <?php if(!empty($x['is_alternative'])):?>checked<?php endif;?>> Alternativposition</label></div>
<div class="erp-item-price"><label>EP netto</label><input type="number" step="0.01" min="0" name="unitPrices[]" value="<?php p($x['unit_price']);?>" data-price></div>
<div class="erp-offer-line-total"><label>Gesamt</label><strong data-line-total></strong></div><button type="button" class="erp-icon-button erp-offer-remove" data-remove-item>×</button></div>
<?php endforeach;?></div>
<div class="erp-offer-bottom"><div><label>Zahlungsbedingungen</label><select name="paymentTermKey" id="invoicePaymentTerm"><option value="">Manuell / unverändert</option><?php foreach(($_['paymentTerms']??[]) as $key=>$term):?><option value="<?php p($key);?>" data-days="<?php p($term['days']);?>" data-text="<?php p($term['text']);?>" <?php if($key===($i['payment_term_key']??'')):?>selected<?php endif;?>><?php p($term['label']);?></option><?php endforeach;?></select><label>Hinweise / Zahlungstext</label><textarea name="notes" id="invoiceNotes" rows="4"><?php p($i['notes']??'');?></textarea></div><div class="erp-offer-totals"><div><span>Netto</span><strong id="invoiceNet"></strong></div><div><span>USt.</span><strong id="invoiceVat"></strong></div><div class="erp-offer-grand"><span>Brutto</span><strong id="invoiceGross"></strong></div></div></div>
<div class="erp-actions"><button class="button primary">Änderungen speichern</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$i['id']]));?>">Abbrechen</a></div>
</form></div></div>
