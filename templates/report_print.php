<?php
$report = $_['report'];
$project = $_['project'];
$customer = $_['customer'];
$hours = $_['hours'];
$items = $_['items'];
$photos = $_['photos'] ?? [];
$logoDataUri = $_['logoDataUri'] ?? null;
$company = $_['company'] ?? [];
$pdfUrl = $_['pdfUrl'] ?? '#';
$totalHours = array_sum(array_map(static fn(array $row): float => (float)$row['hours'], $hours));
$photoLabels = ['before'=>'Vorher','installation'=>'Montage','after'=>'Nachher','damage'=>'Schaden','acceptance'=>'Abnahme','other'=>'Sonstige'];
$photoGroups = [];
foreach ($photos as $photo) {
    $key = strtolower((string)($photo['category'] ?? 'other'));
    $label = $photoLabels[$key] ?? ((string)($photo['category'] ?? 'Sonstige'));
    $photoGroups[$label][] = $photo;
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title><?=p($report['report_no'])?></title>
<style>
@page{size:A4;margin:14mm 15mm 16mm}*{box-sizing:border-box}body{font:13px/1.45 Arial,sans-serif;max-width:900px;margin:18px auto;color:#20252b;background:#fff}.actions{display:flex;gap:8px;margin-bottom:8px}.actions a,.actions button{display:inline-block;border:0;border-radius:8px;padding:10px 16px;background:#1265d8;color:#fff!important;font-weight:700;text-decoration:none;cursor:pointer}.doc-header{padding-bottom:18px;border-bottom:3px solid #1265d8}.brand{text-align:center;margin-top:46px;margin-bottom:18px}.brand img{display:block;max-width:300px;max-height:86px;object-fit:contain;margin:0 auto}.brand-name{font-size:17px;font-weight:700}.header-details{display:grid;grid-template-columns:1fr auto;gap:28px;align-items:start}.brand-address{text-align:left;color:#68717c;font-size:11px;line-height:1.5}.doc-title{text-align:right}.doc-title .eyebrow{font-size:10px;text-transform:uppercase;letter-spacing:1.6px;color:#68717c;font-weight:700}.doc-title h1{font-size:27px;line-height:1.1;margin:5px 0 4px;color:#17202a}.doc-title .number{font-size:13px;color:#1265d8;font-weight:700}.meta-grid{display:grid;grid-template-columns:1.25fr 1fr;gap:12px;margin:20px 0}.meta-card{background:#f6f8fa;border-radius:10px;padding:13px 15px;border:1px solid #e5e9ed}.meta-label{font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#76808b;font-weight:700;margin-bottom:3px}.meta-value{font-weight:700}.section{margin-top:22px}.section-title{font-size:14px;font-weight:700;margin:0 0 9px;padding-bottom:6px;border-bottom:1px solid #dfe4e8;color:#17202a}.work-description{white-space:normal;background:#fbfcfd;border-left:4px solid #1265d8;border-radius:0 8px 8px 0;padding:12px 14px}.note{margin-top:10px;background:#fff8e8;border-radius:8px;padding:10px 12px}.modern-table{width:100%;border-collapse:separate;border-spacing:0;margin:8px 0;border:1px solid #e1e5e9;border-radius:9px;overflow:hidden}.modern-table th{background:#f3f5f7;color:#56606b;font-size:9px;text-transform:uppercase;letter-spacing:.5px;padding:8px;border:0;border-bottom:1px solid #e1e5e9;text-align:left}.modern-table td{padding:8px;border:0;border-bottom:1px solid #edf0f2;vertical-align:top}.modern-table tr:last-child td{border-bottom:0}.modern-table .num{text-align:right;white-space:nowrap}.total-row td{font-weight:700;background:#fafbfc}.signature-wrap{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:28px}.signature{min-height:105px;padding-top:10px;border-top:1px solid #aeb6bf}.signature img{display:block;max-width:170px;max-height:65px;object-fit:contain;margin-bottom:6px}.signature small{color:#737d87}.photo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.photo-grid figure{margin:0;break-inside:avoid}.photo-grid img{width:100%;height:210px;object-fit:contain;border-radius:8px;border:1px solid #e1e5e9}.photo-grid figcaption{font-size:10px;color:#68717c;margin-top:4px}.footer-note{margin-top:28px;padding-top:10px;border-top:1px solid #e5e9ed;font-size:9px;color:#7b858f;text-align:center}@media print{body{margin:0;max-width:none}.actions{display:none}.doc-header{border-bottom-color:#555}.work-description{border-left-color:#555}}
</style>
</head>
<body>
<div class="actions">
<a class="erp-pdf-button" href="<?php p($pdfUrl); ?>">PDF herunterladen</a>
</div>
<header class="doc-header">
    <div class="brand">
        <?php if($logoDataUri):?><img src="<?=p($logoDataUri)?>" alt="Firmenlogo"><?php else:?><div class="brand-name"><?=p($company['name'] ?: 'Betrio')?></div><?php endif;?>
    </div>
    <div class="header-details">
        <div class="brand-address">
            <?php if(!empty($company['name'])):?><strong><?=p($company['name'])?></strong><br><?php endif;?>
            <?=p($company['street'] ?? '')?><?php if(!empty($company['street'])):?><br><?php endif;?>
            <?=p(trim(($company['zip'] ?? '').' '.($company['city'] ?? '')))?><?php if(!empty($company['country'])):?><br><?=p($company['country'])?><?php endif;?>
            <?php if(!empty($company['phone'])):?><br>Tel. <?=p($company['phone'])?><?php endif;?><?php if(!empty($company['email'])):?><br><?=p($company['email'])?><?php endif;?>
        </div>
        <div class="doc-title"><div class="eyebrow">Leistungsnachweis</div><h1>Rapport</h1><div class="number"><?=p($report['report_no'])?></div></div>
    </div>
</header>
<div class="meta-grid">
    <div class="meta-card"><div class="meta-label">Kunde</div><div class="meta-value"><?=p(($customer['customer_no']??'').' '.($customer['name']??''))?></div></div>
    <div class="meta-card"><div class="meta-label">Datum</div><div class="meta-value"><?=p(date('d.m.Y',strtotime((string)$report['report_date'])))?></div></div>
    <div class="meta-card"><div class="meta-label">Projekt</div><div class="meta-value"><?=p(($project['project_no']??'').' '.($project['title']??''))?></div></div>
    <div class="meta-card"><div class="meta-label">Status</div><div class="meta-value"><?=p($report['status'])?></div></div>
</div>
<section class="section"><h2 class="section-title"><?=p($report['title'])?></h2><div class="work-description"><?=nl2br(p($report['description']??''))?></div>
<?php if(!empty($report['customer_note'])):?><div class="note"><strong>Hinweis für den Auftraggeber</strong><br><?=nl2br(p($report['customer_note']))?></div><?php endif;?></section>
<section class="section"><h2 class="section-title">Arbeitszeiten</h2><table class="modern-table"><tr><th>Datum</th><th>Mitarbeiter</th><th class="num">Stunden</th><th>Tätigkeit</th></tr><?php if(empty($hours)):?><tr><td colspan="4">Keine Arbeitszeiten vorhanden.</td></tr><?php else:foreach($hours as $x):?><tr><td><?=p(!empty($x['work_date'])?date('d.m.Y',strtotime((string)$x['work_date'])):'')?></td><td><?=p($x['display_name']??$x['user_id'])?></td><td class="num"><?=p(number_format((float)$x['hours'],2,',','.'))?></td><td><?=nl2br(p($x['activity']))?></td></tr><?php endforeach;?><tr class="total-row"><td colspan="2">Gesamt</td><td class="num"><?=p(number_format($totalHours,2,',','.'))?></td><td></td></tr><?php endif;?></table></section>
<section class="section"><h2 class="section-title">Material</h2><table class="modern-table"><tr><th>Beschreibung</th><th class="num">Menge</th><th>Einheit</th><th>Bemerkung</th></tr><?php if(empty($items)):?><tr><td colspan="4">Keine Materialeinträge vorhanden.</td></tr><?php else:foreach($items as $x):?><tr><td><?=p($x['description'])?></td><td class="num"><?=p(number_format((float)$x['quantity'],3,',','.'))?></td><td><?=p($x['unit'])?></td><td><?=p($x['notes']??'')?></td></tr><?php endforeach;endif;?></table></section>
<?php if($photoGroups):?><section class="photo-section"><h2>Fotodokumentation</h2><?php foreach($photoGroups as $category=>$group):?><h3><?=p($category)?></h3><div class="photo-grid"><?php foreach($group as $photo):?><figure><img src="<?=p($photo['dataUri'])?>" alt="<?=p($photo['name'])?>"><figcaption><?=p($photo['name'])?><?php if(!empty($photo['createdAt'])):?> · <?=p($photo['createdAt'])?><?php endif;?></figcaption></figure><?php endforeach;?></div><?php endforeach;?></section><?php endif;?>
<div class="signature-wrap"><section><h3>Unterschrift Auftraggeber</h3><?php if(!empty($report['signature_data'])):?><div class="signature"><img src="<?=p($report['signature_data'])?>" alt="Unterschrift Auftraggeber"><div class="signature-name"><?=p($report['signed_by']??'')?></div></div><?php if(!empty($report['signed_at'])):?><p>Unterschrieben am <?=p(date('d.m.Y H:i',strtotime((string)$report['signed_at'])))?></p><?php endif;?><?php else:?><div class="signature"></div><?php endif;?></section><section><h3>Unterschrift Monteur</h3><?php if(!empty($report['technician_signature_data'])):?><div class="signature"><img src="<?=p($report['technician_signature_data'])?>" alt="Unterschrift Monteur"><div class="signature-name"><?=p($report['technician_signed_by']??'')?></div></div><?php if(!empty($report['technician_signed_at'])):?><p>Unterschrieben am <?=p(date('d.m.Y H:i',strtotime((string)$report['technician_signed_at'])))?></p><?php endif;?><?php else:?><div class="signature"></div><?php endif;?></section></div>
<p class="footer-note">Rapport <?=p($report['report_no'])?> · Erstellt mit Betrio</p>
</body>
</html>
