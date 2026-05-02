<?php
require_once '../includes/db.php';
requireUserLogin(); 

$db = getDB();
$message = "";

// --- SAFETY CHECK ---
$db->query("CREATE TABLE IF NOT EXISTS pack_yields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    user_id INT NOT NULL,
    yield_total_kg DECIMAL(10,2) NOT NULL,
    actual_grams DECIMAL(10,2) NOT NULL,
    total_packs_produced INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 1. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_yield'])) {
    $batchId = (int)$_POST['batch_id'];
    $yieldKg = (float)$_POST['yield_total_kg'];
    $gramsArray = $_POST['actual_grams'] ?? [];
    $qtyArray = $_POST['pack_qty'] ?? [];
    $userId = $_SESSION['user_id'] ?? 0; 
    
    if ($batchId > 0 && !empty($gramsArray) && $yieldKg > 0) {
        $successCount = 0;
        $stmt = $db->prepare("INSERT INTO pack_yields (batch_id, user_id, yield_total_kg, actual_grams, total_packs_produced) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($gramsArray as $index => $grams) {
            $gramsValue = (float)$grams;
            $qtyValue = (int)$qtyArray[$index];

            if ($gramsValue > 0 && $qtyValue > 0) {
                $stmt->bind_param("iiddi", $batchId, $userId, $yieldKg, $gramsValue, $qtyValue);
                if ($stmt->execute()) { 
                    $successCount++; 
                }
            }
        }
        
        if ($successCount > 0) {
            $message = "<div class='alert success'>✅ $successCount entries logged successfully!</div>";
        }
    } else {
        $message = "<div class='alert' style='background:rgba(231,76,60,0.1); color:#e74c3c;'>❌ Please fill in all fields correctly.</div>";
    }
}

// 2. FETCH BATCHES
$batches = $db->query("SELECT id, batch_number, product_name FROM production_batches ORDER BY production_datetime DESC");

// 3. FETCH HISTORY
$historyQuery = "SELECT 
                    pb.batch_number, 
                    pb.product_name, 
                    py.yield_total_kg,
                    py.actual_grams, 
                    py.total_packs_produced, 
                    py.created_at,
                    u.username as logged_by
                 FROM pack_yields py
                 JOIN production_batches pb ON py.batch_id = pb.id
                 LEFT JOIN users u ON py.user_id = u.id
                 ORDER BY py.created_at DESC LIMIT 50";

$yieldLogs = $db->query($historyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aldi Foods | Packs Yield Tracker</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --bg-main: #121212; --card-bg: #1e1e1e; --primary-green: #2ecc71; --muted-text: #888888; --border-color: #2a2a2a; --input-bg: #252525; }
        body { background: var(--bg-main); color: #ffffff; font-family: 'Inter', sans-serif; margin: 0; display: flex; }
        .layout { display: flex; width: 100%; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .glass-card { background: var(--card-bg); border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid var(--border-color); }
        .section-title { font-weight: 700; color: var(--primary-green); border-left: 4px solid var(--primary-green); padding-left: 15px; margin-bottom: 25px; text-transform: uppercase; }
        
        /* Log Packing Result Grid (3 Columns) */
        .yield-row-header { display: grid; grid-template-columns: 1.2fr 1fr 1fr 50px; gap: 15px; margin-bottom: 10px; }
        .yield-row { display: grid; grid-template-columns: 1.2fr 1fr 1fr 50px; gap: 15px; margin-bottom: 10px; align-items: center; }
        
        .input-group label { font-size: 0.7rem; color: var(--muted-text); text-transform: uppercase; margin-bottom: 8px; display: block; }
        input, select { padding: 12px; border: 1px solid var(--border-color); background: var(--input-bg); color: white; border-radius: 8px; width: 100%; box-sizing: border-box; }
        
        .btn-add { background: transparent; color: var(--primary-green); border: 1px dashed var(--primary-green); padding: 10px; border-radius: 8px; cursor: pointer; width: 100%; margin-bottom: 20px; font-weight: 600; }
        .btn-remove { background: #e74c3c; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; height: 42px; }
        .btn-save { background: var(--primary-green); color: #000; padding: 14px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; width: 100%; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; color: var(--muted-text); padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 0.7rem; text-transform: uppercase; }
        .data-table td { padding: 12px 15px; border-bottom: 1px solid #252525; font-size: 0.9rem; }
        
        .batch-header-row { background: rgba(46, 204, 113, 0.05); }
        .batch-tag { color: var(--primary-green); font-weight: 800; font-size: 0.9rem; }
        .yield-text { color: #fff; font-weight: 700; font-size: 0.9rem; }
        .pack-item { color: #ccc; border-left: 2px solid var(--primary-green); padding-left: 10px; }
        
        .user-badge { font-size: 0.7rem; color: var(--muted-text); background: #252525; padding: 3px 8px; border-radius: 4px; border: 1px solid var(--border-color); }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; }
        .alert.success { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.2); }
    </style>
</head>
<body>

<div class="layout">
    <?php include '_sidebar.php'; ?>

    <div class="main-content">
        <h1>Packed Yields</h1>
        <?= $message ?>

        <div class="glass-card">
            <h3 class="section-title">Log Packing Results</h3>
            <form method="POST">
                <div class="input-group" style="margin-bottom: 25px; max-width: 400px;">
                    <label>Active Production Batch</label>
                    <select name="batch_id" required>
                        <option value="">-- Choose batch --</option>
                        <?php while($b = $batches->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_number']) ?> - <?= htmlspecialchars($b['product_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="rowContainer">
                    <div class="yield-row-header">
                        <label style="font-size: 0.7rem; color: var(--muted-text); text-transform: uppercase;">Total Yield Extracted (KG)</label>
                        <label style="font-size: 0.7rem; color: var(--muted-text); text-transform: uppercase;">Actual (Grams/Pack)</label>
                        <label style="font-size: 0.7rem; color: var(--muted-text); text-transform: uppercase;">Qty Packed (Units)</label>
                        <div></div>
                    </div>

                    <div class="yield-row">
                        <div class="input-group">
                            <input type="number" name="yield_total_kg" step="0.01" placeholder="Total KG" required style="border-color: var(--primary-green);">
                        </div>
                        <div class="input-group">
                            <input type="number" name="actual_grams[]" step="0.01" placeholder="e.g. 500" required>
                        </div>
                        <div class="input-group">
                            <input type="number" name="pack_qty[]" placeholder="e.g. 100" required>
                        </div>
                        <div></div>
                    </div>
                </div>

                <button type="button" class="btn-add" onclick="addRow()">+ Add Different Pack Size</button>
                <button type="submit" name="log_yield" class="btn-save">Save All Packs</button>
            </form>
        </div>

        <div class="glass-card">
            <h3 class="section-title">🕒 Combined Yield History</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Batch & Product</th>
                        <th style="width: 20%;">Yield Total (KG)</th>
                        <th style="width: 35%;">Pack Variations</th>
                        <th style="width: 20%;">Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentBatch = null;
                    if($yieldLogs && $yieldLogs->num_rows > 0): 
                        while($log = $yieldLogs->fetch_assoc()): 
                            $batchKey = $log['batch_number'] . $log['created_at']; 
                            if ($currentBatch !== $batchKey): 
                                $currentBatch = $batchKey;
                    ?>
                            <tr class="batch-header-row">
                                <td>
                                    <span class="batch-tag">#<?= htmlspecialchars($log['batch_number']) ?></span><br>
                                    <small style="font-weight:600;"><?= htmlspecialchars($log['product_name']) ?></small>
                                </td>
                                <td>
                                    <span class="yield-text"><?= number_format($log['yield_total_kg'], 2) ?> KG</span>
                                </td>
                                <td></td>
                                <td>
                                    <small style="color:#555;"><?= date('M d, H:i', strtotime($log['created_at'])) ?></small>
                                </td>
                            </tr>
                    <?php endif; ?>
                            <tr>
                                <td></td> <td></td> <td>
                                    <div class="pack-item">
                                        <strong><?= number_format($log['actual_grams'], 1) ?>g</strong> 
                                        &nbsp;&rarr;&nbsp; 
                                        <?= number_format($log['total_packs_produced']) ?> Units
                                    </div>
                                </td>
                                <td>
                                    <span class="user-badge">👤 <?= htmlspecialchars($log['logged_by'] ?? 'Unknown User') ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--muted-text); padding: 50px;">No yield data recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function addRow() {
    const container = document.getElementById('rowContainer');
    const newRow = document.createElement('div');
    newRow.className = 'yield-row';
    // When adding a new row, we don't repeat the "Total KG" input because it applies to the whole batch, 
    // but we leave an empty space to maintain alignment.
    newRow.innerHTML = `
        <div></div>
        <div class="input-group"><input type="number" name="actual_grams[]" step="0.01" placeholder="Grams" required></div>
        <div class="input-group"><input type="number" name="pack_qty[]" placeholder="Quantity" required></div>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(newRow);
}
</script>

</body>
</html>