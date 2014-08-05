<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

class Ucp implements BMO {
	private $message;
	private $registeredHooks = array();
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

	/**
	 * Register a hook from another module
	 * This is semi depreciated as FreePBX 12 has hooking functions now
	 * @param {string} $action The action
	 * @param {string} $class  The class name
	 * @param {string} $method The method name
	 */
	public function registerHook($action,$class,$method) {
		$this->registeredHooks[$action] = array('class' => $class, 'method' => $method);
	}

	/**
	 * Process Module Configuration Pages
	 * This is used in userman to pass settings to submodules of UCP
	 * @param {array} $user The userman user array
	 */
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

	/**
	 * Construct Module Configuration Pages
	 * This is used to setup and display module configuration pages
	 * in User Manager
	 * @param {array} $user The user array
	 */
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
						$data = FreePBX::create()->$mod->getUCPAdminDisplay($user);
						if(isset($data['content'])) {
							$html[] = array(
								'description' => $data['description'],
								'content' => $data['content']
							);
						} elseif(isset($data[0]['content'])) {
							foreach($data as $item) {
								$html[] = array(
									'description' => $item['description'],
									'content' => $item['content']
								);
							}
						}
					}
				}
			}
		}
		return $html;
	}

	/**
	 * Retrieve Conf Hook to search all modules and add their respective UCP folders
	 */
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

	public function deleteUser($uid) {
		//run module functions here if needed otherwise usermanager delete's most of what we are using
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

	/**
	 * Sets a message in user manager
	 * @param {string} $message     The message
	 * @param {string} $type="info" The message type
	 */
	public function setUsermanMessage($message,$type="info") {
		$this->FreePBX->Userman->setMessage(_('Deleted User Session'),'success');
		return true;
	}

	/**
	 * Get Setting from Userman
	 * @param {string} $username The username
	 * @param {string} $module   The module name
	 * @param {string} $setting  The setting name
	 */
	public function getSetting($username,$module,$setting) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->getModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting);
		return $assigned;
	}

	/**
	 * Set a Setting
	 * @param {string} $username The userman
	 * @param {string} $module   The module name
	 * @param {string} $setting  The setting
	 * @param {mixed} $value    The value
	 */
	public function setSetting($username,$module,$setting,$value) {
		$user = $this->getUserByUsername($username);
		$assigned = $this->FreePBX->Userman->setModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting,$value);
		return $assigned;
	}

	/**
	 * Get all Userman Users
	 */
	public function getAllUsers() {
		$final = $this->FreePBX->Userman->getAllUsers();
		return !empty($final) ? $final : array();
	}

	/**
	 * Get User by Username
	 * @param {string} $username The username
	 */
	public function getUserByUsername($username) {
		$user = $this->FreePBX->Userman->getUserByUsername($username);
		if(!empty($user)) {
			$assigned = $this->FreePBX->Userman->getGlobalSettingByID($user['id'],'assigned');
			$user['assigned'] = !empty($assigned) ? $assigned : array();
		}
		return $user;
	}

	/**
	 * Get user by user id
	 * @param {int} $id The user id
	 */
	public function getUserByID($id) {
		$user = $this->FreePBX->Userman->getUserByID($id);
		if(!empty($user)) {
			$assigned = $this->FreePBX->Userman->getGlobalSettingByID($user['id'],'assigned');
			$user['assigned'] = !empty($assigned) ? $assigned : array();
		}
		return $user;
	}

	/**
	 * Clear all sessions (which wille ssentially log any user out)
	 * @param {[type]} $uid [description]
	 */
	public function expireUserSessions($uid) {
		$sql = "DELETE FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		return true;
	}

	/**
	 * Expire User Session
	 * @param {string} $session The session name
	 */
	public function expireUserSession($session) {
		$sql = "DELETE FROM ucp_sessions WHERE session = :session";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':session' => $session));
		return array("status" => true, "type" => "success", "message" => _("Deleted Session"));
	}

	/**
	 * Get all user sessions
	 * @param {int} $uid The user ID
	 */
	public function getUserSessions($uid) {
		$sql = "SELECT * FROM ucp_sessions WHERE uid = :uid";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':uid' => $uid));
		$sessions = $sth->fetchAll(PDO::FETCH_ASSOC);
		return !empty($sessions) ? $sessions : array();
	}

	/**
	 * Delete a single user session
	 * @param {string} $token The string token
	 * @param {int} $uid   The user id
	 */
	public function deleteToken($token, $uid) {
		$sql = "DELETE FROM ucp_sessions WHERE session = :session AND uid = :uid";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid));
	}

	/**
	 * Store a token, assigned to a session
	 * @param {string} $token   The token
	 * @param {int} $uid     The user ID
	 * @param {string} $address The IP address
	 */
	public function storeToken($token, $uid, $address) {
		$sql = "INSERT INTO ucp_sessions (session, uid, address, time) VALUES (:session, :uid, :address, :time) ON DUPLICATE KEY UPDATE time = VALUES(time)";
		$sth = $this->db->prepare($sql);
		return $sth->execute(array(':session' => $token, ':uid' => $uid, ':address' => $address, ':time' => time()));
	}

	/**
	 * Get token
	 * @param {string} $token The token name
	 */
	public function getToken($token) {
		$sql = "SELECT uid FROM ucp_sessions WHERE session = :token";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':token' => $token));
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Check credentials through userman
	 * @param {string} $username      The username
	 * @param {string} $password_sha1 The sha1 password hash
	 */
	public function checkCredentials($username, $password_sha1) {
		return $this->FreePBX->Userman->checkCredentials($username, $password_sha1);
	}
}
