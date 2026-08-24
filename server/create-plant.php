<?php
include_once("connection.php");
include_once("auth.php");
include_once("ai-care.php");

$response = [];
$owner_id = require_auth()["user_id"];
$name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
$species = isset($_POST["species"]) ? trim($_POST["species"]) : "";
$owner_notes = isset($_POST["care_notes"]) ? trim($_POST["care_notes"]) : "";

if($name === ""){
    $response["success"] = false;
    $response["error"] = "plant name is required";
    echo json_encode($response);
    exit;
}
if($species === "") $species = "Unknown";

// The generated text is stored once as the plant's permanent passport care note.
$ai_result = generate_care_notes($name, $species, $owner_notes);
if(!$ai_result["success"]){
    $response["success"] = false;
    $response["error"] = $ai_result["error"];
    echo json_encode($response);
    exit;
}
$care_notes = $ai_result["data"];

$sql = "INSERT INTO plants (owner_id, name, species, care_notes) VALUES (?, ?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("isss", $owner_id, $name, $species, $care_notes);
$query->execute();

$response["success"] = true;
$response["data"] = ["plant_id" => $mysql->insert_id, "care_notes" => $care_notes, "message" => "plant registered with AI care notes"];
echo json_encode($response);
?>
