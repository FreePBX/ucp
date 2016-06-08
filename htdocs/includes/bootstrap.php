<?php
if(!class_exists("Mobile_Detect")) {
	require __DIR__.'/mobileDetect/Mobile_Detect.php';
}
// While only the Less_Cache class is needed, the error
// reporting class must be present first so any errors
// encountered by the less/Cache.php file can be thrown.
// so include less/Less.php and not less/Cache.php
if(!class_exists("Less_Cache")) {
	require __DIR__.'/less/Less.php';
}
if(!class_exists("JShrink\Minifier")) {
	require __DIR__.'/js/Minifier.php';
}
if(!class_exists("po2json")) {
	require __DIR__.'/po2json/po2json.php';
}
if(!class_exists("Emojione\Emojione")) {
	require __DIR__.'/emoji/Emojione.class.php';
}
require __DIR__.'/UCP.class.php';
