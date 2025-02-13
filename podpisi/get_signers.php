<?php
header('Content-Type: application/json');

try {
    $db = new SQLite3('petitions.db');
    $signers = [];
    
    $result = $db->query('SELECT nickname FROM signatures ORDER BY created_at DESC');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $signers[] = $row;
    }
    
    echo json_encode($signers);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}