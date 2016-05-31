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

// VIEW_UCP_FOOTER_CONTENT
$set['value'] = 'views/dashfootercontent.php';
$set['defaultval'] =& $set['value'];
$set['readonly'] = 1;
$set['hidden'] = 1;
$set['level'] = 1;
$set['sortorder'] = 355;
$set['module'] = 'ucp'; //This will help delete the settings when module is uninstalled
$set['category'] = 'Styling and Logos';
$set['emptyok'] = 0;
$set['name'] = 'View: UCP dashfootercontent.php';
$set['description'] = 'dashfootercontent.php view. This should never be changed except for very advanced layout changes';
$set['type'] = CONF_TYPE_TEXT;
FreePBX::Config()->define_conf_setting('VIEW_UCP_FOOTER_CONTENT',$set,true);

// UCPRSSFEEDS
$set['value'] = "";
$set['defaultval'] = "";
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 0;
$set['module'] = 'ucp';
$set['category'] = 'User Control Panel';
$set['emptyok'] = 1;
$set['name'] = 'RSS Feeds';
$set['description'] = 'RSS Feeds that are displayed in UCP. This overrides "System Setup" for UCP. If this is blank then the feeds will be taken from RSS Feeds under "System Setup". Separate each feed by a new line';
$set['type'] = CONF_TYPE_TEXTAREA;
FreePBX::Config()->define_conf_setting('UCPRSSFEEDS',$set);

// VIEW_UCP_FOOTER_CONTENT
$set['value'] = 'assets/icons';
$set['defaultval'] =& $set['value'];
$set['readonly'] = 1;
$set['hidden'] = 1;
$set['level'] = 1;
$set['sortorder'] = 355;
$set['module'] = 'ucp'; //This will help delete the settings when module is uninstalled
$set['category'] = 'Styling and Logos';
$set['emptyok'] = 0;
$set['name'] = 'View: UCP icons';
$set['description'] = 'UCP icons folder. This should never be changed except for very advanced layout changes';
$set['type'] = CONF_TYPE_TEXT;
FreePBX::Config()->define_conf_setting('VIEW_UCP_ICONS_FOLDER',$set,true);

//UCPCHANGEUSERNAME
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 1;
$set['module'] = 'ucp'; //This will help delete the settings when module is uninstalled
$set['category'] = 'User Control Panel';
$set['emptyok'] = 0;
$set['name'] = 'Allow Username Changes';
$set['description'] = 'Allow users to change their username in UCP';
$set['type'] = CONF_TYPE_BOOL;
FreePBX::Config()->define_conf_setting('UCPCHANGEUSERNAME',$set,true);

//UCPCHANGEPASSWORD
$set['value'] = true;
$set['defaultval'] =& $set['value'];
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 1;
$set['module'] = 'ucp'; //This will help delete the settings when module is uninstalled
$set['category'] = 'User Control Panel';
$set['emptyok'] = 0;
$set['name'] = 'Allow Password Changes';
$set['description'] = 'Allow users to change thier password in UCP';
$set['type'] = CONF_TYPE_BOOL;
FreePBX::Config()->define_conf_setting('UCPCHANGEPASSWORD',$set,true);

// UCP_SESSION_TIMEOUT
$set['value'] = '30';
$set['defaultval'] =& $set['value'];
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 1;
$set['sortorder'] = 355;
$set['module'] = 'ucp'; //This will help delete the settings when module is uninstalled
$set['category'] = 'User Control Panel';
$set['emptyok'] = 1;
$set['name'] = 'UCP Session Timeout';
$set['description'] = 'The number of days a session token will be valid for. Clear this setting if you want tokens to last forever (Not Recommended)';
$set['type'] = CONF_TYPE_TEXT;
FreePBX::Config()->define_conf_setting('UCPSESSIONTIMEOUT',$set,true);
