<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once("connection.php");
include_once("auth.php");

$response = [];

// step 1: who is this?
$user = require_auth();
$owner_id = $user["user_id"];

// step 2: validate input
if(isset($_POST["name"])){
    $name = trim($_POST["name"]);
}else{
    $name = "";
}

if(isset($_POST["species"])){
    $species = trim($_POST["species"]);
}else{
    $species = "";
}

if(isset($_POST["care_notes"])){
    $care_notes = trim($_POST["care_notes"]);
}else{
    $care_notes = "";
}

if($name == ""){
    $response["success"] = false;
    $response["error"] = "plant name is required";
    echo json_encode($response);
    exit;
}

// species optional? keep it friendly — default to "Unknown"
if($species == ""){
    $species = "Unknown";
}

// care_notes is optional for now (AI generation comes later)

// step 3: insert the plant passport
$sql = "INSERT INTO plants (owner_id, name, species, care_notes) VALUES (?, ?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("isss", $owner_id, $name, $species, $care_notes);
$query->execute();

$plant_id = $mysql->insert_id;

$response["success"] = true;
$response["data"] = [];
$response["data"]["plant_id"] = $plant_id;
$response["data"]["message"] = "plant registered";

echo json_encode($response);
?>
</｜DSML｜>