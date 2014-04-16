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

	function getDisplay() {
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		if(!empty($ext) && !$this->_checkExtension($ext)) {
			return _("Forbidden");
		}
		$modules = $this->Modules->getModulesByMethod('getSettingsDisplay');
		$html = '';
		$html .= $this->LoadLESS();


		$size = 0;
		$html .= '<div class="row">';
		foreach($modules as $module) {
			$widgets = $this->Modules->$module->getSettingsDisplay($ext);
			foreach($widgets as $data) {
				$size = $size + $data['size'];
				if($size > 12) {
					$html .= '</div>';
					$html .= '<div class="row">';
					$size = $data['size'];
				}
				$html .= '<div class="col-md-'.$data['size'].' section" data-module="'.$module.'">';
				$html .= '<div id="'.$module.'-setting" class="settings">';
				$html .= '<div id="'.$module.'-setting-title" class="title">'.$data['title'].'</div>';
				$html .= '<div id="'.$module.'-setting-content" class="content">';
				$html .= $data['content'];
				$html .= '</div></div></div>';

			}
		}
		$html .= '</div>';
		return $html;
	}

	public function getMenuItems() {
		$user = $this->UCP->User->getUser();
		$menu = array();
		if(!empty($user['assigned'])) {
			$menu = array(
				"rawname" => "settings",
				"name" => _("Settings"),
				"badge" => false,
			);
			foreach($user['assigned'] as $extension) {
				$menu["menu"][] = array(
					"rawname" => $extension,
					"name" => $extension,
					"badge" => false
				);
			}
		}
		return $menu;
	}

	private function _checkExtension($extension) {
		$user = $this->UCP->User->getUser();
		return in_array($extension,$user['assigned']);
	}
}
