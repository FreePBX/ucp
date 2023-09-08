<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * Common Functionality that can be shared between all modules
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
#[\AllowDynamicProperties]
class Template extends UCP {
	function __construct($UCP) {
		$this->UCP = $UCP;
	}

	/**
	 * Generates the Pagnation in bootstrap style
	 * that is at the top and bottom of each page
	 *
	 * @param {int} $total    The total number of pages
	 * @param {int} $current  The current visible page number
	 * @param {string} $link     The link for the href tag that will be appended to
	 * @param {int} $break=10 How many page links to display to the user
	 */
	public function generatePagnation($total, $current, $link, $break = 10) {
		$this->UCP->Modgettext->push_textdomain("ucp");
		$start = (ceil($current / $break) * $break) - ($break - 1);
		$end   = ceil($current / $break) * $break;
		$data  = [ 'startPage' => $start, 'activePage' => $current, 'endPage' => ($end < $total) ? $end : $total, 'totalPages' => $total, 'link' => $link ];
		$html  = $this->UCP->View->load_view(dirname(__DIR__) . '/views/templates/pagnation.php', $data);
		$this->UCP->Modgettext->pop_textdomain();
		return $html;
	}
}