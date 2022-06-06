<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * User class for the UCP Object.
 * Contains all user data for the logged in user
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
class User {
	public $uid = null;
	private $remembermeCookieName = 'ucp_rememberme';
	private $token = null;
	private $user = null;

	public function __construct($UCP) {
		$this->UCP = $UCP;
	}

	/**
	 * Determine what commands are allowed
	 *
	 * Used by Ajax Class to determine what commands are allowed by this class
	 *
	 * @param string $command The command something is trying to perform
	 * @param string $settings The Settings being passed through $_POST or $_PUT
	 * @return bool True if pass
	 */
	function ajaxRequest($command, $settings) {
		switch($command) {
			case 'token':
				return true;
			break;
			case 'reset':
			case 'forgot':
			case 'login':
				if(!$this->UCP->Session->verifyToken('login')) {
					return false;
				}
			return true;
			break;
			case 'getInfo':
				return $this->uid;
				break;
			case 'saveTemplate':
			case 'resetTemplate':
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	/**
	 * The Handler for all ajax events releated to this class
	 *
	 * Used by Ajax Class to process commands
	 *
	 * @return mixed Output if success, otherwise false will generate a 500 error serverside
	 */
	function ajaxHandler() {
		$return = array("status" => false, "message" => "");
		switch($_REQUEST['command']) {
			case 'forgot':
				if(!empty($_POST['username'])) {
					$user = $this->UCP->FreePBX->Ucp->getUserByUsername($_POST['username']);
					$return['status'] = true;
				} elseif(!empty($_POST['email'])) {
					$user = $this->UCP->FreePBX->Ucp->getUserByEmail($_POST['email']);
					$return['status'] = true;
				} else {
					$return['message'] = _('Nothing was Provided!');
					return $return;
				}
				if(empty($user['email'])) {
					/**
					 * Force the status to True, even if this email is not matched. Any hackers could check this response event if
					 * the message show "Submitted".
					 */
					$return['message'] 	= _('Submitted');
					$return['status'] 	= true;  
					return $return;
				}
				if (!$this->UCP->FreePBX->Ucp->sendPassResetEmail($user['id'])) {
					// For some reason, we weren't able to send the email
					$return['message'] = _('Unable to send Password Reset Email');
					$return['status'] = false;
				}
				return $return;
			break;
			case 'reset':
				if($this->validateResetToken($_POST['ftoken']) && ($_POST['npass1'] == $_POST['npass2'])) {
					$policy = $this->UCP->FreePBX->Userman->password_policies($_POST['npass1']);
					if(!empty($policy) && $policy["status"] == true){
							$return['status'] = $this->UCP->FreePBX->Ucp->resetPasswordWithToken($_POST['ftoken'],$_POST['npass1']);
					}
					else{
							return $policy;
					}
				} else {
					$return['message'] = _("Invalid");
				}
				return $return;
			break;
			case 'login':
				$rm = isset($_POST['rememberme']) ? true : false;
				$o = $this->login($_POST['username'],$_POST['password'], $rm);
				if(!$o) {
					$return['message'] = _('Invalid Login Credentials');
				} else {
					$mods = $this->UCP->Modules->getModulesByMethod('login');
					foreach($mods as $mod) {
						$settings[$mod] = $this->UCP->Modules->$mod->login();
					}
					$token = $this->UCP->Session->token;
					if(!empty($token)) {
						$return['status'] = true;
						$return['token'] = $this->UCP->Session->token;
					} else {
						$return['status'] = false;
					}
				}
				return $return;
			break;
			case 'getInfo':
				return $return;
			break;
			case 'token':
				$token = $this->UCP->Session->token;
				if(!empty($token)) {
					$return['status'] = true;
					$return['token'] = $this->UCP->Session->token;
				} else {
					$return['status'] = false;
				}
				return $return;
			break;
			case 'saveTemplate':
				$tempId = $_REQUEST['tempid'];
				$uId = $_REQUEST['uid'];
				if(!empty($tempId) && !empty($uId)) {
					return $this->saveTemplateById($tempId, $uId);
				}
			break;
			case 'resetTemplate':
				$uId = $_REQUEST['uid'];
				if(!empty($uId)) {
					return $this->resetTemplateById($uId);
				}
			break;
		}
		return false;
	}

	public function validateResetToken($token) {
		return $this->UCP->FreePBX->UCP->validatePasswordResetToken($token);
	}

	public function canChange($setting) {
		switch($setting) {
			case "username":
				return $this->UCP->FreePBX->Config->get('UCPCHANGEUSERNAME') && $this->UCP->FreePBX->Userman->getAuthPermission('modifyUser');
			break;
			case "password":
				return $this->UCP->FreePBX->Config->get('UCPCHANGEPASSWORD') && $this->UCP->FreePBX->Userman->getAuthPermission('changePassword');
			break;
			case "details":
				return $this->UCP->FreePBX->Userman->getAuthPermission('modifyUser');
			break;
			default:
				return false;
		}
	}

	/**
	 * Force a refresh, negating pjax
	 * @return bool True to refresh, false to not
	 */
	public function refresh() {
		if(!empty($this->user)) {
			return $this->UCP->FreePBX->UCP->refreshInterface($this->uid);
		}
		return false;
	}

	/**
	 * Get Logged in user information
	 *
	 * Get's the logged in user's information or false if not logged in
	 *
	 * @return mixed array if logged in, false if not
	 */
	public function getUser() {
		if($this->_checkToken()) {
			if(!empty($this->user)) {
				return $this->user;
			}

			return $this->UCP->FreePBX->Ucp->getUserByID($this->uid);
		}
		return false;
	}

	/**
	 * Login
	 *
	 * Used to log a user in, will first check authentication and then,
	 * depending on the setting of remember will either drop a cookie or work with sessions
	 *
	 * @param string $username The passed username
	 * @param string $password The passed password
	 * @param bool $remember Whether to use cookies or sessions
	 * @return bool True if username and password matched, otherwise false
	 */
	public function login($username, $password, $remember=false, $directlogin = false) {
		if(!empty($username) && !empty($password) && $this->_authenticate($username, $password, $directlogin)) {
			if(!$this->_checkToken()) {
				$this->token = $this->_generateToken();
				$this->_storeToken($this->token);
				if($remember) {
					if(!$this->_setCookie($this->token)) {
						//this doesnt really matter but for sanity sakes it makes me feel better
						$remember = false;
					}
				}
				return true;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Logout
	 *
	 * Used to log a user out, clear all sessions and all cookies
	 *
	 * @return bool True regardless
	 */
	public function logout() {
		if($this->_checkToken()) {
			//TODO: this is done in the javascript
			$mods = $this->UCP->Modules->getModulesByMethod('logout');
			foreach($mods as $mod) {
				$this->UCP->Modules->$mod->logout();
			}
			$token = !empty($this->UCP->Session->token) ? $this->UCP->Session->token : (isset($_COOKIE[$this->remembermeCookieName]) ? $_COOKIE[$this->remembermeCookieName] : '');
			if(isset($_COOKIE[$this->remembermeCookieName])) {
				$this->_deleteCookie();
			}
			$this->_deleteToken($token);
			$this->uid = null;
			$this->user = null;
			unset($_SESSION['id']);
		}
		return true;
	}

	/**
	 * Set Cookie
	 *
	 * Used to set the active token into a cookie
	 * NOTE: We only store the session token into the cookie for security reasons
	 * there is no need to put anything else into the cookie
	 *
	 * @param string $token The token to put into the cookie
	 * @return bool True if cookie was set, otherwise false
	 */
	private function _setCookie($token) {
		return setcookie($this->remembermeCookieName, $token, time()+60*60*24*7);
	}

	/**
	 * Delete Cookie
	 *
	 * Used to delete the active token from a cookie
	 * NOTE: the token will have already been or is about to be deleted from the database
	 * so if this fails, it doesnt matter because the token will be invalid
	 *
	 * @param string $token The token to put into the cookie
	 * @return bool True if cookie was set, otherwise false
	 */
	private function _deleteCookie() {
		return setcookie($this->remembermeCookieName, "", time() - 3600);
	}

	/**
	 * Delete Token from FreePBX
	 *
	 * Delete's the token from FreePBX
	 *
	 * @param string $token The token to delete
	 * @return bool True if token was deleted, otherwise false
	 */
	private function _deleteToken($token) {
		$this->UCP->Session->token = null;
		return $this->UCP->FreePBX->Ucp->deleteToken($token, $this->uid);
	}

	/**
	 * Store Token into FreePBX
	 *
	 * Stores the token into FreePBX
	 *
	 * @param string $token The token to store
	 * @return bool True if token was stored, otherwise false
	 */
	private function _storeToken($token) {
		$this->UCP->Session->token = $token;
		return $this->UCP->FreePBX->Ucp->storeToken($token, $this->uid, $_SERVER['REMOTE_ADDR']);
	}

	/**
	 * Generates a token
	 *
	 * The token is generated from openssl, however it could be md5 as well
	 * because its just a token, it doesnt contain anything useful
	 *
	 * @return string the token
	 */
	private function _generateToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	/**
	 * Check the token from either the session or cookie
	 *
	 * This will attempt to get the session from the session first,
	 * if it can't it will then it will next check the cookie
	 * it will then check to see if the token is valid, which will
	 * return a user session
	 *
	 * @return bool True if token was valid, otherwise false
	 */
	private function _checkToken() {
		$token = !empty($this->UCP->Session->token) ? $this->UCP->Session->token : (isset($_COOKIE[$this->remembermeCookieName]) ? $_COOKIE[$this->remembermeCookieName] : '');
		if(!empty($token)) {
			$result = $this->UCP->FreePBX->Ucp->getToken($token);
			if($result['address'] !='CLI' && $result['address'] != $_SERVER['REMOTE_ADDR']) {
				return false;
			}
			if(!empty($result['uid'])) {
				if(!$this->_allowed($result['uid'])) {
					$this->_deleteToken($token);
					return false;
				}
				$this->_storeToken($token); //update the token time
				$this->uid = $result['uid'];
				return true;
			}
		}
		return false;
	}

	/**
	 * Check the Credentials from FreePBX
	 *
	 * This will check the provided credentials to see if they are valid
	 * We encrypt it here first before passing it to the next step
	 *
	 * @return bool True if credentials were valid, otherwise false
	 */
	private function _authenticate($username, $password, $directlogin = false) {
		if($directlogin) {
			$uData = $this->UCP->FreePBX->Ucp->getUserByUsername($username);
			$result = $uData['id'];
		} else{
			$result = $this->UCP->FreePBX->Ucp->checkCredentials($username, $password);
		}
		if(!empty($result) && $this->_allowed($result)) {
			$this->uid = $result;
			if(function_exists('freepbx_log_security')) {
				freepbx_log_security('Authentication successful for '.(!empty($username) ? $username : 'unknown').' from '.$_SERVER['REMOTE_ADDR']);
			}
			return true;
		}
		if(function_exists('freepbx_log_security')) {
			freepbx_log_security('Authentication failure for '.(!empty($username) ? $username : 'unknown').' from '.$_SERVER['REMOTE_ADDR']);
		}
		return false;
	}

	/**
	 * Check to make sure login is allowed
	 * @param  {int} $uid The user ID
	 * @return {bool}      True if allowed
	 */
	private function _allowed($uid) {
		$status = $this->UCP->getCombinedSettingByID($uid,'Global','allowLogin');
		return !empty($status) ? $status : false;
	}

	private function getTemplateCreatorUid(){
		$key = $this->UCP->FreePBX->Userman->getConfig('unlockkey','templatecreator');
		if($key !=''){
			$uid = $this->UCP->FreePBX->Userman->getConfig($key,'templatecreator');
			return $uid;
		}
		return false;
	}
	public function getUserInfo($key) {
		$uid = $this->UCP->FreePBX->Ucp->getUserIdByKey($key);
		if(!empty($uid)) {
			$user = $this->UCP->FreePBX->Ucp->getUserByID($uid);
			return $user;
		}
		return false;
	}

	public  function getTemplateIdUsingKey($key) {
		$tempId = $this->UCP->FreePBX->Userman->getTemplateIdByKey($key);
		return $tempId;
	}

	public function saveTemplateById($tempId, $uid) {
		$ret = $this->UCP->FreePBX->Userman->addTemplateSettings($tempId, $uid);
		return $ret;
	}

	public function resetTemplateById($uid) {
		$ret = $this->UCP->FreePBX->Userman->resetUserTemplateFromUCP($uid);
		return $ret;
	}
}
