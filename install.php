<?php

global $amp_conf;
$location = $amp_conf['AMPWEBROOT'].'/ucp';
if(!file_exists($location)) {
	symlink(dirname(__FILE__).'/htdocs',$location);
}
if(!file_exists(dirname(__FILE__).'/htdocs/assets/framework')) {
	mkdir(dirname(__FILE__).'/htdocs/assets/framework');
}

$links = array('js','css','fonts','images');
foreach($links as $link) {
	if(!file_exists(dirname(__FILE__).'/htdocs/assets/framework/'.$link)) {
		symlink($amp_conf['AMPWEBROOT'].'/admin/assets/'.$link,dirname(__FILE__).'/htdocs/assets/framework/'.$link);
	}
}

$sql = "CREATE TABLE IF NOT EXISTS `ucp_sessions` (
  `session` varchar(255) NOT NULL,
  `uid` 	int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `time` 	int(11) DEFAULT NULL,
  PRIMARY KEY (`session`),
  UNIQUE KEY `session_UNIQUE` (`session`)
);";
$result = $db->query($sql);
if (DB::IsError($result)) {
	die_freepbx($result->getDebugInfo());
}
unset($result);

$sql = "CREATE TABLE IF NOT EXISTS `ucp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `settings` longblob,
  PRIMARY KEY (`id`)
);";
$result = $db->query($sql);
if (DB::IsError($result)) {
	die_freepbx($result->getDebugInfo());
}
unset($result);

if (!$db->getAll('SHOW COLUMNS FROM ucp_users WHERE FIELD = "settings"')) {
    $sql = "ALTER TABLE `ucp_users` CHANGE COLUMN `assigned` `settings` LONGBLOB NULL DEFAULT NULL";
    $result = $db->query($sql);
	$sql = "SELECT id, settings FROM ucp_users";
	$old = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	foreach($old as $list) {
		$array = array();
		$vmsettings = json_decode($list['settings'],true);
		$array['modules']['Voicemail']['assigned'] = $vmsettings;
		$settings = json_encode($array);
		$sql2 = "UPDATE `ucp_users` SET `settings` = '".$settings."' WHERE id = ".$list['id'];
		$result = $db->query($sql2);
		if (DB::IsError($result)) {
			die_freepbx($result->getDebugInfo());
		}
	}
}