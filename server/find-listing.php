<?php
include_once("connection.php");
include_once("regions.php");

$response = [];

// step 1: grab all open sits with plant + owner info
$region = isset($_GET["region"]) ? trim($_GET["region"]) : "";
$proximity = isset($_GET["proximity"]) ? trim($_GET["proximity"]) : "any";
$sort = isset($_GET["sort"]) ? trim($_GET["sort"]) : "soonest";
if($region != "" && !in_array($region, lebanon_regions(), true)) $region = "";
if(!in_array($proximity, ["any", "same", "nearby"], true)) $proximity = "any";
if(!in_array($sort, ["soonest", "recent"], true)) $sort = "soonest";
$allowed_regions = $proximity == "same" ? [$region] : ($proximity == "nearby" ? nearby_regions($region) : []);
$order_by = $sort == "recent" ? "s.created_at DESC" : "s.start_date ASC, s.created_at DESC";

$sql = "SELECT s.id AS sit_id, s.start_date, s.end_date, s.created_at,
               p.id AS plant_id, p.name AS plant_name, p.species, p.care_notes,
               u.username AS owner_username, u.region AS owner_region
        FROM sits s
        JOIN plants p ON p.id = s.plant_id
        JOIN users u ON u.id = p.owner_id
        WHERE s.status = 'open'
        ORDER BY $order_by";

$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();

$listings = [];
while($row = $result->fetch_assoc()){
    if($proximity == "any" || ($region != "" && in_array($row["owner_region"], $allowed_regions, true))){
        $listings[] = $row;
    }
}

$response["success"] = true;
$response["data"] = $listings;

echo json_encode($response);
?>
