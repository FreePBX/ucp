<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is Part of the User Control Panel Object
 * A replacement for the Asterisk Recording Interface
 * for FreePBX
 *
 * User class for the UCP Object.
 * Contains all user data for the logged in user
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace UCP;
use \Ramsey\Uuid\Uuid;
use \Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
#[\AllowDynamicProperties]
class Dashboards {

	private $dashboardsCache;

	public function __construct($UCP) {
		$this->UCP = $UCP;
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
      'add', 'rename', 'remove', 'reorder', 'dashboards', 'savedashlayout', 'savesimplelayout', 'getdashlayout', 'getallwidgets', 'getwidgetcontent', 'getsimplewidgetcontent', 'getwidgetsettingscontent', 'getsimplewidgetsettingscontent' => true,
      default => false,
  };
	}

	/**
	 * The Handler for all ajax events releated to this class
	 *
	 * Used by Ajax Class to process commands
	 *
	 * @return mixed Output if success, otherwise false will generate a 500 error serverside
	 */
	function ajaxHandler() {
		$return = ["status" => false, "message" => ""];
		switch($_REQUEST['command']) {
			case 'add':
				$user = $this->UCP->User->getUser();
				$dashboards = $this->UCP->getGlobalSettingByID($user['id'],'dashboards');
				$dashboards = is_array($dashboards) ? $dashboards : [];
				$id = (string)Uuid::uuid4();
				$dashboards[] = ["id" => $id, "name" => $_POST['name']];
				$this->UCP->setGlobalSettingByID($user['id'],'dashboards',$dashboards);
				return ["status" => true, "id" => $id??''];
			break;
			case 'rename':
				$user = $this->UCP->User->getUser();
				$dashboards = $this->UCP->getGlobalSettingByID($user['id'],'dashboards');
				$dashboards = is_array($dashboards) ? $dashboards : [];
				foreach($dashboards as $k => $d) {
					if($d['id'] == $_POST['id']) {
						$dashboards[$k]['name'] = $_POST['name'];
						$this->UCP->setGlobalSettingByID($user['id'],'dashboards',$dashboards);
						return ["status" => true, "id" => $id??''];
						break;
					}
				}
				return ["status" => false, "message" => "Invalid Dashboard ID"];
			break;
			case 'remove':
				$user = $this->UCP->User->getUser();
				$dashboards = $this->UCP->getGlobalSettingByID($user['id'],'dashboards');
				$dashboards = is_array($dashboards) ? $dashboards : [];
				foreach($dashboards as $k => $d) {
					if($d['id'] == $_POST['id']) {
						unset($dashboards[$k]);
						$this->UCP->setGlobalSettingByID($user['id'],'dashboards',$dashboards);
						$this->UCP->setGlobalSettingByID($user['id'],'dashboard-layout-'.$_POST['id'],null);
						return ["status" => true];
						break;
					}
				}
			break;
			case 'reorder':
				$order = $_POST['order'];
				$user = $this->UCP->User->getUser();
				$dashboards = $this->UCP->getGlobalSettingByID($user['id'],'dashboards');
				$dashboards = is_array($dashboards) ? $dashboards : [];
				@usort($dashboards, function($a,$b) use ($order) {
					$keya = array_search($a['id'],$order);
					$keyb = array_search($b['id'],$order);
					return ($keya < $keyb) ? -1 : 1;
				});
				$this->UCP->setGlobalSettingByID($user['id'],'dashboards',$dashboards);
				return ["status" => true];
			break;
			case 'dashboards':
				return $this->getDashboards();
			break;
			case 'orderdashboards':
			break;
			case 'savedashlayout':
				$user = $this->UCP->User->getUser();
				return $this->UCP->setGlobalSettingByID($user['id'],'dashboard-layout-'.$_POST['id'],$_POST['data']);
			break;
			case 'savesimplelayout':
				$user = $this->UCP->User->getUser();
				return $this->UCP->setGlobalSettingByID($user['id'],'dashboard-simple-layout',$_POST['data']);
			break;
			case 'getdashlayout':
				return $this->getLayoutByID($_POST['id']);
			break;
			case 'getsimplelayout':
				return $this->getSimpleLayout();
			break;
			case 'getallwidgets':
				return $this->getAllWidgets();
			break;
			case 'getwidgetcontent':
				$uuid = !empty($_POST['uuid']) ? $_POST['uuid'] : null;
				return $this->getWidgetContent($_POST['rawname'],$_POST['id'],$uuid);
			break;
			case 'getsimplewidgetcontent':
				$uuid = !empty($_POST['uuid']) ? $_POST['uuid'] : null;
				return $this->getSimpleWidgetContent($_POST['rawname'],$_POST['id'],$uuid);
			break;
			case 'getwidgetsettingscontent':
				$uuid = !empty($_POST['uuid']) ? $_POST['uuid'] : null;
				return $this->getWidgetSettingsContent($_POST['rawname'],$_POST['id'],$uuid);
			break;
			case 'getsimplewidgetsettingscontent':
				$uuid = !empty($_POST['uuid']) ? $_POST['uuid'] : null;
				return $this->getSimpleWidgetSettingsContent($_POST['rawname'],$_POST['id'],$uuid);
			break;
		}
		return false;
	}

	public function getDashboards() {
		if(!empty($this->dashboardCache)) {
			return $this->dashboardCache;
		}
		$user = $this->UCP->User->getUser();
		$dashboards = $user ? $this->UCP->getGlobalSettingByID($user['id'],'dashboards') : [];
		$dashboards = is_array($dashboards) ? $dashboards : [];
		$this->dashboardCache = $dashboards;
		return $this->dashboardCache;
	}

	public function getDashboardByID($id) {
		$dashboards = $this->getDashboards();
		foreach($dashboards as $dashboard) {
			if($dashboard['id'] == $id) {
				return $dashboard;
			}
		}
		return false;
	}

	public function getLayoutByID($id) {
		$user = $this->UCP->User->getUser();
		return $this->UCP->getGlobalSettingByID($user['id'],'dashboard-layout-'.$id);
	}

	public function getSimpleLayout() {
		$user = $this->UCP->User->getUser();
		return $user ? $this->UCP->getGlobalSettingByID($user['id'],'dashboard-simple-layout') : '';
	}

	public function getAllWidgets() {
		$modules = $this->UCP->Modules->getModulesByMethod('getWidgetList');
		$list = [];
		foreach($modules as $module) {
			$module = ucfirst(strtolower((string) $module));
			$lc = strtolower($module);
			$this->UCP->Modgettext->push_textdomain($lc);
			$mm = $this->UCP->Modules->$module->getWidgetList();
			$this->UCP->Modgettext->pop_textdomain();
			if(!empty($mm)) {
				$list[$module] = $mm;
			}
		}
		return ["status" => true, "widget" => $list];
	}

	public function getAllSimpleWidgets() {
		$modules = $this->UCP->Modules->getModulesByMethod('getSimpleWidgetList');
		$list = [];
		foreach($modules as $module) {
			$module = ucfirst(strtolower((string) $module));
			$lc = strtolower($module);
			$this->UCP->Modgettext->push_textdomain($lc);
			$mm = $this->UCP->Modules->$module->getSimpleWidgetList();
			$this->UCP->Modgettext->pop_textdomain();
			if(!empty($mm)) {
				$list[$module] = $mm;
			}
		}

		return ["status" => true, "widget" => $list];
	}

	public function getWidgetContent($rawname, $id, $uuid) {
		if($this->UCP->Modules->moduleHasMethod($rawname, 'getWidgetDisplay')) {
			$module = ucfirst(strtolower((string) $rawname));
			return $this->UCP->Modules->$module->getWidgetDisplay($id, $uuid);
		}
	}

	public function getSimpleWidgetContent($rawname, $id, $uuid) {
		if($this->UCP->Modules->moduleHasMethod($rawname, 'getSimpleWidgetDisplay')) {
			$module = ucfirst(strtolower((string) $rawname));
			return $this->UCP->Modules->$module->getSimpleWidgetDisplay($id, $uuid);
		}
		if($this->UCP->Modules->moduleHasMethod($rawname, 'getWidgetDisplay')) {
			$module = ucfirst(strtolower((string) $rawname));
			return $this->UCP->Modules->$module->getWidgetDisplay($id, $uuid);
		}
	}

	public function getWidgetSettingsContent($rawname, $id, $uuid) {
		if($this->UCP->Modules->moduleHasMethod($rawname, 'getWidgetSettingsDisplay')) {
			$module = ucfirst(strtolower((string) $rawname));
			return $this->UCP->Modules->$module->getWidgetSettingsDisplay($id, $uuid);
		}
	}
	public function getSimpleWidgetSettingsContent($rawname, $id, $uuid) {
		if($this->UCP->Modules->moduleHasMethod($rawname, 'getSimpleWidgetSettingsDisplay')) {
			$module = ucfirst(strtolower((string) $rawname));
			return $this->UCP->Modules->$module->getSimpleWidgetSettingsDisplay($id, $uuid);
		}
	}
}
