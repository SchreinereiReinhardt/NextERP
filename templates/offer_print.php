<?php
require __DIR__.'/_document_master.php';
$o=$_['offer'];$items=$_['items']??[];$project=$_['project']??null;
$intro=trim((string)($o['intro']??'')) ?: 'Sehr geehrte Damen und Herren, wir freuen uns, Ihnen folgendes Angebot unterbreiten zu dürfen:';
$outro=trim((string)($o['outro']??'')) ?: 'Wir hoffen, dass unser Angebot Ihnen zusagt und verbleiben mit freundlichen Grüßen';
?><!doctype html><html><head><meta charset="utf-8"><title>Angebot <?php p($o['offer_no']);?></title></head><body>
<div class="doc">
 <header class="doc-head">
  <div><?php if($logo):?><img class="doc-logo" src="<?php p($logo);?>"><?php else:?><strong><?php p($company['name']??'');?></strong><?php endif;?></div>
  <div class="doc-company">
   <strong><?php p($company['name']??'');?></strong><br><?php p($company['street']??'');?><br><?php p(trim(($company['zip']??'').' '.($company['city']??'')));?>
   <?php if(!empty($company['phone'])):?><br>Telefon: <?php p($company['phone']);?><?php endif;?>
   <?php if(!empty($company['mobile'])):?><br>Mobil: <?php p($company['mobile']);?><?php endif;?>
   <?php if(!empty($company['email'])):?><br>Mail: <?php p($company['email']);?><?php endif;?>
   <?php if(!empty($company['website'])):?><br>Web: <?php p($company['website']);?><?php endif;?>
   <?php if(!empty($company['vatId'])):?><br>USt-IdNr.: <?php p($company['vatId']);?><?php endif;?>
   <?php foreach([1,2] as $b): $bn=$company['bank'.$b.'_name']??''; if($bn):?><div class="doc-bank"><strong>Bankverbindung</strong><?php p($bn);?><br>IBAN <?php p($company['bank'.$b.'_iban']??'');?><br>BIC <?php p($company['bank'.$b.'_bic']??'');?></div><?php endif; endforeach;?>
  </div>
 </header>
 <section class="doc-recipient-wrap">
  <div><div class="doc-senderline"><?php p(erpCompanyLine($company));?></div><div class="doc-recipient"><?php echo erpAddr($customer);?></div></div>
  <div class="doc-meta"><div class="doc-meta-row"><span>Sachbearbeiter/-in:</span><strong><?php p($company['owner']??'');?></strong></div><div class="doc-meta-row"><span>Datum:</span><strong><?php p(date('d.m.Y',strtotime((string)$o['offer_date'])));?></strong></div><div class="doc-meta-row"><span>Angebots-Nr.:</span><strong><?php p($o['offer_no']);?></strong></div><?php if($project):?><div class="doc-meta-row"><span>Projekt:</span><strong><?php p(($project['project_no']??'').' '.($project['title']??''));?></strong></div><?php endif;?></div>
 </section>
 <h1 class="doc-title">Angebot</h1><div class="doc-intro"><?php echo $intro;?></div>
 <table class="doc-table"><thead><tr><th class="p">Pos.</th><th class="q">Anzahl</th><th class="u">Einheit</th><th>Bezeichnung</th><th class="price">Einzelpreis</th><th class="total">Gesamtpreis</th></tr></thead><tbody>
 <?php foreach($items as $n=>$x):?><tr><td class="p"><?php p($n+1);?></td><td class="q"><?php p(number_format((float)$x['quantity'],2,',','.'));?></td><td class="u"><?php p($x['unit']);?></td><td class="doc-desc"><?php if(!empty($x['is_alternative'])):?><div class="doc-alt">Alternativposition</div><?php endif;?><?php echo (string)$x['description'];?></td><td class="price"><?php p(number_format((float)$x['unit_price'],2,',','.'));?> €</td><td class="total"><?php p(number_format((float)$x['total_price'],2,',','.'));?> €<?php if(!empty($x['is_alternative'])):?><br><small>nicht in Summe</small><?php endif;?></td></tr><?php endforeach;?>
 </tbody></table>
 <div class="doc-totals"><div class="doc-total-row"><span>Summe</span><strong><?php p(number_format((float)$o['net_amount'],2,',','.'));?> €</strong></div><div class="doc-total-row"><span>Mehrwertsteuer <?php p(number_format((float)$o['vat_rate'],0,',','.'));?>%</span><span><?php p(number_format((float)$o['gross_amount']-(float)$o['net_amount'],2,',','.'));?> €</span></div><div class="doc-total-row doc-total-grand"><span>Gesamtbetrag</span><span><?php p(number_format((float)$o['gross_amount'],2,',','.'));?> €</span></div></div>
 <?php if(!empty($o['notes'])):?><div class="doc-notes"><?php echo (string)$o['notes'];?></div><?php endif;?>
 <div class="doc-outro"><?php echo $outro;?><br><br><?php p($company['owner']??'');?></div>
 <footer class="doc-footer"><div><?php p($company['name']??'');?><br><?php p($company['street']??'');?><br><?php p(trim(($company['zip']??'').' '.($company['city']??'')));?></div><div><?php if(!empty($company['taxNo'])):?>St.-Nr. <?php p($company['taxNo']);?><br><?php endif;?><?php if(!empty($company['vatId'])):?>USt-IdNr. <?php p($company['vatId']);?><?php endif;?></div><div><?php p($company['email']??'');?><br><?php p($company['website']??'');?></div></footer>
</div><script>window.print()</script></body></html>
