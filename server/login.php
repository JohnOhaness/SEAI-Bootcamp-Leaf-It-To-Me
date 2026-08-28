<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

include("connection.php");

$response = [];

if(isset($_POST["username"])){
    $username = $_POST["username"];
}else{
    $username = "";
}

if(isset($_POST["password"])){
    $password = $_POST["password"];
}else{
    $password = "";
}

if($username == "" || $password == ""){
    $response["success"] = false;
    $response["error"] = "username and password are required";
    echo json_encode($response);
    exit;
}

$sql = "SELECT * FROM users WHERE username = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();

if($result->num_rows == 0){
    $response["success"] = false;
    $response["error"] = "invalid username or password";
    echo json_encode($response);
    exit;
}

$user = $result->fetch_assoc();

if(password_verify($password, $user["password_hash"]) == false){
    $response["success"] = false;
    $response["error"] = "invalid username or password";
    echo json_encode($response);
    exit;
}

$payload = [
    "user_id" => $user["id"],
    "username" => $user["username"],
    "exp" => time() + (60 * 60 * 24) // expires in 24 hours
];

$jwt = JWT::encode($payload, JWT_SECRET, "HS256");

$response["success"] = true;
$response["data"] = [];
$response["data"]["token"] = $jwt;
$response["data"]["username"] = $user["username"];

echo json_encode($response);
?>