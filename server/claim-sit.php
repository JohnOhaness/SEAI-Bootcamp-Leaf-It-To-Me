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

if($sit_id <= 0){
    $response["success"] = false;
    $response["error"] = "sit_id is required";
    echo json_encode($response);
    exit;
}

// step 3: can they claim it? — atomic check-and-set
// Only claims if the sit still exists AND is still open.
$sql = "UPDATE sits SET sitter_id = ?, status = 'claimed', claimed_at = NOW()
        WHERE id = ? AND status = 'open'";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $sitter_id, $sit_id);
$query->execute();

if($mysql->affected_rows == 0){
    $response["success"] = false;
    $response["error"] = "could not claim this sit — it may already be claimed";
    echo json_encode($response);
    exit;
}

// step 4: fetch the claimed sit so the client knows what happened
$sql_get = "SELECT id, plant_id, start_date, end_date FROM sits WHERE id = ?";
$query_get = $mysql->prepare($sql_get);
$query_get->bind_param("i", $sit_id);
$query_get->execute();
$result = $query_get->get_result();
$sit = $result->fetch_assoc();

$response["success"] = true;
$response["data"] = $sit;

echo json_encode($response);
?>