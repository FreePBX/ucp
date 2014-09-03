<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
try {
	$userman = FreePBX::Userman();
} catch(\Exception $e) {
	return false;
}
$userman->registerHook('addUser','ucp_hook_userman_addUser');
$userman->registerHook('delUser','ucp_hook_userman_delUser');
$userman->registerHook('updateUser','ucp_hook_userman_updateUser');
$userman->registerHook('welcome','ucp_hook_userman_welcome');

function ucp_hook_userman_welcome($id,$display,$data) {
	return sprintf(_('User Control Panel: %s'),$data['host'].'/ucp');
}

function ucp_hook_userman_addUser($id,$display,$data) {
	if($display == 'extensions' || $display == 'users') {
		FreePBX::create()->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
		$ucp = FreePBX::create()->Ucp;
		$user = $ucp->getUserByID($id);
		if($user['default_extension'] != "none") {
			$ucp->setSetting($user['username'],'Settings','assigned',array($user['default_extension']));
			$ucp->setSetting($user['username'],'Voicemail','assigned',array($user['default_extension']));
		}
	} else {
		ucp_hook_userman_updateUser($id,$display,$data);
	}
}
function ucp_hook_userman_updateUser($id,$display,$data) {
	if($display == 'userman') {
		if(isset($_POST['ucp|login'])) {
			if($_POST['ucp|login'] == 'true') {
				FreePBX::create()->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
			} else {
				FreePBX::create()->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
			}
			$ucp = FreePBX::create()->Ucp;
			$user = $ucp->getUserByID($id);
			if(isset($_POST['ucp|settings'])) {
				$ucp->setSetting($user['username'],'Settings','assigned',$_POST['ucp|settings']);
			} else {
				$ucp->setSetting($user['username'],'Settings','assigned',array());
			}
		}
	} else {
		$allowed = FreePBX::create()->Userman->getModuleSettingByID($id,'ucp|Global','allowLogin');
		if(empty($allowed)) {
			FreePBX::create()->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
		}
	}
	return true;
}

function ucp_hook_userman_delUser($id,$display,$data) {
	$ucp = FreePBX::create()->Ucp;
	$ucp->expireUserSessions($id);
	$ucp->deleteUser($id);
}

function ucp_hook_userman() {
	if(isset($_REQUEST['action'])) {
		$ucp = FreePBX::create()->Ucp;
		switch($_REQUEST['action']) {
			case 'showuser':
				$user = $ucp->getUserByID($_REQUEST['user']);
				if(isset($_POST['submit'])) {
					$ucp->processModuleConfigPages($user);
					$ucp->expireUserSessions($_REQUEST['user']);
				}
				if(!empty($_REQUEST['deletesession'])) {
					$ucp->expireUserSession($_REQUEST['deletesession']);
					$ucp->setUsermanMessage(_('Deleted User Session'),'success');
				}
				$fpbxusers = array();
				$cul = array();
				foreach(core_users_list() as $list) {
					$cul[$list[0]] = array(
						"name" => $list[1],
					);
				}
				$sassigned = $ucp->getSetting($user['username'],'Settings','assigned');
				$sassigned = !empty($sassigned) ? $sassigned : array();
				foreach($user['assigned'] as $assigned) {
					$fpbxusers[] = array("ext" => $assigned, "data" => $cul[$assigned], "selected" => in_array($assigned,$sassigned));
				}
				return load_view(dirname(__FILE__).'/views/users_hook.php',array("fpbxusers" => $fpbxusers, "mHtml" => $ucp->constructModuleConfigPages($user), "user" => $user, "allowLogin" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','allowLogin'), "sessions" => $ucp->getUserSessions($user['id'])));
			break;
			case 'adduser':
				if(isset($_POST['submit'])) {
					$user = $ucp->getUserByUsername($_REQUEST['username']);
					$ucp->processModuleConfigPages($user);
				}
				$fpbxusers = array();
				$cul = array();
				foreach(core_users_list() as $list) {
					$cul[$list[0]] = array(
						"name" => $list[1],
					);
				}

				return load_view(dirname(__FILE__).'/views/users_hook.php',array("fpbxusers" => $fpbxusers, "mHtml" => $ucp->constructModuleConfigPages($user), "user" => array(), "allowLogin" => true, "sessions" => array()));
			break;
			default:
			break;
		}
	}
}
