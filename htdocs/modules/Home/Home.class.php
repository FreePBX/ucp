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

	function getDisplay() {
		$modules = $this->Modules->getModulesByMethod('getHomeWidgets');
		$html = '<div class="masonry-container">';
		foreach($modules as $module) {
			$this->UCP->Modgettext->push_textdomain(strtolower($module));
			$widgets = $this->Modules->$module->getHomeWidgets();
			$this->UCP->Modgettext->pop_textdomain();
			foreach($widgets as $data) {
				$html .= '<div id="'.$module.'-widget-'.$data['id'].'" class="widget" style="width:'.$data['size'].';">';
				$html .= '<div id="'.$module.'-title-'.$data['id'].'" class="title">'.$data['title'].'<a onclick="Home.refresh(\''.$module.'\',\''.$data['id'].'\')"><i class="fa fa-refresh"></i></a></div>';
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
		foreach($feeds as $k => $feed) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $feed);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,1);
			curl_setopt($curl, CURLOPT_TIMEOUT, 5);
			$feed = curl_exec($curl);
			curl_close($curl);
			$xml = simplexml_load_string($feed);
			$content = '<ul>';
			$i = 1;
			foreach($xml->channel->item as $item) {
				if($i > 10) {
					break;
				}
				$content .= '<li><a href="'.$item->link.'" target="_blank">'.$item->title.'</a></li>';
				$i++;
			}
			$content .= '</ul>';
			$out[] = array(
				"id" => $k,
				"title" => '<a href="'.$xml->channel->link.'" target="_blank">'.$xml->channel->title.'</a>',
				"content" => $content,
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
				$o = $this->UCP->FreePBX->Userman->getCombinedSettingByID($this->user['id'],'ucp|Global','originate');
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
						$this->astman->originate(array(
							"Channel" => "Local/".$data['id']."@from-internal",
							"Exten" => $_REQUEST['to'],
							"Context" => "from-internal",
							"Priority" => 1,
							"Async" => "yes",
							"CallerID" => "UCP <".$data['id'].">"
						));
					}
					$return['status'] = true;
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
		$extensions = $this->UCP->getSetting($this->user['username'],'Settings','assigned');
		//force default extension to the top.
		if(!empty($this->user['default_extension']) && is_array($extensions)) {
			$extensions = array_diff($extensions, array($this->user['default_extension']));
			array_unshift($extensions,$this->user['default_extension']);
		} elseif(empty($extensions)) {
			$extensions = array($this->user['default_extension']);
		}
		return array(
			'extensions' => $extensions,
			'enableOriginate' => 1
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
		$extensions = $this->UCP->getSetting($this->user['username'],'Settings','assigned');
		return in_array($extension,$extensions);
	}
}
