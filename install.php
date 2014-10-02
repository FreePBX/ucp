<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
global $amp_conf;

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


if ($db->getAll('SHOW TABLES LIKE "ucp_users"') && !$db->getAll('SHOW COLUMNS FROM ucp_users WHERE FIELD = "settings"')) {
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

if ($db->getAll('SHOW TABLES LIKE "ucp_users"')) {
	$userman = FreePBX::create()->Userman;
	$Ucp = FreePBX::create()->Ucp;
	$sql = "SELECT username,password,settings FROM ucp_users";
	$old = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	foreach($old as $user) {
		$assigned = json_decode($user['settings'],true);
		$ret = $userman->addUser($user['username'], $user['password'],'none','User Migrated from UCP',array(),false);
		if($ret['status']) {
			$userman->setAssignedDevices($ret['id'],$assigned['modules']['Voicemail']['assigned']);
			$userman->setModuleSettingByID($ret['id'],'ucp|Voicemail','assigned',$assigned['modules']['Voicemail']['assigned']);
		}
	}
	$sql = 'DROP TABLE IF EXISTS ucp_users';
	$result = $db->query($sql);
}

switch(true) {
	case FreePBX::Modules()->checkStatus('ucp',MODULE_STATUS_NOTINSTALLED):
		//ok so auto enable UCP for all users
		$ucp = FreePBX::create()->Ucp;
		$ucp->enableAllUsers();
	break;
}
