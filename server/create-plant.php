<?php
// create-plant.php

// Ensure clean JSON output without accidental HTML warnings
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Safe includes using absolute paths
    $files = ['connection.php', 'auth.php', 'ai-care.php'];
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) {
            throw new Exception("Missing required server file: $file");
        }
        require_once $path;
    }

    // 2. Authentication check
    if (!function_exists('require_auth')) {
        throw new Exception("Function require_auth() not found in auth.php");
    }
    
    $auth = require_auth();
    if (!isset($auth["user_id"])) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Unauthorized. Please log in."]);
        exit;
    }
    $owner_id = $auth["user_id"];

    // 3. Read input (handles both FormData and JSON fetch)
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents("php://input");
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }

    $name = isset($input["name"]) ? trim($input["name"]) : "";
    $species = (isset($input["species"]) && trim($input["species"]) !== "") ? trim($input["species"]) : "Unknown";
    $owner_notes = isset($input["care_notes"]) ? trim($input["care_notes"]) : "";

    if ($name === "") {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Plant name is required."]);
        exit;
    }

    // 4. Generate AI care notes
    $ai_result = generate_care_notes($name, $species, $owner_notes);
    if (!$ai_result["success"]) {
        http_response_code(502);
        echo json_encode(["success" => false, "error" => $ai_result["error"]]);
        exit;
    }
    $care_notes = $ai_result["data"];

    // 5. Detect database connection variable ($mysql or $conn)
    $db = isset($mysql) ? $mysql : (isset($conn) ? $conn : null);
    if (!$db || !($db instanceof mysqli)) {
        throw new Exception("Database connection (\$mysql or \$conn) is not initialized.");
    }

    // 6. Save plant to MySQL
    $sql = "INSERT INTO plants (owner_id, name, species, care_notes) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database prepare statement failed: " . $db->error);
    }

    $stmt->bind_param("isss", $owner_id, $name, $species, $care_notes);

    if (!$stmt->execute()) {
        throw new Exception("Database execution failed: " . $stmt->error);
    }

    $new_plant_id = $stmt->insert_id;
    $stmt->close();

    // 7. Success response
    echo json_encode([
        "success" => true,
        "data" => [
            "plant_id" => $new_plant_id,
            "name" => $name,
            "species" => $species,
            "care_notes" => $care_notes,
            "message" => "Plant registered successfully with AI care notes."
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Server Error: " . $e->getMessage()
    ]);
    exit;
}
?>