<?php require __DIR__.'/_nav.php';use OCP\IURLGenerator;$url=\OC::$server->get(IURLGenerator::class);$o=$_['order'];?>
<div id="app-content"><div class="erp-page"><div class="erp-record-head"><div><span class="erp-eyebrow">AUFTRAG</span><h1><?php p($o['order_no'].' · '.$o['title']);?></h1><p><?php p($o['customer_name'].($o['project_no']?' · '.$o['project_no'].' '.$o['project_title']:''));?></p></div><span class="erp-badge"><?php p($o['status']);?></span></div><div class="erp-grid-2"><div class="erp-card"><h2>Positionen</h2><div class="erp-table"><table><thead><tr><th>Pos.</th><th>Beschreibung</th><th>Menge</th><th>Gesamt</th></tr></thead><tbody><?php foreach($_['items'] as $i):?><tr><td><?php p($i['position_no']);?></td><td><?php p($i['description']);?></td><td><?php p(number_format((float)$i['quantity'],2,',','.').' '.$i['unit']);?></td><td><?php p(number_format((float)$i['total_price'],2,',','.').' €');?></td></tr><?php endforeach;?></tbody></table></div></div><div class="erp-card"><h2>Auftragssteuerung</h2><div class="erp-kpi-line erp-total"><span>Brutto</span><strong><?php p(number_format((float)$o['gross_amount'],2,',','.').' €');?></strong></div><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateOrderStatus',['id'=>$o['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><select name="status"><option value="open">Offen</option><option value="confirmed">Bestätigt</option><option value="production">Fertigung</option><option value="installation">Montage</option><option value="completed">Abgeschlossen</option><option value="cancelled">Storniert</option></select><button class="button primary">Status speichern</button></form></div></div>

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