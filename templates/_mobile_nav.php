<?php
/** Shared mobile navigation. Expects $_['urlGenerator']; optional $mobileActive and $mobileProjectId. */
$url = $_['urlGenerator'];
$mobileActive = $mobileActive ?? 'today';
$mobileBase = $url->linkToRoute('reinhardterp.business.mobile');
$navItem = static function (string $key, string $href, string $iconClass, string $label, string $mobileActive): void {
    $active = $mobileActive === $key;
    ?>
    <a class="<?php p($active ? 'active' : ''); ?>" href="<?php p($href); ?>"<?php if ($active): ?> aria-current="page"<?php endif; ?>>
      <span class="erp-mobile-nav-icon"><span class="erp-ui-icon <?php p($iconClass); ?>"></span></span>
      <b><?php p($label); ?></b>
    </a>
    <?php
};
?>
<nav id="nexterp-mobile-bottom-nav" class="erp-mobile-bottom" aria-label="NextERP Mobile Navigation">
 <?php $navItem('today', $mobileBase, 'erp-icon-dashboard', 'Heute', $mobileActive); ?>
 <?php $navItem('projects', $mobileBase.'?view=projects', 'erp-icon-project', 'Projekte', $mobileActive); ?>
 <?php $navItem('time', $url->linkToRoute('reinhardterp.business.mobileTime', isset($mobileProjectId)?['projectId'=>(int)$mobileProjectId]:[]), 'erp-icon-time', 'Zeit', $mobileActive); ?>
 <?php $navItem('scanner', $url->linkToRoute('reinhardterp.business.mobileMaterial', isset($mobileProjectId)?['projectId'=>(int)$mobileProjectId]:[]), 'erp-icon-search', 'Scanner', $mobileActive); ?>
 <?php $navItem('more', $mobileBase.'?view=more', 'erp-icon-document', 'Mehr', $mobileActive); ?>
</nav>
