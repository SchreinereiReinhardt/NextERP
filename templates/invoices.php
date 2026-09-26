<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
use OCP\Util;
$url=\OCP\Server::get(IURLGenerator::class);
Util::addScript('reinhardterp','invoices');
$statusLabels=['draft'=>'Entwurf','open'=>'Offen','paid'=>'Bezahlt','cancelled'=>'Storniert'];$typeLabels=['invoice'=>'Rechnung','advance'=>'Abschlag','final'=>'Schlussrechnung','credit'=>'Gutschrift'];
?>
<div id="app-content"><div class="erp-page erp-offers-v2">
 <?php if(!empty($_['datevError'])):?><div class="erp-card" style="margin-bottom:16px;padding:16px 18px;border-left:4px solid var(--color-warning);"><strong>DATEV-Export nicht möglich</strong><p style="margin:6px 0 0"><?php p($_['datevError']);?></p></div><?php endif;?>
 <div class="erp-head"><div><span class="erp-eyebrow">FINANZEN</span><h1>Rechnungen</h1><p class="erp-sub">Rechnungen und Entwürfe suchen, filtern und öffnen.</p></div><div class="erp-actions"><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.business.invoiceForm'));?>">+ Neue Rechnung</a></div></div>

 <div class="erp-card" style="margin-bottom:16px">
  <div class="erp-section-head"><div><h2>DATEV Export</h2><p class="erp-muted">Finalisierte Ausgangsrechnungen und Gutschriften als Buchungsdaten-CSV für die Übergabe an die Buchhaltung. Kontierung vor dem ersten Produktiveinsatz mit dem Steuerbüro abstimmen.</p></div></div>
  <form method="get" action="<?php p($url->linkToRoute('reinhardterp.business.datevExport'));?>" class="erp-form-grid" style="padding:0 18px 18px">
   <div><label>Von</label><input type="date" name="from" value="<?php p(date('Y-m-01'));?>" required></div>
   <div><label>Bis</label><input type="date" name="to" value="<?php p(date('Y-m-t'));?>" required></div>
   <div><label>Kontenrahmen</label><input value="SKR <?php p($_['datevSettings']['skr']??'03');?>" disabled></div>
   <div><label>&nbsp;</label><button class="button primary" type="submit">DATEV CSV herunterladen</button></div>
  </form>
 </div>
 <div class="erp-card erp-document-list-card" id="invoiceOverview"><div class="erp-section-head"><div><h2>Rechnungsübersicht</h2><p class="erp-muted"><span id="invoiceVisibleCount"><?php p(count($_['invoices']));?></span> von <?php p(count($_['invoices']));?> Rechnungen / Entwürfen</p></div></div>
  <div class="erp-document-filterbar">
   <div class="erp-document-search"><span aria-hidden="true">⌕</span><input type="search" id="invoiceSearch" placeholder="Rechnung, Kunde oder Auftrag suchen …" autocomplete="off"></div>
   <select id="invoiceYear" aria-label="Jahr"><option value="">Alle Jahre</option><?php $invoiceYears=[];foreach($_['invoices'] as $row){$y=substr((string)($row['invoice_date']??''),0,4);if($y!=='')$invoiceYears[$y]=true;}krsort($invoiceYears);foreach(array_keys($invoiceYears) as $y):?><option value="<?php p($y);?>"><?php p($y);?></option><?php endforeach;?></select>
   <select id="invoiceMonth" aria-label="Monat"><option value="">Alle Monate</option><?php foreach([1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'] as $m=>$label):?><option value="<?php p(str_pad((string)$m,2,'0',STR_PAD_LEFT));?>"><?php p($label);?></option><?php endforeach;?></select>
   <button type="button" class="button" id="invoiceShowAll">Alle anzeigen</button>
  </div>
  <div class="erp-table"><table><thead><tr><th>Art</th><th>Nummer</th><th>Datum</th><th>Kunde</th><th>Auftrag</th><th>Status</th><th>Fällig</th><th>Brutto</th><th>Aktionen</th></tr></thead><tbody id="invoiceTableBody">
  <?php foreach($_['invoices'] as $i): $invoiceDate=(string)($i['invoice_date']??''); $invoiceSearch=strtolower(trim(($i['invoice_no']??'').' '.($i['customer_name']??'').' '.($i['order_no']??'').' '.($i['status']??'').' '.($typeLabels[$i['invoice_type']??'invoice']??'Rechnung')));?><tr data-doc-row data-year="<?php p(substr($invoiceDate,0,4));?>" data-month="<?php p(substr($invoiceDate,5,2));?>" data-search="<?php p($invoiceSearch);?>">
   <td><?php p($typeLabels[$i['invoice_type']??'invoice']??'Rechnung');?></td>
   <td><a href="<?php p($url->linkToRoute('reinhardterp.business.invoiceDetail',['id'=>$i['id']]));?>"><strong><?php p($i['status']==='draft'?'Entwurf #'.$i['id']:$i['invoice_no']);?></strong></a></td>
   <td><?php p(date('d.m.Y',strtotime($invoiceDate)));?></td><td><?php p($i['customer_name']??'');?></td><td><?php p($i['order_no']??'—');?></td>
   <td>
    <?php if(($i['status']??'')==='draft'):?>
     <span class="erp-badge">Entwurf</span>
    <?php elseif(($i['status']??'')==='cancelled'):?>
     <span class="erp-badge erp-status-cancelled">Storniert</span>
    <?php else:?>
     <form method="post" class="erp-inline-status-form" action="<?php p($url->linkToRoute('reinhardterp.business.updateInvoiceStatus',['id'=>$i['id']]));?>">
      <input type="hidden" name="requesttoken" value="<?php p(\OCP\Util::callRegister());?>">
      <select name="status" aria-label="Rechnungsstatus" onchange="this.form.submit()">
       <option value="open" <?php if(($i['status']??'')==='open') print_unescaped('selected');?>>Offen</option>
       <option value="paid" <?php if(($i['status']??'')==='paid') print_unescaped('selected');?>>Bezahlt</option>
      </select>
     </form>
    <?php endif;?>
   </td>
   <td><?php p(!empty($i['due_date'])?date('d.m.Y',strtotime((string)$i['due_date'])):'—');?></td>
   <td><strong><?php p(number_format((float)$i['gross_amount'],2,',','.').' €');?></strong></td>
   <td class="erp-list-actions">
    <?php if(($i['status']??'')==='draft'):?>
     <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.deleteInvoice',['id'=>$i['id']]));?>" onsubmit="return confirm('Diesen Rechnungsentwurf wirklich endgültig löschen?');"><input type="hidden" name="requesttoken" value="<?php p(\OCP\Util::callRegister());?>"><button type="submit" class="button erp-action-delete">Löschen</button></form>
    <?php elseif(($i['status']??'')!=='cancelled' && ($i['invoice_type']??'invoice')!=='credit'):?>
     <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.updateInvoiceStatus',['id'=>$i['id']]));?>" onsubmit="return confirm('Rechnung wirklich stornieren?\n\nBetrio erstellt automatisch einen eigenen festgeschriebenen Gegenbeleg mit eigener Rechnungsnummer. Die Ursprungsrechnung bleibt erhalten und wird als storniert markiert. Diese Aktion kann nicht rückgängig gemacht werden.');"><input type="hidden" name="requesttoken" value="<?php p(\OCP\Util::callRegister());?>"><input type="hidden" name="status" value="cancelled"><button type="submit" class="button erp-action-cancel">Storno</button></form>
    <?php elseif(($i['invoice_type']??'invoice')==='credit'):?><span class="erp-muted">Gegenbeleg</span><?php else:?><span class="erp-muted">Storniert</span><?php endif;?>
   </td>
  </tr><?php endforeach;?>
  <?php if($_['invoices']===[]):?><tr><td colspan="9" class="erp-empty">Noch keine Rechnungen vorhanden.</td></tr><?php endif;?>
  <tr id="invoiceNoResults" hidden><td colspan="9" class="erp-empty">Keine passenden Rechnungen gefunden.</td></tr>
  </tbody></table></div>
 </div>

</div></div>
