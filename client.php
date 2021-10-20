<?php

include("config.php");
include_once("digikey.class.php");

$dk = new Digikey( $digikey_client_id, $digikey_secret, $digikey_url, $digikey_token_file, $digikey_app_url);

if($_SERVER['REQUEST_METHOD'] == 'GET') {
	if(isset($_GET['error'])) {
		print "Authorization error";
	} elseif(isset($_GET['code'])) {
		if($dk->authorize($_GET['code']) === true) {
			print "Authorized";
		}
	} else {
		header('Location: '.$dk->get_authorization_url(), 302);
		exit();
	}
} else {
	print "POST request";
}
