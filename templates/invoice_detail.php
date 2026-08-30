<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OCP\Server::get(IURLGenerator::class);
$i=$_['invoice'];$customer=$_['invoiceCustomer']??[];$company=$_['company']??[];
$mailRecipient=trim((string)($customer['invoice_email']??''));if($mailRecipient==='')$mailRecipient=trim((string)($customer['email']??''));
$mailNumber=$i['status']==='draft'?'Entwurf #'.$i['id']:(string)($i['invoice_no']??'');
$mailType=($i['invoice_type']??'invoice')==='credit'?'Gutschrift':'Rechnung';
$mailSubject=$mailType.' '.$mailNumber;
$mailCustomerName=trim((string)($customer['name']??$i['customer_name']??''));
$mailSalutation=$mailCustomerName!==''?'Guten Tag,':'Guten Tag,';
$mailBody=$mailSalutation."\r\n\r\nanbei erhalten Sie unsere ".$mailType." ".$mailNumber.".";
if(($i['status']??'draft')!=='draft')$mailBody.="\r\n\r\nDie Rechnung finden Sie im Anhang dieser E-Mail.";
$mailBody.="\r\n\r\nMit freundlichen Grüßen\r\n".trim((string)($company['name']??''));
$mailHref='mailto:'.rawurlencode($mailRecipient).'?subject='.rawurlencode($mailSubject).'&body='.rawurlencode($mailBody);
$statusLabels=['draft'=>'Entwurf','open'=>'Offen','paid'=>'Bezahlt','cancelled'=>'Storniert'];$typeLabels=['invoice'=>'Rechnung','advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift'];$type=$i['invoice_type']??'invoice';$paid=array_sum(array_map(fn($p)=>(float)$p['amount'],$_['payments']??[]));$payable=max(0,(float)$i['gross_amount']-($type==='final'?(float)($i['advance_gross_amount']??0):0));$remaining=max(0,$payable-$paid);
?>
<div id="app-content"><div class="erp-page">
<?php if(!empty($_['sourceOffer'])||!empty($_['sourceOrder'])||!empty($_['relatedInvoice'])||!empty($_['creditNotes'])):?><div class="erp-notice"><strong>Vorgangskette:</strong> <?php if(!empty($_['sourceOffer'])):$vo=$_['sourceOffer'];?><a href="<?php p($url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$vo['id']]));?>">Angebot <?php p($vo['offer_no']);?></a> → <?php endif;?><?php if(!empty($_['sourceOrder'])):$vord=$_['sourceOrder'];?><a href="<?php p($url->linkToRoute('reinhardterp.business.orderDetail',['id'=>$vord['id']]));?>">Auftrag <?php p($vord['order_no']);?></a> → <?php endif;?><strong><?php p($typeLabels[$type]??'Rechnung');?> <?php p(($i['status']??'draft')==='draft'?'Entwurf #'.$i['id']:$i['invoice_no']);?></strong><?php if(!empty($_['relatedInvoice'])):$ri=$_['relatedInvoice'];?> · Gutschrift zu <a href="<?php p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$ri['id']]));?>"><?php p($ri['invoice_no']);?></a><?php endif;?><?php foreach($_['creditNotes']??[] as $cn):?> · <a href="<?php p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$cn['id']]));?>">Gutschrift <?php p(($cn['status']??'draft')==='draft'?'Entwurf #'.$cn['id']:$cn['invoice_no']);?></a><?php endforeach;?></div><?php endif;?>
 <div class="erp-record-head"><div><span class="erp-eyebrow"><?php p(mb_strtoupper($typeLabels[$type]??'RECHNUNG'));?></span><h1><?php p($i['status']==='draft'?'Rechnungsentwurf #'.$i['id']:$i['invoice_no']);?></h1><p><?php p(($customer['name']??$i['customer_name']??'').(!empty($i['project_no'])?' · '.$i['project_no'].' '.$i['project_title']:''));?></p></div><span class="erp-badge"><?php p($statusLabels[$i['status']]??$i['status']);?></span></div>

 <div class="erp-kpis"><div><span>Netto</span><strong><?php p(number_format((float)$i['net_amount'],2,',','.'));?> €</strong></div><div><span>USt.</span><strong><?php p(number_format((float)$i['vat_rate'],2,',','.'));?> %</strong></div><div><span>Brutto</span><strong><?php p(number_format((float)$i['gross_amount'],2,',','.'));?> €</strong></div><div><span>Fällig</span><strong><?php p(!empty($i['due_date'])?date('d.m.Y',strtotime((string)$i['due_date'])):'—');?></strong></div></div>

 <div class="erp-grid-2">
  <div class="erp-card"><h2>Positionen</h2><div class="erp-table"><table><thead><tr><th>Leistung</th><th>Menge</th><th>Einheit</th><th>EP</th><th>Gesamt</th></tr></thead><tbody><?php foreach($_['items'] as $x):?><tr><td><?php if(!empty($x['is_alternative'])):?><strong>Alternativposition</strong><br><?php endif;?><?php echo (string)$x['description'];?></td><td><?php p(number_format((float)$x['quantity'],2,',','.'));?></td><td><?php p($x['unit']);?></td><td><?php p(number_format((float)$x['unit_price'],2,',','.'));?> €</td><td><strong><?php p(number_format((float)$x['total_price'],2,',','.'));?> €</strong></td></tr><?php endforeach;?></tbody></table></div><?php if(!empty($i['notes'])):?><div class="erp-notice"><?php echo (string)$i['notes'];?></div><?php endif;?></div>

  <div class="erp-card"><h2>Rechnungssteuerung</h2>
   <p><strong>Betreff:</strong> <?php p($i['subject']??'—');?><br><strong>Sachbearbeiter:</strong> <?php p($_['clerkName']??($i['clerk_name']??'—'));?><br><strong>Rechnungsdatum:</strong> <?php p(date('d.m.Y',strtotime((string)$i['invoice_date'])));?><br><strong>Leistungsdatum:</strong> <?php p(!empty($i['service_date'])?date('d.m.Y',strtotime((string)$i['service_date'])):'—');?><br><?php if(!empty($i['order_no'])):?><strong>Auftrag:</strong> <?php p($i['order_no']);?><br><?php endif;?></p>
   <div class="erp-actions"><?php if(!empty($i['project_id'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$i['project_id']]));?>">Projekt öffnen</a><?php endif;?><a class="button" target="_blank" href="<?php p($url->linkToRoute('reinhardterp.business.invoicePdf',['id'=>$i['id']]));?>">PDF herunterladen</a><?php if($i['status']!=='draft'):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceXRechnung',['id'=>$i['id']]));?>">XRechnung XML</a><a class="button primary" href="<?php p($mailHref);?>">Im Mailprogramm öffnen</a><?php endif;?></div>
   <?php if($i['status']!=='draft'):?><div class="erp-notice"><strong>E-Mail:</strong> Der Button öffnet das unter Windows hinterlegte Standard-Mailprogramm mit Empfänger, Betreff <strong><?php p($mailSubject);?></strong> und vorbereitetem Nachrichtentext. Die PDF-Rechnung wurde beim Finalisieren zusätzlich automatisch unter <strong>ERP/30_Finanzen/Ausgangsrechnungen/Jahr/Monat</strong> gespeichert. Browser dürfen lokale Mail-Anhänge nicht automatisch setzen; PDF/XML stehen direkt daneben zum Herunterladen bereit.<?php if($mailRecipient===''):?><br><strong>Hinweis:</strong> Beim Kunden ist noch keine Rechnungs-E-Mail-Adresse hinterlegt.<?php endif;?></div><?php endif;?>
   
   <?php if($i['status']!=='draft'):?>
   <div class="erp-card">
    <h2>Rechnung direkt per E-Mail senden</h2>
    <p class="erp-muted">Versand erfolgt über die in Nextcloud eingerichtete Mailverbindung. PDF und XRechnung können direkt angehängt werden.</p>
    <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.sendInvoiceEmail',['id'=>$i['id']]));?>" class="erp-form-grid">
     <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
     <div><label>Empfänger</label><input type="email" name="to" required value="<?php p($mailRecipient);?>"></div>
     <div><label>Betreff</label><input type="text" name="subject" required value="<?php p($mailSubject);?>"></div>
     <div style="grid-column:1/-1"><label>Nachricht</label><textarea name="body" rows="7" required><?php p($mailBody);?></textarea></div>
     <div><label><input type="checkbox" name="attachPdf" value="1" checked> PDF anhängen</label></div>
     <div><label><input type="checkbox" name="attachXml" value="1"> XRechnung XML zusätzlich anhängen</label></div>
     <div><label>&nbsp;</label><button class="button primary" type="submit">E-Mail jetzt senden</button></div>
    </form>
   </div>
   <?php endif;?>
<?php if($i['status']!=='draft'):?><?php $ew=$_['eInvoiceWarnings']??[];?><div class="erp-notice"><strong>E-Rechnung:</strong> XRechnung 3.0 (UBL 2.1).<?php if($ew):?><br><strong>Noch fehlende Angaben:</strong><ul><?php foreach($ew as $w):?><li><?php p($w);?></li><?php endforeach;?></ul><span class="erp-muted">Der XML-Export wird blockiert, bis die erforderlichen Angaben vollständig sind.</span><?php else:?><br>Die aktuell geprüften Stammdaten sind vollständig.<?php endif;?></div><?php endif;?><?php if($i['status']==='draft'):?><div class="erp-actions"><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.business.editInvoice',['id'=>$i['id']]));?>">Entwurf bearbeiten</a></div><?php endif;?>
   <?php if($type==='final' && (float)($i['advance_gross_amount']??0)>0):?><div class="erp-notice"><strong>Berücksichtigte Abschläge:</strong> <?php p(number_format((float)$i['advance_gross_amount'],2,',','.'));?> € brutto<br><strong>Verbleibender Zahlbetrag:</strong> <?php p(number_format((float)$i['gross_amount']-(float)$i['advance_gross_amount'],2,',','.'));?> €</div><?php endif;?>
   <?php if($i['status']!=='draft' && $type!=='credit'):?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.createCreditNote',['id'=>$i['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Gutschrift erstellen</button></form><?php endif;?>
   <?php if($i['status']==='draft'):?>
    <div class="erp-notice"><strong>Entwurf:</strong> Noch keine endgültige Rechnungsnummer. Beim Finalisieren werden Rechnungsnummer sowie Firmen- und Kundendaten festgeschrieben.</div>
    <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.finalizeInvoice',['id'=>$i['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button primary">Rechnung finalisieren</button></form>
   <?php else:?>
    <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateInvoiceStatus',['id'=>$i['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><label>Status</label><select name="status"><option value="open" <?php if($i['status']==='open'):?>selected<?php endif;?>>Offen</option><option value="paid" <?php if($i['status']==='paid'):?>selected<?php endif;?>>Bezahlt</option><option value="cancelled" <?php if($i['status']==='cancelled'):?>selected<?php endif;?>>Storniert</option></select><button class="button primary">Status speichern</button></form>
   <?php endif;?>
  </div>
 </div>
 <?php if($i['status']!=='draft' && $i['status']!=='cancelled'):?>
 <div class="erp-grid-2">
  <div class="erp-card"><h2>Zahlungen</h2><div class="erp-kpis"><div><span>Zahlbetrag</span><strong><?php p(number_format($payable,2,',','.'));?> €</strong></div><div><span>Bezahlt</span><strong><?php p(number_format($paid,2,',','.'));?> €</strong></div><div><span>Offen</span><strong><?php p(number_format($remaining,2,',','.'));?> €</strong></div></div>
  <?php if($remaining>0.005):?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.addInvoicePayment',['id'=>$i['id']]));?>" class="erp-form-grid"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><div><label>Zahlungsdatum</label><input type="date" name="paymentDate" value="<?php p(date('Y-m-d'));?>"></div><div><label>Betrag</label><input type="number" min="0.01" step="0.01" max="<?php p($remaining);?>" name="amount" value="<?php p(number_format($remaining,2,'.',''));?>"></div><div><label>Notiz</label><input name="note" placeholder="z. B. Überweisung"></div><div><label>&nbsp;</label><button class="button primary">Zahlung buchen</button></div></form><?php endif;?>
  <?php foreach($_['payments']??[] as $p):?><div class="erp-list-row"><span><?php p(date('d.m.Y',strtotime($p['payment_date'])).' · '.($p['note']??'Zahlung'));?></span><strong><?php p(number_format((float)$p['amount'],2,',','.'));?> €</strong><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.deleteInvoicePayment',['id'=>$i['id'],'paymentId'=>$p['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Löschen</button></form></div><?php endforeach;?></div>
  <div class="erp-card"><h2>Mahnwesen</h2><p>Aktuelle Mahnstufe: <strong><?php p((int)($i['reminder_level']??0));?></strong><?php if(!empty($i['last_reminder_at'])):?> · zuletzt <?php p(date('d.m.Y',strtotime($i['last_reminder_at'])));?><?php endif;?></p>
  <?php if($i['status']==='open' && $remaining>0.005 && (int)($i['reminder_level']??0)<3):?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.advanceReminder',['id'=>$i['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Mahnstufe <?php p((int)($i['reminder_level']??0)+1);?> setzen</button></form><?php elseif($remaining<=0.005):?><p class="erp-muted">Vollständig bezahlt – keine Mahnung erforderlich.</p><?php endif;?></div>
 </div><?php endif;?>
</div></div>

<?php if(($i['invoice_type']??'')==='final' && !empty($_['previousInstallments'])): ?>
<section class="erp-card"><h3>Bereits berechnete Abschläge</h3><table><thead><tr><th>Rechnung</th><th>Datum</th><th>Netto</th><th>USt.</th><th>Brutto</th></tr></thead><tbody>
<?php foreach($_['previousInstallments'] as $a): ?><tr><td><?php p($a['invoice_no']??('Entwurf #'.$a['id']));?></td><td><?php p($a['invoice_date']??'');?></td><td><?php p(number_format((float)($a['net_total']??0),2,',','.').' €');?></td><td><?php p(number_format((float)($a['vat_total']??0),2,',','.').' €');?></td><td><?php p(number_format((float)($a['gross_total']??0),2,',','.').' €');?></td></tr><?php endforeach; ?>
</tbody></table></section>
<?php endif; ?>
