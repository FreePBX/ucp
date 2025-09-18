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
use Emojione\Client;
use Emojione\Ruleset;
include(__DIR__.'/UCP_Helpers.class.php');
class UCP extends UCP_Helpers {
	// Static Object used for self-referencing.
	private static $uobj;

	function __construct($mode = 'local') {
		if($mode == 'local') {
			//Setup our objects for use
			//FreePBX is the FreePBX Object
			$this->FreePBX = \FreePBX::create();
			//UCP is the UCP Specific Object from BMO
			$this->Ucp = $this->FreePBX->Ucp;
			//System Notifications Class
			//TODO: pull this from BMO
			$this->notifications = \notifications::create();
			//database subsystem
			$this->db = $this->FreePBX->Database;
			//This causes crazy errors later on. Dont use it
			//$this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		}

		$this->emoji = new Client(new Ruleset());
		$this->emoji->imageType = 'svg';
		$this->emoji->imagePathSVG = 'assets/images/emoji/svg/'; // defaults to jsdelivr's free CDN

		$this->detect = new \Mobile_Detect;
		// Ensure the local object is available
		self::$uobj = $this;
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
		if (!isset(self::$uobj)) {
			self::$uobj = new UCP();
		}
		return self::$uobj;
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
		$info = $this->FreePBX->Modules->getInfo("Ucp");
		return 'v'.$info['ucp']['dbversion'];
	}

	/**
	* Get a UCP Setting
	* @param string $username The username
	* @param string $module   The module name
	* @param string $setting  The setting key
	*/
	function getCombinedSettingByID($uid,$module,$setting) {
		return $this->FreePBX->Ucp->getCombinedSettingByID($uid,$module,$setting);
	}

	/**
	 * Get a UCP Setting
	 * @param string $username The username
	 * @param string $module   The module name
	 * @param string $setting  The setting key
	 */
	function getSetting($username,$module,$setting) {
		return $this->FreePBX->Ucp->getSetting($username,$module,$setting);
	}

	/**
	 * Get Setting By ID
	 * @param  {[type]}       $id      [description]
	 * @param  {[type]}       $module  [description]
	 * @param  {[type]}       $setting [description]
	 * @return {[type]}                [description]
	 */
	function getSettingByID($id,$module,$setting) {
		return $this->FreePBX->Ucp->getSettingByID($id,$module,$setting);
	}

	/**
	 * [setSettingByID description]
	 * @method setSettingByID
	 * @param  {[type]}       $id      [description]
	 * @param  {[type]}       $module  [description]
	 * @param  {[type]}       $setting [description]
	 * @param  {[type]}       $value   [description]
	 */
	function setSettingByID($id,$module,$setting,$value) {
		return $this->FreePBX->Ucp->setSettingByID($id,$module,$setting,$value);
	}

	function setGlobalSettingByID($id,$setting,$value) {
		return $this->FreePBX->Ucp->setSettingByID($id,'Global',$setting,$value);
	}

	function getGlobalSettingByID($id,$setting) {
		return $this->FreePBX->Ucp->getSettingByID($id,'Global',$setting);
	}

	/**
	 * Set a UCP Setting
	 * @param string $username The username
	 * @param string $module   The module name
	 * @param string $setting  The setting key
	 * @param string $value    the setting value
	 */
	function setSetting($username,$module,$setting,$value) {
		return $this->FreePBX->Ucp->setSetting($username,$module,$setting,$value);
	}

	/**
	 * Get the Node JS Server Settings
	 */
	function getServerSettings() {
		$enabled = $this->FreePBX->Config->get('NODEJSENABLED');
		$enabled = is_bool($enabled) || is_int($enabled) ? $enabled : true;
		$port = $this->FreePBX->Config->get('NODEJSBINDPORT');
		$port = !empty($port) ? $port : 8001;

		$enabledS = $this->FreePBX->Config->get('NODEJSTLSENABLED');
		$enabledS = is_bool($enabledS) || is_int($enabledS) ? $enabledS : true;
		$portS = $this->FreePBX->Config->get('NODEJSHTTPSBINDPORT');
		$portS = !empty($portS) ? $portS : 8003;

		$serverparts = explode(":", $_SERVER['HTTP_HOST']); //strip off port because we define it
		return array("enabled" => $enabled, "port" => $port, "host" => $serverparts[0], "enabledS" => $enabledS, "portS" => $portS);
	}

	/**
	 * These scripts persist throughout the navigation of UCP
	 * Minified all scripts.
	 * @param bool $force Whether to forcefully regenerate the minified JS
	 */
	public function getScripts($force = false,$packaged=false) {
		set_time_limit(0);
		$cache = dirname(__DIR__).'/assets/js/compiled/main';
		if(!file_exists($cache) && !mkdir($cache,0777,true)) {
			die('Can Not Create Cache Folder at '.$cache);
		}

		//Loading order is important here
		$globalJavascripts = array(
			"async-2.1.4.min.js",
			"jquery-migrate-3.0.0.js",
			"socket.io-1.7.2.js",
			"bootstrap-3.3.7.custom.min.js",
			"bootstrap-table-1.11.0.min.js",
			"bootstrap-table-extensions-1.11.0/bootstrap-table-cookie.min.js",
			"bootstrap-table-extensions-1.11.0/bootstrap-table-toolbar.min.js",
			"bootstrap-table-extensions-1.11.0/bootstrap-table-mobile.min.js",
			"bootstrap-table-extensions-1.11.0/bootstrap-table-export.min.js",
			"bootstrap-multiselect-0.9.13.js",
			"bootstrap-select-1.12.1.min.js",
			"ajax-bootstrap-select-1.3.8.min.js",
			"tableexport-3.2.10.min.js",
			"jquery-ui-1.12.1.min.js",
			"fileinput-3.1.3.js",
			"recorder.js",
			"jquery.iframe-transport-9.12.5.js",
			"jquery.fileupload-9.12.5.js",
			"jquery.form-3.51.min.js",
			"jquery.jplayer-2.9.2.min.js",
			"purl-2.3.1.js",
			"modernizr-3.3.1.js",
			"notify-2.0.3.js",
			"class.js",
			"jquery.textfill-0.6.0.min.js",
			"jed-1.1.1.js",
			"modgettext.js",
			"js.cookie-2.1.3.min.js",
			"emojione-2.2.7.min.js",
			"emojionearea-3.1.6.min.js",
			"jquery.tokenize-2.6.js",
			"moment-with-locales-2.20.1.min.js",
			"moment-timezone-with-data-1970-2030-0.5.41.min.js",
			"moment-duration-format-2.2.1.js",
			"nprogress-0.2.0.js",
			"imagesloaded.pkgd-4.1.1.min.js",
			"lodash-4.17.2.js",
			"jquery.ui.touch-punch-0.2.3.min.js",
			"gridstack-0.2.6.js",
			"Sortable-1.10.4.js",
			"bootstrap-toggle-2.2.2.min.js",
			"browser-locale-1.0.0.min.js",
			"uuid-3.0.1.js",
			"ucp.js",
			"module.js"
		);

		$time_start = microtime(true);

		$contents = '';
		$files = array();
		$md5string = '';
		foreach ($globalJavascripts as $f) {
			$file = dirname(__DIR__).'/assets/js/'.$f;
			if(file_exists($file)) {
				$files[] = str_replace(dirname(__DIR__).'/assets/js/','',$file);
				$md5string .= md5_file($file);
			} else {
				throw new \Exception("Cant find $file");
			}
		}

		// If we're not using our minified files, don't make them.
		if (!$packaged) {
			return $files;
		}

		$md5 = md5($md5string);
		$filename = 'jsphpg_'.$md5.'.js';
		if(!file_exists($cache.'/'.$filename) || $force) {
			foreach(glob($cache.'/jsphpg_*.js') as $f) {
				unlink($f);
			}
			foreach($globalJavascripts as $f) {
				$file = dirname(__DIR__).'/assets/js/'.$f;
				$raw = file_get_contents($file);
				if(!preg_match("/min\.js$/",$file)) {
					$contents .= \JShrink\Minifier::minify($raw)."\n\n";
				} else {
					$contents .= $raw."\n\n";
				}
			}
			file_put_contents($cache.'/'.$filename,$contents);
		}

		return array("compiled/main/".$filename);
	}

	/**
	 * Generate and Minify LESS into CSS
	 * These Scripts persist throughout the navigation of UCP
	 * @param bool $force Whether to forcefully regenerate the minified CSS
	 */
	public function getCss($force = false, $packaged=false) {
		set_time_limit(0);
		$cache = dirname(__DIR__).'/assets/css/compiled/main';
		//TODO: needs to be an array of directories that need to be created on install
		if(!file_exists($cache) && !mkdir($cache,0777,true)) {
			die('Can Not Create Cache Folder at '.$cache);
		}
		if($force) {
			foreach(glob($cache.'/lessphp*') as $f) {
				unlink($f);
			}
		}

		$globalCssFiles = array(
			"bootstrap-3.3.7.min.css",
			"bootstrap-toggle-2.2.2.min.css",
			"bootstrap-select-1.12.1.min.css",
			"ajax-bootstrap-select-1.3.8.min.css",
			"emojione-2.2.7.min.css",
			"emojionearea-3.1.6.min.css",
			"font-awesome.min-4.7.0.css",
			"gridstack.min.css",
			"jquery.tokenize-2.6.css"
		);


		$contents = '';
		$files = array();
		$md5string = '';
		foreach($globalCssFiles as $f) {
			$file = dirname(__DIR__).'/assets/css/'.$f;
			if(file_exists($file)) {
				$files[] = str_replace(dirname(__DIR__).'/assets/css/','',$file);
				$md5string .= md5_file($file);
			} else {
				throw new \Exception("Cant find $file");
			}
		}

		$final = array();

		$md5 = md5($md5string);
		$filename = 'cssphpg_'.$md5.'.css';
		if(!file_exists($cache.'/'.$filename) || $force) {
			foreach(glob($cache.'/cssphpg_*.css') as $f) {
				unlink($f);
			}
			foreach($globalCssFiles as $f) {
				$minifier = new \MatthiasMullie\Minify\CSS();
				$file = dirname(__DIR__).'/assets/css/'.$f;
				$raw = file_get_contents($file);
				if(!preg_match("/min\.css$/",$file)) {
					$minifier->add($raw);
					$contents .= $minifier->minify()."\n";
				} else {
					$contents .= $raw."\n";
				}
			}
			file_put_contents($cache.'/'.$filename,$contents);
		}

		$final[] = "compiled/main/".$filename;

		$options = array( 'cache_dir' => $cache );

		$ucpfiles = array();
		$ucpfiles[dirname(__DIR__).'/assets/less/ucp/ucp.less'] = '../../../../';

		$ucpSkinVariables = array();
		if ($this->FreePBX->Modules->checkStatus('oembranding') && 
				($this->FreePBX->Modules->moduleHasMethod('oembranding', 'getUCPSkin'))) {
                        $ucpSkinVariables = \FreePBX::Oembranding()->getUCPSkin();
                }
		$final[] = "compiled/main/".\Less_Cache::Get( $ucpfiles, $options , $ucpSkinVariables);


		$ucpfiles = array();
		$vars = array("fa-font-path" => '"fonts"');
		$ucpfiles[dirname(__DIR__).'/assets/less/schmooze-font/schmooze-font.less'] = '../../';
		$final[] = "compiled/main/".\Less_Cache::Get( $ucpfiles, $options, $vars );

		return $final;
	}
}
