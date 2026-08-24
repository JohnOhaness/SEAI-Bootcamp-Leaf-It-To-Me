<?php
include_once("connection.php");
include_once("auth.php");
$response = [];
$user_id = require_auth()["user_id"];
$sql_owned = "SELECT s.id AS sit_id, s.start_date, s.end_date, s.status, p.id AS plant_id, p.name AS plant_name, p.species, sitter.username AS sitter_username FROM sits s JOIN plants p ON p.id = s.plant_id LEFT JOIN users sitter ON sitter.id = s.sitter_id WHERE p.owner_id = ? ORDER BY s.start_date DESC, s.created_at DESC";
$query_owned = $mysql->prepare($sql_owned); $query_owned->bind_param("i", $user_id); $query_owned->execute(); $owned_sits = []; $result_owned = $query_owned->get_result(); while($row = $result_owned->fetch_assoc()) $owned_sits[] = $row;
$sql_claimed = "SELECT s.id AS sit_id, s.start_date, s.end_date, s.status, s.sitter_note, p.id AS plant_id, p.name AS plant_name, p.species, owner.username AS owner_username FROM sits s JOIN plants p ON p.id = s.plant_id JOIN users owner ON owner.id = p.owner_id WHERE s.sitter_id = ? ORDER BY s.start_date DESC, s.created_at DESC";
$query_claimed = $mysql->prepare($sql_claimed); $query_claimed->bind_param("i", $user_id); $query_claimed->execute(); $claimed_sits = []; $result_claimed = $query_claimed->get_result(); while($row = $result_claimed->fetch_assoc()) $claimed_sits[] = $row;
$response["success"] = true; $response["data"] = ["owned_sits" => $owned_sits, "claimed_sits" => $claimed_sits];
echo json_encode($response);
?>
