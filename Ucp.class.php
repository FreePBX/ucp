<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Ucp Object, a subset of BMO.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Andrew Nagy <andrew.nagy@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX UCP
 * @author    Andrew Nagy <andrew.nagy@schmoozecom.com>
 * @license   AGPL v3
 */

class Ucp implements BMO {
	private $message;
	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}

	function processModuleConfigPages($user) {
		$modulef =& module_functions::create();
		$modules = $modulef->getinfo(false);
		$path = FreePBX::create()->Config->get_conf_setting('AMPWEBROOT');
		$location = $path . "/admin/modules";
		foreach($modules as $module) {
			if(isset($module['rawname']) && $module['status'] == MODULE_STATUS_ENABLED) {
				$rawname = trim($module['rawname']);
				$mod = ucfirst(strtolower($module['rawname']));
				if(file_exists($location."/".$rawname."/".$mod.".class.php")) {
					if(method_exists(FreePBX::create()->$mod,'processUCPAdminDisplay')) {
						FreePBX::create()->$mod->processUCPAdminDisplay($user);
					}
				}
			}
		}
	}

	function constructModuleConfigPages($user) {
		//module with no module folder
		$html = '';
		$modulef =& module_functions::create();
		$modules = $modulef->getinfo(false);
		$path = FreePBX::create()->Config->get_conf_setting('AMPWEBROOT');
		$location = $path . "/admin/modules";
		foreach($modules as $module) {
			if(isset($module['rawname']) && $module['status'] == MODULE_STATUS_ENABLED) {
				$rawname = trim($module['rawname']);
				$mod = ucfirst(strtolower($module['rawname']));
				if(file_exists($location."/".$rawname."/".$mod.".class.php")) {
					if(method_exists(FreePBX::create()->$mod,'getUCPAdminDisplay')) {
						$html .= FreePBX::create()->$mod->getUCPAdminDisplay($user);
					}
				}
			}
		}
		return $html;
	}

	public function genConfig() {
		$modulef =& module_functions::create();
		$modules = $modulef->getinfo(false);
		$path = FreePBX::create()->Config->get_conf_setting('AMPWEBROOT');
		$location = $path.'/ucp';
		foreach($modules as $module) {
			if(isset($module['rawname'])) {
				$rawname = trim($module['rawname']);
				if(file_exists($path.'/admin/modules/'.$rawname.'/ucp') && file_exists($path.'/admin/modules/'.$rawname.'/ucp/'.ucfirst($rawname).".class.php")) {
					if($module['status'] == MODULE_STATUS_ENABLED) {
						if(!file_exists($location."/modules/".ucfirst($rawname))) {
							symlink($path.'/admin/modules/'.$rawname.'/ucp',$location.'/modules/'.ucfirst($rawname));
						}
					} elseif($module['status'] != MODULE_STATUS_DISABLED && $module['status'] != MODULE_STATUS_ENABLED) {
						if(file_exists($location."/modules/".ucfirst($rawname)) && is_link($location."/modules/".ucfirst($rawname))) {
							unlink($location."/modules/".ucfirst($rawname));
						}
					}
				}
			}
		}
	}

	public function writeConfig($conf){
		$this->FreePBX->WriteConfig($conf);
	}

	public function doConfigPageInit($display) {
		switch($_REQUEST['category']) {
			case 'users':
				if(isset($_POST['submit'])) {
					$user = $this->getUserByID($_REQUEST['user']);
					$this->processModuleConfigPages($user);
					$this->expireUserSessions($_REQUEST['user']);
				}
				if(!empty($_REQUEST['deletesession'])) {
					$this->message = $this->expireUserSession($_REQUEST['deletesession']);
				}
			break;
		}
	}

	public function setUsermanMessage($message,$type="info") {
		$this->FreePBX->Userman->setMessage(_('Deleted User Session'),'success');
		return true;
	}

	public function getSetting($username,$module,$setting) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->getModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting);
		return $assigned;
	}

	public function setSetting($username,$module,$setting,$value) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->setModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting,$value);
		return $assigned;
	}

	public function myShowPage() {
		$category = !empty($_REQUEST['category']) ? $_REQUEST['category'] : '';
		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$html = '';
		$html .= load_view(dirname(__FILE__).'/views/header.php',array());

		$users = $this->getAllUsers();

		$html .= load_view(dirname(__FILE__).'/views/rnav.php',array("users"=>$users));
		switch($action) {
			case 'showuser':
				if(empty($_REQUEST['user'])) {
					$html = _('No User Selected');
					break;
				}
				$user = $this->getUserByID($_REQUEST['user']);
				$html .= load_view(dirname(__FILE__).'/views/users.php',array("mHtml" => $this->constructModuleConfigPages($user), "user" => $user, "message" => $this->message, "sessions" => $this->getUserSessions($user['id'])));
			break;
			default:
				$html .= load_view(dirname(__FILE__).'/views/main.php',array());
			break;
		}
		$html .= load_view(dirname(__FILE__).'/views/footer.php',array());

		return $html;
	}

	public function getAllUsers() {
		$final = $this->FreePBX->Userman->getAllUsers();
		return !empty($final) ? $final : array();
	}

	public function getUserByUsername($username) {
		$user = $this->FreePBX->Userman->getUserByUsername($username);
		if(!empty($user)) {
			$assigned = $this->FreePBX->Userman->getGlobalSettingByID($user['id'],'assigned');
			$user['assigned'] = !empty($assigned) ? $assigned : array();
		}
		return $user;
	}

	public function getUserByID($id) {
		$user = $this->FreePBX->Userman->getUserByID($id);
		if(!empty($user)) {
			$assigned = $this->FreePBX->Userman->getGlobalSettingByID($user['id'],'assigned');
			$user['assigned'] = !empty($assigned) ? $assigned : array();
		}
		return $user;
	}

	//clear all sessions (which will essentially log any user out)
	public function expireUserSessions($uid) {
		$sql = "DELETE FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		return true;
	}

	public function expireUserSession($session) {
		$sql = "DELETE FROM ucp_sessions WHERE session = :session";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':session' => $session));
		return array("status" => true, "type" => "success", "message" => _("Deleted Session"));
	}

	public function getUserSessions($uid) {
		$sql = "SELECT * FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		$sessions = $sth->fetchAll(PDO::FETCH_ASSOC);
		return !empty($sessions) ? $sessions : array();
	}

	public function deleteToken($token, $uid) {
	    $sql = "DELETE FROM ucp_sessions WHERE session = :session AND uid = :uid";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid));
	}

	public function storeToken($token, $uid, $address) {
	    $sql = "INSERT INTO ucp_sessions (session, uid, address, time) VALUES (:session, :uid, :address, :time) ON DUPLICATE KEY UPDATE time = VALUES(time)";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid, ':address' => $address, ':time' => time()));
	}

	public function getToken($token) {
		$sql = "SELECT uid FROM ucp_sessions WHERE session = :token";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':token' => $token));
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}

	public function checkCredentials($username, $password_sha1) {
		return $this->FreePBX->Userman->checkCredentials($username, $password_sha1);
	}
}
