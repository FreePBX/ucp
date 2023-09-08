<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * View Class for UCP, Generates views. Taken from FreePBX.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
#[\AllowDynamicProperties]
class View extends UCP {
	private $timezone = '';
	private string $dateformat = '';
	private string $datetimeformat = '';
	private string $timeformat = '';

	public function __construct($UCP) {
		$this->UCP = $UCP;
	}

	/**
	 * Load View
	 *
	 * This function is used to load a "view" file. It has two parameters:
	 *
	 * 1. The name of the "view" file to be included.
	 * 2. An associative array of data to be extracted for use in the view.
	 *
	 * NOTE: you cannot use the variable $view_filename_protected in your views!
	 *
	 * @param	string
	 * @param	array
	 * @return	string
	 *
	 */
	public function load_view($view_filename_protected, $vars = []) {

		//return false if we cant find the file or if we cant open it
		if (!$view_filename_protected || !file_exists($view_filename_protected) || !is_readable($view_filename_protected)) {
			dbug('load_view failed to load view for inclusion:', $view_filename_protected);
			return false;
		}

		// Import the view variables to local namespace
		extract((array) $vars, EXTR_SKIP);

		// Capture the view output
		ob_start();

		// Load the view within the current scope
		include($view_filename_protected);

		// Get the captured output
		$buffer = ob_get_contents();

		//Flush & close the buffer
		ob_end_clean();

		//Return captured output
		return $buffer;
	}

	/**
	 * Show View
	 *
	 * This function is used to show a "view" file. It has two parameters:
	 *
	 * 1. The name of the "view" file to be included.
	 * 2. An associative array of data to be extracted for use in the view.
	 *
	 * This simply echos the output of load_view() if not false.
	 *
	 * NOTE: you cannot use the variable $view_filename_protected in your views!
	 *
	 * @param	string
	 * @param	array
	 * @return	string
	 *
	 */
	public function show_view($view_filename_protected, $vars = []) {
		$buffer = $this->load_view($view_filename_protected, $vars);
		if ($buffer !== false) {
			echo $buffer;
		}
	}

	/**
	 * Set Locales for the UCP Interface
	 */
	public function setGUILocales($user) {
		$view = $this->UCP->FreePBX->View;
		// set the language so local module languages take
		$lang = '';
		if (php_sapi_name() !== 'cli') {
			$language = $user ? $this->UCP->FreePBX->Userman->getLocaleSpecificSettingByUID($user['id'], 'language') : '';
			if (!empty($language)) {
				$lang = $language;
			}
			elseif (!empty($_COOKIE['lang'])) {
				$lang = $_COOKIE['lang'];
			}
		}
		$lang = $view->setLanguage($lang);
		if (php_sapi_name() !== 'cli') {
			setcookie("lang", (string) $lang);
			$_COOKIE['lang'] = $lang;
		}
		$language = $lang;
		//set this before we run date functions
		$timezone = $user ? $this->UCP->FreePBX->Userman->getLocaleSpecificSettingByUID($user['id'], 'timezone') : '';
		if (php_sapi_name() !== 'cli' && !empty($timezone)) {
			//userman mode
			$phptimezone = $timezone;
		}
		else {
			$phptimezone = '';
		}
		$this->timezone = $view->setTimezone($phptimezone);

		$datetimeformat = $user ? $this->UCP->FreePBX->Userman->getLocaleSpecificSettingByUID($user['id'], 'datetimeformat') : '';
		if (php_sapi_name() !== 'cli' && !empty($datetimeformat)) {
			$view->setDateTimeFormat($datetimeformat);
		}

		$timeformat = $user ? $this->UCP->FreePBX->Userman->getLocaleSpecificSettingByUID($user['id'], 'timeformat') : '';
		if (php_sapi_name() !== 'cli' && !empty($timeformat)) {
			$view->setTimeFormat($timeformat);
		}

		$dateformat = $user ? $this->UCP->FreePBX->Userman->getLocaleSpecificSettingByUID($user['id'], 'dateformat') : '';
		if (php_sapi_name() !== 'cli' && !empty($dateformat)) {
			$view->setDateFormat($dateformat);
		}

		return [ "timezone" => $timezone, "language" => $language, "datetimeformat" => "", "timeformat" => "", "dateformat" => "" ];
	}

	public function getLocale() {
		return $this->UCP->FreePBX->View->getLocale();
	}

	/**
	 * See function in BMO
	 */
	public function getDate($timestamp = null) {
		return $this->UCP->FreePBX->View->getDate($timestamp);
	}

	/**
	 * See function in BMO
	 */
	public function getDateTime($timestamp = null) {
		return $this->UCP->FreePBX->View->getDateTime($timestamp);
	}

	/**
	 * See function in BMO
	 */
	public function getTime($timestamp = null) {
		return $this->UCP->FreePBX->View->getTime($timestamp);
	}

	/**
	 * See function in BMO
	 */
	public function getDateTimeFormat() {
		return $this->UCP->FreePBX->View->getDateTimeFormat();
	}

	/**
	 * See function in BMO
	 */
	public function getTimeFormat() {
		return $this->UCP->FreePBX->View->getTimeFormat();
	}

	/**
	 * See function in BMO
	 */
	public function getDateFormat() {
		return $this->UCP->FreePBX->View->getDateFormat();
	}

	/**
	 * See function in BMO
	 */
	public function getTimezone() {
		return $this->UCP->FreePBX->View->getTimezone();
	}
}