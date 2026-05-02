<?php
require_once '../includes/db.php';
$db = getDB();

    if (isset($_GET['batch_id'])) {
        $batchId = intval($_GET['batch_id']);
        // Adjust table name if your raw materials are stored in a different table
        $stmt = $db->prepare("SELECT material_name FROM batch_materials WHERE batch_id = ?");
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $materials = [];
        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($materials);
    }
?>