<?php
style('reinhardterp','style');
script('reinhardterp','project-explorer');
$url=$_['urlGenerator'];$path=trim((string)($_['path']??''),'/');$items=$_['items']??[];$project=$_['project'];
$parts=$path===''?[]:explode('/',$path);$crumb='';
$formatSize=static function(int $b):string{if($b<1024)return $b.' B';if($b<1048576)return number_format($b/1024,1,',','.').' KB';return number_format($b/1048576,1,',','.').' MB';};
$isPreview=static fn(array $x):bool=>strtolower((string)$x['mime'])==='application/pdf'||str_starts_with(strtolower((string)$x['mime']),'image/');
?>
<div id="app-content"><div id="app-content-wrapper"><?php print_unescaped($this->inc('_nav'));?><main class="erp-main erp-explorer-page">
<header class="erp-project-hero"><div><span class="erp-record-kicker">Projektakte</span><h1><?php p($project['project_no'].' · '.$project['title']);?></h1><p class="erp-muted">Dateien und Ordner direkt in Betrio öffnen.</p></div><a class="button" href="<?php p($url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$project['id']]).'#documents');?>">Zur Projektakte</a></header>
<nav class="erp-explorer-breadcrumb"><a href="<?php p($url->linkToRoute('reinhardterp.page.projectExplorer',['id'=>$project['id']]));?>">Projekt</a><?php foreach($parts as $part):$crumb=$crumb===''?$part:$crumb.'/'.$part;?> <span>›</span> <a href="<?php p($url->linkToRoute('reinhardterp.page.projectExplorer',['id'=>$project['id'],'path'=>$crumb]));?>"><?php p($part);?></a><?php endforeach;?></nav>
<section class="erp-card erp-wide">
<?php if($path!==''):?>
<div id="projectExplorerDrop" class="erp-finance-dropzone erp-explorer-drop" data-upload-url="<?php p($url->linkToRoute('reinhardterp.page.uploadProjectExplorerFile',['id'=>$project['id']]));?>" data-path="<?php p($path);?>" data-token="<?php p($_['requesttoken']);?>">
<div class="erp-finance-drop-content"><span class="erp-ui-icon erp-icon-document" aria-hidden="true"></span><strong>Dateien hier reinziehen</strong><span>Mehrere Dateien nacheinander werden automatisch in diesen Projektordner übernommen.</span><button type="button" class="button primary" id="projectExplorerChoose">Dateien auswählen</button><input id="projectExplorerFiles" type="file" multiple hidden></div></div>
<?php else:?><div class="erp-notice">Öffne zuerst einen Projektordner. Dort kannst du Dateien per Drag & Drop hochladen.</div><?php endif;?>
<div class="erp-explorer-toolbar"><div></div><form method="post" action="<?php p($url->linkToRoute('reinhardterp.page.createProjectFolder',['id'=>$project['id']]));?>"><input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>"><input type="hidden" name="path" value="<?php p($path);?>"><input name="name" placeholder="Neuer Ordner" required><button class="button">Ordner anlegen</button></form></div>
<div class="erp-explorer-list"><div class="erp-explorer-row erp-explorer-head"><span>Name</span><span>Typ</span><span>Größe</span><span>Geändert</span></div>
<?php if(empty($items)):?><div class="erp-empty">Dieser Ordner ist leer.</div><?php endif;?>
<?php foreach($items as $item):$rel=$path===''?$item['name']:$path.'/'.$item['name'];?>
<div class="erp-explorer-row"><span class="erp-explorer-name"><?php if($item['isFolder']):?><span class="erp-ui-icon erp-icon-folder"></span><a href="<?php p($url->linkToRoute('reinhardterp.page.projectExplorer',['id'=>$project['id'],'path'=>$rel]));?>"><span class="erp-explorer-item-title"><?php p($item['name']);?></span></a><?php else:?><span class="erp-ui-icon erp-icon-document"></span><span class="erp-explorer-item-title"><?php p($item['name']);?></span><?php endif;?></span><span><?php p($item['isFolder']?'Ordner':($item['mime']?:'Datei'));?></span><span><?php if(!$item['isFolder'])p($formatSize((int)$item['size']));?></span><span><?php p(date('d.m.Y H:i',(int)$item['mtime']));?></span>
<?php if(!$item['isFolder']):?><span class="erp-explorer-actions"><?php $file=$url->linkToRoute('reinhardterp.page.projectFile',['id'=>$project['id'],'path'=>$item['path']]);?><?php if($isPreview($item)):?><button type="button" class="button erp-preview-open" data-src="<?php p($file);?>" data-name="<?php p($item['name']);?>" data-mime="<?php p($item['mime']);?>">Öffnen</button><?php else:?><a class="button" href="<?php p($file);?>">Herunterladen</a><?php endif;?></span><?php endif;?></div><?php endforeach;?></div></section>
<section id="erp-file-preview" class="erp-card erp-wide erp-inline-preview" hidden>
 <div class="erp-preview-head"><div><span class="erp-record-kicker">Dateivorschau</span><strong id="erp-preview-title"></strong></div><button type="button" class="button" id="erp-preview-close">Zurück zu Dateien</button></div>
 <div id="erp-preview-status" class="erp-muted">Datei wird geladen …</div>
 <div id="erp-preview-body"></div>
</section>
</main></div></div>
