<?php

global $amp_conf;
$location = $amp_conf['AMPWEBROOT'].'/ucp';
if(!file_exists($location)) {
	symlink(dirname(__FILE__).'/htdocs',$location);
}

$links = array('js','css','fonts','images');
foreach($links as $link) {
	if(!file_exists(dirname(__FILE__).'/htdocs/global/assets/'.$link)) {
		symlink($amp_conf['AMPWEBROOT'].'/admin/assets/'.$link,dirname(__FILE__).'/htdocs/global/assets/'.$link);
	}
}