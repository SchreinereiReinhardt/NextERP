<?php
$url=\OC::$server->get(\OCP\IURLGenerator::class);
$typeLabel=(string)($_['sectionLabel']??'Belege');
$route=(string)($_['sectionRoute']??'reinhardterp.document.finance');
?>
<section class="erp-card erp-wide">
 <div class="erp-section-head"><div><h2><?php p($typeLabel); ?></h2><p class="erp-muted"><?php p((string)($_['sectionHelp']??'')); ?></p></div>
 <form method="get" class="erp-inline-filter"><input type="search" name="q" value="<?php p($_['q']??''); ?>" placeholder="Beleg, Nummer, Name …"><button class="button">Suchen</button><?php if(!empty($_['q'])):?><a class="button" href="<?php p($url->linkToRoute($route)); ?>">Zurücksetzen</a><?php endif;?></form></div>
 <?php if(empty($_['documents'])):?><p class="erp-muted"><?php p((string)($_['emptyText']??'Noch keine Belege vorhanden.')); ?></p><?php else:?><div class="erp-dms-list">
 <?php foreach($_['documents'] as $document): ?>
  <a class="erp-dms-row" href="<?php p($url->linkToRoute('reinhardterp.document.review',['id'=>$document['id']])); ?>">
   <span class="erp-dms-row-icon"><span class="erp-ui-icon erp-icon-document" aria-hidden="true"></span></span>
   <span class="erp-dms-row-main"><strong><?php p($document['original_name']); ?></strong><small><?php p($document['document_date']??$document['created_at']??''); ?></small></span>
   <span class="erp-dms-row-main"><strong><?php p($document['document_no']??$document['suggested_document_no']??'–'); ?></strong><small><?php p($document['supplier_name']??$document['customer_name']??''); ?></small></span><span>›</span>
  </a>
 <?php endforeach; ?></div><?php endif; ?>
</section>
