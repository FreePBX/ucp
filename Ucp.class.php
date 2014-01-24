<?php
// vim: set ai ts=4 sw=4 ft=php:

class Ucp implements BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}
	
	public function install() {
		
	}
	public function uninstall() {
		
	}
	public function backup(){
		
	}
	public function restore($backup){
		
	}
	public function genConfig() {

	}
	public function writeConfig($conf){
		$this->FreePBX->WriteConfig($conf);
	}
	
	public function doConfigPageInit($display) {
		
	}
	
	public function myShowPage() {
		$html = '';
		$html .= load_view(dirname(__FILE__).'/views/header.php',array());
		
		$users = $this->getAllUsers();
		
		$html .= load_view(dirname(__FILE__).'/views/rnav.php',array("users"=>$users));
		switch($_REQUEST['category']) {
			case 'users':
				if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'showuser' && !empty($_REQUEST['user'])) {
					$user = $this->getUser($_REQUEST['user']);
				} else {
					$user = array();
				}
				$html .= load_view(dirname(__FILE__).'/views/users.php',array("user" => $user));
			break;
			default:
				$html .= load_view(dirname(__FILE__).'/views/main.php',array());
			break;
		}
		$html .= load_view(dirname(__FILE__).'/views/footer.php',array());
		
		return $html;
	}
	
	public function getAllUsers() {
		$sql = "SELECT * FROM ucp_users";
		return $this->db->query($sql,PDO::FETCH_ASSOC);
	}
	
	public function getUser($id) {
		$sql = "SELECT * FROM ucp_users WHERE id = :id";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':id' => $id));
		return $sth->fetch(PDO::FETCH_ASSOC);
	}
}