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

fwrite(STDOUT, "Starting \n");

stream_set_timeout($astman->socket, 1);
stream_set_blocking($astman->socket, 0);

$astman->Events("on");

$astman->add_event_handler("hangup", "output");
$lastping = null;
$stdin = fopen('php://stdin', 'r');

while(true) {
  $astman->wait_response(true);
  usleep(500000);
  if(empty($lastping)) {
    fwrite(STDOUT, json_encode(array("event"=>"ping"))."\n");
    stream_set_blocking($stdin, 1);
    $line = fgets($stdin);
    stream_set_blocking($stdin, 0);
    if($line !== false) {
      $json = json_decode($line,true);
      if($json['event'] == "pong") {
        fwrite(STDOUT, json_encode(array("event"=>"gotcha"))."\n");
        $lastping = time();
      } else {
        return false;
      }
    }
  }
}

function output($e, $p, $server, $port) {
  $array = array(
    "event" => $e,
    "data" => $p
  );
  fwrite(STDOUT, json_encode($array)."\n");
}
