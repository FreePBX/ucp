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

class Voicemail extends Modules{
	function __construct($Modules) {
		$this->Modules = $Modules;
	}
	
	function getDisplay() {
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		
		$folders = $this->UCP->FreePBX->Voicemail->getFolders();
		$messages = array();
		foreach($folders as $folder) {
			$messages[$folder['folder']] = $this->UCP->FreePBX->Voicemail->getMessagesByExtensionFolder($ext,$folder['folder']);
		}
		
		$displayvars = array();
		$displayvars['folders'] = $folders;
		$displayvars['messages'] = $messages;
		
		return $this->loadScript().$this->loadCSS().load_view(__DIR__.'/views/mailbox.php',$displayvars);
	}
	
	
	
	public function doConfigPageInit($display) {
	}
	
	public function myShowPage() {
	}
	
	public function loadScript() {
		$contents = '';
		foreach (glob(__DIR__."/assets/js/*.js") as $filename) {
			$contents .= file_get_contents($filename);
		}
		return "<script>".$contents."</script>";
	}
	
	public function loadCSS() {
		$contents = '';
		foreach (glob(__DIR__."/assets/css/*.css") as $filename) {
			$contents .= file_get_contents($filename);
		}
		return "<style>".$contents."</style>";
	}
	
	public function getBadge() {
		$total = 0;
		foreach($this->Modules->getAssignedDevices() as $extension) {
			$mailbox = $this->UCP->FreePBX->astman->MailboxCount($extension);
			$total = $total + $mailbox['NewMessages'];
		}
		return !empty($total) ? $total : false;
	}
	
	public function getMenuItems() {
		$menu = array(
			"rawname" => "voicemail",
			"name" => "Vmail",
			"badge" => $this->getBadge()
		);
		foreach($this->Modules->getAssignedDevices() as $extension) {
			$mailbox = $this->UCP->FreePBX->astman->MailboxCount($extension);
			$menu["menu"][] = array(
				"rawname" => $extension,
				"name" => $extension,
				"badge" => $mailbox['NewMessages']
			);
		}
		return $menu; 
	}
}