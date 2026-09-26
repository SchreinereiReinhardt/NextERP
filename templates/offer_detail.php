<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OCP\Server::get(IURLGenerator::class);
$o=$_['offer'];
$customer=$_['customer']??[];$company=$_['company']??[];
$offerMailRecipient=trim((string)($customer['invoice_email']??''));if($offerMailRecipient==='')$offerMailRecipient=trim((string)($customer['email']??''));
$offerMailSubject='Angebot '.(string)($o['offer_no']??'');
$offerMailBody="Guten Tag,\r\n\r\nanbei erhalten Sie unser Angebot ".(string)($o['offer_no']??'').".\r\n\r\nMit freundlichen Grüßen\r\n".trim((string)($company['name']??''));
$offerMailHref='mailto:'.rawurlencode($offerMailRecipient).'?subject='.rawurlencode($offerMailSubject).'&body='.rawurlencode($offerMailBody);
$statusLabels=['draft'=>'Entwurf','sent'=>'Versendet','accepted'=>'Angenommen','rejected'=>'Abgelehnt','expired'=>'Abgelaufen'];
?>
<div id="app-content"><div class="erp-page erp-offer-detail-v2">
<?php if(!empty($_['linkedOrder'])):$lo=$_['linkedOrder'];?>
<div class="erp-notice erp-notice-success">Auftrag <a href="<?php p($url->linkToRoute('reinhardterp.business.orderDetail',['id'=>$lo['id']]));?>"><strong><?php p($lo['order_no']);?></strong></a> erstellt.</div>
<?php endif;?>
 <div class="erp-record-head"><div><span class="erp-eyebrow">ANGEBOT</span><h1><?php p($o['offer_no'].' · '.$o['title']);?></h1><p><?php p($o['customer_name'].($o['project_no']?' · '.$o['project_no'].' '.$o['project_title']:''));?></p></div><div class="erp-actions"><?php if(!empty($o['project_id'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$o['project_id']]));?>">Projekt öffnen</a><?php endif;?><?php if(empty($_['hasOrder'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.editOffer',['id'=>$o['id']]));?>">Bearbeiten</a><?php else:?><span class="erp-muted">Als Auftrag übernommen</span><?php endif;?><span class="erp-badge"><?php p($statusLabels[$o['status']]??$o['status']);?></span><a class="button" target="_blank" href="<?php p($url->linkToRoute('reinhardterp.business.offerPdf',['id'=>$o['id']]));?>">PDF herunterladen</a><a class="button primary" href="<?php p($offerMailHref);?>">Im Mailprogramm öffnen</a></div></div>

 <div class="erp-card">
  <h2>Angebot per E-Mail senden</h2>
  <p class="erp-muted">Betrio versendet über die in Nextcloud konfigurierte Mailverbindung. Das Angebots-PDF wird automatisch angehängt.</p>
  <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.sendOfferEmail',['id'=>$o['id']]));?>" class="erp-form-grid">
   <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
   <div><label>Empfänger</label><input type="email" name="to" required value="<?php p($offerMailRecipient);?>"></div>
   <div><label>Betreff</label><input type="text" name="subject" required value="<?php p($offerMailSubject);?>"></div>
   <div style="grid-column:1/-1"><label>Nachricht</label><textarea name="body" rows="7" required><?php p($offerMailBody);?></textarea></div>
   <div><label><input type="checkbox" name="attachPdf" value="1" checked> Angebots-PDF anhängen</label></div>
   <div><label>&nbsp;</label><button class="button primary" type="submit">E-Mail jetzt senden</button></div>
  </form>
 </div>
 <div class="erp-grid-2">
  <div class="erp-card"><h2>Positionen</h2><div class="erp-table"><table><thead><tr><th>Pos.</th><th>Beschreibung</th><th>Menge</th><th>EP netto</th><th>Gesamt</th></tr></thead><tbody><?php foreach($_['items'] as $i):?><tr><td><?php p($i['position_no']);?></td><td><?php p($i['description']);?></td><td><?php p(number_format((float)$i['quantity'],2,',','.').' '.$i['unit']);?></td><td><?php p(number_format((float)$i['unit_price'],2,',','.').' €');?></td><td><strong><?php p(number_format((float)$i['total_price'],2,',','.').' €');?></strong></td></tr><?php endforeach;?></tbody></table></div><?php if(trim((string)($o['notes']??''))!==''):?><div class="erp-offer-notes"><h3>Hinweise</h3><p><?php echo nl2br(htmlspecialchars((string)$o['notes'],ENT_QUOTES));?></p></div><?php endif;?></div>
  <div class="erp-card"><h2>Summen</h2><div class="erp-kpi-line"><span>Netto</span><strong><?php p(number_format((float)$o['net_amount'],2,',','.').' €');?></strong></div><div class="erp-kpi-line"><span>USt. <?php p(number_format((float)$o['vat_rate'],2,',','.'));?> %</span><strong><?php p(number_format((float)$o['gross_amount']-(float)$o['net_amount'],2,',','.').' €');?></strong></div><div class="erp-kpi-line erp-total"><span>Brutto</span><strong><?php p(number_format((float)$o['gross_amount'],2,',','.').' €');?></strong></div>
   <?php if(!empty($o['valid_until'])):?><div class="erp-kpi-line"><span>Gültig bis</span><strong><?php p(date('d.m.Y',strtotime($o['valid_until'])));?></strong></div><?php endif;?>
   <?php if (!empty($o['source_document_id'])): ?><div class="erp-kpi-line"><span>Quelldokument</span><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.review',['id'=>$o['source_document_id']])); ?>">PDF öffnen</a></div><?php endif; ?>
   <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateOfferStatus',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><label>Status</label><select name="status"><?php foreach($statusLabels as $key=>$label):?><option value="<?php p($key);?>"<?php if($o['status']===$key)echo ' selected';?>><?php p($label);?></option><?php endforeach;?></select><button class="button">Status speichern</button></form>
   <div class="erp-document-chain" style="margin-top:16px"><h3>Weiterverarbeiten</h3><p class="erp-muted">Gewünschten Folgebeleg auswählen.</p>
   <form method="post" onsubmit="return confirm('Auftrag aus diesem Angebot erstellen?');" action="<?php p($url->linkToRoute('reinhardterp.business.createOrderFromOffer',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button primary">Auftrag erzeugen</button></form>
   <form method="post" onsubmit="return confirm('Lieferschein aus diesem Angebot erstellen?');" action="<?php p($url->linkToRoute('reinhardterp.business.createDeliveryFromOffer',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Lieferschein erzeugen</button></form>
   <form method="post" onsubmit="return confirm('Rechnung aus diesem Angebot vorbereiten?');" action="<?php p($url->linkToRoute('reinhardterp.business.createInvoiceFromOffer',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="invoiceType" value="invoice"><button class="button">Rechnung vorbereiten</button></form>
   <form method="post" onsubmit="return confirm('Abschlagsrechnung aus diesem Angebot vorbereiten?');" action="<?php p($url->linkToRoute('reinhardterp.business.createInvoiceFromOffer',['id'=>$o['id']]));?>" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="invoiceType" value="advance"><label style="max-width:120px">Abschlag %<input type="number" name="installmentPercent" value="30" min="0.01" max="100" step="0.01"></label><button class="button">Abschlagsrechnung</button></form>
   <form method="post" onsubmit="return confirm('Schlussrechnung aus diesem Angebot vorbereiten?');" action="<?php p($url->linkToRoute('reinhardterp.business.createInvoiceFromOffer',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="invoiceType" value="final"><button class="button">Schlussrechnung vorbereiten</button></form>
   <?php if(!empty($_['linkedOrder'])):?><p class="erp-muted" style="margin-top:10px">Verknüpfter Auftrag: <a href="<?php p($url->linkToRoute('reinhardterp.business.orderDetail',['id'=>$_['linkedOrder']['id']]));?>"><strong><?php p($_['linkedOrder']['order_no']);?></strong></a></p><?php endif;?>
   <?php if(!empty($_['deliveryNotes'])):?><p class="erp-muted">Lieferscheine: <?php foreach($_['deliveryNotes'] as $idx=>$d):?><?php if($idx>0)echo ' · ';?><a href="<?php p($url->linkToRoute('reinhardterp.business.deliveryDetail',['id'=>$d['id']]));?>"><?php p($d['delivery_no']);?></a><?php endforeach;?></p><?php endif;?></div>
  </div>
 </div>
</div></div>