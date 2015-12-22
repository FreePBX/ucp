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

class Home extends Modules{
	protected $module = 'Home';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->astman = $this->UCP->FreePBX->astman;
		$this->user = $this->UCP->User->getUser();
	}

	function getDisplay() {
		$modules = $this->Modules->getModulesByMethod('getHomeWidgets');
		$html = '<div class="masonry-container">';
		foreach($modules as $module) {
			$this->UCP->Modgettext->push_textdomain(strtolower($module));
			$widgets = $this->Modules->$module->getHomeWidgets();
			$this->UCP->Modgettext->pop_textdomain();
			foreach($widgets as $data) {
				$html .= '<div id="'.$module.'-widget-'.$data['id'].'" class="widget" style="width:'.$data['size'].';">';
				$html .= '<div id="'.$module.'-title-'.$data['id'].'" class="title">'.$data['title'].'<a onclick="UCP.Modules.Home.refresh(\''.$module.'\',\''.$data['id'].'\')"><i class="fa fa-refresh"></i></a></div>';
				$html .= '<div id="'.$module.'-content-'.$data['id'].'" class="content">';
				$html .= $data['content'];
				$html .= '</div></div>';
			}
		}
		$html .= '</div>';
		return $html;
	}

	public function poll() {
		return array("status" => true, "data" => array());
	}

	public function getHomeWidgets($feed=null) {
		//return array();
		$fpbxfeeds = $this->UCP->FreePBX->Config->get('UCPRSSFEEDS');
		$fpbxfeeds = !empty($fpbxfeeds) ? $fpbxfeeds : $this->UCP->FreePBX->Config->get('RSSFEEDS');

		$fpbxfeeds = trim($fpbxfeeds);
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
		$out = array();
		$reader = new Reader;

		//Check if dashboard is installed and enabled,
		//if so then we will use the same cache engine dashboard uses
		if($this->UCP->FreePBX->Modules->moduleHasMethod("dashboard","getConfig")) {
			$storage = $this->UCP->FreePBX->Dashboard;
		} else {
			$storage = $this->UCP->FreePBX->Ucp;
		}
		foreach($feeds as $k => $feed) {
			$etag = $storage->getConfig($feed, "etag");
			$last_modified = $storage->getConfig($feed, "last_modified");
			$content = '';
			try {
				$resource = $reader->download($feed, $last_modified, $etag);
				if ($resource->isModified()) {

					$parser = $reader->getParser(
						$resource->getUrl(),
						$resource->getContent(),
						$resource->getEncoding()
					);

					$content = $parser->execute();
					$etag = $resource->getEtag();
					$last_modified = $resource->getLastModified();

					$storage->setConfig($feed, $content, "content");
					$storage->setConfig($feed, $etag, "etag");
					$storage->setConfig($feed, $last_modified, "last_modified");
				} else {
					$content = $storage->getConfig($feed, "content");
				}
			}	catch (\PicoFeed\PicoFeedException $e) {
				$content = $storage->getConfig($feed, "content");
			}
			if(empty($content)) {
				continue;
			}
			$htmlcontent = '<ul>';
			$i = 1;
			foreach($content->items as $item) {
				if($i > 5) {
					break;
				}
				$htmlcontent .= '<li><a href="'.$item->url.'" target="_blank">'.$item->title.'</a></li>';
				$i++;
			}
			$htmlcontent .= '</ul>';
			$out[] = array(
				"id" => $k,
				"title" => '<a href="'.$content->site_url.'" target="_blank">'.$content->title.'</a>',
				"content" => $htmlcontent,
				"size" => '33.33%'
			);
		}

		return $out;
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
				if($this->_checkExtension($_REQUEST['from'])) {
					$data = $this->UCP->FreePBX->Core->getDevice($_REQUEST['from']);
					if(!empty($data)) {
						$out = $this->astman->originate(array(
							"Channel" => "Local/".$data['id']."@originate-skipvm",
							"Exten" => $_REQUEST['to'],
							"Context" => "from-internal",
							"Priority" => 1,
							"Async" => "yes",
							"CallerID" => "UCP <".$data['id'].">"
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
}
