<?php
include_once("connection.php");
include_once("auth.php");
$response = [];
$user = require_auth();
$sitter_id = $user["user_id"];
$sit_id = isset($_POST["sit_id"]) ? intval($_POST["sit_id"]) : 0;
if($sit_id <= 0){ $response["success"] = false; $response["error"] = "sit_id is required"; echo json_encode($response); exit; }
// Atomic claim that rejects a person's own listings.
$sql = "UPDATE sits s INNER JOIN plants p ON p.id = s.plant_id SET s.sitter_id = ?, s.status = 'claimed', s.claimed_at = NOW() WHERE s.id = ? AND s.status = 'open' AND p.owner_id != ?";
$query = $mysql->prepare($sql); $query->bind_param("iii", $sitter_id, $sit_id, $sitter_id); $query->execute();
if($mysql->affected_rows == 0){ $response["success"] = false; $response["error"] = "could not claim this sit; it may be yours or already claimed"; echo json_encode($response); exit; }
$response["success"] = true; $response["data"] = ["sit_id" => $sit_id, "message" => "sit claimed"];
echo json_encode($response);
?>
