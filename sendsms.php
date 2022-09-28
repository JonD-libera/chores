<?php
include_once (dirname(__FILE__)."/config.php");

$method = "sendSMS";
$did = $argv[1];
$dst = $argv[2];
$message = urlencode($argv[3]);
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
$url = "https://voip.ms/api/v1/rest.php?api_username={$voipmsapiuser}&api_password={$voipmsapikey}&method={$method}&did={$did}&dst={$dst}&message={$message}";
echo $url."\n";
curl_setopt($ch, CURLOPT_URL, $url);
$result = curl_exec($ch);
curl_close($ch);

$response=json_decode($result,true);

if($response['status']!='success'){
    echo $response['status'];
    exit;
}
