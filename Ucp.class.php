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
		$this->expireAllUserSessions();
		out(_("Refreshing all UCP Assets, this could take a while..."));
		$this->generateUCP(true);
		out("Done!");
	}
	public function uninstall() {
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
		$location = $path.'/ucp';
		unlink($location);
	}
	public function backup(){

	}
	public function restore($backup){

	}

	public function getModulesLanguage($language, $modules) {
		if(!class_exists("po2json")) {
			require_once(__DIR__."/includes/po2json.php");
		}
		$final = array();
		$root = $this->FreePBX->Config->get("AMPWEBROOT");
		foreach ($modules as $module) {
			$module = strtolower($module);
			$po = $root."/admin/modules/".$module."/i18n/" . $language . "/LC_MESSAGES/".$module.".po";
			if(file_exists($po)) {
				$c = new po2json($po,$module);

				$array = $c->po2array();
				if(!empty($array)) {
					$final[$module] = $array;
				}
			}
		}
		return json_encode($final);
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
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
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
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
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
		$this->generateUCP();
	}

	public function generateUCP($regenassets = false) {
		$modulef =& module_functions::create();
		$modules = $modulef->getinfo(false);
		$path = $this->FreePBX->Config->get_conf_setting('AMPWEBROOT');
		$location = $path.'/ucp';
		if(!file_exists($location)) {
			symlink(dirname(__FILE__).'/htdocs',$location);
		}
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
		if($regenassets) {
			$this->refreshAssets();
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
	* Trash all sessions (used for upgrade purposes)
	*/
	public function expireAllUserSessions() {
		$sql = "TRUNCATE TABLE ucp_sessions";
		try {
			$sth = $this->db->prepare($sql);
		} catch(\Exception $e) {}
		return true;
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
	 * Unlock a UCP Session by session token and username
	 * @param {string} $username The username to login to
	 * @param {string} $session  session id
	 */
	public function sessionUnlock($username, $session) {
		$user = $this->getUserByUsername(trim($username));
		if(empty($user["id"])) {
			return false;
		}
		session_id(trim($session));
		session_start();
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION["UCP_token"] = $token;
		session_write_close();
		$this->storeToken($token, $user["id"], "CLI");
		return true;
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
		$sql = "SELECT uid, address FROM ucp_sessions WHERE session = :token";
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

	/**
	 * Enable and Allow all users in User Manager to login to UCP
	 */
	public function enableAllUsers() {
		$userman = $this->FreePBX->Userman;
		foreach($userman->getAllUsers() as $user) {
			if(!empty($user['default_extension']) && $user['default_extension'] != 'none') {
				$ext = $user['default_extension'];
				$userman->setModuleSettingByID($user['id'],'ucp|Global','allowLogin',true);
				$sassigned = $this->getSetting($user['username'],'Settings','assigned');
				if(!in_array($ext, $sassigned)) {
					$this->setSetting($user['username'],'Settings','assigned',array($ext));
				}
				$vassigned = $this->getSetting($user['username'],'Voicemail','assigned');
				if(!in_array($ext, $vassigned)) {
					$this->setSetting($user['username'],'Voicemail','assigned',array($ext));
				}

				$this->setSetting($user['username'],'Presencestate','enabled',true);
			}
		}
		return true;
	}

	public function dashboardService() {
		return array();
		$services = array(
			array(
				'title' => 'UCP Daemon',
				'type' => 'unknown',
				'tooltip' => _("Unknown"),
				'order' => 999,
				'command' => "service ucp status"
			)
		);
		foreach($services as &$service) {
			$output = '';
			exec($service['command']." 2>&1", $output, $ret);
			if ($ret === 0) {
				$service = array_merge($service, $this->genAlertGlyphicon('ok', _("Running")));
				continue;
			}

			$service = array_merge($service, $this->genAlertGlyphicon('warning', $output));
		}

		return $services;
	}

	private function genAlertGlyphicon($res, $tt = null) {
		$glyphs = array(
			"ok" => "glyphicon-ok text-success",
			"warning" => "glyphicon-warning-sign text-warning",
			"error" => "glyphicon-remove text-danger",
			"unknown" => "glyphicon-question-sign text-info",
			"info" => "glyphicon-info-sign text-info",
			"critical" => "glyphicon-fire text-danger"
		);
		// Are we being asked for an alert we actually know about?
		if (!isset($glyphs[$res])) {
			return array('type' => 'unknown', "tooltip" => "Don't know what $res is", "glyph-class" => $glyphs['unknown']);
		}

		if ($tt === null) {
			// No Tooltip
			return array('type' => $res, "tooltip" => null, "glyph-class" => $glyphs[$res]);
		} else {
			// Generate a tooltip
			$html = '';
			if (is_array($tt)) {
				foreach ($tt as $line) {
					$html .= htmlentities($line, ENT_QUOTES)."\n";
				}
			} else {
				$html .= htmlentities($tt, ENT_QUOTES);
			}

			return array('type' => $res, "tooltip" => $html, "glyph-class" => $glyphs[$res]);
		}
		return '';
	}

	public function refreshAssets() {
		include(dirname(__FILE__).'/htdocs/includes/bootstrap.php');
		$ucp = \UCP\UCP::create();

		outn(_("Generating Module Scripts..."));
		$ucp->Modules->getGlobalScripts(true);
		out(_("Done"));

		outn(_("Generating Module CSS..."));
		$ucp->Modules->getGlobalLess(true);
		out(_("Done"));

		outn(_("Generating Main Scripts..."));
		$ucp->getLess(true);
		out(_("Done"));

		outn(_("Generating Main CSS..."));
		$ucp->getScripts(true);
		out(_("Done"));

		exec("amportal chown");
	}
}
