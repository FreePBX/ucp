<?php
/**
 * This is the User Control Panel Object.
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
 * @package   FreePBX UCP BMO
 * @author   Andrew Nagy <andrew.nagy@schmoozecom.com>
 * @license   AGPL v3
 */
namespace UCP;
class User extends UCP {
	public $uid = null;
	private $cookieName = 'ucp_tokenkey';
	private $token = null;
	
	public function __construct($UCP) {
		$this->UCP = $UCP;
	}
	
	function ajaxRequest($command, $settings) {
		switch($command) {
			case 'login':
				if(!$this->UCP->Session->verifyToken('login')) {
					return false;
				}
			case 'logout':
				return true;
			break;
			case 'getInfo':
				return $this->uid;
				break;
			default:
				return false;
			break;
		}
	}
	
	function ajaxHandler() {
		$return = array("status" => false, "message" => "");	
		switch($_REQUEST['command']) {
			case 'login':
				$rm = isset($_POST['rememberme']) ? true : false;
				$o = $this->login($_POST['username'],$_POST['password'], $rm);
				if(!$o) {
					$return['message'] = _('Invalid Login Credentials');
				} else {
					$return['status'] = true;
				}
				return $return;
			break;
			case 'logout':
				$this->logout();
				return $return['status'] = true;
			break;
			case 'getInfo':
				return $return;
		}
		return false;
	}
	
	public function getUser() {
		return $this->_checkToken() ? $this->FreePBX->Ucp->getUserByID($this->uid) : false;
	}
	
	public function login($username, $password, $remember=false) {
		if(!empty($username) && !empty($password) && $this->_authenticate($username, $password)) {
			if(!$this->_checkToken()) {
				$this->token = $this->_generateToken();
				$this->_storeToken($this->token);
				if($remember) {
					$this->_setCookie($this->token);
				}
				$this->UCP->Session->token = $this->token;
				return true;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	public function logout() {
		if($this->_checkToken()) {
			$token = !empty($this->UCP->Session->token) ? $this->UCP->Session->token : (isset($_COOKIE[$this->cookieName]) ? $_COOKIE[$this->cookieName] : '');
			if(isset($_COOKIE[$this->cookieName])) {
				setcookie($this->cookieName, "", time() - 3600);
			}
			$this->_deleteToken($token);
			$this->uid = null;
			$this->UCP->Session->token = null;
		}
		return true;
	}
	
	private function _setCookie($token) {
		return setcookie($this->cookieName, $token, time()+60*60*24*7);
	}
	
	private function _deleteToken($token) {
		$this->UCP->FreePBX->Ucp->deleteToken($token, $this->uid);
	}
	
	private function _storeToken($token) {
		$this->UCP->FreePBX->Ucp->storeToken($token, $this->uid, $_SERVER['REMOTE_ADDR']);
	}
	
	private function _generateToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
	
	private function _checkToken() {
		$token = !empty($this->UCP->Session->token) ? $this->UCP->Session->token : (isset($_COOKIE[$this->cookieName]) ? $_COOKIE[$this->cookieName] : '');
		if(!empty($token)) {
			$result = $this->UCP->FreePBX->Ucp->getToken($token);
			if(!empty($result['uid'])) {
				$this->_storeToken($token); //update the token time
				$this->uid = $result['uid'];
				return true;
			}
		}
		return false;
	}
	
	private function _authenticate($username, $password) {
		$result = $this->UCP->FreePBX->Ucp->checkCredentials($username, $password);
		if(!empty($result)) {
			$this->uid = $result;
			return true;
		}
		return false;
	}
}