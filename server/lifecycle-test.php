<?php
// TEMPORARY end-to-end test for the plant/sit lifecycle. Delete after running.
// Writes results to lifecycle-results.json so shell redirection can't corrupt JSON.
file_put_contents(__DIR__ . "/lifecycle-results.json", ""); // clear
function emit($key, $value){
    global $RESULTS;
    $RESULTS[$key] = $value;
    file_put_contents(__DIR__ . "/lifecycle-results.json", json_encode($RESULTS));
}
$RESULTS = [];

$base = "http://localhost/Final-Project/server/";

function http_post($url, $data, $token = ""){
    $headers = ["Content-Type: application/x-www-form-urlencoded"];
    if($token !== ""){
        $headers[] = "Authorization: Bearer " . $token;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    $parsed = json_decode($body, true);
    if($parsed === null){
        file_put_contents(__DIR__ . "/raw-fail.txt", $body);
    }
    return $parsed;
}

function http_get($url, $token = ""){
    $headers = [];
    if($token !== ""){
        $headers[] = "Authorization: Bearer " . $token;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body, true);
}

function log_result($label, $res){
    echo "--- " . $label . " ---\n";
    echo json_encode($res) . "\n\n";
}

$owner = "pp_owner_" . time();
$sitter = "pp_sitter_" . time();
$password = "testpass123";

// 1. signup both
emit("signup owner", http_post($base . "signup.php", [
    "username" => $owner, "email" => $owner . "@example.com", "password" => $password
]));
emit("signup sitter", http_post($base . "signup.php", [
    "username" => $sitter, "email" => $sitter . "@example.com", "password" => $password
]));

// 2. login both
$owner_login = http_post($base . "login.php", ["username" => $owner, "password" => $password]);
$sitter_login = http_post($base . "login.php", ["username" => $sitter, "password" => $password]);
emit("login owner", $owner_login);
emit("login sitter", $sitter_login);

$owner_token = $owner_login["data"]["token"];
$sitter_token = $sitter_login["data"]["token"];

// 3. owner creates a plant
$create_plant = http_post($base . "create-plant.php", [
    "name" => "Fernando", "species" => "Boston Fern", "care_notes" => ""
], $owner_token);
emit("create plant (owner)", $create_plant);

$plant_id = $create_plant["data"]["plant_id"];

// 4. owner opens a sit
$create_sit = http_post($base . "create-sit.php", [
    "plant_id" => $plant_id, "start_date" => "2026-09-01", "end_date" => "2026-09-10"
], $owner_token);
emit("create sit (owner)", $create_sit);

$sit_id = $create_sit["data"]["sit_id"];

// 5. sitter tries to open a sit on the owner's plant -> must fail
emit("sitter creates sit on someone else's plant (should fail)", http_post($base . "create-sit.php", [
    "plant_id" => $plant_id, "start_date" => "2026-10-01", "end_date" => "2026-10-05"
], $sitter_token));

// 6. find-listing shows the open sit
emit("find-listing", http_get($base . "find-listing.php"));

// 7. sitter claims
emit("claim sit (sitter)", http_post($base . "claim-sit.php", ["sit_id" => $sit_id], $sitter_token));

// 8. second claim fails
emit("claim same sit again (should fail)", http_post($base . "claim-sit.php", ["sit_id" => $sit_id], $owner_token));

// 9. owner (not the sitter) tries to complete -> fails
emit("owner tries to complete (should fail)", http_post($base . "complete-sit.php", [
    "sit_id" => $sit_id, "sitter_note" => "nope"
], $owner_token));

// 10. sitter completes with a note
emit("complete sit (sitter)", http_post($base . "complete-sit.php", [
    "sit_id" => $sit_id, "sitter_note" => "Fernando got new soil and loves the window."
], $sitter_token));

// 11. passport shows the full timeline
emit("passport", http_get($base . "passport.php?plant_id=" . $plant_id));

echo "DONE\n";
?>