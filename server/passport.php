<?php
// server/passport.php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$response = ["success" => false];

try {
    // 1. Database connection
    $conn_path = __DIR__ . '/connection.php';
    if (!file_exists($conn_path)) {
        throw new Exception("connection.php file not found.");
    }
    require_once $conn_path;

    $db = isset($mysql) ? $mysql : (isset($conn) ? $conn : null);
    if (!$db || !($db instanceof mysqli)) {
        throw new Exception("Database connection variable (\$mysql / \$conn) is not initialized.");
    }

    // 2. Validate plant_id parameter
    $plant_id = isset($_GET["plant_id"]) ? intval($_GET["plant_id"]) : 0;
    if ($plant_id <= 0) {
        http_response_code(400);
        $response["error"] = "Valid plant_id is required";
        echo json_encode($response);
        exit;
    }

    // 3. Fetch plant details & owner info from `plants` and `users` tables
    $sql_plant = "SELECT p.id, p.owner_id, p.name, p.species, p.care_notes, p.created_at,
                         u.username AS owner_username,
                         u.region AS owner_region
                  FROM plants p
                  LEFT JOIN users u ON u.id = p.owner_id
                  WHERE p.id = ?";

    $query_plant = $db->prepare($sql_plant);
    if (!$query_plant) {
        throw new Exception("SQL error on plants query: " . $db->error);
    }

    $query_plant->bind_param("i", $plant_id);
    $query_plant->execute();
    $plant_result = $query_plant->get_result();

    if ($plant_result->num_rows === 0) {
        http_response_code(404);
        $response["error"] = "Plant not found";
        echo json_encode($response);
        exit;
    }

    $plant = $plant_result->fetch_assoc();
    $query_plant->close();

    // Fallback if the user account was deleted
    if (empty($plant['owner_username'])) {
        $plant['owner_username'] = 'Unknown Owner';
    }

    // 4. Fetch the sits / bookings timeline for this plant
    $timeline = [];
    
    // Check if the `sits` table exists to avoid SQL crashing
    $table_check = $db->query("SHOW TABLES LIKE 'sits'");
    if ($table_check && $table_check->num_rows > 0) {
        $sql_sits = "SELECT s.id, s.start_date, s.end_date, s.status, s.sitter_note, 
                            s.created_at, s.claimed_at,
                            sitter.username AS sitter_username
                     FROM sits s
                     LEFT JOIN users sitter ON sitter.id = s.sitter_id
                     WHERE s.plant_id = ?
                     ORDER BY s.start_date ASC, s.created_at ASC";

        $query_sits = $db->prepare($sql_sits);
        if ($query_sits) {
            $query_sits->bind_param("i", $plant_id);
            $query_sits->execute();
            $sits_result = $query_sits->get_result();

            while ($row = $sits_result->fetch_assoc()) {
                $timeline[] = $row;
            }
            $query_sits->close();
        }
    }

    // 5. Structure final response matching passport.js expectations
    $response["success"] = true;
    $response["data"] = $plant;
    $response["data"]["timeline"] = $timeline;

    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    $response["success"] = false;
    $response["error"] = "Server error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}
?>