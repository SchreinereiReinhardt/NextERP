<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title><?=p($report['report_no'])?></title>
<style>
body{font:15px Arial,sans-serif;max-width:920px;margin:30px auto;color:#222}
header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #555;padding-bottom:15px}
header img{max-width:230px;max-height:85px;object-fit:contain}
h1,h2,h3{color:#222}
table{width:100%;border-collapse:collapse;margin:18px 0}
th,td{border:1px solid #aaa;padding:9px;text-align:left}th{background:#eee}
.meta{margin:20px 0;background:#f5f5f5;border:1px solid #ccc;padding:15px}
.actions{margin-bottom:20px}.signature-box{width:360px;min-height:125px;border:1px solid #888;padding:12px;text-align:center;margin-top:12px}.signature-box img{display:block;max-width:100%;max-height:90px;margin:0 auto 8px;object-fit:contain}.signature-name{border-top:1px solid #aaa;padding-top:7px}
@media print{.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Drucken / als PDF speichern</button></div>
<header><div><h1>Rapport <?=p($report['report_no'])?></h1><b><?=p($report['title'])?></b></div><div><?php if(!empty($logoDataUri)):?><img src="<?=p($logoDataUri)?>" alt="Firmenlogo"><?php else:?>Schreinerei Reinhardt<?php endif;?></div></header>
<div class="meta"><b>Kunde:</b> <?=p(($customer['customer_no']??'').' '.($customer['name']??''))?><br><b>Projekt:</b> <?=p(($project['project_no']??'').' '.($project['title']??''))?><br><b>Datum:</b> <?=p($report['report_date'])?><br><b>Status:</b> <?=p($report['status'])?></div>
<h2>Ausgeführte Arbeiten</h2><p><?=nl2br(p($report['description']??''))?></p>
<?php if(!empty($report['customer_note'])):?><h3>Hinweis für den Auftraggeber</h3><p><?=nl2br(p($report['customer_note']))?></p><?php endif;?>
<h2>Arbeitszeiten</h2><table><tr><th>Mitarbeiter</th><th>Stunden</th><th>Tätigkeit</th></tr><?php if(empty($hours)):?><tr><td colspan="3">Keine Arbeitszeiten vorhanden.</td></tr><?php else:foreach($hours as $x):?><tr><td><?=p($x['user_id'])?></td><td><?=p($x['hours'])?></td><td><?=p($x['activity'])?></td></tr><?php endforeach;endif;?></table>
<h2>Material</h2><table><tr><th>Beschreibung</th><th>Menge</th><th>Einheit</th></tr><?php if(empty($items)):?><tr><td colspan="3">Keine Materialeinträge vorhanden.</td></tr><?php else:foreach($items as $x):?><tr><td><?=p($x['description'])?></td><td><?=p($x['quantity'])?></td><td><?=p($x['unit'])?></td></tr><?php endforeach;endif;?></table>
<section style="margin-top:45px"><h3>Unterschrift Auftraggeber</h3>
<?php if(!empty($report['signature_data'])):?><div class="signature-box"><img src="<?=p($report['signature_data'])?>" alt="Unterschrift Auftraggeber"><div class="signature-name"><?=p($report['signed_by']??'')?></div></div><?php if(!empty($report['signed_at'])):?><p>Unterschrieben am <?=p($report['signed_at'])?></p><?php endif;?><?php else:?><p>Noch nicht unterschrieben.</p><?php endif;?>
</section>
</body></html>
