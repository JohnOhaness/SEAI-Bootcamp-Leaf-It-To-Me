<?php

$DB_HOST = 'localhost';
$DB_NAME = 'leaf';
$DB_USER = 'root';
$DB_PASS = '';   

$mysql = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$mysql) {
    die("Connection failed: " . $mysql->connect_error);
}

?>
