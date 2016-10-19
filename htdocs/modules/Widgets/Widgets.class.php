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

class Widgets extends Modules{
	protected $module = 'Widgets';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->astman = $this->UCP->FreePBX->astman;
		$this->user = $this->UCP->User->getUser();
	}

	function getDisplay($dashboard_id) {

		$widgets_info_serialized = $this->Modules->Widgets->getWidgetsFromDashboard($dashboard_id);

		$widgets_info = json_decode($widgets_info_serialized);

		$html = '<div class="gridster"><ul>';

		$this->UCP->Modgettext->push_textdomain("widgets");

		if(!empty($widgets_info)){
			foreach($widgets_info as $data) {
				$html .= '
						<li data-widget_module_name="'.$data->widget_module_name.'" data-id="'.$data->id.'" data-name="'.$data->name.'" data-row="'.$data->row.'" data-col="'.$data->col.'" data-sizex="'.$data->size_x.'" data-sizey="'.$data->size_y.'" data-rawname="'.$data->rawname.'" data-widget_type_id="'.$data->widget_type_id.'" class="flip-container">
							<div class="widget-title">
								<div class="widget-module-name truncate-text">'.$data->widget_module_name.'</div>
								<div class="widget-module-subname truncate-text">('.$data->name.')</div>
								<div class="widget-options">
									<div class="widget-option remove-widget" data-widget_id="'.$data->id.'">
										<i class="fa fa-times" aria-hidden="true"></i>
									</div>
									<div class="widget-option edit-widget" data-widget_id="'.$data->id.'">
										<i class="fa fa-cog" aria-hidden="true"></i>
									</div>
								</div>
							</div>
							<div class="flipper">
								<div class="front">
									<div class="widget-content"></div>
								</div>
								<div class="back">
									<p>SETTINGS</p>
									<p>SETTINGS</p>
									<p>SETTINGS</p>
									<p>SETTINGS</p>
									<p>SETTINGS</p>
								</div>
							</div>
						</li>';
			}
		}

		$this->UCP->Modgettext->pop_textdomain();

		return $html;
	}

	public function poll() {
		return array("status" => true, "data" => array());
	}

	public function getWidgetsFromDashboard($dashboard_id) {

		$dashboard_layout = $this->UCP->Dashboards->getLayoutByID($dashboard_id);

		return $dashboard_layout;
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
			case 'contacts':
				return true;
			break;
			case 'homeRefresh':
				return true;
			break;
			case 'originate':
				$o = $this->UCP->FreePBX->Userman->getCombinedModuleSettingByID($this->user['id'],'ucp|Global','originate');
				return !empty($o) ? true : false;
			break;
			default:
				return false;
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
			case 'homeRefresh':
				$data = $this->getWidgetsFromDashboard($_REQUEST['id']);
				return array("status" => true, "content" => $data[0]['content']);
			break;
			case "originate":
				if($this->_checkExtension($_REQUEST['from'])) {
					// prevent caller id spoofing
					if($this->user['default_extension'] == $_REQUEST['from']) {
						$out = $this->astman->originate(array(
							"Channel" => "Local/".$_REQUEST['from']."@originate-skipvm",
							"Exten" => $_REQUEST['to'],
							"Context" => "from-internal",
							"Priority" => 1,
							"Async" => "yes",
							"CallerID" => "UCP <".$_REQUEST['from'].">"
						));
						if($out['Response'] == "Error") {
							$return['status'] = false;
							$return['message'] = $out['Message'];
						} else {
							$return['status'] = true;
						}
					} else {
						$return['status'] = false;
						$return['message'] = _('Invalid Device');
					}
				} else {
					$return['status'] = false;
					$return['message'] = _('Invalid Device');
				}
				return $return;
			break;
			case "contacts":
				if($this->Modules->moduleHasMethod('Contactmanager','lookupMultiple')) {
					$search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : "";
					$results = $this->Modules->Contactmanager->lookupMultiple($search);
					if(!empty($results)) {
						$return = array();
						foreach($results as $res) {
							foreach($res['numbers'] as $type => $num) {
								if(!empty($num)) {
									$return[] = array(
										"value" => $num,
										"text" => $res['displayname'] . " (".$type.")"
									);
								}
							}
						}
					} else {
						return array();
					}
				}
				return $return;
			break;
			default:
				return false;
			break;
		}
	}

	/**
	* Send settings to UCP upon initalization
	*/
	function getStaticSettings() {
		$user = $this->UCP->User->getUser();
		$extensions = array($this->user['default_extension']);
		return array(
			'extensions' => $extensions,
			'enableOriginate' => $this->UCP->getCombinedSettingByID($user['id'],'Global','originate')
		);
	}

	public function getMenuItems() {
		return array(
			"rawname" => "widgets",
			"name" => _("Widgets"),
			"badge" => false,
			"menu" => false
		);
	}

	private function _checkExtension($extension) {
		$user = $this->UCP->User->getUser();
		return $this->UCP->getCombinedSettingByID($user['id'],'Global','originate');
	}
}
