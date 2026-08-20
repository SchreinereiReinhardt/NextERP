<?php require __DIR__.'/_mobile_pwa.php'; ?>
<?php $url=$_['urlGenerator'];$p=$_['project'];$c=$_['customer']; ?><style>
.erp-mob2{max-width:720px;margin:0 auto;padding:16px 14px 96px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#14213d}.erp-mob2 *{box-sizing:border-box}.erp-mob2 a{text-decoration:none}.erp-mob2-head{display:flex;align-items:center;gap:12px;margin-bottom:18px}.erp-mob2-back{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:#e8f3fa;color:#1265d8;font-size:26px}.erp-mob2-head h1{margin:0;font-size:23px;color:#0b1f55}.erp-mob2-head p{margin:3px 0 0;color:#6b7280;font-size:13px}.erp-mob2-card{border:1px solid #dfe5ec;border-radius:18px;background:#fff;padding:15px;margin:10px 0}.erp-mob2-projectno{font-size:12px;font-weight:800;color:#1265d8}.erp-mob2-card h2{margin:4px 0 7px;font-size:20px}.erp-mob2-muted{color:#6b7280;font-size:13px}.erp-mob2-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}.erp-mob2-action{min-height:92px;border:1px solid #dfe5ec;border-radius:17px;padding:14px;display:flex;flex-direction:column;justify-content:center;gap:5px;color:#14213d!important;background:#fff}.erp-mob2-action b{font-size:16px}.erp-mob2-action small{color:#6b7280}.erp-mob2-action.primary{background:#1265d8;color:#fff!important;border-color:#1265d8}.erp-mob2-action.primary small{color:#eaf3ff}.erp-mob2-form label{display:block;font-size:13px;font-weight:700;margin:13px 0 5px}.erp-mob2-form input,.erp-mob2-form select,.erp-mob2-form textarea{width:100%;min-height:46px;border:1px solid #cfd7e3;border-radius:12px;padding:10px 12px;background:#fff;color:#14213d;font:inherit}.erp-mob2-form textarea{min-height:100px;resize:vertical}.erp-mob2-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}.erp-mob2-save{width:100%;min-height:52px;border:0;border-radius:14px;background:#1265d8;color:#fff;font-size:16px;font-weight:800;margin-top:18px}.erp-mob2-note{font-size:12px;color:#6b7280;margin-top:8px}.erp-contact-actions{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:12px}.erp-contact-btn{min-height:48px;border-radius:13px;background:#e8f3fa;color:#1265d8!important;display:flex;align-items:center;justify-content:center;font-weight:800;padding:8px;text-align:center}.erp-contact-btn.nav{grid-column:1/-1;background:#1265d8;color:#fff!important}.erp-contact-line{margin-top:9px;font-size:14px;line-height:1.45}.erp-contact-label{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:800;margin-top:13px}@media(max-width:430px){.erp-mob2-grid,.erp-mob2-row{grid-template-columns:1fr 1fr}.erp-mob2{padding-left:12px;padding-right:12px}}
</style>
<div id="app-content"><main class="erp-mob2">
<header class="erp-mob2-head"><a class="erp-mob2-back" href="<?php p($url->linkToRoute('reinhardterp.business.mobile')); ?>">‹</a><div><h1>Projekt</h1><p>Mobile Projektakte</p></div></header>
<section class="erp-mob2-card"><span class="erp-mob2-projectno"><?php p($p['project_no']??'Projekt'); ?></span><h2><?php p($p['title']??''); ?></h2><div class="erp-mob2-muted"><?php p($c['name']??''); ?><?php if(!empty($p['status'])){p(' · '.$p['status']);} ?></div></section>
<div class="erp-project-quickbar"><a href="<?php p($url->linkToRoute('reinhardterp.business.mobileTime',['projectId'=>(int)$p['id']])); ?>">Zeit</a><a href="<?php p($url->linkToRoute('reinhardterp.page.mobileProjectPhotos',['id'=>(int)$p['id']])); ?>">Foto</a><a href="<?php p($url->linkToRoute('reinhardterp.business.mobileReports',['projectId'=>(int)$p['id']])); ?>">Rapport</a></div>
<div class="erp-mob2-grid">
<a class="erp-mob2-action primary" href="<?php p($url->linkToRoute('reinhardterp.business.mobileTime',['projectId'=>(int)$p['id']])); ?>"><b>Zeit erfassen</b><small>Direkt auf dieses Projekt</small></a>
<a class="erp-mob2-action" href="<?php p($url->linkToRoute('reinhardterp.business.mobileReports',['projectId'=>(int)$p['id']])); ?>"><b>Rapporte</b><small>Öffnen & unterschreiben</small></a>
<a class="erp-mob2-action" href="<?php p($url->linkToRoute('reinhardterp.business.mobileMaterial',['projectId'=>(int)$p['id']])); ?>"><b>Material</b><small>Material & Entnahme</small></a>
<a class="erp-mob2-action" href="<?php p($url->linkToRoute('reinhardterp.page.mobileProjectDocuments',['id'=>(int)$p['id']])); ?>"><b>Dokumente</b><small>Projektunterlagen</small></a>
<a class="erp-mob2-action" href="<?php p($url->linkToRoute('reinhardterp.page.mobileProjectPhotos',['id'=>(int)$p['id']])); ?>"><b>Fotos</b><small>Projektfotos öffnen</small></a>
<a class="erp-mob2-action" href="#kunde-baustelle"><b>Kunde & Baustelle</b><small>Kontakt & Navigation</small></a>
</div>
<?php if($c):
$phone=trim((string)($c['mobile']??''));if($phone==='')$phone=trim((string)($c['phone']??''));
$email=trim((string)($c['email']??''));
$street=trim((string)($c['street']??''));
$postal=trim((string)($c['postal_code']??''));
$city=trim((string)($c['city']??''));
$country=trim((string)($c['country']??''));
$address=trim(implode(', ',array_filter([$street,trim($postal.' '.$city),$country],static fn(string $v):bool=>$v!=='')));
$contact=trim((string)($c['contact_name']??''));
$notes=trim((string)($c['notes']??''));
?><section id="kunde-baustelle" class="erp-mob2-card">
<span class="erp-mob2-projectno">KUNDE & BAUSTELLE</span><h2><?php p($c['name']??''); ?></h2>
<?php if($contact!==''):?><div class="erp-contact-label">Ansprechpartner</div><div class="erp-contact-line"><?php p($contact);?></div><?php endif;?>
<?php if($address!==''):?><div class="erp-contact-label">Adresse</div><div class="erp-contact-line"><?php p($street);?><br><?php p(trim($postal.' '.$city));?><?php if($country!==''):?><br><?php p($country);?><?php endif;?></div><?php endif;?>
<?php if($phone!==''||$email!==''||$address!==''):?><div class="erp-contact-actions">
<?php if($phone!==''):?><a class="erp-contact-btn" href="tel:<?php p(preg_replace('/[^0-9+]/','',$phone));?>">Anrufen</a><?php endif;?>
<?php if($email!==''):?><a class="erp-contact-btn" href="mailto:<?php p($email);?>">E-Mail</a><?php endif;?>
<?php if($address!==''):?><a class="erp-contact-btn nav" href="https://www.google.com/maps/search/?api=1&amp;query=<?php p(rawurlencode($address));?>" target="_blank" rel="noopener">Navigation starten</a><?php endif;?>
</div><?php endif;?>
<?php if($notes!==''):?><div class="erp-contact-label">Hinweise</div><div class="erp-contact-line"><?php p($notes);?></div><?php endif;?>
</section><?php endif;?>

<section class="erp-mob2-card" id="projektnotizen">
	<span class="erp-mob2-projectno">NOTIZEN</span>
	<h2>Projekt- und Auftragsnotizen</h2>

	<form class="erp-mob2-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveMobileProjectNote',['id'=>(int)$p['id']])); ?>">
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">

		<label for="noteType">Art</label>
		<select id="noteType" name="noteType" required>
			<option value="note">Notiz</option>
			<option value="measurement">Aufmaß</option>
			<option value="meeting">Besprechung</option>
			<option value="phone">Telefonnotiz</option>
		</select>

		<label for="noteContent">Eintrag</label>
		<textarea
			id="noteContent"
			name="content"
			rows="6"
			required
			placeholder="Notiz eingeben"></textarea>

		<button class="erp-mob2-save" type="submit">Notiz speichern</button>
	</form>

	<?php
	$noteLabels = [
		'measurement' => 'Aufmaß',
		'note' => 'Notiz',
		'meeting' => 'Besprechung',
		'phone' => 'Telefonnotiz',
	];
	$projectNotes = $_['notes'] ?? [];
	?>

	<?php if (!empty($projectNotes)): ?>
		<div style="margin-top:18px">
			<?php foreach ($projectNotes as $note): ?>
				<div style="border-top:1px solid #e5e7eb;padding:13px 0">
					<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
						<strong style="font-size:14px">
							<?php p($noteLabels[$note['note_type']] ?? 'Notiz'); ?>
						</strong>
						<span class="erp-mob2-muted" style="font-size:11px;white-space:nowrap">
							<?php p(date('d.m.Y H:i', strtotime($note['created_at']))); ?>
						</span>
					</div>

					<?php if (!empty($note['created_by'])): ?>
						<div class="erp-mob2-muted" style="margin-top:3px">
							<?php p($note['created_by']); ?>
						</div>
					<?php endif; ?>

					<div style="margin-top:8px;white-space:pre-wrap;line-height:1.5;font-size:15px">
						<?php p($note['content']); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<div class="erp-mob2-note">Noch keine Notizen vorhanden.</div>
	<?php endif; ?>
</section>

<section class="erp-mob2-card"><a href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>(int)$p['id']])); ?>" style="color:#1265d8;font-weight:800">Vollständige Projektinformationen öffnen →</a></section>
</main></div>
<?php $mobileActive='projects'; $mobileProjectId=(int)$p['id']; require __DIR__.'/_mobile_nav.php'; ?>
