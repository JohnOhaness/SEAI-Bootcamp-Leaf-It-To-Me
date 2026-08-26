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

// Timeline: every sit this user touched, either as the plant's owner or as the sitter.
$sql_timeline = "SELECT s.id AS sit_id, s.start_date, s.end_date, s.status, s.created_at,
                         p.id AS plant_id, p.name AS plant_name,
                         CASE WHEN p.owner_id = ? THEN 'owner' ELSE 'sitter' END AS role,
                         owner.username AS owner_username,
                         sitter.username AS sitter_username
                  FROM sits s
                  JOIN plants p ON p.id = s.plant_id
                  JOIN users owner ON owner.id = p.owner_id
                  LEFT JOIN users sitter ON sitter.id = s.sitter_id
                  WHERE p.owner_id = ? OR s.sitter_id = ?
                  ORDER BY s.start_date ASC, s.created_at ASC";
$query_timeline = $mysql->prepare($sql_timeline);
$query_timeline->bind_param("iii", $user_id, $user_id, $user_id);
$query_timeline->execute();
$timeline = [];
$result_timeline = $query_timeline->get_result();
while($row = $result_timeline->fetch_assoc()) $timeline[] = $row;

$response["success"] = true; $response["data"] = $profile;
$response["data"]["timeline"] = $timeline;
echo json_encode($response);
?>
