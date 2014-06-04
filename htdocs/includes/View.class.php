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
class View extends UCP {

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
	function load_view($view_filename_protected, $vars = array()) {

		//return false if we cant find the file or if we cant open it
		if (!$view_filename_protected || !file_exists($view_filename_protected) || !is_readable($view_filename_protected) ) {
			dbug('load_view failed to load view for inclusion:', $view_filename_protected);
			return false;
		}

		// Import the view variables to local namespace
		extract( (array) $vars, EXTR_SKIP);

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
	function show_view($view_filename_protected, $vars = array()) {
		$buffer = $this->load_view($view_filename_protected, $vars);
		if ($buffer !== false) {
			echo $buffer;
		}
	}
}
