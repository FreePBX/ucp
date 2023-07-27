<?php
require dirname(__DIR__).'/vendor/autoload.php';

if(!class_exists("po2json")) {
	require __DIR__.'/po2json/po2json.php';
}
if(!class_exists(\Emojione\Emojione::class)) {
	require __DIR__.'/emoji/Emojione.class.php';
}
require __DIR__.'/UCP.class.php';
