<?php
require __DIR__.'/_nav.php';
$url=\OC::$server->get(\OCP\IURLGenerator::class);
\OCP\Util::addScript('reinhardterp','finance');
$filesBase=$url->linkToRoute('files.view.index');
$types=['unassigned'=>'Nicht erkannt','incoming_invoice'=>'Eingangsrechnung','outgoing_invoice'=>'Ausgangsrechnung','delivery_note'=>'Lieferschein','credit_note'=>'Gutschrift','bank_statement'=>'Kontoauszug','cash'=>'Kassenbeleg','tax'=>'Steuerunterlage','accounting_other'=>'Sonstiger Buchhaltungsbeleg','offer'=>'Angebot','order'=>'Auftragsbestätigung','report'=>'Rapport','drawing'=>'Zeichnung / Plan','other'=>'Sonstiges'];
$processingLabels=['all'=>'Alle','new'=>'Neu','review'=>'In Prüfung','assigned'=>'Zugeordnet','error'=>'Fehler'];
$uxView=(string)($_['view']??'overview');
if(!in_array($uxView,['overview','inbox','invoices','offers','other','search','more'],true))$uxView='overview';
$visibleBlocks = match($uxView) {
    'overview' => ['head','kpis','list'],
    'inbox' => ['intake','head','automation','kpis','list'],
    'invoices' => ['head','filters','list'],
    'offers' => ['head','filters','list'],
    'other' => ['head','filters','list'],
    'search' => ['list'],
    'more' => ['intake','automation','rules'],
    default => ['head','kpis','list'],
};
$blockClass = static fn(string $name): string => in_array($name,$visibleBlocks,true) ? '' : ' erp-beleg-ux-hidden';
$documentsForView = $_['documents'] ?? [];
$effectiveType = static function(array $document): string {
    $type = (string)($document['document_type'] ?? 'unassigned');
    return $type !== 'unassigned' ? $type : (string)($document['suggested_type'] ?? 'unassigned');
};
if (in_array($uxView, ['overview','inbox'], true) && (string)($_['processing'] ?? 'all') !== 'assigned') {
    // Übersicht und Eingang sind eine echte Arbeitswarteschlange: erledigte Belege verschwinden sofort.
    $documentsForView = array_values(array_filter($documentsForView, static fn(array $d): bool => (string)($d['processing_status'] ?? 'new') !== 'assigned'));
} elseif ($uxView === 'invoices') {
    $documentsForView = array_values(array_filter($documentsForView, static fn(array $d): bool => in_array($effectiveType($d), ['incoming_invoice','outgoing_invoice'], true)));
} elseif ($uxView === 'offers') {
    $documentsForView = array_values(array_filter($documentsForView, static fn(array $d): bool => in_array($effectiveType($d), ['offer','order'], true)));
} elseif ($uxView === 'other') {
    $documentsForView = array_values(array_filter($documentsForView, static fn(array $d): bool => in_array($effectiveType($d), ['delivery_note','credit_note','bank_statement','cash','tax','accounting_other','report','drawing','other'], true)));
} elseif ($uxView === 'search' && trim((string)($_['q'] ?? '')) === '') {
    $documentsForView = [];
}
$openCount=(int)($_['counts']['new']??0)+(int)($_['counts']['review']??0)+(int)($_['counts']['error']??0);
$assignedCount=(int)($_['counts']['assigned']??0);
$totalProcessed=$openCount+$assignedCount;
$donePercent=$totalProcessed>0?(int)round(($assignedCount/$totalProcessed)*100):100;
?>
<div id="app-content"><style>
.erp-document-categories{gap:10px}
.erp-document-categories a{display:flex;align-items:center;gap:9px;min-height:48px;padding:8px 14px}
.erp-document-categories .erp-beleg-icon{display:block!important;width:20px!important;height:20px!important;min-width:20px!important;max-width:20px!important;min-height:20px!important;max-height:20px!important;flex:0 0 20px!important;color:#1265d8!important;stroke:#1265d8!important;overflow:visible}
.erp-document-categories a strong{white-space:nowrap}
</style>
<style>
/* Belege: Button-Breiten */
.erp-document-categories{
    display:flex!important;
    flex-wrap:wrap!important;
    align-items:stretch!important;
    gap:10px!important;
}
.erp-document-categories a{
    display:inline-flex!important;
    flex:0 0 auto!important;
    width:auto!important;
    min-width:max-content!important;
    max-width:none!important;
    box-sizing:border-box!important;
    align-items:center!important;
    justify-content:flex-start!important;
    gap:9px!important;
    padding:8px 14px!important;
    white-space:nowrap!important;
    overflow:visible!important;
}
.erp-document-categories a strong{
    display:block!important;
    width:auto!important;
    max-width:none!important;
    white-space:nowrap!important;
    overflow:visible!important;
    text-overflow:clip!important;
}
</style>
<div class="erp-page erp-dms-page">
<style>
.erp-dms-page{max-width:none}.erp-beleg-hero{margin-bottom:12px}.erp-beleg-hero .erp-project-identity p{color:var(--color-text-maxcontrast)}.erp-beleg-tabs{margin-bottom:12px}.erp-beleg-tabs>a{white-space:nowrap}.erp-beleg-search-hero{padding:16px 18px}.erp-beleg-search-hero form{display:flex;gap:10px;align-items:center}.erp-beleg-search-hero input[type=search]{flex:1;min-height:42px;font-size:15px}.erp-beleg-search-hero p{margin:8px 0 0}.erp-beleg-legacy-head{display:none!important}.erp-beleg-metrics{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.erp-beleg-metrics>div{background:var(--color-main-background);border:1px solid var(--color-border);border-radius:12px;padding:14px 16px}.erp-beleg-metrics span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--color-text-maxcontrast)}.erp-beleg-metrics strong{display:block;margin-top:5px;font-size:22px}.erp-beleg-ux-hidden{display:none!important}.erp-beleg-more-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px}.erp-beleg-more-grid .erp-card{margin:0}.erp-beleg-hint{margin:0 0 14px}@media(max-width:900px){.erp-beleg-metrics{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.erp-beleg-search-hero form{flex-wrap:wrap}.erp-beleg-search-hero input[type=search]{flex-basis:100%}.erp-beleg-metrics{grid-template-columns:1fr}}.erp-beleg-workbench{display:grid;grid-template-columns:minmax(260px,.82fr) minmax(520px,1.7fr);gap:16px;margin:0 0 16px}.erp-beleg-workbench-main{position:relative;overflow:hidden;padding:22px 24px;border:1px solid color-mix(in srgb,var(--color-primary-element) 24%,var(--color-border));border-radius:18px;background:linear-gradient(145deg,color-mix(in srgb,var(--color-primary-element) 8%,var(--color-main-background)) 0%,var(--color-main-background) 68%);box-shadow:0 10px 28px rgba(0,0,0,.055)}.erp-beleg-workbench-main:after{content:"";position:absolute;width:160px;height:160px;border-radius:50%;right:-72px;top:-72px;background:color-mix(in srgb,var(--color-primary-element) 8%,transparent)}.erp-beleg-workbench-number{font-size:42px;font-weight:800;line-height:1;margin:9px 0 5px;letter-spacing:-.04em}.erp-beleg-workbench-main>strong{font-size:16px}.erp-beleg-workbench-main p{max-width:540px;margin:7px 0 14px;color:var(--color-text-maxcontrast);line-height:1.45}.erp-beleg-progress{height:7px;border-radius:999px;background:var(--color-background-dark);overflow:hidden;margin:3px 0 7px}.erp-beleg-progress span{display:block;height:100%;border-radius:inherit;background:var(--color-primary-element);transition:width .25s ease}.erp-beleg-kpi-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.erp-beleg-kpi-grid>a{display:flex;flex-direction:column;justify-content:center;min-height:108px;padding:16px 18px;border:1px solid var(--color-border);border-radius:16px;background:var(--color-main-background);color:var(--color-main-text);text-decoration:none;box-shadow:0 8px 22px rgba(0,0,0,.045);transition:transform .15s ease,border-color .15s ease,box-shadow .15s ease}.erp-beleg-kpi-grid>a:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--color-primary-element) 38%,var(--color-border));box-shadow:0 12px 28px rgba(0,0,0,.07)}.erp-beleg-kpi-grid span{text-transform:uppercase;letter-spacing:.045em;font-size:10px;font-weight:700;color:var(--color-text-maxcontrast)}.erp-beleg-kpi-grid strong{font-size:27px;line-height:1.1;margin:4px 0}.erp-beleg-kpi-grid small{color:var(--color-text-maxcontrast)}.erp-beleg-kpi-grid .is-success{border-color:color-mix(in srgb,#2f9e44 30%,var(--color-border));background:color-mix(in srgb,#2f9e44 4%,var(--color-main-background))}.erp-beleg-kpi-grid .is-danger{border-color:color-mix(in srgb,#d94841 34%,var(--color-border));background:color-mix(in srgb,#d94841 4%,var(--color-main-background))}.erp-inbox-list-head{margin-bottom:10px}.erp-inbox-count{display:inline-grid;place-items:center;min-width:34px;height:34px;padding:0 10px;border-radius:999px;background:var(--color-background-dark);font-weight:800}.erp-premium-inbox-list .erp-dms-row{min-height:76px;padding:11px 14px;border-radius:12px;margin:4px 0;border:1px solid transparent;transition:background .15s ease,border-color .15s ease,transform .15s ease}.erp-premium-inbox-list .erp-dms-row:hover{background:color-mix(in srgb,var(--color-primary-element) 4%,var(--color-main-background));border-color:color-mix(in srgb,var(--color-primary-element) 18%,var(--color-border));transform:translateX(2px)}.erp-premium-inbox-list .erp-dms-row-icon{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:color-mix(in srgb,var(--color-primary-element) 9%,var(--color-main-background))}.erp-premium-inbox-list .erp-dms-row-main strong{font-size:14px}.erp-premium-inbox-list .erp-dms-row-main small{margin-top:4px}.erp-inbox-empty{min-height:190px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border:1px dashed var(--color-border);border-radius:15px;background:color-mix(in srgb,var(--color-background-dark) 28%,transparent)}.erp-inbox-empty .erp-ui-icon{width:32px;height:32px;margin-bottom:10px;color:#2f9e44}.erp-inbox-empty strong{font-size:18px}.erp-inbox-empty p{margin:5px 0 0;color:var(--color-text-maxcontrast)}@media(max-width:1050px){.erp-beleg-workbench{grid-template-columns:1fr}.erp-beleg-kpi-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.erp-beleg-kpi-grid>a{min-height:94px}}@media(max-width:760px){.erp-beleg-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.erp-beleg-workbench-main{padding:18px}.erp-beleg-workbench-number{font-size:36px}}
</style>
<header class="erp-project-hero erp-beleg-hero">
  <div class="erp-project-identity">
    <span class="erp-record-kicker">Digitale Belegverwaltung</span>
    <h1>Belege</h1>
    <p>Angebote, Aufträge, Rechnungen und weitere Dokumente zentral prüfen und zuordnen.</p>
  </div>
  <div class="erp-project-hero-actions">
    <form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.scan')); ?>">
      <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
      <button class="button primary">Jetzt einlesen</button>
    </form>
    <a class="button" target="_blank" rel="noopener" href="<?php p($filesBase.'?dir='.rawurlencode('/ERP/00_Dokumenteneingang/01_Unbearbeitet')); ?>">Eingangsordner</a>
  </div>
</header>
<nav class="erp-project-center-nav erp-beleg-tabs" aria-label="Belegbereiche">
<?php $uxTabs=['overview'=>'Übersicht','inbox'=>'Eingang','invoices'=>'Rechnungen','offers'=>'Angebote & Aufträge','other'=>'Sonstige Belege','search'=>'Suche','more'=>'Mehr']; foreach($uxTabs as $key=>$label): ?>
<a class="<?=$uxView===$key?'is-active':''?>" href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view='.rawurlencode($key));?>"><?php p($label);?></a>
<?php endforeach; ?>
</nav>
<?php if($uxView==='search'): ?>
<section class="erp-card erp-wide erp-beleg-search-hero"><form method="get" action="<?php p($url->linkToRoute('reinhardterp.document.index'));?>"><input type="hidden" name="view" value="search"><input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Belegnummer, Datei, Kunde, Projekt oder Lieferant …" autofocus><button class="button primary">Suchen</button></form><p class="erp-muted">Durchsucht Belegnummer, Datei, Kunde, Projekt und Lieferant in Betrio.</p></section>
<?php endif; ?>
<section class="erp-card erp-wide erp-document-drop-card<?=$blockClass('intake')?>" data-beleg-block="intake"><div id="globalDocumentDrop" class="erp-finance-dropzone"><div class="erp-finance-drop-content"><span class="erp-ui-icon erp-icon-document" aria-hidden="true"></span><strong>Belege und Dokumente hier reinziehen</strong><span>Mehrere Dateien nacheinander werden automatisch übernommen.</span></div></div><div class="erp-scanner-target"><strong>Scanner/WebDAV-Ziel:</strong> <code>ERP/00_Dokumenteneingang/01_Unbearbeitet</code></div></section>
<div class="erp-beleg-legacy-head<?=$blockClass('head')?>" data-beleg-block="head"></div>
<?php if(!empty($_['message'])):?><div class="erp-notice"><?php p($_['message']);?></div><?php endif;?>
<?php if(!empty($_['error'])):?><div class="erp-notice erp-notice-warning"><strong>Fehler:</strong> <?php p($_['error']);?></div><?php endif;?>
<div class="erp-dms-scan-status<?=$blockClass('automation')?>" data-beleg-block="automation"><strong>Automatik aktiv</strong><span>Überwachung alle 5 Minuten</span><span>Besitzer: <?php p($_['scanInfo']['owner'] ?: 'wird beim ersten Öffnen gesetzt'); ?></span><span>Letzter Lauf: <?php p($_['scanInfo']['last_at'] ?: 'noch nicht ausgeführt'); ?></span></div>
<nav class="erp-document-categories<?=$blockClass('categories')?>" data-beleg-block="categories" aria-label="Dokumentbereiche">
<?php
$categoryLinks=[
 ['Eingang',['processing'=>'new'],'<path d="M5 4h14v16H5z"/><path d="M8 14h2l2 2 2-2h2"/><path d="M12 6v6"/><path d="m9.5 9.5 2.5 2.5 2.5-2.5"/>'],
 ['Ausgangsrechnungen',['type'=>'outgoing_invoice'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 12h5M9 16h4"/><path d="M18 16v-5"/><path d="m16 13 2-2 2 2"/>'],
 ['Eingangsrechnungen',['type'=>'incoming_invoice'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 12h5M9 16h4"/><path d="M3 11v5"/><path d="m1 14 2 2 2-2"/>'],
 ['Lieferscheine',['type'=>'delivery_note'],'<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/>'],
 ['Angebote',['type'=>'offer'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 12h6M9 16h6"/><path d="M9 8h2"/>'],
 ['Auftragsbestätigungen',['type'=>'order'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="m9 14 2 2 4-5"/>'],
 ['Rechnungen',['type'=>'incoming_invoice'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M15 11.5a3 3 0 1 0 0 5"/><path d="M9 13h4M9 15h4"/>'],
 ['Gutschriften',['type'=>'credit_note'],'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M15 14H9"/><path d="m11 11-3 3 3 3"/>'],
 ['Kontoauszüge',['type'=>'bank_statement'],'<path d="m4 9 8-5 8 5"/><path d="M5 10h14M5 20h14"/><path d="M7 10v8M12 10v8M17 10v8"/>'],
 ['Archiv',['processing'=>'assigned'],'<path d="M5 8h14v12H5z"/><path d="M4 4h16v4H4z"/><path d="M9 12h6"/>'],
];
foreach($categoryLinks as [$label,$params,$svg]): ?>
<a href="<?php p($url->linkToRoute('reinhardterp.document.index',$params)); ?>">
<svg class="erp-beleg-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#1265d8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php print_unescaped($svg); ?></svg>
<strong><?php p($label); ?></strong></a>
<?php endforeach; ?>
</nav>

<section class="erp-beleg-workbench<?=$blockClass('kpis')?>" data-beleg-block="kpis">
  <div class="erp-beleg-workbench-main">
    <span class="erp-record-kicker">Arbeitsvorrat</span>
    <div class="erp-beleg-workbench-number"><?php p($openCount); ?></div>
    <strong>Dokumente noch zu bearbeiten</strong>
    <p>Neue, zu prüfende oder fehlerhafte Belege. Erfolgreich zugeordnete Dokumente verschwinden automatisch aus dieser Liste.</p>
    <div class="erp-beleg-progress"><span style="width:<?php p($donePercent); ?>%"></span></div>
    <small><?php p($donePercent); ?> % des aktuellen Bestands bereits zugeordnet</small>
  </div>
  <div class="erp-beleg-kpi-grid">
    <a href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view=inbox&processing=new'); ?>"><span>Neu</span><strong><?php p($_['counts']['new']??0);?></strong><small>Noch nicht geprüft</small></a>
    <a href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view=inbox&processing=review'); ?>"><span>In Prüfung</span><strong><?php p($_['counts']['review']??0);?></strong><small>Entscheidung erforderlich</small></a>
    <a class="is-success" href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view=overview&processing=assigned'); ?>"><span>Zugeordnet</span><strong><?php p($_['counts']['assigned']??0);?></strong><small>Erledigt / Archiv</small></a>
    <a class="<?php p(((int)($_['counts']['error']??0)>0)?'is-danger':''); ?>" href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view=inbox&processing=error'); ?>"><span>Fehler</span><strong><?php p($_['counts']['error']??0);?></strong><small>Bitte prüfen</small></a>
  </div>
</section>

<section class="erp-card erp-wide erp-dms-toolbar-card<?=$blockClass('filters')?>" data-beleg-block="filters">
<form class="erp-finance-filter-grid" method="get" action="<?php p($url->linkToRoute('reinhardterp.document.index')); ?>">
<input type="hidden" name="view" value="<?php p($uxView);?>"><input type="hidden" name="processing" value="<?php p($_['processing']??'all');?>">
<label>Belegart<select name="type"><option value="all">Alle Dokumentarten</option><?php foreach($types as $key=>$label):if($key==='unassigned')continue;?><option value="<?php p($key);?>" <?=($_['type']??'all')===$key?'selected':''?>><?php p($label);?></option><?php endforeach;?></select></label>
<label>Jahr<select name="year"><option value="">Alle</option><?php for($y=(int)date('Y');$y>=2020;$y--):?><option value="<?php p((string)$y);?>" <?=($_['year']??'')===(string)$y?'selected':''?>><?php p((string)$y);?></option><?php endfor;?></select></label>
<label>Monat<select name="month"><option value="">Alle</option><?php foreach([1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'] as $mn=>$ml):?><option value="<?php p((string)$mn);?>" <?php if((int)($_['month']??0)===$mn)p('selected');?>><?php p($ml);?></option><?php endforeach;?></select></label>
<label>Lieferant<select name="supplierId"><option value="">Alle</option><?php foreach($_['suppliers'] as $x):?><option value="<?php p($x['id']);?>" <?php if((int)($_['supplierId']??0)===(int)$x['id'])p('selected');?>><?php p($x['name']);?></option><?php endforeach;?></select></label>
<label>Kunde<select name="customerId"><option value="">Alle</option><?php foreach($_['customers'] as $x):?><option value="<?php p($x['id']);?>" <?php if((int)($_['customerId']??0)===(int)$x['id'])p('selected');?>><?php p($x['name']);?></option><?php endforeach;?></select></label>
<label>Projekt<select name="projectId"><option value="">Alle</option><?php foreach($_['projects'] as $x):?><option value="<?php p($x['id']);?>" <?php if((int)($_['projectId']??0)===(int)$x['id'])p('selected');?>><?php p(trim(($x['project_no']??'').' '.($x['title']??'')));?></option><?php endforeach;?></select></label>
<label class="erp-finance-search">Suche<input type="search" name="q" value="<?php p($_['q']??'');?>" placeholder="Datei, Nummer, Kunde, Projekt, Lieferant …"></label>
<div class="erp-actions"><button class="button primary">Filter anwenden</button><a class="button" href="<?php p($url->linkToRoute('reinhardterp.document.index').'?view='.rawurlencode($uxView));?>">Zurücksetzen</a></div>
</form>
<div class="erp-dms-filter"><?php foreach($processingLabels as $key=>$label):?><a class="button <?=($_['processing']??'all')===$key?'primary':''?>" href="<?php p($url->linkToRoute('reinhardterp.document.index',['processing'=>$key,'type'=>$_['type']??'all','q'=>$_['q']??'','year'=>$_['year']??'','month'=>$_['month']??'','supplierId'=>$_['supplierId']??0,'customerId'=>$_['customerId']??0,'projectId'=>$_['projectId']??0,'view'=>$uxView]));?>"><?php p($label);?></a><?php endforeach;?></div>
</section>
<section class="erp-card erp-wide erp-dms-list-card<?=$blockClass('list')?>" data-beleg-block="list">
<div class="erp-section-head erp-inbox-list-head"><div><h2><?php p((string)($_['processing']??'all')==='assigned'?'Zugeordnete Dokumente':'Zu bearbeiten'); ?></h2><p class="erp-muted"><?php p((string)($_['processing']??'all')==='assigned'?'Bereits verarbeitete Belege bleiben nachvollziehbar im Archiv.':'Priorisierte Arbeitsliste der Dokumenten-Inbox.'); ?></p></div><span class="erp-inbox-count"><?php p(count($documentsForView)); ?></span></div>
<?php if($uxView==='search' && trim((string)($_['q']??''))===''):?><div class="erp-inbox-empty"><strong>Dokumente durchsuchen</strong><p>Suchbegriff eingeben und auf „Suchen“ klicken.</p></div><?php elseif(empty($documentsForView)):?><div class="erp-inbox-empty"><span class="erp-ui-icon erp-icon-check" aria-hidden="true"></span><strong>Alles erledigt</strong><p>In dieser Ansicht warten aktuell keine Dokumente auf Bearbeitung.</p></div><?php else:?><div class="erp-dms-list erp-premium-inbox-list"><?php foreach($documentsForView as $document):$suggestedType=(string)($document['suggested_type']??'unassigned');$confidence=(int)($document['suggestion_confidence']??0);$shownType=$document['document_type']!=='unassigned'?$document['document_type']:$suggestedType;$state=(string)($document['processing_status']??'new');?><a class="erp-dms-row" href="<?php p($url->linkToRoute('reinhardterp.document.review',['id'=>$document['id']]));?>"><span class="erp-dms-row-icon"><span class="erp-ui-icon <?=str_starts_with((string)$document['mime_type'],'image/')?'erp-icon-image':'erp-icon-document'?>" aria-hidden="true"></span></span><span class="erp-dms-row-main"><strong title="<?php p($document['original_name']);?>"><?php p($document['original_name']);?></strong><small><?php p(number_format(((int)$document['file_size'])/1024,1,',','.'));?> KB · <?php p($document['created_at']??'');?></small></span><span class="erp-dms-row-type"><?php p($types[$shownType]??$shownType);?><?php if($document['document_type']==='unassigned'&&$suggestedType!=='unassigned'):?><small>Vorschlag · <?php p($confidence);?> %<?php if(!empty($document['auto_rule_id'])):?> · Regel<?php endif;?></small><?php endif;?></span><?php if(!empty($document['duplicate_of'])):?><span class="erp-status-pill erp-status-warning">Mögliche Dublette</span><?php else:?><span class="erp-status-pill erp-dms-state-<?php p($state);?>"><?php p($processingLabels[$state]??$state);?></span><?php endif;?><span>›</span></a><?php endforeach;?></div><?php endif;?></section>
<section class="erp-card erp-wide<?=$blockClass('rules')?>" data-beleg-block="rules"><details><summary><strong>Automatische Dokumentenregeln</strong> <span class="erp-muted">Dateinamen automatisch zuordnen</span></summary><form class="erp-rule-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.document.createRule'));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input name="name" placeholder="Regelname" required><input name="matchValue" placeholder="Dateiname enthält …" required><select name="documentType"><option value="">Dokumentart unverändert</option><?php foreach($types as $key=>$label):if($key==='unassigned')continue;?><option value="<?php p($key);?>"><?php p($label);?></option><?php endforeach;?></select><input type="number" name="priority" value="100" min="1" max="999"><button class="button primary">Regel anlegen</button></form><?php if(empty($_['rules'])):?><p class="erp-muted">Noch keine Regeln vorhanden.</p><?php else:?><div class="erp-rule-list"><?php foreach($_['rules'] as $rule):?><div><span><strong><?php p($rule['name']);?></strong><small> enthält „<?php p($rule['match_value']);?>“ · Priorität <?php p($rule['priority']);?></small></span><form method="post" action="<?php p($url->linkToRoute('reinhardterp.document.deleteRule',['id'=>$rule['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><button class="button">Löschen</button></form></div><?php endforeach;?></div><?php endif;?></details></section>

</div></div>
