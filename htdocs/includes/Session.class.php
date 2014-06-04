<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * Manages sessions for UCP
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
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
