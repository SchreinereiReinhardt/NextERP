<?php
$url=$_['urlGenerator']; $notes=$_['releaseNotes']??[];
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav')); ?><main class="erp-main erp-whatsnew-page">
<div class="erp-head"><div><span class="erp-record-kicker">Hilfe</span><h1>Was ist neu?</h1><p class="erp-sub">Die wichtigsten Änderungen der letzten Betrio-Versionen.</p></div></div>
<?php foreach($notes as $version=>$note): ?>
<section class="erp-card erp-release-note"><div class="erp-release-version">Version <?php p($version); ?></div><h2><?php p($note['title']); ?></h2><p><?php p($note['intro']); ?></p><ul><?php foreach($note['items'] as $item): ?><li><?php p($item); ?></li><?php endforeach; ?></ul></section>
<?php endforeach; ?>
</main></div></div>
