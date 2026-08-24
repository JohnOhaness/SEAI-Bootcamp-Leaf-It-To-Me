<?php
include_once("connection.php");
include_once("auth.php");
$response = [];
$owner_id = require_auth()["user_id"];
$sit_id = isset($_POST["sit_id"]) ? intval($_POST["sit_id"]) : 0;
if($sit_id <= 0){ $response["success"] = false; $response["error"] = "sit_id is required"; echo json_encode($response); exit; }
$sql = "UPDATE sits s INNER JOIN plants p ON p.id = s.plant_id SET s.status = 'cancelled' WHERE s.id = ? AND s.status = 'open' AND p.owner_id = ?";
$query = $mysql->prepare($sql); $query->bind_param("ii", $sit_id, $owner_id); $query->execute();
if($mysql->affected_rows == 0){ $response["success"] = false; $response["error"] = "only an owner can cancel an open listing"; echo json_encode($response); exit; }
$response["success"] = true; $response["data"] = ["sit_id" => $sit_id, "message" => "listing cancelled"];
echo json_encode($response);
?>
