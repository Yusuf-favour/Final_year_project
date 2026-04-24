<?php
/* ================================================================
   API Endpoint: Get all active institutions
   Used by login page to populate dropdown
   ================================================================ */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include "../db.php";

$institutions = [];

try {
    // Check if institutions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'institutions'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Table exists, query it
        $stmt = $conn->prepare(
            "SELECT id, code, name, short_name 
             FROM institutions 
             WHERE is_active = 1 
             ORDER BY name ASC"
        );

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()) {
                $institutions[] = $row;
            }
            $stmt->close();
        }
    } else {
        // Table doesn't exist - return empty array
        // User needs to run migration
        $institutions = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $institutions
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching institutions',
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
