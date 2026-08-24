<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once("connection.php");
include_once("auth.php");

$response = [];

// step 1: who is this?
$user = require_auth();
$sitter_id = $user["user_id"];

// step 2: validate input
if(isset($_POST["sit_id"])){
    $sit_id = intval($_POST["sit_id"]);
}else{
    $sit_id = 0;
}

if(isset($_POST["sitter_note"])){
    $sitter_note = trim($_POST["sitter_note"]);
}else{
    $sitter_note = "";
}

if($sit_id <= 0){
    $response["success"] = false;
    $response["error"] = "sit_id is required";
    echo json_encode($response);
    exit;
}

// step 3: atomic check-and-set
// Only the sitter who claimed this sit can complete it, and only while claimed.
$sql = "UPDATE sits SET status = 'completed', sitter_note = ?
        WHERE id = ? AND sitter_id = ? AND status = 'claimed'";
$query = $mysql->prepare($sql);
$query->bind_param("sii", $sitter_note, $sit_id, $sitter_id);
$query->execute();

if($mysql->affected_rows == 0){
    $response["success"] = false;
    $response["error"] = "could not complete this sit — it must be claimed by you and not yet completed";
    echo json_encode($response);
    exit;
}

$response["success"] = true;
$response["data"] = [];
$response["data"]["sit_id"] = $sit_id;
$response["data"]["message"] = "sit completed";

echo json_encode($response);
?>