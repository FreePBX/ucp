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
			//TODO: pull this from BMO
			$this->notifications = \notifications::create();
			//database subsystem
			$this->db = $this->FreePBX->Database;
			//This causes crazy errors later on. Dont use it
			//$this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		}

		$this->detect = new \Mobile_Detect;
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
		return $this->FreePBX->UCP->getCombinedSettingByID($uid,$module,$setting);
	}

	/**
	 * Get a UCP Setting
	 * @param string $username The username
	 * @param string $module   The module name
	 * @param string $setting  The setting key
	 */
	function getSetting($username,$module,$setting) {
		return $this->FreePBX->UCP->getSetting($username,$module,$setting);
	}

	/**
	 * Set a UCP Setting
	 * @param string $username The username
	 * @param string $module   The module name
	 * @param string $setting  The setting key
	 * @param string $value    the setting value
	 */
	function setSetting($username,$module,$setting,$value) {
		return $this->FreePBX->UCP->setSetting($username,$module,$setting,$value);
	}

	/**
	 * Get the Node JS Server Settings
	 */
	function getServerSettings() {
		if(!$this->FreePBX->Modules->checkStatus('ucpnode')) {
			return array("enabled" => false, "port" => "0", "host" => "");
		}
		$enabled = $this->FreePBX->Config->get('NODEJSENABLED');
		$enabled = is_bool($enabled) || is_int($enabled) ? $enabled : true;
		$port = $this->FreePBX->Config->get('NODEJSBINDPORT');
		$port = !empty($port) ? $port : 8001;
		$serverparts = explode($_SERVER['HTTP_HOST'], ":"); //strip off port because we define it
		return array("enabled" => $enabled, "port" => $port, "host" => $serverparts[0]);
	}

	/**
	 * These scripts persist throughout the navigation of UCP
	 * Minified all scripts.
	 * @param bool $force Whether to forcefully regenerate the minified JS
	 */
	public function getScripts($force = false) {
		$cache = dirname(__DIR__).'/assets/js/compiled/main';
		if(!file_exists($cache) && !mkdir($cache,0777,true)) {
			die('Can Not Create Cache Folder at '.$cache);
		}

		//Loading order is important here
		$globalJavascripts = array(
			"socket.io.js",
			"bootstrap-3.3.5.custom.min.js",
			"bootstrap-table-1.9.0.js",
			"bootstrap-table-cookie.js",
			"bootstrap-table-toolbar.js",
			"bootstrap-table-mobile.js",
			"jquery-ui-1.10.4.custom.min.js",
			/*"jquery.keyframes.min.js",*/
			"fileinput.js",
			"recorder.js",
			"jquery.iframe-transport.js",
			"jquery.fileupload.js",
			"jquery.form.min.js",
			"jquery.jplayer.min.js",
			/*"quo.js",*/
			"localforage.js",
			"purl.js",
			"modernizr.js",
			"jquery.pjax.js",
			"notify.js",
			"packery.pkgd.min.js",
			"class.js",
			/*"jquery.transit.min.js",*/
			"date.format.js",
			"jquery.textfill.min.js",
			"jed.js",
			"modgettext.js",
			"jquery.cookie.js",
			"emojione.min.js",
			"jquery.tokenize.js",
			"ucp.js",
			"module.js"
		);
		$contents = '';
		$files = array();
		foreach ($globalJavascripts as $f) {
			$file = dirname(__DIR__).'/assets/js/'.$f;
			if(file_exists($file)) {
				$files[] = $file;
				$contents .= file_get_contents($file)."\n\n";
			}
		}

		$md5 = md5($contents);
		$filename = 'jsphpg_'.$md5.'.js';
		if(!file_exists($cache.'/'.$filename) || $force) {
			foreach(glob($cache.'/jsphp_*.js') as $f) {
				unlink($f);
			}
			$output = \JShrink\Minifier::minify($contents);
			file_put_contents($cache.'/'.$filename,$output);
		}

		return $filename;
	}

	/**
	 * Generate and Minify LESS into CSS
	 * These Scripts persist throughout the navigation of UCP
	 * @param bool $force Whether to forcefully regenerate the minified CSS
	 */
	public function getLess($force = false) {
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

		$options = array( 'cache_dir' => $cache );

		$final = array();
		//Needs to be one unified LESS file along with the module LESS file

		$ucpfiles = array();
		$ucpfiles[dirname(__DIR__).'/assets/less/ucp/ucp.less'] = '../../../../';
		$final['ucpcssless'] = \Less_Cache::Get( $ucpfiles, $options );

		$ucpfiles = array();
		$vars = array("fa-font-path" => '"fonts"');
		$ucpfiles[dirname(__DIR__).'/assets/less/schmooze-font/schmooze-font.less'] = '../../';
		$final['sfcssless'] = \Less_Cache::Get( $ucpfiles, $options, $vars );

		return $final;
	}
}
