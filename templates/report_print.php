<?php
$report = $_['report'];
$project = $_['project'];
$customer = $_['customer'];
$hours = $_['hours'];
$items = $_['items'];
$photos = $_['photos'] ?? [];
$logoDataUri = $_['logoDataUri'] ?? null;
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
@page{size:A4;margin:15mm}*{box-sizing:border-box}body{font:14px Arial,sans-serif;max-width:920px;margin:20px auto;color:#222}header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #555;padding-bottom:15px}header img{max-width:230px;max-height:85px;object-fit:contain}h1,h2,h3{color:#222}h2{margin-top:24px}table{width:100%;border-collapse:collapse;margin:14px 0}th,td{border:1px solid #aaa;padding:8px;text-align:left;vertical-align:top}th{background:#eee}.num{text-align:right;white-space:nowrap}.meta{margin:18px 0;background:#f5f5f5;border:1px solid #ccc;padding:14px}.actions{margin-bottom:20px}.signature-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:36px}.signature-box{min-height:135px;border:1px solid #888;padding:12px;text-align:center}.signature-box img{display:block;max-width:100%;max-height:90px;margin:0 auto 8px;object-fit:contain}.signature-name{border-top:1px solid #aaa;padding-top:7px}.photo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.photo-grid figure{margin:0;break-inside:avoid;page-break-inside:avoid}.photo-grid img{width:100%;height:220px;object-fit:contain;border:1px solid #bbb;background:#fff}.photo-grid figcaption{font-size:10px;color:#555;margin-top:4px}.photo-section{page-break-before:auto}.photo-section h3{margin:16px 0 8px}.footer-note{margin-top:30px;font-size:11px;color:#666}@media print{body{margin:0;max-width:none}.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Drucken / als PDF speichern</button></div>
<header><div><h1>Rapport <?=p($report['report_no'])?></h1><b><?=p($report['title'])?></b></div><div><?php if($logoDataUri):?><img src="<?=p($logoDataUri)?>" alt="Firmenlogo"><?php else:?>Schreinerei Reinhardt<?php endif;?></div></header>
<div class="meta"><b>Kunde:</b> <?=p(($customer['customer_no']??'').' '.($customer['name']??''))?><br><b>Projekt:</b> <?=p(($project['project_no']??'').' '.($project['title']??''))?><br><b>Datum:</b> <?=p(date('d.m.Y',strtotime((string)$report['report_date'])))?><br><b>Status:</b> <?=p($report['status'])?></div>
<h2>Ausgeführte Arbeiten</h2><p><?=nl2br(p($report['description']??''))?></p>
<?php if(!empty($report['customer_note'])):?><h3>Hinweis für den Auftraggeber</h3><p><?=nl2br(p($report['customer_note']))?></p><?php endif;?>
<h2>Arbeitszeiten</h2><table><tr><th>Datum</th><th>Mitarbeiter</th><th class="num">Stunden</th><th>Tätigkeit</th></tr><?php if(empty($hours)):?><tr><td colspan="4">Keine Arbeitszeiten vorhanden.</td></tr><?php else:foreach($hours as $x):?><tr><td><?=p(!empty($x['work_date'])?date('d.m.Y',strtotime((string)$x['work_date'])):'')?></td><td><?=p($x['display_name']??$x['user_id'])?></td><td class="num"><?=p(number_format((float)$x['hours'],2,',','.'))?></td><td><?=nl2br(p($x['activity']))?></td></tr><?php endforeach;?><tr><th colspan="2">Gesamt</th><th class="num"><?=p(number_format($totalHours,2,',','.'))?></th><th></th></tr><?php endif;?></table>
<h2>Material</h2><table><tr><th>Beschreibung</th><th class="num">Menge</th><th>Einheit</th><th>Bemerkung</th></tr><?php if(empty($items)):?><tr><td colspan="4">Keine Materialeinträge vorhanden.</td></tr><?php else:foreach($items as $x):?><tr><td><?=p($x['description'])?></td><td class="num"><?=p(number_format((float)$x['quantity'],3,',','.'))?></td><td><?=p($x['unit'])?></td><td><?=p($x['notes']??'')?></td></tr><?php endforeach;endif;?></table>
<?php if($photoGroups):?><section class="photo-section"><h2>Fotodokumentation</h2><?php foreach($photoGroups as $category=>$group):?><h3><?=p($category)?></h3><div class="photo-grid"><?php foreach($group as $photo):?><figure><img src="<?=p($photo['dataUri'])?>" alt="<?=p($photo['name'])?>"><figcaption><?=p($photo['name'])?><?php if(!empty($photo['createdAt'])):?> · <?=p($photo['createdAt'])?><?php endif;?></figcaption></figure><?php endforeach;?></div><?php endforeach;?></section><?php endif;?>
<div class="signature-grid"><section><h3>Unterschrift Auftraggeber</h3><?php if(!empty($report['signature_data'])):?><div class="signature-box"><img src="<?=p($report['signature_data'])?>" alt="Unterschrift Auftraggeber"><div class="signature-name"><?=p($report['signed_by']??'')?></div></div><?php if(!empty($report['signed_at'])):?><p>Unterschrieben am <?=p(date('d.m.Y H:i',strtotime((string)$report['signed_at'])))?></p><?php endif;?><?php else:?><div class="signature-box"></div><?php endif;?></section><section><h3>Unterschrift Monteur</h3><?php if(!empty($report['technician_signature_data'])):?><div class="signature-box"><img src="<?=p($report['technician_signature_data'])?>" alt="Unterschrift Monteur"><div class="signature-name"><?=p($report['technician_signed_by']??'')?></div></div><?php if(!empty($report['technician_signed_at'])):?><p>Unterschrieben am <?=p(date('d.m.Y H:i',strtotime((string)$report['technician_signed_at'])))?></p><?php endif;?><?php else:?><div class="signature-box"></div><?php endif;?></section></div>
<p class="footer-note">Rapport <?=p($report['report_no'])?> · Erstellt mit NextERP</p>
</body>
</html>
