<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url=\OC::$server->get(IURLGenerator::class);
$materials=$_['materials']??[];
?>
<div id="app-content"><div class="erp-page erp-inventory-page">
<div class="erp-page-head"><div><h1>Inventur-Assistent</h1><p class="erp-sub">Artikel nacheinander zählen. Nur Abweichungen werden beim Abschluss als Bestandskorrektur gebucht.</p></div><div class="erp-head-actions"><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.inventory'));?>">Zurück zum Lager</a></div></div>
<?php if(!$materials):?><div class="erp-empty">Im Materialstamm sind noch keine Artikel vorhanden.</div><?php else:?>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveInventoryCount'));?>" id="inventory-count-form">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>">
<div class="erp-info-banner"><strong><?php p(count($materials));?> Artikel</strong> · Gezählt wird direkt gegen den aktuellen Sollbestand. Leere Felder bleiben unverändert.</div>
<div class="erp-inventory-toolbar"><div class="erp-inventory-search-wrap"><input id="count-search" type="search" placeholder="Artikel, Bezeichnung oder Lagerort suchen"></div><span class="erp-inventory-count" id="count-progress">0 / <?php p(count($materials));?> erfasst</span></div>
<div class="erp-table"><table><thead><tr><th>Artikel</th><th>Bezeichnung</th><th>Lagerort</th><th>Soll</th><th>Einheit</th><th>Gezählt</th><th>Abweichung</th></tr></thead><tbody>
<?php foreach($materials as $i=>$m):$stock=(float)($m['stock_quantity']??0);$search=mb_strtolower(implode(' ',[$m['article_no']??'',$m['name']??'',$m['storage_location']??'']));?>
<tr class="inventory-count-row" data-search="<?php p($search);?>" data-soll="<?php p(number_format($stock,3,'.',''));?>"><td><input type="hidden" name="materialIds[]" value="<?php p($m['id']);?>"><strong><?php p($m['article_no']??'');?></strong></td><td><?php p($m['name']??'');?></td><td><?php p($m['storage_location']??'—');?></td><td><?php p(number_format($stock,3,',','.'));?></td><td><?php p($m['unit']??'');?></td><td><input class="inventory-count-input" type="number" min="0" step="0.001" name="countedQuantities[]" inputmode="decimal" placeholder="Ist"></td><td class="inventory-count-diff">—</td></tr>
<?php endforeach;?></tbody></table></div>
<div class="erp-head-actions" style="margin-top:18px"><button class="button primary" type="submit" onclick="return confirm('Inventur abschließen und abweichende Lagerbestände korrigieren?')">Inventur abschließen</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.inventory'));?>">Abbrechen</a></div>
</form>
<script>
(()=>{const rows=[...document.querySelectorAll('.inventory-count-row')], inputs=[...document.querySelectorAll('.inventory-count-input')], progress=document.getElementById('count-progress'), search=document.getElementById('count-search');const sync=()=>{let n=0;rows.forEach(r=>{const i=r.querySelector('.inventory-count-input'),d=r.querySelector('.inventory-count-diff');if(i.value!==''){n++;const diff=parseFloat(i.value)-parseFloat(r.dataset.soll||'0');d.textContent=(diff>0?'+':'')+diff.toFixed(3).replace('.',',');d.style.fontWeight=Math.abs(diff)>0.0005?'700':'';}else{d.textContent='—';d.style.fontWeight='';}});progress.textContent=`${n} / ${rows.length} erfasst`;};inputs.forEach(i=>i.addEventListener('input',sync));search?.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();rows.forEach(r=>r.hidden=!!q&&!r.dataset.search.includes(q));});sync();})();
</script>
<?php endif;?></div></div>
