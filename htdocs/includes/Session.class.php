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
	
	/**
	 * Generates and Stores a Form Token for later verification
	 *
	 * Generates a token for use in form submittal to protect against CSRF or XSRF
	 *
	 * @param string $id The Token 'key'
	 * @return string The token
	 */
	function generateToken($id='default') {
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION['UCP_'.$id.'_token'] = $token; 
		return $token;
	}
	
	/**
	 * Verify our Security Token
	 *
	 * Verifies the Security token against the form request
	 *
	 * @param string $id The Token 'key'
	 * @return bool true is passed, false if failure
	 */
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
	
	/**
	 * Magic Function to check if session parameters exist
	 *
	 * This will check if a session parameter exists
	 * Example: $this->Session->variable, variable would be what you are trying to get
	 *
	 * @param string $name The parameter name
	 * @return bool True if it exists
	 */
	public function __isset($name) {
		return isset($_SESSION[$this->prefix.$name]);
	}
	
	/**
	 * Magic Function to get a session parameter
	 *
	 * This will check get a session parameter if it exists
	 * Example: $this->Session->variable, variable would be what you are trying to get
	 *
	 * @param string $name The parameter name
	 * @return mixed Return value if it exists, otherwise null
	 */
	public function __get($name) {
		if(isset($_SESSION[$this->prefix.$name])) {
			return $_SESSION[$this->prefix.$name];
		} else {
			return null;
		}
	}
	
	/**
	 * Magic Function to set a session parameter
	 *
	 * This will set a session parameter's value
	 * Example: $this->Session->variable, variable would be what you are trying to get
	 *
	 * @param string $name The parameter name
	 * @param string $value The parameter value
	 * @return bool true
	 */
	public function __set($name, $value) {
		$_SESSION[$this->prefix.$name] = $value;
		return true;
	}
}