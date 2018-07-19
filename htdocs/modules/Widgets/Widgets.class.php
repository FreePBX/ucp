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
use \Ramsey\Uuid\Uuid;
use \Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class Widgets extends Modules{
	protected $module = 'Widgets';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->astman = $this->UCP->FreePBX->astman;
		$this->user = $this->UCP->User->getUser();
	}

	function getDisplay($dashboard_id) {
		if(empty($dashboard_id)) {
			return '<div class="dashboard-error no-dash" style="cursor:pointer;"><div class="message"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><br/>'._("You have no dashboards. Click here to add one").'</div></div>';
		}

		$dashboard = $this->UCP->Dashboards->getDashboardByID($dashboard_id);
		if(empty($dashboard)) {
			return '<div class="dashboard-error"><div class="message"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><br/>'._('Invalid dashboard id').'</div></div>';
		}

		$widgets = $this->UCP->Dashboards->getAllWidgets();

		$widgets_info_serialized = $this->Modules->Widgets->getWidgetsFromDashboard($dashboard_id);

		$widgets_info = json_decode($widgets_info_serialized);

		$html = '<div class="grid-stack" data-dashboard_id="'.$dashboard_id.'">';

		$this->UCP->Modgettext->push_textdomain("widgets");

		if(!empty($widgets_info)){
			foreach($widgets_info as $data) {
				if(empty($widgets['widget'][ucfirst($data->rawname)]['list'][$data->widget_type_id])) {
					continue;
				}
				$widgetData = $widgets['widget'][ucfirst($data->rawname)]['list'][$data->widget_type_id];
				$minsize = '';
				if(!empty($widgetData['minsize'])) {
					if($widgetData['minsize']['height'] > $widgetData['defaultsize']['height']) {
						throw new \Exception("Minsize height is less than defaultsize height in ".$data->rawname."!!");
					}
					if($widgetData['minsize']['width'] > $widgetData['defaultsize']['width']) {
						throw new \Exception("Minsize width is less than defaultsize width in ".$data->rawname."!!");
					}
					$minsize = 'data-gs-min-height="'.$widgetData['minsize']['height'].'" data-gs-min-width="'.$widgetData['minsize']['width'].'"';
				}
				$maxsize = '';
				if(!empty($widgetData['maxsize'])) {
					if($widgetData['maxsize']['height'] < $widgetData['defaultsize']['height']) {
						throw new \Exception("Maxsize height is greater than defaultsize height in ".$data->rawname."!!");
					}
					if($widgetData['maxsize']['width'] < $widgetData['defaultsize']['width']) {
						throw new \Exception("Maxsize width is greater than defaultsize width in ".$data->rawname."!!");
					}
					$maxsize = 'data-gs-max-height="'.$widgetData['maxsize']['height'].'" data-gs-max-width="'.$widgetData['maxsize']['width'].'"';
				}
				$noresize = '';
				if(!empty($widgetData['noresize'])) {
					$noresize = 'data-gs-no-resize="true" data-no-resize="true"';
				}
				$locked = '';
				$lockedIcon = 'fa-unlock-alt';
				if(!empty($data->locked)) {
					$locked = 'data-gs-locked="true" data-gs-no-resize="true" data-gs-no-move="true"';
					$lockedIcon = 'fa-lock';
				}
				$iconClass = !empty($widgetData['icon']) ? $widgetData['icon'] : $widgets['widget'][ucfirst($data->rawname)]['icon'];
				$settings_html = '';
				if($data->has_settings == 1){
					$settings_html = '<div class="widget-option edit-widget" data-widget_type_id="'.$data->widget_type_id.'" data-rawname="'.$data->rawname.'">
												<i class="fa fa-cog" aria-hidden="true"></i>
											</div>';
				}

				$regenuuid = '';
				if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i',$data->id)) {
					//TODO: need to mark that this happened
					$data->id = (string)Uuid::uuid4();
					$regenuuid = 'data-regenuuid="true"';
				}

				$html .= '<div class="grid-stack-item flip-container" '.$maxsize.' '.$minsize.' '.$noresize.' '.$locked.' '.$regenuuid.' data-gs-x="'.$data->size_x.'" data-gs-y="'.$data->size_y.'" data-gs-width="'.$data->col.'" data-gs-height="'.$data->row.'" data-widget_module_name="'.$data->widget_module_name.'" data-gs-id="'.$data->id.'" data-id="'.$data->id.'" data-name="'.$data->name.'" data-rawname="'.$data->rawname.'" data-widget_type_id="'.$data->widget_type_id.'" data-has_settings="' . $data->has_settings . '">';

				$html .= '<div class="grid-stack-item-content flipper">
						<div class="front">
							<div class="widget-title">
								<div class="widget-module-name truncate-text"><i class="fa-fw '.$iconClass.'"></i>'.$data->name.'</div>
								<div class="widget-module-subname truncate-text">('.$data->widget_module_name.')</div>
								<div class="widget-options">
									<div class="widget-option remove-widget" data-widget_id="'.$data->id.'" data-widget_type_id="'.$data->widget_type_id.'" data-widget_rawname="'.$data->rawname.'">
										<i class="fa fa-times" aria-hidden="true"></i>
									</div>
									'.$settings_html.'
									<div class="widget-option lock-widget" data-widget_id="'.$data->id.'" data-widget_type_id="'.$data->widget_type_id.'" data-widget_rawname="'.$data->rawname.'">
										<i class="fa '.$lockedIcon.'" aria-hidden="true"></i>
									</div>
								</div>
							</div>
							<div class="widget-content container"></div>
						</div>
				</div>';
				$html .= '</div>';
			}
		}
		$html .= '</div></br>';

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
			case 'homeRefresh':
				return true;
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
			default:
				return false;
			break;
		}
	}

	/**
	* Send settings to UCP upon initalization
	*/
	function getStaticSettings() {
		return array();
	}

	public function getMenuItems() {
		return array(
			"rawname" => "widgets",
			"name" => _("Widgets"),
			"badge" => false,
			"menu" => false
		);
	}
}
