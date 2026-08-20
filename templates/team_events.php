<?php
require __DIR__ . '/_nav.php';
use OCP\IURLGenerator;
$url = \OC::$server->get(IURLGenerator::class);
\OCP\Util::addScript('reinhardterp', 'team_events');
?>
<div id="app-content"><div class="erp-page">
<div class="erp-head"><div><h1>Teamkalender</h1><p class="erp-sub">Der ausgewählte Nextcloud-Kalender ist die führende Quelle. Termine vom Handy werden beim Öffnen und über „Jetzt synchronisieren“ nach Betrio übernommen.</p></div><div class="erp-actions">
<a class="button" href="<?php p($url->linkToRoute('reinhardterp.module.settings')); ?>">Kalender auswählen</a>
<?php if (!empty($_['calendarConfigured'])): ?><form method="post" action="<?php p($url->linkToRoute('reinhardterp.integration.syncCalendar')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><button class="button primary" type="submit">Jetzt synchronisieren</button></form><?php endif; ?>
</div></div>
<?php if (!empty($_['error'])): ?><div class="erp-notice erp-wide"><strong>Termin konnte nicht gespeichert werden.</strong> <?php p($_['error']); ?></div><?php endif; ?>
<?php if (!empty($_['success'])): ?><div class="erp-integration-state is-connected erp-wide"><span>✓ Erfolgreich</span><strong><?php p($_['success']); ?></strong></div><?php endif; ?>
<?php if (!empty($_['calendarConfigured'])): ?>
<div class="erp-integration-state is-connected erp-wide"><span>✓ Bidirektionaler Abgleich aktiv</span><strong><?php p($_['selectedCalendarName']); ?></strong><small>Betrio → Nextcloud sofort · Nextcloud/Handy → Betrio beim Öffnen oder manuellen Abgleich<?php if (!empty($_['lastCalendarSync'])): ?> · zuletzt <?php p(date('d.m.Y H:i', strtotime((string)$_['lastCalendarSync']))); ?><?php endif; ?></small></div>
<?php if (!empty($_['lastCalendarError'])): ?><div class="erp-notice erp-wide"><strong>Letzter Kalenderfehler:</strong> <?php p($_['lastCalendarError']); ?></div><?php endif; ?>
<?php else: ?>
<div class="erp-notice erp-wide"><strong>Noch kein Nextcloud-Kalender ausgewählt.</strong> Termine bleiben im ERP, bis unter Einstellungen ein Kalender gewählt wurde.</div>
<?php endif; ?>
<form id="teamEventForm" class="erp-inline-form" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveTeamEvent')); ?>">
<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
<input name="title" placeholder="Termin" required>
<label class="erp-field-inline">Beginn<input id="teamEventStart" type="datetime-local" name="startAt" required></label>
<label class="erp-field-inline">Ende<input id="teamEventEnd" type="datetime-local" name="endAt" required></label>
<input name="location" placeholder="Ort">
<input name="description" placeholder="Beschreibung">
<button class="button primary" type="submit">Termin speichern</button>
<div id="teamEventError" class="erp-form-error" hidden></div>
</form>
<div class="erp-table"><table><thead><tr><th>Termin</th><th>Beginn</th><th>Ende</th><th>Ort</th><th>Quelle</th></tr></thead><tbody>
<?php foreach ($_['rows'] as $r): ?><tr>
<td><strong><?php p($r['title']); ?></strong><?php if (!empty($r['description'])): ?><small class="erp-block-muted"><?php p($r['description']); ?></small><?php endif; ?></td>
<td><?php p(date('d.m.Y H:i', strtotime((string)$r['start_at']))); ?></td>
<td><?php p(!empty($r['end_at']) ? date('d.m.Y H:i', strtotime((string)$r['end_at'])) : '—'); ?></td>
<td><?php p($r['location'] ?: '—'); ?></td>
<td><?php if (($r['sync_source'] ?? '') === 'nextcloud'): ?><span class="erp-badge">Handy / Nextcloud</span><?php elseif (!empty($r['calendar_object_uri'])): ?><span class="erp-badge">Betrio → Nextcloud</span><?php else: ?><span class="erp-muted">nur ERP</span><?php endif; ?></td>
</tr><?php endforeach; ?>
<?php if (empty($_['rows'])): ?><tr><td colspan="5" class="erp-empty">Noch keine Termine vorhanden.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
