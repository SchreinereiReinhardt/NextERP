<?php require __DIR__.'/_nav.php'; use OCP\IURLGenerator; $url=\OC::$server->get(IURLGenerator::class); script('reinhardterp','material_catalog'); ?>
<div id="app-content"><div class="erp-page erp-material-page">
<div class="erp-page-head"><div><h1>Materialstamm</h1><p class="erp-sub">Material, Preise, Lagerorte, Gruppen und Lieferanten zentral verwalten.</p></div><a class="button" href="<?php p($url->linkToRoute('reinhardterp.business.inventory')); ?>">Lagerübersicht öffnen</a></div>
<div class="erp-material-summary">
 <div class="erp-stat"><span>Materialien</span><strong><?php p(count($_['rows'])); ?></strong></div>
 <div class="erp-stat"><span>Gruppen</span><strong><?php p(count($_['groups'])); ?></strong></div>
 <div class="erp-stat"><span>Lieferanten</span><strong><?php p(count($_['suppliers'])); ?></strong></div>
 <div class="erp-stat"><span>Unter Mindestbestand</span><strong><?php p(count(array_filter($_['rows'],fn($r)=>(float)($r['stock_quantity']??0)<=(float)($r['min_stock']??0)))); ?></strong></div>
</div>
<details class="erp-form-card" open><summary><strong>Neues Material</strong></summary>
<form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveMaterial')); ?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<div class="erp-form-grid erp-material-form-grid">
 <div><label>Artikelnummer</label><input name="articleNo" placeholder="automatisch"></div>
 <div class="erp-span-2"><label>Bezeichnung *</label><input name="name" required></div>
 <div><label>Materialgruppe</label><select name="materialGroupId"><option value="">keine Gruppe</option><?php foreach($_['groups'] as $g): ?><option value="<?php p($g['id']); ?>"><?php p($g['name']); ?></option><?php endforeach; ?></select></div>
 <div><label>Einheit</label><input name="unit" placeholder="Stk, m, m², kg"></div>
 <div><label>Lieferant</label><select name="supplierId"><option value="">kein Lieferant</option><?php foreach($_['suppliers'] as $s): ?><option value="<?php p($s['id']); ?>"><?php p($s['name']); ?></option><?php endforeach; ?></select></div>
 <div><label>Lagerort</label><input name="storageLocation" placeholder="Regal A / Fach 3"></div>
 <div><label>Barcode / EAN</label><input name="barcode"></div>
 <div><label>EK netto</label><input type="number" step="0.01" min="0" name="purchasePrice"></div>
 <div><label>VK netto</label><input type="number" step="0.01" min="0" name="salePrice"></div>
 <div><label>Startbestand</label><input type="number" step="0.001" min="0" name="stockQuantity"></div>
 <div><label>Mindestbestand</label><input type="number" step="0.001" min="0" name="minStock"></div>
 <div><label>Bestellmenge</label><input type="number" step="0.001" min="0" name="reorderQuantity"></div>
</div><button class="button primary">Material speichern</button></form></details>
<div class="erp-two-column erp-masterdata-grid">
<details class="erp-form-card"><summary><strong>Materialgruppe anlegen</strong></summary><form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveMaterialGroup')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><label>Name</label><input name="name" required><label>Beschreibung</label><input name="description"><button class="button primary">Gruppe speichern</button></form><div class="erp-chip-list"><?php foreach($_['groups'] as $g): ?><span class="erp-chip"><?php p($g['name']); ?></span><?php endforeach; ?></div></details>
<details class="erp-form-card"><summary><strong>Lieferant anlegen</strong></summary><form method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveSupplier')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><div class="erp-form-grid"><div><label>Name *</label><input name="name" required></div><div><label>Ansprechpartner</label><input name="contactPerson"></div><div><label>E-Mail</label><input type="email" name="email"></div><div><label>Telefon</label><input name="phone"></div><div><label>Kundennummer beim Lieferanten</label><input name="customerNo"></div><div><label>Notiz</label><input name="notes"></div></div><button class="button primary">Lieferant speichern</button></form></details>
</div>
<div class="erp-toolbar"><input id="material-search" type="search" placeholder="Material suchen: Artikel, Bezeichnung, Barcode, Lieferant …"><select id="material-group-filter"><option value="">Alle Gruppen</option><?php foreach($_['groups'] as $g): ?><option value="<?php p(mb_strtolower($g['name'])); ?>"><?php p($g['name']); ?></option><?php endforeach; ?></select><select id="material-stock-filter"><option value="">Alle Bestände</option><option value="low">Mindestbestand</option><option value="out">Nicht auf Lager</option></select><span id="material-result-count"><?php p(count($_['rows'])); ?> Treffer</span></div>
<div class="erp-table erp-material-table"><table><thead><tr><th>Artikel</th><th>Bezeichnung</th><th>Gruppe</th><th>Einheit</th><th>EK</th><th>VK</th><th>Bestand</th><th>Minimum</th><th>Lagerort</th><th>Lieferant</th></tr></thead><tbody id="material-table-body"><?php foreach($_['rows'] as $r): $stock=(float)($r['stock_quantity']??0);$min=(float)($r['min_stock']??0);$state=$stock<=0?'out':($stock<=$min?'low':'ok');$search=mb_strtolower(implode(' ',[$r['article_no']??'',$r['name']??'',$r['group_name']??$r['material_group']??'',$r['supplier_name']??$r['supplier']??'',$r['barcode']??'',$r['storage_location']??''])); ?><tr data-search="<?php p($search); ?>" data-group="<?php p(mb_strtolower((string)($r['group_name']??$r['material_group']??''))); ?>" data-stock="<?php p($state); ?>"><td><strong><?php p($r['article_no']); ?></strong><?php if(!empty($r['barcode'])):?><small><?php p($r['barcode']); ?></small><?php endif;?></td><td><?php p($r['name']); ?></td><td><?php p($r['group_name']??$r['material_group']??'—'); ?></td><td><?php p($r['unit']?:'—'); ?></td><td><?php p(number_format((float)($r['purchase_price']??$r['price']),2,',','.').' €'); ?></td><td><?php p(number_format((float)($r['sale_price']??$r['price']),2,',','.').' €'); ?></td><td><span class="erp-stock-badge erp-stock-<?php p($state); ?>"><?php p(number_format($stock,3,',','.').' '.($r['unit']??'')); ?></span></td><td><?php p(number_format($min,3,',','.')); ?></td><td><?php p($r['storage_location']??'—'); ?></td><td><?php p($r['supplier_name']??$r['supplier']??'—'); ?></td></tr><?php endforeach; ?></tbody></table><div id="material-empty" class="erp-empty" hidden>Keine Materialien gefunden.</div></div>
</div></div>
