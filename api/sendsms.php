<?php
include_once ("../config.php");

$method = "sendSMS";
$did = urlencode($_REQUEST['did']);
$dst = urlencode($_REQUEST['dst']);
$message = urlencode($_REQUEST['message']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
$url = "https://voip.ms/api/v1/rest.php?api_username={$voipmsapiuser}&api_password={$voipmsapikey}&method={$method}&did={$did}&dst={$dst}&message={$message}";
curl_setopt($ch, CURLOPT_URL, $url);
$result = curl_exec($ch);
curl_close($ch);

$response=json_decode($result,true);

if($response['status']!='success'){
    echo $response['status'];
    exit;
}
