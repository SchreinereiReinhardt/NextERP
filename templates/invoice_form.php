<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
use OCP\Util;
$url=\OCP\Server::get(IURLGenerator::class);
Util::addScript('reinhardterp','invoices');
$prefill=$_['prefillOrder']??null;$prefillProject=$_['prefillProject']??null;$prefillCustomerId=$prefill?(int)$prefill['customer_id']:(int)($prefillProject['customer_id']??0);$prefillProjectId=$prefill?(int)($prefill['project_id']??0):(int)($_['prefillProjectId']??0);
$prefillItems=$_['prefillItems']??[];
$prefillType=$_['prefillType']??'invoice';$prefillPercent=$_['prefillPercent']??null;$prefillAmount=$_['prefillAmount']??null;$prefillMode=$_['prefillMode']??null;
$statusLabels=['draft'=>'Entwurf','open'=>'Offen','paid'=>'Bezahlt','cancelled'=>'Storniert'];$typeLabels=['invoice'=>'Rechnung','advance'=>'Abschlag','final'=>'Schlussrechnung','credit'=>'Gutschrift'];
?>
<div id="app-content"><div class="erp-page erp-offers-v2 erp-commercial-form-v3">
 <div class="erp-head"><div><span class="erp-eyebrow">FINANZEN</span><h1>Neue Rechnung</h1><p class="erp-sub"><?php p($prefill?'Positionen aus '.$prefill['order_no'].' wurden übernommen':($prefillProject?'Projektleistungen aus '.$prefillProject['project_no'].' wurden übernommen':'Kunde, Positionen und Zahlungsdaten erfassen.'));?></p></div><div class="erp-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoices'));?>">Abbrechen</a></div></div>
<div class="erp-form-card erp-offer-create">
  <form enctype="multipart/form-data" method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveInvoice'));?>" id="invoiceCreateForm">
   <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
   <input type="hidden" name="orderId" value="<?php p($prefill['id']??'');?>">

   <div class="erp-form-grid">
    <div><label>Kunde</label><select name="customerId" id="invoiceCustomerId" required><?php foreach($_['customers'] as $c):?><option value="<?php p($c['id']);?>" <?php if($prefillCustomerId===(int)$c['id']):?>selected<?php endif;?>><?php p($c['name']);?></option><?php endforeach;?></select></div>
    <div><label>Projekt</label><select name="projectId" id="invoiceProjectId"><option value="">ohne Projekt</option><?php foreach($_['projects'] as $p):?><option value="<?php p($p['id']);?>" data-customer-id="<?php p($p['customer_id']??'');?>" <?php if($prefillProjectId===(int)$p['id']):?>selected<?php endif;?>><?php p($p['project_no'].' · '.$p['title']);?></option><?php endforeach;?></select></div>
    <div><label>Sachbearbeiter</label><input name="clerkName" value="<?php p($_['defaultClerkName']??'');?>" placeholder="wird aus dem angemeldeten Benutzer übernommen"><small class="erp-muted">Kann für dieses Dokument manuell überschrieben werden.</small></div>
    <div><label>Rechnungsdatum</label><input type="date" name="invoiceDate" value="<?php p(date('Y-m-d'));?>" required></div>
    <div><label>Leistungsdatum</label><input type="date" name="serviceDate" value="<?php p(date('Y-m-d'));?>"></div>
    <div><label>Fällig am</label><input type="date" name="dueDate" id="invoiceDueDate" value="<?php p(date('Y-m-d',strtotime('+14 days')));?>"></div>
    <div><label>Besteuerungsart</label><select name="taxMode" id="invoiceTaxMode"><option value="standard19" selected>19 % MwSt. (Standard)</option><option value="standard7">7 % MwSt.</option><option value="small_business">Kleinunternehmer § 19 UStG</option><option value="reverse_charge_13b">Bauleistung § 13b – Steuerschuldner Leistungsempfänger</option><option value="custom">Individueller Steuersatz</option></select><small class="erp-muted" id="invoiceTaxHint"></small></div>
    <div id="invoiceCustomVatWrap" hidden><label>Individueller USt.-Satz</label><div class="erp-input-suffix"><input type="number" step="0.01" min="0" max="100" name="vatRate" id="invoiceVatRate" value="19"><span>%</span></div></div>
    <div><label>Rechnungsart</label><select name="invoiceType"><option value="invoice" <?php if($prefillType==='invoice'):?>selected<?php endif;?>>Rechnung</option><option value="advance" <?php if($prefillType==='advance'):?>selected<?php endif;?>>Abschlagsrechnung</option><option value="final" <?php if($prefillType==='final'):?>selected<?php endif;?>>Schlussrechnung</option></select><?php if($prefillType==='advance'&&$prefillMode==='percent'&&$prefillPercent!==null):?><small class="erp-muted">Abschlag mit <?php p(number_format((float)$prefillPercent,2,',','.'));?> % des Netto-Auftragswerts vorbelegt.</small><?php elseif($prefillType==='advance'&&$prefillMode==='amount'&&$prefillAmount!==null):?><small class="erp-muted">Abschlag mit <?php p(number_format((float)$prefillAmount,2,',','.'));?> € netto vorbelegt.</small><?php endif;?></div>
   </div>


   <div class="erp-form-grid">
    <div style="grid-column:1/-1"><label>Betreff</label><input name="subject" placeholder="z. B. Rechnung Fensterreparatur / Projekt Musterstraße"></div>
   </div>
   <div>
    <label>Einleitungstext</label>
    <textarea name="introText" rows="4" placeholder="Individueller Text oberhalb der Positionen"></textarea>
   </div>
   <div>
    <label>Schlusstext</label>
    <textarea name="outroText" rows="4" placeholder="Individueller Text unterhalb der Summen"></textarea>
   </div>
   <?php $billingCandidates=$_['billingCandidates']??[]; ?>
   <section class="erp-billing-assistant" id="billingAssistant" data-api-base="<?php p($url->linkToRoute('reinhardterp.business.billingCandidatesApi',['projectId'=>0]));?>" data-check-base="<?php p($url->linkToRoute('reinhardterp.business.billingCheck',['projectId'=>0]));?>">
    <div class="erp-billing-head"><div><span class="erp-eyebrow">ABRECHNUNGSASSISTENT</span><h2>Nichts vergessen</h2><p class="erp-muted" id="billingAssistantText">Projekt auswählen – Betrio prüft Arbeitszeiten, Material, Rapporte und Lieferantenvorgänge.</p></div><div class="erp-billing-count" id="billingAssistantCount">–</div></div>
    <div class="erp-billing-list" id="billingAssistantList"></div>
    <div class="erp-billing-complete" id="billingAssistantComplete" hidden>✓ Abrechnung vollständig geprüft</div>
   </section>

   <div class="erp-offer-items-head"><div><h2>Positionen</h2><p class="erp-muted">Leistungen und Produkte netto erfassen.</p></div><button type="button" class="button" id="invoiceAddItem">+ Position</button></div>
   <div class="erp-offer-items" id="invoiceItems">
   <?php $rows=$prefillItems?:[['description'=>'','quantity'=>1,'unit'=>'Stk.','unit_price'=>0,'total_price'=>0]]; foreach($rows as $idx=>$row):?>
    <div class="erp-offer-item" data-invoice-item>
     <div class="erp-offer-pos" data-position><?php p($idx+1);?></div>
     <div class="erp-offer-description"><label>Beschreibung</label><textarea name="descriptions[]" rows="2" required><?php p($row['description']??'');?></textarea><div class="erp-item-image"><label class="button erp-image-button">Bild hinzufügen<input type="file" name="itemImages[]" accept="image/jpeg,image/png" hidden></label><span class="erp-image-name" data-image-name></span></div></div>
     <div class="erp-item-qty"><label>Menge</label><input type="number" name="quantities[]" min="0.01" step="0.01" value="<?php p((string)($row['quantity']??1));?>" required data-qty></div>
     <div class="erp-item-unit"><label>Einheit</label><input name="units[]" value="<?php p((string)($row['unit']??'Stk.'));?>" required></div><div class="erp-alt-position"><label>Alternative</label><label class="erp-check"><input type="checkbox" name="alternatives[<?php p($idx);?>]" value="1" data-alternative <?php if(!empty($row['is_alternative'])):?>checked<?php endif;?>> Alternativposition</label></div>
     <div class="erp-item-price"><label>EP netto</label><input type="number" name="unitPrices[]" min="0" step="0.01" value="<?php p((string)($row['unit_price']??0));?>" required data-price></div>
     <div class="erp-offer-line-total"><label>Gesamt</label><strong data-line-total><?php p(number_format((float)($row['total_price']??0),2,',','.').' €');?></strong></div>
     <button type="button" class="erp-icon-button erp-offer-remove" data-remove-item aria-label="Position entfernen">×</button>
    </div>
   <?php endforeach;?>
   </div>

   <div class="erp-offer-bottom">
    <div><label>Zahlungsbedingungen</label><select name="paymentTermKey" id="invoicePaymentTerm"><?php foreach(($_['paymentTerms']??[]) as $key=>$term):?><option value="<?php p($key);?>" data-days="<?php p($term['days']);?>" data-text="<?php p($term['text']);?>" <?php if($key===($_['defaultPaymentTerm']??'net14')):?>selected<?php endif;?>><?php p($term['label']);?></option><?php endforeach;?></select><small class="erp-muted">Vorlagen kannst du unter Einstellungen anpassen.</small><label>Hinweise / Zahlungstext</label><textarea name="notes" id="invoiceNotes" rows="4"></textarea></div>
    <div class="erp-offer-totals" aria-live="polite">
     <div><span>Netto</span><strong id="invoiceNet">0,00 €</strong></div>
     <div><span>USt.</span><strong id="invoiceVat">0,00 €</strong></div>
     <div class="erp-offer-grand"><span>Brutto</span><strong id="invoiceGross">0,00 €</strong></div>
    </div>
   </div>
   <div class="erp-actions"><button class="button primary">Als Entwurf anlegen</button></div>
  </form>
 </div>


</div></div>
