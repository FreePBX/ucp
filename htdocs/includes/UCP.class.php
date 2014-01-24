<?php
namespace UCP;
include(__DIR__.'/UCP_Helpers.class.php');
class UCP extends UCP_Helpers {
	// Static Object used for self-referencing. 
	private static $obj;
	public static $conf;
	
	function __construct($mode='local') {
		if($mode == 'local') {
			//Setup our objects for use
			//FreePBX is the FreePBX Object
			$this->FreePBX = \FreePBX::create();
			//UCP is the UCP Specific Object from BMO
			$this->Ucp = $this->FreePBX->Ucp;
			//System Notifications Class
			$this->notifications = \notifications::create();
		}
		
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
	public static function create($mode='local') {
		if (!isset(self::$obj)) {
			self::$obj = new UCP($mode);
		}
		return self::$obj;
	}
}