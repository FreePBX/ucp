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

class Home extends Modules{
	protected $module = 'Home';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->astman = $this->UCP->FreePBX->astman;
		$this->user = $this->UCP->User->getUser();
	}

	function getWidgetList() {
		$raws = $this->getFeeds();
		$list = array();
		foreach($raws as $id=>$raw) {
			$list[$id] = array(
				"display" => $raw['display'],
				"description" => $raw['description'],
				"defaultsize" => array("height" => 4, "width" => 4)
			);
		}
		if(empty($list)) {
			return;
		}

		return array(
			"rawname" => "home",
			"display" => _("RSS Feeds"),
			"icon" => "fa fa-rss",
			"list" => $list
		);
	}

	function getWidgetDisplay($id) {
		$feeds = $this->getFeeds();
		if(!empty($feeds[$id])) {
			return array(
				'title' => $feeds[$id]['display'],
				'html' => $feeds[$id]['content']
			);
		}
		return array();
	}


	public function poll() {
		return array("status" => true, "data" => array());
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
				$data = $this->getHomeWidgets($_REQUEST['id']);
				return array("status" => true, "content" => $data[0]['content']);
			break;
			case "originate":				
				$_REQUEST['from'] = filter_var($_REQUEST['from'], FILTER_SANITIZE_STRING);
				$_REQUEST['to'] = filter_var($_REQUEST['to'], FILTER_SANITIZE_STRING);
				if (strpos($_REQUEST['to'], ".") !== false || strpos($_REQUEST['from'], ".") !== false) {
					$return['status'] = false;
					$return['message'] = _('Invalid Device');
				}else if($this->_checkExtension($_REQUEST['from'])) {
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
			"rawname" => "home",
			"name" => _("Home"),
			"badge" => false,
			"menu" => false
		);
	}

	private function _checkExtension($extension) {
		$user = $this->UCP->User->getUser();
		return $this->UCP->getCombinedSettingByID($user['id'],'Global','originate');
	}

	private function getFeeds() {
		//return array();
		$fpbxfeeds = $this->UCP->FreePBX->Config->get('UCPRSSFEEDS');
		$fpbxfeeds = !empty($fpbxfeeds) ? $fpbxfeeds : $this->UCP->FreePBX->Config->get('RSSFEEDS');
		if(empty($fpbxfeeds)) {
			return array();
		}

		$feeds = array();
		$fpbxfeeds = str_replace("\r","",$fpbxfeeds);
		foreach(explode("\n",$fpbxfeeds) as $k => $f) {
			$feeds['feed-'.$k] = $f;
		}
		if(!empty($feed) && !empty($feeds[$feed])) {
			$feeds = array($feeds[$feed]);
		}
		$widgets = array();

		$storage = $this->UCP->FreePBX->Dashboard;

		foreach($feeds as $k => $feed) {
			try {
				$reader = new \SimplePie();
				$reader->set_cache_location($this->UCP->FreePBX->Config->get('ASTSPOOLDIR'));
				$reader->set_cache_class("SimplePie_Cache_File");

				$reader->set_feed_url($feed);
				$reader->enable_cache(true);
				$reader->init();

				$items = $reader->get_items();
				$content = array(
					"title" => $reader->get_title(),
					"description" => $reader->get_description(),
					"items" => array()
				);
				foreach ($items as $item) {
					$content['items'][] = array(
						"title" => $item->get_title(),
						"url" => $item->get_permalink(),
						"content" => $item->get_description()
					);
				}
				$storage->setConfig($feed, $content, "content");
			}	catch (\Exception $e) {
				$content = $storage->getConfig($feed, "content");
			}

			if(empty($content)) {
				continue;
			}
			$htmlcontent = '<ul>';
			$i = 1;
			foreach($content['items'] as $item) {
				if($i > 5) {
					break;
				}
				$htmlcontent .= '<li><a href="'.$item['url'].'" target="_blank">'.$item['title'].'</a></li>';
				$i++;
			}
			$htmlcontent .= '</ul>';
			$widgets[$k] = array(
				"display" => $content['title'],
				"content" => $htmlcontent,
				"description" => $content['description']
			);
		}
		return $widgets;
	}

}
