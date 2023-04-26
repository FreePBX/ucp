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

class Settings extends Modules{
	protected $module = 'Settings';

	function __construct($Modules) {
		$this->Modules = $Modules;
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
		switch($command) {
			case 'settings':
				return true;
			break;
		}
	}

	/**
	* The Handler for all ajax events releated to this class
	*
	* Used by Ajax Class to process commands
	*
	* @return mixed Output if success, otherwise false will generate a 500 error serverside
	*/
	function ajaxHandler() {
		$return = array("status" => false, "message" => "");
		switch($_REQUEST['command']) {
			case 'settings':
				$user = $this->UCP->User->getUser();
				if(($_POST['key'] == 'username' || $_POST['key'] == 'password') && !$this->UCP->User->canChange($_POST['key'])) {
					return array(
						"status" => false
					);
				}
				switch($_POST['key']) {
					case 'fname':
					case 'lname':
					case 'email':
					case 'title':
					case 'company':
					case 'fax':
					case 'cell':
					case 'displayname':
					case 'work':
					case 'home':
						$val = htmlentities(strip_tags($_POST['value']));
						$OtherInputValues = (isset($_POST['OtherInputValues']) && is_array($_POST['OtherInputValues']))? $_POST['OtherInputValues']: [];
						$postFieldValues = (count($OtherInputValues) > 0 )? $OtherInputValues: array($_POST['key'] => $val);
						if (!in_array($_POST['key'], $postFieldValues)) { 
							$postFieldValues[$_POST['key']] = $val;
						}
						$this->UCP->FreePBX->Userman->updateUserExtraData($user['id'],$postFieldValues);
						$ret = array(
							"status" => true
						);
					break;
					case 'notifications':
					break;
					case 'usernamecheck':
						$val = htmlentities(strip_tags($_POST['value']));
						$user = $this->UCP->FreePBX->Userman->getUserByUsername($val);
						$ret = array(
							"status" => empty($user)
						);
					break;
					case 'username':
						$val = htmlentities(strip_tags($_POST['value']));
						$status = $this->UCP->FreePBX->Userman->updateUser($user['id'],$user['username'], $val, $user['default_extension'], $user['description']);
						$ret = array(
							"status" => $status['status']
						);
					break;
					case 'password':
						$status = $this->UCP->FreePBX->Userman->updateUser($user['id'],$user['username'], $user['username'], $user['default_extension'], $user['description'], $user, $_POST['value']);
						$ret = array(
							"status" => $status['status'],
							"message" => $this->UCP->View->load_view(__DIR__.'/views/pwd_alert.php',array("message" => $status['message'])),
						);
					break;
					case 'timezone':
					case 'language':
					case 'timeformat':
					case 'dateformat':
					case 'datetimeformat':
						$val = !empty($_POST['value']) ? htmlentities(strip_tags($_POST['value'])) : null;
						$status = $this->UCP->FreePBX->Userman->updateUserExtraData($user['id'], array($_POST['key'] => $val));
						$ret = array(
							"status" => $status
						);
					break;
					default:
						$ret = array(
							"status" => false,
							"message" => 'Invalid Parameter'
						);
				}
				return $ret;
			break;
		}
		return $return;
	}

	public function getSimpleWidgetSettingsDisplay($id) {
		$user = $this->UCP->User->getUser();
		if(empty($user)) {
			return array();
		}
		$lang = $this->UCP->View->getLocale();
		$displayvars = array();
		$displayvars['desktop'] = (!$this->UCP->Session->isMobile && !$this->UCP->Session->isTablet);
		$displayvars['lang'] = $lang;
		$displayvars['user'] = $user;
		$displayvars['languages'] = array(
			'en_US' => _('English'). " (US)"
		);
		foreach(glob($this->UCP->FreePBX->Config()->get('AMPWEBROOT')."/admin/modules/ucp/i18n/*",GLOB_ONLYDIR) as $langDir) {
			$l = basename($langDir);
			$displayvars['languages'][$l] = function_exists('locale_get_display_name') ? locale_get_display_name($l, $lang) : $l;
		}
		$displayvars['changepassword'] = $this->UCP->User->canChange("password");
		$displayvars['changeusername'] = $this->UCP->User->canChange("username");
		$displayvars['changedetails'] = $this->UCP->User->canChange("details");
		$displayvars['username'] = $user['username'];
		if($this->UCP->Modules->moduleHasMethod('Contactmanager', 'userDetails')) {
			$displayvars['contactmanager'] = array(
				"status" => true,
				"data" => $this->UCP->Modules->Contactmanager->userDetails()
			);
		} else {
			$displayvars['contactmanager'] = array(
				"status" => false
			);
		}

		$modules = $this->UCP->Modules->getModulesByMethod('getUserSettingsDisplay');
		$displayvars['extra'] = [];
		foreach($modules as $module) {
			$out = $this->UCP->Modules->$module->getUserSettingsDisplay();
			if(!empty($out)) {
				foreach($out as $o) {
					if(!empty($o)) {
						$displayvars['extra'][$module][] = $o;
					}
				}
			}
		}

		$displayvars['placeholders'] = array(
			'language' => $this->getPlaceholder($user['id'], 'language'),
			'timezone' => $this->getPlaceholder($user['id'], 'timezone'),
			'dateformat' => $this->getPlaceholder($user['id'], 'dateformat'),
			'timeformat' => $this->getPlaceholder($user['id'], 'timeformat'),
			'datetimeformat' => $this->getPlaceholder($user['id'], 'datetimeformat')
		);

		$display = array(
			'title' => _("User"),
			'html' => $this->UCP->View->load_view(__DIR__.'/views/settings.php',$displayvars)
		);

		return $display;
	}

	private function getPlaceholder($uid, $type) {
		$res = $this->UCP->FreePBX->Userman->getLocaleSpecificGroupSettingByUID($uid, $type);
		if(empty($res)) {
			switch($type) {
				case 'language':
					return $this->UCP->FreePBX->Config->get("UIDEFAULTLANG");
				break;
				case 'timezone':
					return $this->UCP->FreePBX->Config->get('PHPTIMEZONE');
				break;
				case 'dateformat':
					return $this->UCP->FreePBX->Config->get("MDATEFORMAT");
				break;
				case 'timeformat':
					return $this->UCP->FreePBX->Config->get("MTIMEFORMAT");
				break;
				case 'datetimeformat':
					return $this->UCP->FreePBX->Config->get("MDATETIMEFORMAT");
				break;
			}
		} else {
			return $res;
		}
	}
}
