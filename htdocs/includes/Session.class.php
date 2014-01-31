<?php
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