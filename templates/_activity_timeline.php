<?php
$activityIcons = [
    'created' => '＋',
    'updated' => '✎',
    'archived' => '▣',
    'status_changed' => '↻',
    'document_uploaded' => '📎',
    'signed' => '✓',
    'finalized' => '🔒',
    'reopened' => '↺',
    'note' => '💬',
];
?>
<?php if (empty($activities)): ?>
    <p class="erp-muted">Noch keine Aktivitäten vorhanden.</p>
<?php else: ?>
    <div class="erp-timeline">
        <?php foreach ($activities as $activity): ?>
            <article class="erp-timeline-item">
                <div class="erp-timeline-icon"><?= p($activityIcons[$activity['action']] ?? '•') ?></div>
                <div class="erp-timeline-content">
                    <div class="erp-timeline-head">
                        <strong><?= p($activity['title']) ?></strong>
                        <time><?= p(date('d.m.Y H:i', strtotime((string)$activity['created_at']))) ?></time>
                    </div>
                    <?php if (!empty($activity['details'])): ?>
                        <p><?= nl2br(p($activity['details'])) ?></p>
                    <?php endif; ?>
                    <small><?= p($activity['display_name'] ?? $activity['created_by']) ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
