<?php
$dbhost="chores.home.jdsnetwork.com";
$dbuser="chores";
$dbpass="35q5cvFzCH5uSxA&A";
$dbname="choresdev";
$dbport="3306";
$dbcharset = 'utf8mb4';
$emailuser='system@jdsnetwork.com';
$emailpass='ML-EN24`hY-D,/SW'; 
$emailfrom='system@jdsnetwork.com';
$emailto='parents@jdsnetwork.com';
$emailreply='system@jdsnetwork.com';
$voipmsapiuser='parents@jdsnetwork.com';
$voipmsapikey='WcF18jWPAfas';
$voipmsfrom='4848540682';
$user = "parents@jdsnetwork.com";
$pass = "cMjAVny3c!5P3Rf";

$dsn = "mysql:host=$dbhost;dbname=$dbname;charset=$dbcharset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>