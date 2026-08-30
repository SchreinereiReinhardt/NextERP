<?php require __DIR__.'/_nav.php';use OCP\IURLGenerator;$url=\OC::$server->get(IURLGenerator::class);$o=$_['order'];?>
<div id="app-content"><div class="erp-page">
<?php if(!empty($_['sourceOffer'])):$so=$_['sourceOffer'];?><div class="erp-notice"><strong>Vorgang:</strong> Entstanden aus Angebot <a href="<?php p($url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$so['id']]));?>"><strong><?php p($so['offer_no']);?></strong></a>.</div><?php endif;?><div class="erp-record-head"><div><span class="erp-eyebrow">AUFTRAG</span><h1><?php p($o['order_no'].' · '.$o['title']);?></h1><p><?php p($o['customer_name'].($o['project_no']?' · '.$o['project_no'].' '.$o['project_title']:''));?></p></div><div class="erp-actions"><?php if(!empty($o['project_id'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$o['project_id']]));?>">Projekt öffnen</a><?php endif;?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceForm',['orderId'=>$o['id'],'invoiceType'=>'invoice']));?>">Rechnung</a><?php if(!empty($o['project_id'])):?><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceForm',['orderId'=>$o['id'],'projectId'=>$o['project_id'],'invoiceType'=>'invoice','includeTimes'=>1,'includeMaterials'=>1,'includeReports'=>1]));?>">Projektleistungen abrechnen</a><?php endif;?><a class="button primary" href="#abschlagsrechnung">Abschlagsrechnung</a><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceForm',['orderId'=>$o['id'],'invoiceType'=>'final']));?>">Schlussrechnung</a><span class="erp-badge"><?php p($o['status']);?></span></div></div><div class="erp-grid-2"><div class="erp-card"><h2>Positionen</h2><div class="erp-table"><table><thead><tr><th>Pos.</th><th>Beschreibung</th><th>Menge</th><th>Gesamt</th></tr></thead><tbody><?php foreach($_['items'] as $i):?><tr><td><?php p($i['position_no']);?></td><td><?php p($i['description']);?></td><td><?php p(number_format((float)$i['quantity'],2,',','.').' '.$i['unit']);?></td><td><?php p(number_format((float)$i['total_price'],2,',','.').' €');?></td></tr><?php endforeach;?></tbody></table></div></div><div class="erp-card"><h2>Auftragssteuerung</h2><div class="erp-kpi-line erp-total"><span>Brutto</span><strong><?php p(number_format((float)$o['gross_amount'],2,',','.').' €');?></strong></div><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateOrderStatus',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><select name="status"><option value="open">Offen</option><option value="confirmed">Bestätigt</option><option value="production">Fertigung</option><option value="installation">Montage</option><option value="completed">Abgeschlossen</option><option value="cancelled">Storniert</option></select><button class="button primary">Status speichern</button></form></div></div>

<div class="erp-card"><h2>Rechnungen zu diesem Auftrag</h2>
<?php if(!empty($_['invoices'])):?><div class="erp-table"><table><thead><tr><th>Art</th><th>Nummer</th><th>Status</th><th>Brutto</th></tr></thead><tbody><?php $labels=['invoice'=>'Rechnung','advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift'];foreach($_['invoices'] as $invoice):?><tr><td><?php p($labels[$invoice['invoice_type']??'invoice']??'Rechnung');?></td><td><a href="<?php p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$invoice['id']]));?>"><?php p(($invoice['status']??'draft')==='draft'?'Entwurf #'.$invoice['id']:$invoice['invoice_no']);?></a></td><td><?php p($invoice['status']??'');?></td><td><?php p(number_format((float)($invoice['gross_amount']??0),2,',','.').' €');?></td></tr><?php endforeach;?></tbody></table></div><?php else:?><p class="erp-muted">Zu diesem Auftrag gibt es noch keine Rechnungen.</p><?php endif;?>
<form id="abschlagsrechnung" method="get" action="<?php p($url->linkToRoute('reinhardterp.business.invoiceForm'));?>" class="erp-form-grid">
<input type="hidden" name="orderId" value="<?php p($o['id']);?>"><input type="hidden" name="invoiceType" value="advance">
<div><label>Berechnung</label><select name="installmentMode" id="erp-installment-mode"><option value="amount">Fester Betrag</option><option value="percent">Prozent vom Auftragswert</option></select></div>
<div id="erp-installment-amount"><label>Betrag netto</label><input type="number" name="installmentAmount" min="0.01" step="0.01" placeholder="z. B. 2500,00"></div>
<div id="erp-installment-percent" style="display:none"><label>Prozent</label><input type="number" name="installmentPercent" min="0.01" max="100" step="0.01" placeholder="z. B. 30"></div>
<div><label>&nbsp;</label><button class="button primary" type="submit">Abschlagsrechnung vorbereiten</button></div>
</form>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const m=document.getElementById('erp-installment-mode'),a=document.getElementById('erp-installment-amount'),p=document.getElementById('erp-installment-percent');
 if(!m)return;
 const toggle=()=>{const percent=m.value==='percent';p.style.display=percent?'':'none';a.style.display=percent?'none':'';};
 m.addEventListener('change',toggle);toggle();
});
</script></div>
<div class="erp-card"><h2>Zahlungseingänge zum Auftrag</h2><?php if(empty($_['orderPayments'])):?><p class="erp-muted">Noch keine Zahlungseingänge zu den Rechnungen dieses Auftrags.</p><?php else:?><div class="erp-table"><table><thead><tr><th>Datum</th><th>Rechnung</th><th>Betrag</th><th>Notiz</th></tr></thead><tbody><?php foreach($_['orderPayments'] as $pmt):?><tr><td><?php p(date('d.m.Y',strtotime((string)$pmt['payment_date'])));?></td><td><?php p($pmt['invoice_no']);?></td><td><strong><?php p(number_format((float)$pmt['amount'],2,',','.'));?> €</strong></td><td><?php p($pmt['note']??'');?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></div>
<div class="erp-card erp-order-notes">
	<h2>Notizen</h2>

	<form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveOrderNote',['id'=>$o['id']]));?>">
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">

		<label for="noteType">Art</label>
		<select id="noteType" name="noteType" required>
			<option value="note">Notiz</option>
			<option value="measurement">Aufmaß</option>
			<option value="meeting">Besprechung</option>
			<option value="phone">Telefonnotiz</option>
		</select>

		<label for="orderNoteContent">Notiz</label>
		<textarea
			id="orderNoteContent"
			name="content"
			rows="6"
			required
			placeholder="Notiz zum Auftrag eingeben"></textarea>

		<button class="button primary" type="submit">Notiz speichern</button>
	</form>

	<?php if (!empty($_['notes'])): ?>
		<div class="erp-order-note-list">
			<?php
			$noteLabels = [
				'measurement' => 'Aufmaß',
				'note' => 'Notiz',
				'meeting' => 'Besprechung',
				'phone' => 'Telefonnotiz',
			];
			foreach ($_['notes'] as $note):
			?>
				<div class="erp-order-note">
					<div class="erp-order-note-head">
						<strong><?php p($noteLabels[$note['note_type']] ?? 'Notiz'); ?></strong>
						<span>
							<?php p(date('d.m.Y H:i', strtotime($note['created_at']))); ?>
							<?php if (!empty($note['created_by'])): ?>
								· <?php p($note['created_by']); ?>
							<?php endif; ?>
						</span>
					</div>

					<div class="erp-order-note-content">
						<?php p($note['content']); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<p class="erp-muted">Für diesen Auftrag sind noch keine Notizen vorhanden.</p>
	<?php endif; ?>
</div>

</div></div>