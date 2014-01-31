<?php

function ucp_configpageinit($pagename) {
	global $currentcomponent;
	global $amp_conf;

	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
	
    // We only want to hook 'users' or 'extensions' pages. 
	if ($pagename != 'users' && $pagename != 'extensions')  {
		return true; 
	}
	
	if ($tech_hardware != null || $extdisplay != '' || $pagename == 'users') {
		// On a 'new' user, 'tech_hardware' is set, and there's no extension. Hook into the page. 
		if ($tech_hardware != null ) {
			ucp_applyhooks();
		} elseif ($action=="add") { 
			// We don't need to display anything on an 'add', but we do need to handle returned data. 
			if ($_REQUEST['display'] == 'users') {
				ucp_applyhooks();
			} else {
				$currentcomponent->addprocessfunc('ucp_configprocess', 1);
			}
		} elseif ($extdisplay != '' || $pagename == 'users') { 
			// We're now viewing an extension, so we need to display _and_ process. 
			ucp_applyhooks();
			$currentcomponent->addprocessfunc('ucp_configprocess', 1);
		} 
	}
}

function ucp_applyhooks() {
	global $currentcomponent;
	$currentcomponent->addguifunc('ucp_configpageload');
}

function ucp_configpageload() {
	global $currentcomponent;
	global $amp_conf;
	global $astman;

	// Init vars from $_REQUEST[]
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$ext = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extn = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;

	if ($ext==='') {
		$extdisplay = $extn;
	} else {
		$extdisplay = $ext;
	}
	
	if ($action != 'del') {
		$section = _("User Control Panel Access");
		$users = FreePBX::create()->Ucp->getAllUsers();
		foreach($users as $user) {
			$status = ($extdisplay != '') ? in_array($extdisplay, $user['assigned']) : true;
			$currentcomponent->addguielem($section, new gui_checkbox( 'ucp|'.$user['id'],$status, $user['username'], _('If checked this User will be able to access this user/extension in the User Control Panel'),'true','',''));
		}
	} else {
		//TODO: Design the removal functions here.
	}
}

function ucp_configprocess() {
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	
	//if submitting form, update database
	switch ($action) {
		case "add":
		case "edit":
			$users = array();
			foreach($_REQUEST as $key => $value) {
				if(preg_match('/^ucp\|(.*)$/i',$key,$matches)) {
					$users[] = $matches[1];
				}
			}
			FreePBX::create()->Ucp->addUsersToExtension($extdisplay, $users);
		break;
	}
}