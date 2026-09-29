<?php
require __DIR__.'/_nav.php';
use OCP\IURLGenerator;
$url = \OC::$server->get(IURLGenerator::class);
$days=['mondayHours'=>'Mo','tuesdayHours'=>'Di','wednesdayHours'=>'Mi','thursdayHours'=>'Do','fridayHours'=>'Fr','saturdayHours'=>'Sa','sundayHours'=>'So'];
?>
<div id="app-content"><div class="erp-page erp-staff-compact">
<h1>Mitarbeiter</h1><p class="erp-sub"><?php p(!empty($_['canManageTeam'])?'Arbeitszeit- und Urlaubsprofile verwalten.':'Eigenes Arbeitszeit- und Urlaubsprofil.'); ?></p>
<div class="erp-card">
<div class="erp-section-head"><div><h2 class="erp-help-target" data-betrio-help="working-time-profile">Arbeitszeitprofile</h2><p class="erp-muted">Wochen-Soll, Urlaub und Start des Arbeitszeitkontos auf einen Blick.</p></div></div>
<?php if(!empty($_['canManageTeam'])): ?>
<div class="erp-staff-table-wrap"><table class="erp-table erp-staff-table"><thead><tr><th>Mitarbeiter</th><th>Wochen-Soll</th><th>Urlaub/Jahr</th><th>Konto ab</th><th>Startsaldo</th><th></th></tr></thead><tbody>
<?php foreach($_['users'] as $u): $uid=(string)$u['uid']; $p=$_['profiles'][$uid]??[]; $wh=array_pad((array)($p['workdayHours']??[8,8,8,8,8,0,0]),7,0); $weekly=array_sum(array_map('floatval',$wh)); $fid='staff-'.substr(sha1($uid),0,10); ?>
<tr><td><strong><?php p($u['displayName']); ?></strong><br><small class="erp-muted"><?php p($uid); ?></small></td><td><?php p(number_format($weekly,2,',','.')); ?> h</td><td><?php p(number_format((float)($p['annualVacationDays']??30),1,',','.')); ?> Tage</td><td><?php p(!empty($p['timeAccountStartDate'])?date('d.m.Y',strtotime($p['timeAccountStartDate'])):'Noch nicht gesetzt'); ?></td><td><?php p(number_format((float)($p['timeAccountStartBalance']??0),2,',','.')); ?> h</td><td><button type="button" class="button erp-staff-edit" data-target="<?php p($fid); ?>">Bearbeiten</button></td></tr>
<tr id="<?php p($fid); ?>-row" class="erp-staff-editor-row" hidden><td colspan="6"><form id="<?php p($fid); ?>" method="post" action="<?php p($url->linkToRoute('reinhardterp.module.saveStaffProfile')); ?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>"><input type="hidden" name="userId" value="<?php p($uid); ?>">
<div class="erp-staff-editor-head"><strong><?php p($u['displayName']); ?> bearbeiten</strong><span class="erp-muted">Wochen-Soll wird aus den Wochentagen berechnet.</span></div>
<div class="erp-staff-days"><?php $n=0; foreach($days as $name=>$label): ?><label><span><?php p($label); ?></span><input type="number" name="<?php p($name); ?>" min="0" max="24" step="0.25" value="<?php p(number_format((float)$wh[$n++],2,'.','')); ?>"><small>h</small></label><?php endforeach; ?></div>
<div class="erp-staff-meta"><label>Urlaub / Jahr<input type="number" name="annualVacationDays" min="0" max="60" step="0.5" value="<?php p(number_format((float)($p['annualVacationDays']??30),1,'.','')); ?>"></label><label class="erp-help-target" data-betrio-help="working-time-account">Arbeitszeitkonto ab<input type="date" name="timeAccountStartDate" value="<?php p((string)($p['timeAccountStartDate']??'')); ?>"></label><label>Startsaldo (h)<input type="number" name="timeAccountStartBalance" min="-9999" max="9999" step="0.25" value="<?php p(number_format((float)($p['timeAccountStartBalance']??0),2,'.','')); ?>"></label><div class="erp-staff-save"><button class="button primary">Speichern</button></div></div>
</form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php else: ?>
<?php foreach($_['users'] as $u): $uid=(string)$u['uid']; $p=$_['profiles'][$uid]??[]; $wh=array_pad((array)($p['workdayHours']??[8,8,8,8,8,0,0]),7,0); ?>
<div class="erp-staff-self"><h3><?php p($u['displayName']); ?></h3><p><strong><?php p(number_format(array_sum(array_map('floatval',$wh)),2,',','.')); ?> h/Woche</strong> · <?php p(number_format((float)($p['annualVacationDays']??30),1,',','.')); ?> Urlaubstage</p><div class="erp-staff-days readonly"><?php $n=0; foreach($days as $label): ?><span><b><?php p($label); ?></b> <?php p(number_format((float)$wh[$n++],2,',','.')); ?> h</span><?php endforeach; ?></div><p class="erp-muted">Arbeitszeitkonto ab <?php p(!empty($p['timeAccountStartDate'])?date('d.m.Y',strtotime($p['timeAccountStartDate'])):'noch nicht festgelegt'); ?> · Startsaldo <?php p(number_format((float)($p['timeAccountStartBalance']??0),2,',','.')); ?> h</p></div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div>
<script nonce="<?php p($_['cspNonce']??''); ?>">document.querySelectorAll('.erp-staff-edit').forEach(function(b){b.addEventListener('click',function(){var r=document.getElementById(b.dataset.target+'-row');if(!r)return;var opening=r.hidden;document.querySelectorAll('.erp-staff-editor-row').forEach(function(x){x.hidden=true});document.querySelectorAll('.erp-staff-edit').forEach(function(x){x.textContent='Bearbeiten'});r.hidden=!opening;b.textContent=opening?'Schließen':'Bearbeiten';});});</script>
