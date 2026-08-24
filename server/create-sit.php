<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once("connection.php");
include_once("auth.php");

$response = [];

// step 1: who is this?
$user = require_auth();
$owner_id = $user["user_id"];

// step 2: validate input
if(isset($_POST["plant_id"])){
    $plant_id = intval($_POST["plant_id"]);
}else{
    $plant_id = 0;
}

if(isset($_POST["start_date"])){
    $start_date = trim($_POST["start_date"]);
}else{
    $start_date = "";
}

if(isset($_POST["end_date"])){
    $end_date = trim($_POST["end_date"]);
}else{
    $end_date = "";
}

if($plant_id <= 0){
    $response["success"] = false;
    $response["error"] = "plant_id is required";
    echo json_encode($response);
    exit;
}

// basic date format check (YYYY-MM-DD)
$date_pattern = "/^\d{4}-\d{2}-\d{2}$/";
if(!preg_match($date_pattern, $start_date) || !preg_match($date_pattern, $end_date)){
    $response["success"] = false;
    $response["error"] = "start_date and end_date are required (YYYY-MM-DD)";
    echo json_encode($response);
    exit;
}

if(strtotime($end_date) < strtotime($start_date)){
    $response["success"] = false;
    $response["error"] = "end_date must be after start_date";
    echo json_encode($response);
    exit;
}

// step 3: does this plant exist AND belong to this user?
$sql_plant = "SELECT id, owner_id, name FROM plants WHERE id = ?";
$query_plant = $mysql->prepare($sql_plant);
$query_plant->bind_param("i", $plant_id);
$query_plant->execute();
$plant_result = $query_plant->get_result();

if($plant_result->num_rows == 0){
    $response["success"] = false;
    $response["error"] = "plant not found";
    echo json_encode($response);
    exit;
}

$plant = $plant_result->fetch_assoc();

if($plant["owner_id"] != $owner_id){
    $response["success"] = false;
    $response["error"] = "you can only create sits for your own plants";
    echo json_encode($response);
    exit;
}

// step 4: insert the open sit
$status = "open";

$sql_insert = "INSERT INTO sits (plant_id, sitter_id, start_date, end_date, status) VALUES (?, NULL, ?, ?, ?)";
$query_insert = $mysql->prepare($sql_insert);
$query_insert->bind_param("isss", $plant_id, $start_date, $end_date, $status);
$query_insert->execute();

$sit_id = $mysql->insert_id;

$response["success"] = true;
$response["data"] = [];
$response["data"]["sit_id"] = $sit_id;
$response["data"]["message"] = "sit listed";

echo json_encode($response);
?>