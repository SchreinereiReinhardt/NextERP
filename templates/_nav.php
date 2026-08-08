<?php
use OCP\Util;
use OCP\IURLGenerator;
use OCA\ReinhardtERP\Service\PermissionService;

$url = \OC::$server->get(IURLGenerator::class);
$permissions = \OC::$server->get(PermissionService::class);
Util::addStyle('reinhardterp', 'style');
Util::addScript('reinhardterp', 'navigation');
Util::addScript('reinhardterp', 'command_palette');
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
            ['Belege', 'reinhardterp.document.index', 'documents', '/documents'],
            ['Aufträge', 'reinhardterp.business.orders', 'orders', '/orders'],
            ['Rapporte', 'reinhardterp.module.reports', 'reports', '/reports'],
            ['Abrechnung vorbereiten', 'reinhardterp.module.invoicePreparation', 'invoices', '/invoice-preparation'],
        ],
    ],
    [
        'label' => 'Mitarbeiter', 'icon' => 'employee', 'key' => 'staff',
        'items' => [
            ['Zeiterfassung', 'reinhardterp.module.workdays', 'time', '/workdays'],
            ['Teamkalender', 'reinhardterp.module.teamEvents', 'calendar', '/team-events'],
            ['Monteuransicht', 'reinhardterp.business.mobile', 'mobile', '/mobile'],
        ],
    ],
    [
        'label' => 'Lager', 'icon' => 'inventory', 'key' => 'inventory',
        'items' => [
            ['Lagerbestand', 'reinhardterp.business.inventory', 'inventory', '/inventory'],
            ['Materialstamm', 'reinhardterp.module.materials', 'materials', '/materials'],
        ],
    ],
    [
        'label' => 'Belege', 'icon' => 'document', 'key' => 'documents',
        'items' => [
            ['Alle Belege', 'reinhardterp.document.index', 'documents', '/documents'],
            ['Ausgangsrechnungen', 'reinhardterp.document.index', 'documents', '/documents?type=outgoing_invoice'],
            ['Eingangsrechnungen', 'reinhardterp.document.index', 'documents', '/documents?type=incoming_invoice'],
            ['Lieferscheine', 'reinhardterp.document.index', 'documents', '/documents?type=delivery_note'],
            ['Angebote', 'reinhardterp.document.index', 'documents', '/documents?type=offer'],
            ['Auftragsbestätigungen', 'reinhardterp.document.index', 'documents', '/documents?type=order'],
            ['Gutschriften', 'reinhardterp.document.index', 'documents', '/documents?type=credit_note'],
            ['Kontoauszüge', 'reinhardterp.document.index', 'documents', '/documents?type=bank_statement'],
            ['Dokumentenarchiv', 'reinhardterp.document.index', 'documents', '/documents?processing=assigned'],
        ],
    ],
    [
        'label' => 'Auswertung', 'icon' => 'statistics', 'key' => 'evaluation',
        'items' => [
            ['Zeitauswertung', 'reinhardterp.module.timeEvaluation', 'time_billing', '/time-evaluation'],
            ['Abrechnung', 'reinhardterp.module.invoicePreparation', 'invoices', '/invoice-preparation'],
        ],
    ],
    [
        'label' => 'Verwaltung', 'icon' => 'settings', 'key' => 'admin',
        'items' => [
            ['Integration', 'reinhardterp.integration.index', 'settings', '/integration'],
            ['Benutzer & Rechte', 'reinhardterp.module.users', 'users_view', '/users'],
            ['Einstellungen', 'reinhardterp.module.settings', 'settings', '/settings'],
            ['Systemprüfung', 'reinhardterp.systemCheck.index', 'settings', '/system-check'],
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
];
?>
<nav id="app-navigation" class="erp-app-navigation" aria-label="NextERP Navigation">
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
                            if ($group['key'] === 'documents' && str_contains($match, '?')) {
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
            <input id="erpCommandInput" type="search" autocomplete="off" placeholder="Kunde, Projekt, Rapport oder Befehl suchen …" aria-label="NextERP durchsuchen">
            <kbd>Esc</kbd>
        </header>
        <div class="erp-command-results" id="erpCommandResults">
            <p class="erp-command-hint">Mindestens zwei Zeichen eingeben. Mit ↑ ↓ auswählen, mit Enter öffnen.</p>
        </div>
        <footer><span>NextERP Schnellsuche</span><span>Strg + K</span></footer>
    </section>
</div>
