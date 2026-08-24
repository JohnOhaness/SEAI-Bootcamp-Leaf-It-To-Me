<?php
include_once("connection.php");

$response = [];

// step 1: grab all open sits with plant + owner info
$sql = "SELECT s.id AS sit_id, s.start_date, s.end_date, s.created_at,
               p.id AS plant_id, p.name AS plant_name, p.species, p.care_notes,
               u.username AS owner_username
        FROM sits s
        JOIN plants p ON p.id = s.plant_id
        JOIN users u ON u.id = p.owner_id
        WHERE s.status = 'open'
        ORDER BY s.start_date ASC";

$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();

$listings = [];
while($row = $result->fetch_assoc()){
    $listings[] = $row;
}

$response["success"] = true;
$response["data"] = $listings;

echo json_encode($response);
?>