<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
use OCP\Util;
$url=\OCP\Server::get(IURLGenerator::class);
Util::addScript('reinhardterp','offers');
?>
<div id="app-content"><div class="erp-page erp-offers-v2 erp-sales-premium">
 <div class="erp-head"><div><span class="erp-eyebrow">VERKAUF</span><h1>Angebote</h1><p class="erp-sub">Angebote suchen, filtern und öffnen.</p></div><div class="erp-actions"><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.business.offerForm'));?>">+ Neues Angebot</a></div></div>
 <div class="erp-card erp-offer-list-card erp-document-list-card" id="offerOverview"><div class="erp-section-head"><div><h2>Angebotsübersicht</h2><p class="erp-muted"><span id="offerVisibleCount"><?php p(count($_['offers']));?></span> von <?php p(count($_['offers']));?> Angeboten</p></div></div>
  <div class="erp-document-filterbar">
   <div class="erp-document-search"><span aria-hidden="true">⌕</span><input type="search" id="offerSearch" placeholder="Angebot, Kunde oder Titel suchen …" autocomplete="off"></div>
   <select id="offerYear" aria-label="Jahr"><option value="">Alle Jahre</option><?php $offerYears=[];foreach($_['offers'] as $row){$y=substr((string)($row['offer_date']??''),0,4);if($y!=='')$offerYears[$y]=true;}krsort($offerYears);foreach(array_keys($offerYears) as $y):?><option value="<?php p($y);?>"><?php p($y);?></option><?php endforeach;?></select>
   <select id="offerMonth" aria-label="Monat"><option value="">Alle Monate</option><?php foreach([1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'] as $m=>$label):?><option value="<?php p(str_pad((string)$m,2,'0',STR_PAD_LEFT));?>"><?php p($label);?></option><?php endforeach;?></select>
   <button type="button" class="button" id="offerShowAll">Alle anzeigen</button>
  </div>
  <div class="erp-table"><table><thead><tr><th>Nummer</th><th>Datum</th><th>Kunde</th><th>Titel</th><th>Status</th><th>Netto</th><th>Brutto</th><th>Aktionen</th></tr></thead><tbody id="offerTableBody">
  <?php foreach($_['offers'] as $o): $offerDate=(string)($o['offer_date']??''); $offerSearch=strtolower(trim(($o['offer_no']??'').' '.($o['customer_name']??'').' '.($o['title']??'').' '.($o['status']??'')));?><tr data-doc-row data-year="<?php p(substr($offerDate,0,4));?>" data-month="<?php p(substr($offerDate,5,2));?>" data-search="<?php p($offerSearch);?>"><td><a href="<?php p($url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$o['id']]));?>"><strong><?php p($o['offer_no']);?></strong></a></td><td><?php p(date('d.m.Y',strtotime($offerDate)));?></td><td><?php p($o['customer_name']);?></td><td><?php p($o['title']);?></td><td><span class="erp-badge"><?php p($o['status']);?></span></td><td><?php p(number_format((float)$o['net_amount'],2,',','.').' €');?></td><td><strong><?php p(number_format((float)$o['gross_amount'],2,',','.').' €');?></strong></td><td class="erp-list-actions"><form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.deleteOffer',['id'=>$o['id']]));?>" onsubmit="return confirm('Dieses Angebot wirklich endgültig löschen?');"><input type="hidden" name="requesttoken" value="<?php p(\OCP\Util::callRegister());?>"><button type="submit" class="button erp-action-delete">Löschen</button></form></td></tr><?php endforeach;?>
  <?php if($_['offers']===[]):?><tr><td colspan="8" class="erp-empty">Noch keine Angebote vorhanden.</td></tr><?php endif;?>
  <tr id="offerNoResults" hidden><td colspan="8" class="erp-empty">Keine passenden Angebote gefunden.</td></tr>
  </tbody></table></div>
 </div>

</div></div>
