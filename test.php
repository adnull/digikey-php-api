<?php

include("config.php");
include_once("digikey.class.php");

$dk = new Digikey( $digikey_client_id, $digikey_secret, $digikey_url, $digikey_token_file, $digikey_app_url);

$request = array(
		'Keywords' => 'max232',
		'RecordCount' => 10
);
$response = $dk->api_request("/Search/v3/Products/Keyword", json_encode($request));
print json_encode($response, JSON_PRETTY_PRINT);
 ?>
