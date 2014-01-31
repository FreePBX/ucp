<?php
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Home extends Modules{	
	function __construct($Modules) {
		$this->Modules = $Modules;
	}
	
	function getDisplay() {
		return "This is where we would put content dynamically after page load through pjax";
	}
	
	public function doConfigPageInit($display) {
	}
	
	public function myShowPage() {
	}
	
	public function getBadge() {
		return false;
	}
	
	public function getMenuItems() {
		return array(
			"rawname" => "home",
			"name" => "Home",
			"badge" => $this->getBadge(),
			"menu" => false
		);
	}
}