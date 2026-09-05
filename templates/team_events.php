<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
$url = \OC::$server->get(IURLGenerator::class);
\OCP\Util::addScript('reinhardterp', 'team_events');

$customers = $_['customers'] ?? [];
$projects = $_['projects'] ?? [];
$users = $_['users'] ?? [];
$selectedCustomerId = (int)($_['selectedCustomerId'] ?? 0);
$selectedProjectId = (int)($_['selectedProjectId'] ?? 0);
$selectedUserId = (string)($_['selectedUserId'] ?? '');
$customerNames = [];
foreach ($customers as $customer) $customerNames[(int)$customer['id']] = (string)$customer['name'];
$projectNames = [];
foreach ($projects as $project) $projectNames[(int)$project['id']] = trim((string)($project['project_no'] ?? '') . ' ' . (string)($project['title'] ?? ''));
$userNames = [];
foreach ($users as $user) $userNames[(string)$user['uid']] = (string)$user['displayName'];
?>
<div id="app-content"><div class="erp-page erp-team-workspace">
<div class="erp-head"><div><h1>Teamkalender</h1><p class="erp-sub">Termine können direkt einem Kunden, Projekt und Mitarbeiter zugeordnet werden. Der ausgewählte Nextcloud-Kalender bleibt die führende Kalenderquelle.</p></div><div class="erp-actions">
<a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.settings')); ?>">Kalender auswählen</a>
<?php if (!empty($_['calendarConfigured'])): ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.syncCalendar')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button primary" type="submit">Jetzt synchronisieren</button></form><?php endif; ?>
</div></div>
<nav class="erp-customer-tabs erp-team-tabs"><a href="<?php p($url->linkToRoute('reinhardterp.module.users')); ?>">Übersicht</a><a href="<?php p($url->linkToRoute('reinhardterp.module.timeEvaluation')); ?>">Zeiterfassung</a><a class="is-active" href="<?php p($url->linkToRoute('reinhardterp.module.teamEvents')); ?>">Teamkalender</a></nav>
<?php if (!empty($_['error'])): ?><div class="erp-notice erp-wide"><strong>Termin konnte nicht gespeichert werden.</strong> <?php p($_['error']); ?></div><?php endif; ?>
<?php if (!empty($_['success'])): ?><div class="erp-integration-state is-connected erp-wide"><span>✓ Erfolgreich</span><strong><?php p($_['success']); ?></strong></div><?php endif; ?>
<?php if (!empty($_['calendarConfigured'])): ?>
<div class="erp-integration-state is-connected erp-wide"><span>✓ Bidirektionaler Abgleich aktiv</span><strong><?php p($_['selectedCalendarName']); ?></strong><small>Betrio → Nextcloud sofort · Nextcloud/Handy → Betrio beim Öffnen oder manuellen Abgleich<?php if (!empty($_['lastCalendarSync'])): ?> · zuletzt <?php p(date('d.m.Y H:i', strtotime((string)$_['lastCalendarSync']))); ?><?php endif; ?></small></div>
<?php if (!empty($_['lastCalendarError'])): ?><div class="erp-notice erp-wide"><strong>Letzter Kalenderfehler:</strong> <?php p($_['lastCalendarError']); ?></div><?php endif; ?>
<?php else: ?>
<div class="erp-notice erp-wide"><strong>Noch kein Nextcloud-Kalender ausgewählt.</strong> Termine bleiben im ERP, bis unter Einstellungen ein Kalender gewählt wurde.</div>
<?php endif; ?>

<form id="teamEventForm" class="erp-card erp-outlook-event" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveTeamEvent')); ?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<div class="erp-outlook-title"><input name="title" placeholder="Titel hinzufügen" aria-label="Termintitel" required autofocus></div>
<div class="erp-outlook-timebar">
  <label><span>Beginn</span><input id="teamEventStart" type="datetime-local" name="startAt" required></label>
  <label><span>Ende</span><input id="teamEventEnd" type="datetime-local" name="endAt" required></label>
  <label class="erp-outlook-all-day"><input id="teamEventAllDay" type="checkbox"><span>Ganztägig</span></label>
</div>
<div class="erp-outlook-body">
  <div class="erp-outlook-row"><span class="erp-outlook-icon">👤</span><label>Kunde<select id="teamEventCustomer" name="customerId"><option value="">— Kein Kunde —</option><?php foreach ($customers as $customer): ?><option value="<?php p((int)$customer['id']); ?>"<?php if ($selectedCustomerId === (int)$customer['id']) echo ' selected'; ?>><?php p($customer['name']); ?></option><?php endforeach; ?></select></label></div>
  <div class="erp-outlook-row"><span class="erp-outlook-icon">📁</span><label>Projekt<select id="teamEventProject" name="projectId"><option value="">— Kein Projekt —</option><?php foreach ($projects as $project): ?><option value="<?php p((int)$project['id']); ?>" data-customer-id="<?php p((int)($project['customer_id'] ?? 0)); ?>"<?php if ($selectedProjectId === (int)$project['id']) echo ' selected'; ?>><?php p(trim((string)($project['project_no'] ?? '') . ' · ' . (string)($project['title'] ?? ''))); ?></option><?php endforeach; ?></select></label></div>
  <div class="erp-outlook-row erp-outlook-people"><span class="erp-outlook-icon">👥</span><fieldset><legend>Mitarbeiter</legend><div class="erp-person-picker"><?php foreach ($users as $user): ?><label class="erp-person-chip"><input type="checkbox" name="assignedUserIds[]" value="<?php p($user['uid']); ?>"<?php if ($selectedUserId === (string)$user['uid']) echo ' checked'; ?>><span><?php p($user['displayName']); ?></span></label><?php endforeach; ?><?php if (empty($users)): ?><span class="erp-muted">Keine aktiven Mitarbeiter vorhanden.</span><?php endif; ?></div></fieldset></div>
  <div class="erp-outlook-row"><span class="erp-outlook-icon">📍</span><label>Ort<input name="location" placeholder="Ort hinzufügen"></label></div>
  <div class="erp-outlook-row erp-outlook-notes"><span class="erp-outlook-icon">☰</span><label>Beschreibung<textarea name="description" rows="5" placeholder="Notizen, Hinweise oder Informationen zum Termin"></textarea></label></div>
</div>
<div class="erp-outlook-footer"><button class="button primary" type="submit">Speichern</button><button class="button" type="reset">Verwerfen</button><div id="teamEventError" class="erp-form-error" hidden></div></div>
</form>
<div class="erp-table"><table><thead><tr><th>Termin</th><th>Kunde / Projekt</th><th>Mitarbeiter</th><th>Beginn</th><th>Ende</th><th>Ort</th><th>Quelle</th></tr></thead><tbody>
<?php foreach ($_['rows'] as $r): ?><tr>
<td><strong><?php p($r['title']); ?></strong><?php if (!empty($r['description'])): ?><small class="erp-block-muted"><?php p($r['description']); ?></small><?php endif; ?></td>
<td><?php if (!empty($r['customer_id']) && isset($customerNames[(int)$r['customer_id']])): ?><strong><?php p($customerNames[(int)$r['customer_id']]); ?></strong><?php endif; ?><?php if (!empty($r['project_id']) && isset($projectNames[(int)$r['project_id']])): ?><small class="erp-block-muted"><?php p($projectNames[(int)$r['project_id']]); ?></small><?php endif; ?><?php if (empty($r['customer_id']) && empty($r['project_id'])): ?><span class="erp-muted">—</span><?php endif; ?></td>
<td><?php $assignedNames=[];foreach(($r['assigned_user_ids']??[]) as $uid)$assignedNames[]=$userNames[(string)$uid]??(string)$uid;p($assignedNames?implode(', ',$assignedNames):'—'); ?></td>
<td><?php p(date('d.m.Y H:i', strtotime((string)$r['start_at']))); ?></td>
<td><?php p(!empty($r['end_at']) ? date('d.m.Y H:i', strtotime((string)$r['end_at'])) : '—'); ?></td>
<td><?php p($r['location'] ?: '—'); ?></td>
<td><?php if (($r['sync_source'] ?? '') === 'nextcloud'): ?><span class="erp-badge">Handy / Nextcloud</span><?php elseif (!empty($r['calendar_object_uri'])): ?><span class="erp-badge">Betrio → Nextcloud</span><?php else: ?><span class="erp-muted">nur ERP</span><?php endif; ?></td>
</tr><?php endforeach; ?>
<?php if (empty($_['rows'])): ?><tr><td colspan="7" class="erp-empty">Noch keine Termine vorhanden.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
