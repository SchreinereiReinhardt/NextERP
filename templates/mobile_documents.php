<?php require __DIR__.'/_mobile_pwa.php'; script('reinhardterp','mobile-documents'); ?>
<?php $p=$_['project'];$url=$_['urlGenerator']; ?><style>
.erp-md{max-width:720px;margin:0 auto;padding:16px 14px 96px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#14213d}.erp-md *{box-sizing:border-box}.erp-md a{text-decoration:none}.erp-md-head{display:flex;gap:12px;align-items:center;margin-bottom:18px}.erp-md-back{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:#e8f3fa;color:#1265d8;font-size:26px}.erp-md-head h1{margin:0;font-size:23px;color:#0b1f55}.erp-md-head p{margin:2px 0;color:#6b7280;font-size:13px}.erp-md-card{display:flex;align-items:center;gap:12px;border:1px solid #dfe5ec;border-radius:16px;background:#fff;padding:12px;margin:8px 0;color:#14213d!important}.erp-md-icon{width:44px;height:44px;border-radius:13px;background:#e8f3fa;color:#1265d8;display:grid;place-items:center;font-size:20px;flex:0 0 44px}.erp-md-card span{min-width:0}.erp-md-card b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.erp-md-card small{color:#6b7280}.erp-md-empty{border:1px dashed #cfd7e3;border-radius:16px;padding:22px;text-align:center;color:#6b7280}.erp-photo-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:9px;margin:14px 0}.erp-photo{border:1px solid #dfe5ec;border-radius:16px;overflow:hidden;background:#fff;color:#14213d!important}.erp-photo-preview{height:135px;background:#e8f3fa;display:grid;place-items:center;font-size:35px;color:#1265d8}.erp-photo b{display:block;padding:9px 10px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.erp-photo small{display:block;padding:0 10px 10px;color:#6b7280}.erp-upload{border:1px solid #dfe5ec;border-radius:18px;padding:14px;background:#fff;margin-bottom:18px}.erp-upload label{display:block;font-size:13px;font-weight:700;margin:8px 0 5px}.erp-upload input[type=file],.erp-upload input[type=text]{width:100%;min-height:46px;border:1px solid #cfd7e3;border-radius:12px;padding:9px;font:inherit}.erp-upload button{width:100%;min-height:50px;border:0;border-radius:13px;background:#1265d8;color:#fff;font-weight:800;font-size:15px;margin-top:10px}
.erp-md-folder{width:100%;text-align:left;font:inherit;cursor:pointer}.erp-md-folder .erp-md-icon{background:#fff4d8;color:#9a6700;font-size:23px}.erp-md-chevron{margin-left:auto;font-size:27px;color:#8b98a9}.erp-md-up{border:0;background:#e8f3fa;color:#1265d8;border-radius:12px;min-height:42px;padding:0 14px;font-weight:800;font-size:14px;margin:0 0 8px}.erp-md-crumb{display:flex;align-items:center;gap:5px;overflow-x:auto;white-space:nowrap;margin:0 0 12px;padding:2px 0 5px}.erp-md-crumb button{border:0;background:transparent;color:#1265d8;padding:5px 3px;font:inherit;font-size:12px;font-weight:700}.erp-md-crumb span{color:#9aa5b1}.erp-md-fileicon{font-size:11px;font-weight:900}.erp-folder-view[hidden]{display:none!important}
</style>
<div id="app-content"><main class="erp-md"><header class="erp-md-head"><a class="erp-md-back" href="<?php p($url->linkToRoute('reinhardterp.business.mobileProject',['id'=>(int)$p['id']])); ?>">‹</a><div><h1>Dokumente</h1><p><?php p(($p['project_no']??'').' · '.($p['title']??''));?></p></div></header>
<?php
$docs = array_values($_['documents'] ?? []);
$tree = ['folders'=>[], 'files'=>[]];
foreach ($docs as $d) {
	$path = trim((string)($d['path'] ?? ''), '/');
	if ($path === '') { continue; }
	$parts = array_values(array_filter(explode('/', $path), static fn($v) => $v !== ''));
	if (!$parts) { continue; }
	$node =& $tree;
	$last = count($parts) - 1;
	foreach ($parts as $i => $part) {
		if ($i === $last) {
			$d['_displayName'] = (string)($d['name'] ?? $part);
			$node['files'][] = $d;
			continue;
		}
		if (!isset($node['folders'][$part])) {
			$node['folders'][$part] = ['folders'=>[], 'files'=>[]];
		}
		$node =& $node['folders'][$part];
	}
	unset($node);
}
function erpMobileFolderCount(array $node): int {
	$count = count($node['files'] ?? []);
	foreach (($node['folders'] ?? []) as $child) { $count += erpMobileFolderCount($child); }
	return $count;
}
function erpMobileRenderFolder(array $node, array $crumbs, $url, int $projectId): void {
	$folderNames = array_keys($node['folders'] ?? []);
	natcasesort($folderNames);
	$files = $node['files'] ?? [];
	usort($files, static fn($a,$b) => strnatcasecmp((string)($a['_displayName']??''),(string)($b['_displayName']??'')));
	$current = implode('/', $crumbs);
	echo '<section class="erp-folder-view" data-folder="'.htmlspecialchars($current,ENT_QUOTES,'UTF-8').'"'.($current!==''?' hidden':'').'>';
	if ($current !== '') {
		$parent = $crumbs; array_pop($parent);
		echo '<button type="button" class="erp-md-up" data-open-folder="'.htmlspecialchars(implode('/',$parent),ENT_QUOTES,'UTF-8').'">‹ Zurück</button>';
		echo '<div class="erp-md-crumb"><button type="button" data-open-folder="">Dokumente</button>';
		$walk=[];
		foreach ($crumbs as $c) {
			$walk[]=$c;
			echo '<span>›</span><button type="button" data-open-folder="'.htmlspecialchars(implode('/',$walk),ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($c,ENT_QUOTES,'UTF-8').'</button>';
		}
		echo '</div>';
	}
	foreach ($folderNames as $folder) {
		$child=$node['folders'][$folder];
		$childPath=$current===''?$folder:$current.'/'.$folder;
		$count=erpMobileFolderCount($child);
		echo '<button type="button" class="erp-md-card erp-md-folder" data-open-folder="'.htmlspecialchars($childPath,ENT_QUOTES,'UTF-8').'"><span class="erp-md-icon">📁</span><span><b>'.htmlspecialchars($folder,ENT_QUOTES,'UTF-8').'</b><small>'.$count.' '.($count===1?'Datei':'Dateien').'</small></span><span class="erp-md-chevron">›</span></button>';
	}
	foreach ($files as $d) {
		$path=(string)($d['path']??'');
		$name=(string)($d['_displayName']??basename($path));
		$mime=(string)($d['mime']??$d['mimetype']??'Datei');
		$href=$url->linkToRoute('reinhardterp.page.projectFile',['id'=>$projectId,'path'=>$path]);
		$icon = str_contains(strtolower($mime),'pdf') ? 'PDF' : (str_starts_with(strtolower($mime),'image/') ? 'IMG' : '▤');
		echo '<a class="erp-md-card" href="'.htmlspecialchars($href,ENT_QUOTES,'UTF-8').'"><span class="erp-md-icon erp-md-fileicon">'.htmlspecialchars($icon,ENT_QUOTES,'UTF-8').'</span><span><b>'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'</b><small>'.htmlspecialchars($mime,ENT_QUOTES,'UTF-8').'</small></span></a>';
	}
	if (!$folderNames && !$files) echo '<div class="erp-md-empty">Dieser Ordner ist leer.</div>';
	echo '</section>';
	foreach ($folderNames as $folder) {
		$childPath=$crumbs; $childPath[]=$folder;
		erpMobileRenderFolder($node['folders'][$folder],$childPath,$url,$projectId);
	}
}
?>
<?php if(!$docs):?><div class="erp-md-empty">Keine freigegebenen Dokumente vorhanden.</div><?php else:?>
<?php erpMobileRenderFolder($tree, [], $url, (int)$p['id']); ?>
<?php endif;?>
</main></div>
<?php $mobileActive='projects'; $mobileProjectId=(int)$p['id']; require __DIR__.'/_mobile_nav.php'; ?>
