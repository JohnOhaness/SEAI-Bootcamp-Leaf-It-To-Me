<?php
include("connection.php");

$response = [];

if(isset($_POST["username"])){
    $username = $_POST["username"];
}else{
    $username = "";
}

if(isset($_POST["email"])){
    $email = $_POST["email"];
}else{
    $email = "";
}

if(isset($_POST["password"])){
    $password = $_POST["password"];
}else{
    $password = "";
}

// basic validation
if($username == "" || $email == "" || $password == ""){
    $response["success"] = false;
    $response["error"] = "all fields are required";
    echo json_encode($response);
    exit;
}

if(strlen($password) < 8){
    $response["success"] = false;
    $response["error"] = "password must be at least 8 characters";
    echo json_encode($response);
    exit;
}

// step 1: is the username already taken?
$sql = "SELECT * FROM users WHERE username = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $username);
$query->execute();
$username_result = $query->get_result();

if($username_result->num_rows > 0){
    $response["success"] = false;
    $response["error"] = "username already taken";
    echo json_encode($response);
    exit;
}

// step 2: is the email already taken?
$sql2 = "SELECT * FROM users WHERE email = ?";
$query2 = $mysql->prepare($sql2);
$query2->bind_param("s", $email);
$query2->execute();
$email_result = $query2->get_result();

if($email_result->num_rows > 0){
    $response["success"] = false;
    $response["error"] = "email already registered";
    echo json_encode($response);
    exit;
}

// step 3: hash the password and insert
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql3 = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
$query3 = $mysql->prepare($sql3);
$query3->bind_param("sss", $username, $email, $password_hash);
$query3->execute();

$response["success"] = true;
$response["data"] = [];
$response["data"]["message"] = "account created";

echo json_encode($response);
?>