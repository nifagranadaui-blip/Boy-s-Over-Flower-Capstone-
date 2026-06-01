<?php
require_once '../includes/db.php';
requireUserLogin(); 

$db = getDB();
$error = '';
$success = '';

// 1. Handle Extraction Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_extraction'])) {
    $batchId = $_POST['batch_id'];
    $notes   = trim($_POST['notes']);
    $user    = $_SESSION['user_name'] ?? 'System';
    
    $material_names = $_POST['material_name'] ?? [];
    $material_kgs   = $_POST['material_kg'] ?? [];

    $db->begin_transaction();
    try {
        $stmt = $db->prepare("INSERT INTO material_extractions (batch_id, extracted_by, extraction_notes, material_name, kg_extracted) VALUES (?, ?, ?, ?, ?)");
        
        $successCount = 0;
        foreach ($material_names as $index => $m_name) {
            $m_kg = isset($material_kgs[$index]) ? (float)$material_kgs[$index] : 0;
            
            if ($m_kg > 0 && !empty(trim($m_name))) {
                $stmt->bind_param("isssd", $batchId, $user, $notes, $m_name, $m_kg);
                $stmt->execute();
                $successCount++;
            }
        }
        
        if ($successCount > 0) {
            $db->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . $successCount);
            exit();
        } else {
            $db->rollback();
            $error = "No weights were entered. Nothing saved.";
        }
    } catch (Exception $e) {
        $db->rollback();
        $error = "Database Error: " . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = "Successfully logged " . htmlspecialchars($_GET['success']) . " extraction(s).";
}

// 2. FETCH ACTIVE BATCHES
$batchQuery = "SELECT id, batch_number, product_name FROM production_batches ORDER BY production_datetime DESC";
$batches = $db->query($batchQuery);

// 3. FETCH EXTRACTION HISTORY
$historyQuery = "SELECT 
                    pb.batch_number, 
                    pb.product_name, 
                    me.extracted_by, 
                    me.extraction_notes, 
                    me.extraction_datetime,
                    GROUP_CONCAT(CONCAT(me.material_name, ' (', me.kg_extracted, 'kg)') SEPARATOR ' • ') as combined_materials
                 FROM material_extractions me
                 JOIN production_batches pb ON me.batch_id = pb.id
                 GROUP BY pb.id, me.extraction_datetime, me.extracted_by
                 ORDER BY me.extraction_datetime DESC LIMIT 20";
$extractionLogs = $db->query($historyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raw Materials Extraction — ALDiFOODS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .input-row { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 25px; }
        .material-item { display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; margin-bottom: 12px; align-items: center; }
        .material-item input { margin-bottom: 0; }
        .remove-btn { color: #e74c3c; cursor: pointer; border: none; background: none; font-size: 1.4rem; padding: 0 10px; line-height: 1; }
    </style>
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    
    <div class="main-content">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1>Raw Materials <span class="text-accent">Extraction</span></h1>
                <div class="breadcrumb">Log weights and check items out from production batches</div>
            </div>
        </div>

        <!-- NOTIFICATION ALERTS -->
        <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <!-- STACKED LAYOUT (VERTICAL DIRECTION) -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- EXTRACTION ENTRY CARD FORM (TOP) -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>New Log Entry</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <form method="POST" id="extractionForm">
                        <div class="input-row">
                            <div class="form-group">
                                <label style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Production Batch</label>
                                <select name="batch_id" id="batchSelect" required onchange="fetchMaterials(this.value)">
                                    <option value="">-- Select a Batch --</option>
                                    <?php 
                                    $batches->data_seek(0); 
                                    while($b = $batches->fetch_assoc()): ?>
                                        <option value="<?= $b['id'] ?>">
                                            <?= htmlspecialchars($b['batch_number']) ?> — <?= htmlspecialchars($b['product_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 12px; display: block;">Extraction List</label>
                            <div id="materials-list-container">
                                <p style="color: #555; font-style: italic; margin-bottom: 15px;">Please select a batch above to load materials...</p>
                            </div>
                        </div>
                        
                        <button type="button" id="manualBtn" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; margin-top: 5px; display: none;" onclick="addManualRow()">+ Add Additional Material</button>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Notes / Purpose</label>
                            <textarea name="notes" placeholder="e.g. Daily production run extraction..." rows="2"></textarea>
                        </div>

                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" name="log_extraction" class="btn btn-primary">Save Extraction to Database</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ACTIVITY LOGS DATA CARD (BOTTOM) -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Recent History</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Batch / Product</th>
                                <th style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Materials (Qty)</th>
                                <th style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Logged By / Notes</th>
                                <th style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($extractionLogs && $extractionLogs->num_rows > 0): ?>
                                <?php while ($row = $extractionLogs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--accent);"><?= htmlspecialchars($row['batch_number']) ?></div>
                                            <div style="font-size: 0.85rem; color: var(--text);"><?= htmlspecialchars($row['product_name']) ?></div>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 250px; line-height: 1.4; word-break: break-word;">
                                            <?= htmlspecialchars($row['combined_materials']) ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($row['extracted_by']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">
                                                <?= htmlspecialchars($row['extraction_notes'] ?: 'No notes provided') ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 0.85rem;">
                                            <div style="color: var(--text);"><?= date('M d, Y', strtotime($row['extraction_datetime'])) ?></div>
                                            <div class="text-muted"><?= date('h:i A', strtotime($row['extraction_datetime'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem;" class="text-muted">No logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const container = document.getElementById('materials-list-container');
const manualBtn = document.getElementById('manualBtn');

function fetchMaterials(batchId) {
    if (!batchId) {
        container.innerHTML = '<p style="color: #555; font-style: italic;">Please select a batch above...</p>';
        manualBtn.style.display = 'none';
        return;
    }
    
    container.innerHTML = '<p style="color: var(--accent);">Fetching materials...</p>';
    manualBtn.style.display = 'block';

    fetch('get_batch_materials.php?batch_id=' + batchId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.length > 0) {
                data.forEach(item => {
                    html += `
                        <div class="material-item">
                            <input type="text" name="material_name[]" value="${item.material_name}" readonly style="background:rgba(255,255,255,0.03); color:var(--text-muted); cursor:not-allowed;">
                            <input type="number" step="0.01" name="material_kg[]" placeholder="0.00">
                            <div style="width:44px"></div>
                        </div>`;
                });
            } else { 
                html = '<p class="text-muted" style="font-style: italic;">No predefined materials for this batch. Use manual add.</p>'; 
            }
            container.innerHTML = html;
        });
}

function addManualRow() {
    const div = document.createElement('div');
    div.className = 'material-item';
    div.innerHTML = `
        <input type="text" name="material_name[]" placeholder="Material Name" required>
        <input type="number" step="0.01" name="material_kg[]" placeholder="0.00" required>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
}
</script>
</body>
</html>
