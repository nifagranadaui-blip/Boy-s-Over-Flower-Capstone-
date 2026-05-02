<?php
require_once '../includes/db.php';
requireUserLogin(); 

$db = getDB();
$message = "";

// 1. Handle Extraction Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_extraction'])) {
    $batchId = $_POST['batch_id'];
    $notes   = $_POST['notes'];
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
            $message = "<div class='alert success'>✅ Successfully logged $successCount extraction(s).</div>";
        } else {
            $db->rollback();
            $message = "<div class='alert danger'>⚠️ No weights were entered. Nothing saved.</div>";
        }
    } catch (Exception $e) {
        $db->rollback();
        $message = "<div class='alert danger'>❌ Database Error: " . $e->getMessage() . "</div>";
    }
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
    <title>Aldi Foods |Raw Materials Extraction</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { 
            /* EXACT COLORS FROM YOUR YIELD PAGE */
            --bg-main: #121212;
            --card-bg: #1e1e1e;
            --primary-green: #2ecc71;
            --muted-text: #888888;
            --border-color: #2a2a2a;
            --input-bg: #252525;
        }

        body { 
            background: var(--bg-main);
            min-height: 100vh;
            font-family: 'Inter', sans-serif; 
            color: #ffffff; 
            margin: 0; 
        }

        .main-content { padding: 40px; }

        .glass-card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 25px; 
            border: 1px solid var(--border-color); 
            margin-bottom: 30px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .section-title { 
            color: var(--primary-green); 
            border-left: 4px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 25px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Form Controls */
        select, textarea, input { 
            width: 100%; 
            padding: 12px; 
            border-radius: 6px; 
            background: var(--input-bg); 
            color: white; 
            border: 1px solid var(--border-color); 
            margin-bottom: 15px; 
            box-sizing: border-box;
            outline: none;
            font-size: 0.95rem;
        }

        select:focus, input:focus, textarea:focus {
            border-color: var(--primary-green);
            background: #2a2a2a;
        }

        label { 
            font-weight: 500; 
            font-size: 0.85rem; 
            color: var(--muted-text); 
            display: block; 
            margin-bottom: 8px; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }

        .btn-save { 
            background: var(--primary-green); 
            color: #000; 
            font-weight: 700; 
            border: none; 
            cursor: pointer; 
            padding: 14px 30px; 
            border-radius: 6px; 
            width: 100%; 
            margin-top: 10px; 
            text-transform: uppercase;
            transition: transform 0.2s, opacity 0.2s;
        }

        .btn-save:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-add-manual { 
            background: rgba(255,255,255,0.03); 
            color: var(--muted-text); 
            border: 1px dashed #444; 
            padding: 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            margin-bottom: 20px; 
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-add-manual:hover { border-color: var(--primary-green); color: #fff; background: rgba(255,255,255,0.05); }

        /* Table Styling */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { 
            text-align: left; 
            color: var(--muted-text); 
            padding: 15px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.75rem; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .data-table td { 
            padding: 15px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.9rem; 
        }
        
        .material-row-item { 
            display: grid; 
            grid-template-columns: 2fr 1fr auto; 
            gap: 15px; 
            margin-bottom: 10px; 
            align-items: center; 
        }

        .material-row-item input { margin-bottom: 0; }

        .remove-btn { color: #e74c3c; cursor: pointer; background: none; border: none; font-size: 1.5rem; padding: 0 10px; line-height: 1; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.2); color: var(--primary-green); }
        .danger { background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.2); color: #e74c3c; }
        
        .batch-badge { color: var(--primary-green); font-weight: 700; }
        .notes-text { color: var(--muted-text); font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    
    <div class="main-content">
        <h1 style="margin-bottom: 30px;">Raw Materials Extraction</h1>
        
        <?= $message ?>

        <div class="glass-card">
            <h3 class="section-title">New Log Entry</h3>
            <form method="POST" id="extractionForm">
                <label>Production Batch</label>
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

                <label>Extraction List</label>
                <div id="materials-list-container">
                    <p style="color: #555; font-style: italic; margin-bottom: 15px;">Please select a batch above to load materials...</p>
                </div>

                <button type="button" id="manualBtn" class="btn-add-manual" style="display:none;" onclick="addManualRow()">+ Add Additional Material</button>

                <label>Notes / Purpose</label>
                <textarea name="notes" placeholder="e.g. Daily production run extraction..." rows="2"></textarea>

                <button type="submit" name="log_extraction" class="btn-save">Save Extraction to Database</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 0;">
            <div style="padding: 20px 25px;">
                <h3 class="section-title" style="margin-bottom: 0;">Recent History</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Batch / Product</th>
                            <th>Materials (Qty)</th>
                            <th>Logged By / Notes</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($extractionLogs && $extractionLogs->num_rows > 0): ?>
                            <?php while ($row = $extractionLogs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="batch-badge"><?= htmlspecialchars($row['batch_number']) ?></span><br>
                                        <small style="color:var(--muted-text)"><?= htmlspecialchars($row['product_name']) ?></small>
                                    </td>
                                    <td><span style="color:#eee"><?= htmlspecialchars($row['combined_materials']) ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['extracted_by']) ?></strong><br>
                                        <span class="notes-text"><?= htmlspecialchars($row['extraction_notes'] ?: 'No notes provided') ?></span>
                                    </td>
                                    <td><small><?= date('M d, H:i', strtotime($row['extraction_datetime'])) ?></small></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color: var(--muted-text);">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    
    container.innerHTML = '<p style="color: var(--primary-green);">Fetching materials...</p>';
    manualBtn.style.display = 'block';

    fetch('get_batch_materials.php?batch_id=' + batchId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.length > 0) {
                html = '<div style="margin-bottom:10px; display:grid; grid-template-columns:2fr 1fr auto; font-size:0.75rem; color:var(--muted-text); text-transform:uppercase;"><div>Material Name</div><div>Kg Weight</div><div></div></div>';
                data.forEach(item => {
                    html += `
                        <div class="material-row-item">
                            <input type="text" name="material_name[]" value="${item.material_name}" readonly style="background:rgba(255,255,255,0.03); color:var(--muted-text); cursor:not-allowed;">
                            <input type="number" step="0.01" name="material_kg[]" placeholder="0.00">
                            <div style="width:45px"></div>
                        </div>`;
                });
            } else { 
                html = '<p style="color:var(--muted-text)">No predefined materials for this batch. Use manual add.</p>'; 
            }
            container.innerHTML = html;
        });
}

function addManualRow() {
    const div = document.createElement('div');
    div.className = 'material-row-item';
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