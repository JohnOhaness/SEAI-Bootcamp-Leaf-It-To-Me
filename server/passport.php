<?php
include_once("connection.php");

$response = [];

// step 1: validate input
if(isset($_GET["plant_id"])){
    $plant_id = intval($_GET["plant_id"]);
}else{
    $plant_id = 0;
}

if($plant_id <= 0){
    $response["success"] = false;
    $response["error"] = "plant_id is required";
    echo json_encode($response);
    exit;
}

// step 2: grab the plant's passport record
$sql_plant = "SELECT p.id, p.name, p.species, p.care_notes, p.created_at,
                     u.username AS owner_username
              FROM plants p
              JOIN users u ON u.id = p.owner_id
              WHERE p.id = ?";
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

// step 3: the full sits timeline — oldest first
$sql_sits = "SELECT id, start_date, end_date, status, sitter_note, created_at, claimed_at,
                    sitter.username AS sitter_username
             FROM sits
             LEFT JOIN users sitter ON sitter.id = sits.sitter_id
             WHERE plant_id = ?
             ORDER BY start_date ASC, created_at ASC";
$query_sits = $mysql->prepare($sql_sits);
$query_sits->bind_param("i", $plant_id);
$query_sits->execute();
$sits_result = $query_sits->get_result();

$timeline = [];
while($row = $sits_result->fetch_assoc()){
    $timeline[] = $row;
}

$response["success"] = true;
$response["data"] = $plant;
$response["data"]["timeline"] = $timeline;

echo json_encode($response);
?>