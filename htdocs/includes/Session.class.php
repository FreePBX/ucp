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
class Session extends UCP {
	private $UCP;
	private $prefix = 'UCP_';
	
	function __construct($UCP) {
		session_start();
		$this->UCP = $UCP;
	}
	
	function generateToken($id='default') {
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION['UCP_'.$id.'_token'] = $token; 
		return $token;
	}
	
	function verifyToken($id='default') {
		if(!isset($_SESSION[$this->prefix.$id.'_token'])) { 
			return false;
		}	
		if(!isset($_POST['token'])) {
			return false;
	    }	
		if ($_SESSION[$this->prefix.$id.'_token'] !== $_POST['token']) {
			return false;
	    }
		//clear the token if it was accepted
		//$_SESSION[$this->prefix.$id.'_token'] = '';
		return true;
	}
	
	public function __isset($name) {
		return isset($_SESSION[$this->prefix.$name]);
	}
	
	public function __get($name) {
		if(isset($_SESSION[$this->prefix.$name])) {
			return $_SESSION[$this->prefix.$name];
		} else {
			return null;
		}
	}
	
	public function __set($name, $value) {
		$_SESSION[$this->prefix.$name] = $value;
	}
}