<?php
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Voicemail extends Modules{
	private $vmFolders = array();
	private $vmPath = null;
	
	function __construct($Modules) {
		$this->Modules = $Modules;
		$astspool = $this->UCP->FreePBX->Config->get_conf_setting('ASTSPOOLDIR');
		$this->vmPath = $astspool . "/voicemail";
		$folders = array("INBOX","Family","Friends","Old","Work","Urgent");
		foreach($folders as $folder) {
			$this->vmFolders[] = array(
				"folder" => $folder,
				"name" => _($folder)
			);
		}
		dbug($this->UCP->FreePBX->Voicemail->getVoicemailBoxByExtension(1000));
	}
	
	function getDisplay() {
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		return "Get Infomation for ".$ext;
	}
	
	public function doConfigPageInit($display) {
	}
	
	public function myShowPage() {
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