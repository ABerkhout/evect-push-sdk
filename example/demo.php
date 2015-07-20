<?php

include(__DIR__.'/../vendor/autoload.php');

use EVPushSdk\EVPushSdk;

$sdk = new EVPushSdk(
	array(
		'public' 	=> "public",
		'secret'	=> "secret"
	), // Config
	false // Demo
);

$response = $sdk->messageBroadcast(
	array(
		"aps" => array(
			"alert" => "The message"
		)
	)
);

var_dump($response);
