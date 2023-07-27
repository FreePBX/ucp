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
namespace UCP\Modules;

use \UCP\Modules as Modules;
use PicoFeed\Reader\Reader;

class Ucptour extends Modules {
	protected $module = 'Ucptour';
	private $user = null;
	private $userId = false;

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->astman  = $this->UCP->FreePBX->astman;
		$this->user    = $this->UCP->User->getUser();
		$this->userId  = $this->user ? $this->user["id"] : false;
	}

	/**
	 * Determine what commands are allowed
	 *
	 * Used by Ajax Class to determine what commands are allowed by this class
	 *
	 * @param string $command The command something is trying to perform
	 * @param string $settings The Settings being passed through $_POST or $_PUT
	 * @return bool True if pass
	 */
	function ajaxRequest($command, $settings) {
		return match ($command) {
			'tour' => true,
			default => false,
		};
	}

	function ajaxHandler() {
		$return = [ "status" => false, "message" => "" ];
		switch ($_REQUEST['command']) {
			case 'tour':
				$state = (boolean) $_POST['state'];
				$this->UCP->FreePBX->Userman->setModuleSettingByID($this->userId, 'ucp|Global', 'tour', $state);
				$return['status'] = true;
				return $return;
				break;
		}
	}

	/**
	 * Send settings to UCP upon initalization
	 */
	function getStaticSettings() {
		$show = $this->UCP->FreePBX->Userman->getCombinedModuleSettingByID($this->userId, 'ucp|Global', 'tour');
		return [ 'show' => is_null($show) ? true : (boolean) $show, 'brand' => $this->UCP->FreePBX->Config->get("DASHBOARD_FREEPBX_BRAND") ];
	}
}