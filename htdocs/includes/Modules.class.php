<?php
namespace UCP;
include(__DIR__.'/Module_Helpers.class.php');
class Modules extends Module_Helpers {
	// Static Object used for self-referencing. 
	private static $obj;
	public static $conf;
	
	function __construct($UCP) {
		$this->UCP = $UCP;
		// Ensure the local object is available
		self::$obj = $this;
	}
	
	/**
	 * Alternative Constructor
	 *
	 * This allows the Current BMO to be referenced from anywhere, without
	 * needing to instantiate a new one. Calling $x = FreePBX::create() will
	 * create a new BMO if one has not already beeen created (unlikely!), or
	 * return a reference to the current one.
	 *
	 * @return object FreePBX BMO Object
	 */
	public static function create() {
		if (!isset(self::$obj)) {
			self::$obj = new Modules();
		}
		return self::$obj;
	}
	
	function generateMenu() {
		$menu = array();
		foreach (glob(dirname(__DIR__)."/modules/*.class.php") as $module) {
		    if(preg_match('/^(.*)\.class$/',pathinfo($module,PATHINFO_FILENAME),$matches)) {
		    	$module = ucfirst(strtolower($matches[1]));
				$lc = strtolower($matches[1]);
				$menu[$lc] = $this->$module->getMenuItems();
		    }
		}
		return $menu;
	}
	
	function getAssignedDevices() {
		$user = $this->UCP->User->getUser();
		return $user['assigned'];
	}
}