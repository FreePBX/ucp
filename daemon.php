<?php

if(!file_exists(__DIR__.'/pid')) {
	start();
} else {
	stop();
}

function start() {
	$pid = exec(__DIR__."/websocketd --port=8081 ".__DIR__."/server.php > /dev/null 2>&1 & echo $!",$output);
	file_put_contents(__DIR__.'/pid',$pid);
	echo "started\n";
}

function stop() {
	$pid = file_get_contents(__DIR__.'/pid');
	exec('kill -9 '.$pid);
	unlink(__DIR__.'/pid');
	echo "stopped\n";
}
