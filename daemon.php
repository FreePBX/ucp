<?php
/**
 * This is the User Control Panel Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
if(!file_exists(__DIR__.'/pid')) {
	start();
} else {
	stop();
}

function start() {
	exec("uname -m", $arch);
	$server = "";
	$arch = $arch[0];
	switch($arch) {
		case "i686":
			$server = "websocketd-32";
		break;
		case "x86_64":
			$server = "websocketd-64";
		break;
		default:
			echo "Not a suitable environment: " . $arch . "\n";
			exit(1);
		break;
	}
	$pid = exec(__DIR__."/daemons/" . $server . " --port=8081 ".__DIR__."/server.php > /dev/null 2>&1 & echo $!",$output);
	file_put_contents(__DIR__.'/pid',$pid);
	echo "started\n";
}

function stop() {
	$pid = file_get_contents(__DIR__.'/pid');
	exec('kill -9 '.$pid);
	unlink(__DIR__.'/pid');
	echo "stopped\n";
}
