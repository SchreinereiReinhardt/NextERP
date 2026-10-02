<?php
use OCP\Util;
use OCP\IURLGenerator;
use OCA\ReinhardtERP\Service\PermissionService;

$url = \OC::$server->get(IURLGenerator::class);
$permissions = \OC::$server->get(PermissionService::class);
Util::addStyle('reinhardterp', 'style');
Util::addScript('reinhardterp', 'navigation');
Util::addScript('reinhardterp', 'command_palette');
Util::addScript('reinhardterp', 'context_help');
Util::addScript('reinhardterp', 'customer_select');
Util::addScript('reinhardterp', 'whats_new');
$currentPath = (string)($_SERVER['REQUEST_URI'] ?? '');

$groups = [
    [
        'label' => 'Kunden', 'icon' => 'customer', 'key' => 'customers',
        'items' => [
            ['Kundenakten', 'reinhardterp.page.customers', 'customers', '/customers'],
            ['CRM', 'reinhardterp.business.crm', 'crm', '/crm'],
            ['Kontakte importieren', 'reinhardterp.integration.customerImport', 'customers', '/customers/import'],
        ],
    ],
    [
        'label' => 'Projekte', 'icon' => 'project', 'key' => 'projects',
        'items' => [
            ['Projektakten', 'reinhardterp.page.projects', 'projects', '/projects'],
            ['Dateiaktivitäten', 'reinhardterp.page.fileActivities', 'projects', '/file-activities'],
            ['Rapporte', 'reinhardterp.module.reports', 'reports', '/reports'],
            ['Abrechnung vorbereiten', 'reinhardterp.module.invoicePreparation', 'invoices', '/invoice-preparation'],
        ],
    ],
    [
        'label' => 'Verkauf', 'icon' => 'document', 'key' => 'sales',
        'items' => [
            ['Übersicht', 'reinhardterp.business.salesOverview', 'offers', '/sales'],
            ['Angebote', 'reinhardterp.business.offers', 'offers', '/offers'],
            ['Aufträge', 'reinhardterp.business.orders', 'orders', '/orders'],
            ['Rechnungen', 'reinhardterp.business.invoices', 'invoices', '/invoices'],
            ['Gutschriften', 'reinhardterp.document.finance', 'documents', '/finance?type=credit_note'],
        ],
    ],
    [
        'label' => 'Mitarbeiter', 'icon' => 'employee', 'key' => 'staff',
        'items' => [
            ['Mitarbeiter', 'reinhardterp.module.staff', 'staff', '/staff'],
            ['Zeiterfassung', 'reinhardterp.module.workdays', 'time', '/workdays'],
            ['Abwesenheiten', 'reinhardterp.module.absences', 'time', '/absences'],
            ['Personalplanung', 'reinhardterp.module.staffPlanning', 'settings', '/staff-planning'],
            ['Teamkalender', 'reinhardterp.module.teamEvents', 'calendar', '/team-events'],
            ['Monteuransicht', 'reinhardterp.business.mobile', 'mobile', '/mobile'],
        ],
    ],
    [
        'label' => 'Lager', 'icon' => 'inventory', 'key' => 'inventory',
        'items' => [
            ['Lagerbestand', 'reinhardterp.business.inventory', 'inventory', '/inventory'],
            ['Materialstamm', 'reinhardterp.module.materials', 'materials', '/materials'],
            ['Lieferanten', 'reinhardterp.module.suppliers', 'materials', '/suppliers'],
        ],
    ],
    [
        'label' => 'Einkauf & Belege', 'icon' => 'document', 'key' => 'documents',
        'items' => [
            ['Dokumenteneingang', 'reinhardterp.document.index', 'documents', '/documents'],
            ['Eingangsrechnungen', 'reinhardterp.document.index', 'documents', '/documents?type=incoming_invoice'],
            ['Lieferscheine', 'reinhardterp.document.index', 'documents', '/documents?type=delivery_note'],
            ['Sonstige Belege', 'reinhardterp.document.index', 'documents', '/documents?type=accounting_other'],
            ['Dokumentenarchiv', 'reinhardterp.document.index', 'documents', '/documents?processing=assigned'],
        ],
    ],
    [
        'label' => 'Finanzen', 'icon' => 'statistics', 'key' => 'finance',
        'items' => [
            ['Übersicht', 'reinhardterp.document.finance', 'documents', '/finance'],
            ['Bankumsätze', 'reinhardterp.document.bankStatements', 'documents', '/finance/bank-statements'],
            ['Kontoauszüge', 'reinhardterp.document.finance', 'documents', '/finance?type=bank_statement'],
            ['Kasse', 'reinhardterp.document.cashbook', 'documents', '/finance/cash'],
            ['Steuern', 'reinhardterp.document.taxes', 'documents', '/finance/taxes'],
        ],
    ],
    [
        'label' => 'Auswertung', 'icon' => 'statistics', 'key' => 'evaluation',
        'items' => [
            ['Arbeitszeitkonto', 'reinhardterp.module.workingTimeAccount', 'time', '/working-time-account'],
            ['Zeitauswertung', 'reinhardterp.module.timeEvaluation', 'time_billing', '/time-evaluation'],
            ['Abrechnung', 'reinhardterp.module.invoicePreparation', 'invoices', '/invoice-preparation'],
        ],
    ],
    [
        'label' => 'Verwaltung', 'icon' => 'settings', 'key' => 'admin',
        'items' => [
            ['Mobile', 'reinhardterp.business.mobileAdmin', 'settings', '/mobile-admin'],
            ['Integration', 'reinhardterp.integration.index', 'settings', '/integration'],
            ['Benutzer & Rechte', 'reinhardterp.module.users', 'users_view', '/users'],
            ['Einstellungen', 'reinhardterp.module.settings', 'settings', '/settings'],
            ['Systemprüfung', 'reinhardterp.systemCheck.index', 'settings', '/system-check'],
            ['Datenschutz', 'reinhardterp.business.privacy', 'document', '/privacy'],
            ['Über Betrio & Release', 'reinhardterp.business.aboutRelease', 'settings', '/about-release'],
        ],
    ],
    [
        'label' => 'Hilfe', 'icon' => 'document', 'key' => 'help',
        'items' => [
            ['Dokumentation', 'reinhardterp.business.documentation', 'help', '/documentation'],
            ['Was ist neu?', 'reinhardterp.business.whatsNew', 'help', '/whats-new'],
        ],
    ],
];

$quickCreate = [
    ['Neuer Kunde', 'reinhardterp.page.customerForm', 'customers'],
    ['Neues Projekt', 'reinhardterp.page.projectForm', 'projects'],
    ['Neuer Rapport', 'reinhardterp.module.reports', 'reports'],
    ['Beleg importieren', 'reinhardterp.document.index', 'documents'],
    ['Neuer Termin', 'reinhardterp.module.teamEvents', 'calendar'],
    ['Zeit buchen', 'reinhardterp.module.workdays', 'time'],
    ['Neues Material', 'reinhardterp.module.materials', 'materials'],
];

\OCP\Util::addScript('reinhardterp','richtext');
?>
<nav id="app-navigation" class="erp-app-navigation" aria-label="Betrio Navigation">
    <div class="erp-nav-brand">
        <a href="<?php p($url->linkToRoute('reinhardterp.page.index')); ?>" class="erp-nav-home<?php if (str_ends_with(parse_url($currentPath, PHP_URL_PATH) ?? '', '/reinhardterp/')) { p(' is-active'); } ?>">
            <span class="erp-ui-icon erp-icon-dashboard erp-nav-home-icon" aria-hidden="true"></span><span>Dashboard</span>
        </a>

        <button type="button" class="erp-command-trigger" id="erpCommandTrigger" aria-label="Suchen und Befehle öffnen">
            <span class="erp-ui-icon erp-icon-search" aria-hidden="true"></span><span>Suchen</span><kbd>Strg K</kbd>
        </button>
        <details class="erp-create-menu">
            <summary>＋ Neu</summary>
            <div class="erp-create-popover">
                <?php foreach ($quickCreate as [$label, $route, $permission]): if (!$permissions->can($permission)) continue; ?>
                    <a href="<?php p($url->linkToRoute($route)); ?>"><?php p($label); ?></a>
                <?php endforeach; ?>
            </div>
        </details>
    </div>

    <div class="erp-nav-groups">
        <?php foreach ($groups as $group):
            $visibleItems = array_values(array_filter($group['items'], fn($item) => $permissions->can($item[2])));
            if ($visibleItems === []) continue;
            $groupActive = false;
            foreach ($visibleItems as $item) {
                if (str_contains($currentPath, $item[3])) { $groupActive = true; break; }
            }
        ?>
            <details class="erp-nav-group" data-nav-key="<?php p($group['key']); ?>" <?php if ($groupActive) print_unescaped('open'); ?>>
                <summary><span class="erp-ui-icon erp-nav-icon erp-icon-<?php p($group['icon']); ?>" aria-hidden="true"></span><span><?php p($group['label']); ?></span><span class="erp-nav-chevron">›</span></summary>
                <ul>
                    <?php foreach ($visibleItems as [$label, $route, $permission, $match]):
                        $active = str_contains($currentPath, $match);
                    ?>
                        <?php
                            $routeParams = [];
                            if (in_array($group['key'], ['documents','finance','sales'], true) && str_contains($match, '?')) {
                                [, $queryString] = explode('?', $match, 2);
                                parse_str($queryString, $routeParams);
                            }
                        ?>
                        <li><a class="<?php if ($active) p('is-active'); ?>" href="<?php p($url->linkToRoute($route, $routeParams)); ?>"><?php p($label); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </div>

    <div class="erp-role-note">Rolle: <?php p($permissions->role()); ?></div>
</nav>

<div class="erp-command-overlay" id="erpCommandOverlay" hidden>
    <section class="erp-command-dialog" role="dialog" aria-modal="true" aria-labelledby="erpCommandTitle" data-search-url="<?php p($url->linkToRoute('reinhardterp.search.index')); ?>">
        <header>
            <span class="erp-ui-icon erp-icon-search erp-command-search-icon" aria-hidden="true"></span>
            <input id="erpCommandInput" type="search" autocomplete="off" placeholder="Kunde, Projekt, Rapport oder Befehl suchen …" aria-label="Betrio durchsuchen">
            <kbd>Esc</kbd>
        </header>
        <div class="erp-command-results" id="erpCommandResults">
            <p class="erp-command-hint">Mindestens zwei Zeichen eingeben. Mit ↑ ↓ auswählen, mit Enter öffnen.</p>
        </div>
        <footer><span>Betrio Schnellsuche</span><span>Strg + K</span></footer>
    </section>
</div>

<?php
$erpReleaseNotes=require __DIR__.'/../config/release_notes.php';
$erpConfig=\OC::$server->get(\OCP\IConfig::class);
$erpUser=\OC::$server->get(\OCP\IUserSession::class)->getUser();
$erpCurrentVersion=$erpConfig->getAppValue('reinhardterp','installed_version','');
$erpSeenVersion=$erpUser?$erpConfig->getUserValue($erpUser->getUID(),'reinhardterp','whats_new_seen',''):'';
$erpCurrentNote=$erpReleaseNotes[$erpCurrentVersion]??null;
?>
<?php if($erpUser && $erpCurrentNote && $erpSeenVersion!==$erpCurrentVersion && !str_contains($currentPath,'/whats-new')): ?>
<div class="erp-whatsnew-overlay" id="erpWhatsNew" data-version="<?php p($erpCurrentVersion); ?>" data-dismiss-url="<?php p($url->linkToRoute('reinhardterp.business.dismissWhatsNew')); ?>">
 <section class="erp-whatsnew-dialog" role="dialog" aria-modal="true" aria-labelledby="erpWhatsNewTitle">
  <button type="button" class="erp-whatsnew-close" id="erpWhatsNewClose" aria-label="Was ist neu schließen" title="Für diese Version nicht erneut anzeigen">×</button>
  <span class="erp-whatsnew-kicker">Betrio Update</span>
  <h2 id="erpWhatsNewTitle"><?php p($erpCurrentNote['title']); ?></h2>
  <p><?php p($erpCurrentNote['intro']); ?></p>
  <ul><?php foreach($erpCurrentNote['items'] as $item): ?><li><?php p($item); ?></li><?php endforeach; ?></ul>
  <div class="erp-whatsnew-actions"><a class="button primary" href="<?php p($url->linkToRoute('reinhardterp.business.whatsNew')); ?>">Alle Neuerungen ansehen</a><button type="button" class="button" id="erpWhatsNewDone">Verstanden</button></div>
  <small>Schließen mit × oder „Verstanden“ blendet diesen Hinweis für Version <?php p($erpCurrentVersion); ?> dauerhaft für deinen Benutzer aus.</small>
 </section>
</div>
<?php endif; ?>
