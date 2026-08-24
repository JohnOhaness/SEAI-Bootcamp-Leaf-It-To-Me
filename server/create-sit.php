<?php
include_once("connection.php");
include_once("auth.php");
$response = [];
$user = require_auth();
$owner_id = $user["user_id"];
$plant_id = isset($_POST["plant_id"]) ? intval($_POST["plant_id"]) : 0;
$start_date = isset($_POST["start_date"]) ? trim($_POST["start_date"]) : "";
$end_date = isset($_POST["end_date"]) ? trim($_POST["end_date"]) : "";
$date_pattern = "/^\d{4}-\d{2}-\d{2}$/";
$start_object = DateTime::createFromFormat('!Y-m-d', $start_date);
$end_object = DateTime::createFromFormat('!Y-m-d', $end_date);
if($plant_id <= 0){ $response["success"] = false; $response["error"] = "plant_id is required"; echo json_encode($response); exit; }
if(!preg_match($date_pattern, $start_date) || !preg_match($date_pattern, $end_date) || !$start_object || !$end_object || $start_object->format('Y-m-d') !== $start_date || $end_object->format('Y-m-d') !== $end_date){
    $response["success"] = false; $response["error"] = "start_date and end_date must be real dates (YYYY-MM-DD)"; echo json_encode($response); exit;
}
if(strtotime($end_date) < strtotime($start_date)){ $response["success"] = false; $response["error"] = "end_date must be on or after start_date"; echo json_encode($response); exit; }
$sql_plant = "SELECT id FROM plants WHERE id = ? AND owner_id = ?";
$query_plant = $mysql->prepare($sql_plant); $query_plant->bind_param("ii", $plant_id, $owner_id); $query_plant->execute();
if($query_plant->get_result()->num_rows == 0){ $response["success"] = false; $response["error"] = "plant not found or not owned by you"; echo json_encode($response); exit; }
// Reject a date range that intersects an existing active sit for this plant.
$sql_overlap = "SELECT id FROM sits WHERE plant_id = ? AND status IN ('open', 'claimed') AND start_date <= ? AND end_date >= ?";
$query_overlap = $mysql->prepare($sql_overlap); $query_overlap->bind_param("iss", $plant_id, $end_date, $start_date); $query_overlap->execute();
if($query_overlap->get_result()->num_rows > 0){ $response["success"] = false; $response["error"] = "this plant already has an open or claimed sit during those dates"; echo json_encode($response); exit; }
$status = "open";
$sql_insert = "INSERT INTO sits (plant_id, sitter_id, start_date, end_date, status) VALUES (?, NULL, ?, ?, ?)";
$query_insert = $mysql->prepare($sql_insert); $query_insert->bind_param("isss", $plant_id, $start_date, $end_date, $status); $query_insert->execute();
$response["success"] = true; $response["data"] = ["sit_id" => $mysql->insert_id, "message" => "sit listed"];
echo json_encode($response);
?>
