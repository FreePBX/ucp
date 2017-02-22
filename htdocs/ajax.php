<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006 Sangoma Technologies, Inc
 */
ob_start();
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session

//for error handling mode
$bootstrap_settings['whoops_handler'] = 'JsonResponseHandler';

include '/etc/freepbx.conf';

include(dirname(__FILE__).'/includes/bootstrap.php');
try {
	$ucp = \UCP\UCP::create();
	$ucp->Modgettext->textdomain("ucp");
} catch(\Exception $e) {
	die();
}
ob_end_clean();
$user = $ucp->User->getUser();
$ucp->View->setGUILocales($user);

if(!isset($_REQUEST['command']) || !isset($_REQUEST['module'])) {
	header("HTTP/1.0 403 Forbidden");
	$json = json_encode(array("status" => "false", "message" => "forbidden"));
	die($json);
}
if(($_REQUEST['command'] != "login" && $_REQUEST['module'] != "User") && ($user === false || empty($user))) {
	header("HTTP/1.0 403 Forbidden");
	$json = json_encode(array("status" => "false", "message" => "forbidden"));
	die($json);
}
$ucp->Ajax->doRequest($_REQUEST['module'],$_REQUEST['command']);
die();
