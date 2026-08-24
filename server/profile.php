<?php
include_once("connection.php");
include_once("auth.php");
include_once("regions.php");
$response = [];
$user_id = require_auth()["user_id"];

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $region = isset($_POST["region"]) ? trim($_POST["region"]) : "";
    if(!in_array($region, lebanon_regions(), true)){
        $response["success"] = false; $response["error"] = "please choose a valid Lebanese district"; echo json_encode($response); exit;
    }
    $sql = "UPDATE users SET region = ? WHERE id = ?";
    $query = $mysql->prepare($sql); $query->bind_param("si", $region, $user_id); $query->execute();
}
$sql = "SELECT username, email, region FROM users WHERE id = ?";
$query = $mysql->prepare($sql); $query->bind_param("i", $user_id); $query->execute();
$profile = $query->get_result()->fetch_assoc();
$response["success"] = true; $response["data"] = $profile;
echo json_encode($response);
?>
