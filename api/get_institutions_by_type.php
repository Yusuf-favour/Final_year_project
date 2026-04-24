<?php
/* ================================================================
   API: Get Institutions by Type/Category
   Used by login page cascading dropdowns
   ================================================================ */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include "../db.php";

try {
    // Parameter: type (institution_type)
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    
    if (empty($type)) {
        // Return all types
        $stmt = $conn->prepare(
            "SELECT DISTINCT institution_type 
             FROM institutions 
             WHERE is_active = 1 
             ORDER BY institution_type ASC"
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        $types = [];
        
        while($row = $result->fetch_assoc()) {
            $types[] = [
                'value' => $row['institution_type'],
                'label' => $row['institution_type']
            ];
        }
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $types
        ]);
    } else {
        // Return institutions of selected type
        $stmt = $conn->prepare(
            "SELECT id, code, name, short_name 
             FROM institutions 
             WHERE institution_type = ? AND is_active = 1 
             ORDER BY name ASC"
        );
        
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $institutions = [];
        
        while($row = $result->fetch_assoc()) {
            $institutions[] = $row;
        }
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $institutions
        ]);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching data',
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
