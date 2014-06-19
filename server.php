#!/usr/bin/php
<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
ob_start();
$bootstrap_settings = array();
$bootstrap_settings['freepbx_auth'] = false;
//TODO: We need to make sure security is 100%!
$restrict_mods = true; //Set to true so that we just load framework and the page wont bomb out because we have no session
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
      include_once('/etc/asterisk/freepbx.conf');
}
include(dirname(__FILE__).'/htdocs/includes/UCP.class.php');
$ucp = \UCP\UCP::create();
ob_end_clean();

while(true) {
	$stdin = fopen('php://stdin', 'r');
	$line = fgets($stdin);
	stream_set_blocking($stdin,0);
	$message = array('message' => 'user');
	$line = fgets($stdin);
	if($line !== false) {
		$message['response'] = 'Hello ' . trim($line) . "!\n";
	}
	fwrite(STDOUT, json_encode($message));
	fwrite(STDOUT, "\n");
	sleep(1);
}
