<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * This is the whole shebang. Here she is in all of her glory
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
include(__DIR__.'/UCP_Helpers.class.php');
class UCP extends UCP_Helpers {
	// Static Object used for self-referencing.
	private static $obj;
	public static $conf;

	function __construct($mode = 'local') {
		if($mode == 'local') {
			//Setup our objects for use
			//FreePBX is the FreePBX Object
			$this->FreePBX = \FreePBX::create();
			//UCP is the UCP Specific Object from BMO
			$this->Ucp = $this->FreePBX->Ucp;
			//System Notifications Class
			$this->notifications = \notifications::create();
			//database subsystem
			$this->db = $this->FreePBX->Database;
			$this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		}

		// Ensure the local object is available
		self::$obj = $this;
	}

	/**
	 * Alternative Constructor
	 *
	 * This allows the Current UCP to be referenced from anywhere, without
	 * needing to instantiate a new one. Calling $x = UCP::create() will
	 * create a new UCP if one has not already beeen created (unlikely!), or
	 * return a reference to the current one.
	 *
	 * @return object FreePBX UCP Object
	 */
	public static function create() {
		if (!isset(self::$obj)) {
			self::$obj = new UCP();
		}
		return self::$obj;
	}

	/**
	 * Get the UCP Version
	 *
	 * In accordance with pjax, when the version changes here it will force refresh
	 * the entire page, instead of just the container, when content is retrieved this
	 * will force the client to get new html assets, this version will then be placed
	 * in a meta tag
	 *
	 * https://github.com/defunkt/jquery-pjax#layout-reloading
	 *
	 * @return string The version
	 */
	function getVersion() {
		return 'v1.3';
	}

	function getSetting($username,$module,$setting) {
		return $this->FreePBX->UCP->getSetting($username,$module,$setting);
	}

	function setSetting($username,$module,$setting,$value) {
		return $this->FreePBX->UCP->setSetting($username,$module,$setting,$value);
	}
}
