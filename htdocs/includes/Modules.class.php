<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * Manages UCP Modules.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
include(__DIR__.'/Module_Helpers.class.php');
class Modules extends Module_Helpers {
	// Static Object used for self-referencing.
	private static $obj;
	public static $conf;
	protected $module = null;

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

	/**
	 * Generate the navigation menu
	 */
	public function generateMenu() {
		$menu = array();
		//module with no module folder
		$modules = $this->getModulesByMethod('getMenuItems');
		//Move Home to the Top in the menu structure.
		unset($modules[array_search('Home', $modules)]);
		array_unshift($modules, 'Home');
		foreach($modules as $module) {
			$module = ucfirst(strtolower($module));
			$lc = strtolower($module);
			$mm = $this->$module->getMenuItems();
			if(!empty($mm)) {
				$menu[$lc] = $mm;
			}
		}
		return $menu;
	}

	/**
	 * Check all modules to see if they have the requested method
	 * @param {string} $method method name
	 * @return {array} Hash of the object names
	 */
	public function getModulesByMethod($method) {
		$objects = array();
		foreach (glob(dirname(__DIR__)."/modules/*.class.php") as $module) {
			if(preg_match('/^(.*)\.class$/',pathinfo($module,PATHINFO_FILENAME),$matches)) {
				$module = ucfirst(strtolower($matches[1]));
				if(method_exists($this->$module,$method)) {
					$reflection = new \ReflectionMethod($this->$module, $method);
					if ($reflection->isPublic()) {
						$objects[] = $module;
					}
				}
			}
		}
		//module with module folder
		foreach (glob(dirname(__DIR__)."/modules/*", GLOB_ONLYDIR) as $module) {
			$mod = basename($module);
			if(file_exists($module.'/'.$mod.'.class.php')) {
				$module = ucfirst(strtolower($mod));
				if(method_exists($this->$module,$method)) {
					$reflection = new \ReflectionMethod($this->$module, $method);
					if ($reflection->isPublic()) {
						$objects[] = $module;
					}
				}
			}
		}
		return $objects;
	}

	/**
	 * Get Devices that are assigned to this user
	 */
	function getAssignedDevices() {
		$user = $this->UCP->User->getUser();
		return $user['assigned'];
	}

	/**
	 * Get the default assigned device for this user
	 */
	function getDefaultDevice() {
		$user = $this->UCP->User->getUser();
		return ($user['default_extension'] != 'none') ? $user['default_extension'] : false;
	}

	/** Module Specific Funtions, These should be extended into each module **/

	/**
	 * Get module display
	 */
	public function getDisplay() {
		return '';
	}

	/**
	 * Get menu badge count
	 */
	public function getBadge() {
		return false;
	}

	/**
	 * Get Module Menu Items
	 */
	public function getMenuItems() {
		return array();
	}

	
	public function ajaxRequest($command, $settings) {
		return false;
	}

	public function ajaxHandler() {
		return false;
	}

	public function ajaxCustomHandler() {
		return false;
	}

    public function getActiveModules() {
        return $this->getModulesByMethod('getMenuItems');
    }

    //These Scripts persist throughout the navigation of UCP
    public function getGlobalScripts() {
			$cache = dirname(__DIR__).'/assets/js/compiled';
			if(!file_exists($cache) && !mkdir($cache)) {
				die('Can Not Create Cache Folder at '.$cache);
			}
			$ftime = 0;
			$contents = '';
        $files = array();
        foreach (glob(dirname(__DIR__)."/modules/*", GLOB_ONLYDIR) as $module) {
            $mod = basename($module);
            if(file_exists($module.'/'.$mod.'.class.php')) {
                $module = ucfirst(strtolower($mod));
                $dir = dirname(__DIR__)."/modules/".$module."/assets/js";
                if(is_dir($dir) && file_exists($dir.'/global.js')) {
									$ftime = filemtime($dir.'/global.js') > $ftime ? filemtime($dir.'/global.js') : $ftime;
                    $files[] = 'modules/'.ucfirst($module).'/assets/js/global.js';
										$contents .= file_get_contents($dir.'/global.js')."\n";
                }
            }
        }
				$filename = 'jsphp_'.$ftime.'.js';
				if(!file_exists($cache.'/'.$filename)) {
					$output = \JShrink\Minifier::minify($contents);
					file_put_contents($cache.'/'.$filename,$output);
				}
        return $filename;
    }

		//These Scripts persist throughout the navigation of UCP
		public function getGlobalLess() {
			$cache = dirname(__DIR__).'/assets/css/compiled';
			if(!file_exists($cache) && !mkdir($cache)) {
				die('Can Not Create Cache Folder at '.$cache);
			}
			\Less_Cache::$cache_dir = $cache;
				$files = array();
				foreach (glob(dirname(__DIR__)."/modules/*", GLOB_ONLYDIR) as $module) {
						$mod = basename($module);
						if(file_exists($module.'/'.$mod.'.class.php')) {
								$module = ucfirst(strtolower($mod));
								$dir = dirname(__DIR__)."/modules/".$module."/assets/less";
								if(is_dir($dir) && file_exists($dir.'/bootstrap.less')) {
									$files[$dir."/bootstrap.less"] = '../../../modules/'.ucfirst($module).'/assets';
								}
						}
				}
				$css_file_name = \Less_Cache::Get( $files, array('compress' => true) );
				return $css_file_name;
		}

	protected function load_view($view_filename_protected, $vars = array()) {
		return $this->UCP->View->load_view($view_filename_protected, $vars);
	}

	protected function show_view($view_filename_protected, $vars = array()) {
		return $this->UCP->View->show_view($view_filename_protected, $vars);
	}
}
