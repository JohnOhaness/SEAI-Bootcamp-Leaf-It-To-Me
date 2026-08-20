<?php
require_once __DIR__ . '/config.php';

$mysql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if($mysql->connect_error){
    die("Connection failed: " . $mysql->connect_error);
}
?>