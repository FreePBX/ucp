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
	private $brand = 'FreePBX';
	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		$this->FreePBX = $freepbx;
		$this->Userman = $this->FreePBX->Userman;
		$this->db = $freepbx->Database;

		if (!defined('DASHBOARD_FREEPBX_BRAND')) {
			if (!empty($_SESSION['DASHBOARD_FREEPBX_BRAND'])) {
				define('DASHBOARD_FREEPBX_BRAND', $_SESSION['DASHBOARD_FREEPBX_BRAND']);
			} else {
				define('DASHBOARD_FREEPBX_BRAND', \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND"));
			}
		} else {
			$_SESSION['DASHBOARD_FREEPBX_BRAND'] = DASHBOARD_FREEPBX_BRAND;
		}

		$this->brand = DASHBOARD_FREEPBX_BRAND;
	}

	public function install() {
		$this->expireAllUserSessions();
		out(_("Refreshing all UCP Assets, this could take a while..."));
		$this->generateUCP(true);
		out("Done!");

		//$originate = $this->freepbx->Ucp->getSetting($user['username'],'Webrtc','originate');
		//FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','originate')
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

	public function usermanShowPage() {
		if(isset($_REQUEST['action'])) {
			switch($_REQUEST['action']) {
				case 'showgroup':
				case 'showuser':
					$user = $this->getUserByID($_REQUEST['user']);
					if(isset($_POST['submit']) || isset($_POST['submittype'])) {
						$this->processModuleConfigPages($user);
						$this->expireUserSessions($_REQUEST['user']);
					}
					if(!empty($_REQUEST['deletesession'])) {
						$this->expireUserSession($_REQUEST['deletesession']);
						$this->setUsermanMessage(_('Deleted User Session'),'success');
					}
					$ausers = array();
					$sassigned = $this->getSetting($user['username'],'Settings','assigned');
					if($_REQUEST['action'] == "showgroup") {
						$ausers['self'] = _("User Primary Extension");
					}
					foreach(core_users_list() as $list) {
						$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
					}
					$sassigned = !empty($sassigned) ? $sassigned : array();
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array("ausers" => $ausers, "sassigned" => $sassigned, "mHtml" => $this->constructModuleConfigPages($user,$_REQUEST['action']), "user" => $user, "allowLogin" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','allowLogin'), "originate" => FreePBX::create()->Userman->getModuleSettingByID($_REQUEST['user'],'ucp|Global','originate'), "sessions" => $this->getUserSessions($user['id'])))
						)
					);
				break;
				case 'addgroup':
				case 'adduser':
					if(isset($_POST['submit'])) {
						$user = $this->getUserByUsername($_REQUEST['username']);
						$this->processModuleConfigPages($user);
					}
					$ausers = array();
					if($_REQUEST['action'] == "addgroup") {
						$ausers['self'] = _("User Primary Extension");
					}
					foreach(core_users_list() as $list) {
						$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
					}
					return array(
						array(
							"title" => "UCP",
							"rawname" => "ucp",
							"content" => load_view(dirname(__FILE__).'/views/users_hook.php',array("ausers" => $ausers, "sassigned" => array('self'), "mHtml" => $this->constructModuleConfigPages($user,$_REQUEST['action']), "user" => array(), "allowLogin" => true, "originate" => false, "sessions" => array()))
						)
					);
				break;
				default:
				break;
			}
		}
	}

	/**
	 * Hook functionality for sending an email from userman
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanSendEmail($id, $display, $data) {
		$ports = array();
		if($this->FreePBX->Modules->moduleHasMethod("sysadmin","getPorts")) {
			$ports = \FreePBX::Sysadmin()->getPorts();
		} else {
			if(!function_exists('sysadmin_get_portmgmt') && $this->FreePBX->Modules->checkStatus('sysadmin') && file_exists($this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php')) {
				include $this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php';
			}

			if(function_exists('sysadmin_get_portmgmt')) {
				$ports = sysadmin_get_portmgmt();
			}
		}

		if(!empty($ports['ucp'])) {
			$data['host'] = $data['host'].":".$ports['ucp'];
			$final = array(
				"\t".sprintf(_('User Control Panel: %s'),$data['host']),
			);
			if(!$data['password']) {
				$token = $this->FreePBX->Userman->generatePasswordResetToken($id,"1 hour",true);
				$final[] = "\n".sprintf(_('Password Reset Link (Valid Until: %s): %s'),date("h:i:s A", $token['valid']),$data['host']."/?forgot=".$token['token']);
			}
			return $final;
		}

		$final = array(
			"\t".sprintf(_('User Control Panel: %s'),$data['host']."/ucp"),
		);
		if(!$data['password']) {
			$token = $this->FreePBX->Userman->generatePasswordResetToken($id,"1 hour",true);
			$final[] = "\n".sprintf(_('Password Reset Link (Valid Until: %s): %s'),date("h:i:s A", $token['valid']),$data['host']."/ucp/?forgot=".$token['token']);
		}
		return $final;
	}

	public function validatePasswordResetToken($token) {
		return $this->FreePBX->Userman->validatePasswordResetToken($token);
	}

	public function resetPasswordWithToken($token,$newpassword) {
		return $this->FreePBX->Userman->resetPasswordWithToken($token,$newpassword);
	}

	/**
	* Sends a password reset email
	* @param {int} $id The userid
	*/
	public function sendPassResetEmail($id) {
		global $amp_conf;
		$user = $this->getUserByID($id);
		if(empty($user) || empty($user['email'])) {
			return false;
		}

		$token = $this->Userman->generatePasswordResetToken($id);

		if(empty($token)) {
			return false;
		}

		$user['token'] = $token['token'];
		$user['brand'] = $this->brand;
		$user['host'] = 'http://'.$_SERVER["SERVER_NAME"];
		$user['link'] = $user['host'] . "/ucp/?forgot=".$user['token'];
		$user['valid'] = date("h:i:s A", $token['valid']);

		$ports = array();
		if($this->FreePBX->Modules->moduleHasMethod("sysadmin","getPorts")) {
			$ports = \FreePBX::Sysadmin()->getPorts();
		} else {
			if(!function_exists('sysadmin_get_portmgmt') && $this->FreePBX->Modules->checkStatus('sysadmin') && file_exists($this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php')) {
				include $this->FreePBX->Config()->get('AMPWEBROOT').'/admin/modules/sysadmin/functions.inc.php';
			}

			if(function_exists('sysadmin_get_portmgmt')) {
				$ports = sysadmin_get_portmgmt();
			}
		}

		if(!empty($ports['ucp'])) {
			$user['host'] = $user['host'].":".$ports['ucp'];
			$user['link'] = $user['host'] . "/?forgot=".$user['token'];
		}

		$template = file_get_contents(__DIR__.'/views/emails/reset_text.tpl');
		preg_match_all('/%([\w|\d]*)%/',$template,$matches);
		foreach($matches[1] as $match) {
			$replacement = !empty($user[$match]) ? $user[$match] : '';
			$template = str_replace('%'.$match.'%',$replacement,$template);
		}

		$this->Userman->sendEmail($user['id'],$this->brand . " password reset",$template);
	}

	/**
	 * Hook functionality from userman when a user is deleted
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanDelUser($id, $display, $data) {
		$this->expireUserSessions($id);
		$this->deleteUser($id);
	}

	/**
	 * Hook functionality from userman when a user is added
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanAddUser($id, $display, $data) {
		if($display == 'extensions' || $display == 'users') {
			$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
			$user = $this->getUserByID($id);
			if($user['default_extension'] != "none") {
				$this->setSetting($user['username'],'Settings','assigned',array($user['default_extension']));
				$this->setSetting($user['username'],'Voicemail','assigned',array($user['default_extension']));
			}
		} else {
			$this->usermanUpdateUser($id, $display, $data);
		}
	}

	/**
	 * Hook functionality from userman when a user is updated
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanUpdateUser($id, $display, $data) {
		if($display == 'userman') {
			if(isset($_POST['ucp_login'])) {
				if($_POST['ucp_login'] == 'true') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',true);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
				}
				$user = $this->getUserByID($id);
				if(isset($_POST['ucp_settings'])) {
					$this->setSetting($user['username'],'Settings','assigned',$_POST['ucp_settings']);
				} else {
					$this->setSetting($user['username'],'Settings','assigned',array());
				}
				if($_POST['ucp_originate'] == 'yes') {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',true);
				} else {
					$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','originate',false);
				}
			}
		} else {
			$allowed = $this->FreePBX->Userman->getModuleSettingByID($id,'ucp|Global','allowLogin');
			if(empty($allowed)) {
				$this->FreePBX->Userman->setModuleSettingByID($id,'ucp|Global','allowLogin',false);
			}
		}
		return true;
	}

	/**
	 * Get language from a module and make it json for UCP translations
	 * @param {string} $language The Language name
	 * @param {array} $modules  Array of module rawnames
	 */
	public function getModulesLanguage($language, $modules) {
		if(!class_exists("po2json")) {
			require_once(__DIR__."/includes/po2json.php");
		}
		$final = array();
		$root = $this->FreePBX->Config->get("AMPWEBROOT");
		//first get ucp
		$po = $root."/admin/modules/ucp/i18n/" . $language . "/LC_MESSAGES/ucp.po";
		if(file_exists($po)) {
			$c = new po2json($po,"ucp");
			$array = $c->po2array();
			if(!empty($array)) {
				$final['ucp'] = $array;
			}
		}
		//now get the modules
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
		if(empty($user)){
			return;
		}
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
	function constructModuleConfigPages($user, $action) {
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
						$data = FreePBX::create()->$mod->getUCPAdminDisplay($user, $action);
						if(!empty($data)) {
							foreach($data as $item) {
								if(empty($item)) {
									continue;
								}
								if(is_array($item)) {
									if(!isset($html[$item['rawname']])) {
										$html[$item['rawname']] = array(
											"title" => $item['title'],
											"rawname" => $item['rawname'],
											"content" => $item['content']
										);
									} else {
										$item['rawname']['content'] .= $item['content'];
									}
								} else {
									if(!isset($html[$mod])) {
										$html[$mod] = array(
											"title" => ucfirst(strtolower($mod)),
											"rawname" => $mod,
											"content" => $item
										);
									} else {
										$item[$mod]['content'] .= $item;
									}
								}
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

	/**
	 * Generate UCP assets if needed
	 * @param {bool} $regenassets = false If set to true regenerate assets even if not needed
	 */
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
		$assigned = $this->FreePBX->Userman->getModuleSettingByID($user['id'],'ucp|'.ucfirst(strtolower($module)),$setting,true);
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

	public function getUserByEmail($email) {
		$user = $this->FreePBX->Userman->getUserByEmail($email);
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
	public function sessionUnlock($username, $session, $address = "CLI") {
		$user = $this->getUserByUsername(trim($username));
		if(empty($user["id"])) {
			return false;
		}
		session_id(trim($session));
		session_start();
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION["UCP_token"] = $token;
		session_write_close();
		$this->storeToken($token, $user["id"], $address);
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
	 * @param {string} $password      The password
	 */
	public function checkCredentials($username, $password) {
		return $this->FreePBX->Userman->checkCredentials($username, $password);
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
				if($this->FreePBX->Modules->moduleHasMethod('webrtc','migrationEnable')) {
					$this->FreePBX->Webrtc->migrationEnable($user['username']);
				}
			}
		}
		return true;
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
