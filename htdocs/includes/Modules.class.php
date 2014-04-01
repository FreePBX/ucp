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

	public function generateMenu() {
		$menu = array();
		//module with no module folder
        $modules = $this->getModulesByMethod('getMenuItems');
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

	function getAssignedDevices() {
		$user = $this->UCP->User->getUser();
		return $user['assigned'];
	}

	/** Module Specific Funtions, These should be extended into each module **/

	public function getDisplay() {
		return '';
	}

	public function getBadge() {
		return false;
	}

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

    //These Scripts persist throughout the navigation of UCP
    public function getGlobalScripts() {
        foreach (glob(dirname(__DIR__)."/modules/*", GLOB_ONLYDIR) as $module) {
            $mod = basename($module);
            if(file_exists($module.'/'.$mod.'.class.php')) {
                $module = ucfirst(strtolower($mod));
                $dir = dirname(__DIR__)."/modules/".$module."/assets/js";
                $files = array();
                if(is_dir($dir) && file_exists($dir.'/global.js')) {
                    $files[] = 'modules/'.ucfirst($module).'/assets/js/global.js';
                }
            }
        }
        return $files;
    }

	protected function load_view($view_filename_protected, $vars = array()) {
		return $this->UCP->View->load_view($view_filename_protected, $vars);
	}

	protected function show_view($view_filename_protected, $vars = array()) {
		return $this->UCP->View->show_view($view_filename_protected, $vars);
	}

	/** These Functions attempt to load assets automatically for us **/
	protected function loadScripts() {
		$contents = '';
		$dir = dirname(__DIR__)."/modules/".ucfirst($this->module)."/assets/js";
		if(is_dir($dir)) {
			$filenames = glob($dir."/*.js");
			usort($filenames, "strcmp");
			foreach ($filenames as $filename) {
                $path_parts = pathinfo($filename);
                if($path_parts['filename'] != 'global') {
				    $contents .= file_get_contents($filename);
                }
			}
		}
		return "<script>".$contents."</script>";
	}

	protected function loadCSS() {
		$contents = '';
		$dir = dirname(__DIR__)."/modules/".ucfirst($this->module)."/assets/css";
		if(is_dir($dir)) {
			$filenames = glob($dir."/*.css");
			usort($filenames, "strcmp");
			foreach ($filenames as $filename) {
				$contents .= file_get_contents($filename);
			}
		}
		return "<style>".$contents."</style>";
	}

	protected function loadLESS() {
		$contents = '';
		$dir = dirname(__DIR__)."/modules/".ucfirst($this->module)."/assets/less";
		if(is_dir($dir)) {
			$files = array();
			if(!file_exists($dir.'/cache') && !mkdir($dir.'/cache')) {
				die('Can Not Create Cache Folder at '.$dir.'/cache');
			}
			\Less_Cache::$cache_dir = $dir."/cache";
			if(file_exists($dir."/bootstrap.less")) {
				$files = array( $dir."/bootstrap.less" => 'modules/'.ucfirst($this->module).'/assets' );
			} else {
				$filenames = glob($dir."/*.less");
				usort($filenames, "strcmp");
				foreach ($filenames as $filename) {
					$files[$filename] = 'ucp/';
				}
			}
		}

		$css_file_name = \Less_Cache::Get( $files, array('compress' => true) );
		$compiled = file_get_contents( $dir.'/cache/'.$css_file_name );
		return "<style>".$compiled."</style>";
	}
}
