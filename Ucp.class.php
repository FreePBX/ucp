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
					} else {
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
					$assigned = !empty($_POST['assigned']) ? $_POST['assigned'] : array();
					if(empty($_POST['prevUsername'])) {
						$this->message = $this->addUser($_POST['username'], $_POST['password'],$assigned);
					} else {
						if($_POST['password'] == '******') {
							$this->message = $this->updateUser($_POST['prevUsername'], $_POST['username'], $assigned);
						} else {
							$this->message = $this->updateUser($_POST['prevUsername'], $_POST['username'], $assigned, $_POST['password']);
						}
					}
				}
				if(!empty($_REQUEST['deletesession'])) {
					$this->message = $this->expireUserSession($_REQUEST['deletesession']);
				}
			break;
		}
	}
	
	public function myShowPage() {
		$html = '';
		$html .= load_view(dirname(__FILE__).'/views/header.php',array());
		
		$users = $this->getAllUsers();
		
		$html .= load_view(dirname(__FILE__).'/views/rnav.php',array("users"=>$users));
		switch($_REQUEST['category']) {
			case 'users':
				if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'showuser' && !empty($_REQUEST['user'])) {
					$user = $this->getUserByID($_REQUEST['user']);
				} else {
					$user = array();
				}
				$fpbxusers = array();
				foreach(core_users_list() as $fpbxuser) {
					$fpbxusers[] = array("data" => $fpbxuser, "selected" => in_array($fpbxuser[0],$user['assigned']));
				}
				$html .= load_view(dirname(__FILE__).'/views/users.php',array("fpbxusers" => $fpbxusers, "user" => $user, "message" => $this->message, "sessions" => $this->getUserSessions($user['id'])));
			break;
			default:
				$html .= load_view(dirname(__FILE__).'/views/main.php',array());
			break;
		}
		$html .= load_view(dirname(__FILE__).'/views/footer.php',array());
		
		return $html;
	}
	
	public function getAllUsers() {
		$sql = "SELECT * FROM ucp_users";
		$users = $this->db->query($sql,PDO::FETCH_ASSOC);
		if(!empty($users)) {
			$final = array();
			foreach($users as $key => $user) {
				$final[$key] = $user;
				$final[$key]['assigned'] = !empty($user['assigned']) ? json_decode($user['assigned'], true) : array();
			}
		}
		return !empty($final) ? $final : array();
	}
	
	public function getUserByUsername($username) {
		$sql = "SELECT * FROM ucp_users WHERE username = :username";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':username' => $username));
		$user = $sth->fetch(PDO::FETCH_ASSOC);
		if(!empty($user)) {
			$user['settings'] = json_decode($user['settings'],true);
			$user['assigned'] = $user['settings']['modules']['Voicemail']['assigned'];
		}
		return $user;
	}
	
	public function getUserByID($id) {
		$sql = "SELECT * FROM ucp_users WHERE id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		$user = $sth->fetch(PDO::FETCH_ASSOC);
		if(!empty($user)) {
			$user['settings'] = json_decode($user['settings'],true);
			$user['assigned'] = $user['settings']['modules']['Voicemail']['assigned'];
		}
		return $user;
	}
	
	public function addUsersToExtension($extension, $users) {
		if(empty($users) || !is_array($users)) {
			return false;
		}
		
		foreach($this->getAllUsers() as $user) {			
			if(in_array($user['id'],$users)) {
				//add
				if(in_array($extension, $user['assigned'])) {
					continue;
				}
				$user['assigned'][] = $extension;
			} else {
				//remove
				if(!in_array($extension, $user['assigned'])) {
					continue;
				}
				$user['assigned'] = array_diff($user['assigned'], array($extension));
			}
			$this->updateUser($user['username'], $user['username'], $user['assigned']);
		}
		return true;
	}
	
	public function getSetting($username,$module,$setting) {
		$user = $this->getUserByUsername($username);
		if(isset($user['settings']['modules'][ucfirst($module)][$setting])) {
			return $user['settings']['modules'][ucfirst($module)][$setting];
		} else {
			return false;
		}
	}
	
	public function setSetting($username,$module,$setting,$value) {
		$user = $this->getUserByUsername($username);
		if(!$user) {
			return false;
		}
		$sql = "UPDATE ucp_users SET `settings` = :settings WHERE `username` = :username";
		$sth = $this->db->prepare($sql);
		$user['modules'][ucfirst($module)][$setting] = $value;
		$settings = json_encode($user);
		$sth->execute(array(':username' => $username, ':settings' => $settings));
	}
	
	public function deleteUser($username) {
		if(!$this->getUserByUsername($prevUsername)) {
			return array("status" => false, "type" => "danger", "message" => _("User Does Not Exist"));
		}
		$sql = "DELETE FROM ucp_users WHERE `username` = :username";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':username' => $username));
		return array("status" => true, "type" => "success", "message" => _("User Successfully Deleted"));
	}
	
	public function addUser($username, $password, $assigned) {
		if($this->getUserByUsername($username)) {
			return array("status" => false, "type" => "danger", "message" => _("User Already Exists"));
		}
		$sql = "INSERT INTO ucp_users (`username`,`password`,`settings`) VALUES (:username,:password,:settings)";
		$sth = $this->db->prepare($sql);
		$array = array();
		$array['modules']['Voicemail']['assigned'] = $assigned;
		$settings = json_encode($array);
		$sth->execute(array(':username' => $username, ':password' => sha1($password), ':settings' => $settings));
		return array("status" => true, "type" => "success", "message" => _("User Successfully Added"));
	}
	
	public function updateUser($prevUsername, $username, $assigned, $password=null) {
		$user = $this->getUserByUsername($prevUsername);
		if(!$user || empty($user)) {
			return array("status" => false, "type" => "danger", "message" => _("User Does Not Exist"));
		}
		$array = array();
		$array['modules']['Voicemail']['assigned'] = $assigned;
		$settings = json_encode($array);
		if(!isset($password)) {
			if($prevUsername != $username || $settings != $user['settings']) {
				$sql = "UPDATE ucp_users SET `username` = :username, `settings` = :settings WHERE `username` = :prevusername";
				$sth = $this->db->prepare($sql);
				$sth->execute(array(':username' => $username, ':prevusername' => $prevUsername, ':settings' => $settings));
			} else {
				return array("status" => true, "type" => "info", "message" => _("Nothing Changed, Did you mean that?"));
			}
		} else {
			if(sha1($password) != $user['password'] || $settings != $user['settings']) {
				$sql = "UPDATE ucp_users SET `username` = :username, `password` = :password, `settings` = :settings WHERE `username` = :prevusername";
				$sth = $this->db->prepare($sql);
				$sth->execute(array(':username' => $username, ':prevusername' => $prevUsername, ':password' => sha1($password), ':settings' => $settings));	
			} else {
				return array("status" => true, "type" => "info", "message" => _("Nothing Changed, Did you mean that?"));
			}
		}
		
		//if username and/or password changed then clear the UCP sessions for this user (which will force a logout)
		if($prevUsername != $username || (isset($password) || sha1($password) != $user['password'])) {
			$this->expireUserSessions($user['id']);
		}
		
		return array("status" => true, "type" => "success", "message" => _("User Successfully Updated"));
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
		$sql = "SELECT id, password FROM ucp_users WHERE username = :username";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':username' => $username));
		$result = $sth->fetch(\PDO::FETCH_ASSOC);
		if(!empty($result) && ($password_sha1 == $result['password'])) {
			return $result['id'];
		}
		return false;
	}
}