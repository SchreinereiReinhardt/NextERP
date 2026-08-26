<?php
require __DIR__.'/_nav.php';
$url = $_['urlGenerator'];
$view = (string)($_['view'] ?? 'overview');
$communications = is_array($_['communications'] ?? null) ? $_['communications'] : [];
$dueFollowUps = is_array($_['dueFollowUps'] ?? null) ? $_['dueFollowUps'] : [];
$customers = is_array($_['customers'] ?? null) ? $_['customers'] : [];
$projects = is_array($_['projects'] ?? null) ? $_['projects'] : [];

$typeLabels = [
    'call' => 'Telefonat',
    'email' => 'E-Mail',
    'meeting' => 'Besprechung',
    'note' => 'Notiz',
];
$typeIcons = [
    'call' => '☎',
    'email' => '✉',
    'meeting' => '◉',
    'note' => '✎',
];

$today = date('Y-m-d');
$recentCutoff = strtotime('-7 days');
$recentCount = 0;
$callsCount = 0;
$emailsCount = 0;
foreach ($communications as $row) {
    $ts = strtotime((string)($row['contact_at'] ?? ''));
    if ($ts !== false && $ts >= $recentCutoff) { $recentCount++; }
    if (($row['type'] ?? '') === 'call') { $callsCount++; }
    if (($row['type'] ?? '') === 'email') { $emailsCount++; }
}

$tabUrl = static function(string $tab) use ($url): string {
    return $url->linkToRoute('reinhardterp.business.crm', ['view' => $tab]);
};
?>
<div id="app-content"><div class="erp-page erp-crm-page">
    <div class="erp-project-hero erp-crm-hero">
        <div>
            <div class="erp-eyebrow">KUNDENKOMMUNIKATION</div>
            <h1>CRM</h1>
            <p class="erp-sub">Telefonate, E-Mails, Besprechungen, Notizen und Wiedervorlagen zentral dokumentieren.</p>
        </div>
        <div class="erp-actions">
            <a class="button primary" href="<?php p($tabUrl('new')); ?>">+ Kontakt dokumentieren</a>
            <a class="button" href="<?php p($tabUrl('followups')); ?>">Wiedervorlagen</a>
        </div>
    </div>

    <nav class="erp-section-tabs erp-crm-tabs" aria-label="CRM Bereiche">
        <a class="<?php p($view === 'overview' ? 'active' : ''); ?>" href="<?php p($tabUrl('overview')); ?>">Übersicht</a>
        <a class="<?php p($view === 'new' ? 'active' : ''); ?>" href="<?php p($tabUrl('new')); ?>">Kontakt dokumentieren</a>
        <a class="<?php p($view === 'followups' ? 'active' : ''); ?>" href="<?php p($tabUrl('followups')); ?>">Wiedervorlagen<?php if (count($dueFollowUps) > 0): ?><span class="erp-tab-count"><?php p(count($dueFollowUps)); ?></span><?php endif; ?></a>
        <a class="<?php p($view === 'history' ? 'active' : ''); ?>" href="<?php p($tabUrl('history')); ?>">Historie</a>
    </nav>

    <?php if ($view === 'overview'): ?>
        <div class="erp-kpis erp-crm-kpis">
            <div><span>Kontakte gesamt</span><strong><?php p(count($communications)); ?></strong><small>letzte 100 Einträge</small></div>
            <div><span>Letzte 7 Tage</span><strong><?php p($recentCount); ?></strong><small>neue Aktivitäten</small></div>
            <div><span>Telefonate</span><strong><?php p($callsCount); ?></strong><small>dokumentiert</small></div>
            <div><span>Fällige Wiedervorlagen</span><strong><?php p(count($dueFollowUps)); ?></strong><small>jetzt bearbeiten</small></div>
        </div>

        <div class="erp-grid-2 erp-crm-overview-grid">
            <section class="erp-card erp-wide">
                <div class="erp-section-head"><div><h2>Letzte Kontakte</h2><p class="erp-muted">Die jüngsten Kundenaktivitäten auf einen Blick.</p></div><a class="button" href="<?php p($tabUrl('history')); ?>">Alle anzeigen</a></div>
                <?php if (!$communications): ?><p class="erp-muted">Noch keine Kommunikation dokumentiert.</p><?php endif; ?>
                <div class="erp-crm-activity-list">
                    <?php foreach (array_slice($communications, 0, 6) as $r): ?>
                        <div class="erp-crm-activity-row">
                            <div class="erp-crm-type-icon"><?php p($typeIcons[$r['type']] ?? '•'); ?></div>
                            <div class="erp-crm-activity-main">
                                <strong><?php p($r['subject']); ?></strong>
                                <span><?php p($r['customer_name']); ?><?php if (!empty($r['project_no'])): ?> · <?php p($r['project_no']); ?><?php endif; ?></span>
                            </div>
                            <div class="erp-crm-activity-meta">
                                <span class="erp-badge"><?php p($typeLabels[$r['type']] ?? $r['type']); ?></span>
                                <small><?php p(date('d.m.Y H:i', strtotime($r['contact_at']))); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="erp-card">
                <div class="erp-section-head"><div><h2>Fällige Wiedervorlagen</h2><p class="erp-muted">Was jetzt Aufmerksamkeit braucht.</p></div><a class="button" href="<?php p($tabUrl('followups')); ?>">Öffnen</a></div>
                <?php if (!$dueFollowUps): ?><p class="erp-muted">Keine fälligen Wiedervorlagen.</p><?php endif; ?>
                <?php foreach (array_slice($dueFollowUps, 0, 5) as $r): ?>
                    <div class="erp-crm-followup-row">
                        <strong><?php p($r['subject']); ?></strong>
                        <span><?php p($r['customer_name']); ?></span>
                        <small><?php p(date('d.m.Y H:i', strtotime($r['follow_up_at']))); ?></small>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($view === 'new'): ?>
        <section class="erp-card erp-crm-form-card">
            <div class="erp-section-head"><div><h2>Kontakt dokumentieren</h2><p class="erp-muted">Eine Kundenaktivität schnell und nachvollziehbar erfassen.</p></div></div>
            <form method="post" action="<?php p($url->linkToRoute('reinhardterp.business.saveCommunication')); ?>">
                <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
                <div class="erp-form-grid">
                    <div><label>Kunde</label><select name="customerId" required><?php foreach($customers as $c):?><option value="<?php p($c['id']);?>"><?php p($c['name']);?></option><?php endforeach;?></select></div>
                    <div><label>Projekt</label><select name="projectId"><option value="">ohne Projekt</option><?php foreach($projects as $project):?><option value="<?php p($project['id']);?>"><?php p($project['project_no'].' · '.$project['title']);?></option><?php endforeach;?></select></div>
                    <div><label>Art</label><select name="type"><option value="call">Telefonat</option><option value="email">E-Mail</option><option value="meeting">Besprechung</option><option value="note">Notiz</option></select></div>
                    <div><label>Zeitpunkt</label><input type="datetime-local" name="contactAt" value="<?php p(date('Y-m-d\TH:i')); ?>"></div>
                    <div class="erp-span-2"><label>Betreff</label><input name="subject" required placeholder="Kurz zusammenfassen, worum es ging"></div>
                    <div class="erp-span-2"><label>Details</label><textarea name="details" rows="6" placeholder="Gesprächsinhalt, Ergebnis oder nächste Schritte …"></textarea></div>
                    <div><label>Wiedervorlage</label><input type="datetime-local" name="followUpAt"></div>
                </div>
                <div class="erp-actions erp-crm-form-actions"><button class="button primary">Speichern</button><a class="button" href="<?php p($tabUrl('overview')); ?>">Abbrechen</a></div>
            </form>
        </section>
    <?php endif; ?>

    <?php if ($view === 'followups'): ?>
        <section class="erp-card">
            <div class="erp-section-head"><div><h2>Fällige Wiedervorlagen</h2><p class="erp-muted">Alle aktuell fälligen Rückrufe und nächsten Schritte.</p></div><a class="button primary" href="<?php p($tabUrl('new')); ?>">+ Kontakt dokumentieren</a></div>
            <?php if (!$dueFollowUps): ?><div class="erp-empty-state"><strong>Alles erledigt</strong><span>Aktuell sind keine Wiedervorlagen fällig.</span></div><?php endif; ?>
            <div class="erp-crm-followup-list">
                <?php foreach($dueFollowUps as $r): ?>
                    <article class="erp-crm-followup-card">
                        <div><span class="erp-badge">Wiedervorlage</span><h3><?php p($r['subject']); ?></h3><p><?php p($r['customer_name']); ?></p><?php if (!empty($r['details'])): ?><small><?php p($r['details']); ?></small><?php endif; ?></div>
                        <time><?php p(date('d.m.Y H:i', strtotime($r['follow_up_at']))); ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($view === 'history'): ?>
        <section class="erp-card">
            <div class="erp-section-head"><div><h2>Kommunikationshistorie</h2><p class="erp-muted">Die letzten 100 dokumentierten Kundenkontakte.</p></div><a class="button primary" href="<?php p($tabUrl('new')); ?>">+ Kontakt dokumentieren</a></div>
            <?php if (!$communications): ?><p class="erp-muted">Noch keine Kommunikation dokumentiert.</p><?php endif; ?>
            <div class="erp-crm-history">
                <?php foreach($communications as $r): ?>
                    <article class="erp-crm-history-row">
                        <div class="erp-crm-type-icon"><?php p($typeIcons[$r['type']] ?? '•'); ?></div>
                        <div class="erp-crm-history-content">
                            <div class="erp-crm-history-head"><strong><?php p($r['subject']); ?></strong><span class="erp-badge"><?php p($typeLabels[$r['type']] ?? $r['type']); ?></span></div>
                            <p><?php p($r['customer_name']); ?><?php if (!empty($r['project_no'])): ?> · <?php p($r['project_no'].' '.$r['project_title']); ?><?php endif; ?></p>
                            <?php if (!empty($r['details'])): ?><div class="erp-crm-history-details"><?php p($r['details']); ?></div><?php endif; ?>
                            <?php if (!empty($r['follow_up_at'])): ?><small>Wiedervorlage: <?php p(date('d.m.Y H:i', strtotime($r['follow_up_at']))); ?></small><?php endif; ?>
                        </div>
                        <time><?php p(date('d.m.Y H:i', strtotime($r['contact_at']))); ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div></div>
