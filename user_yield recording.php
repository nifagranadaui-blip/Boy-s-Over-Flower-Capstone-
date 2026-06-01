<?php
require_once '../includes/db.php';
requireUserLogin(); 

$db = getDB();
$message = "";

// 1. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_yield'])) {
    $batchId     = (int)$_POST['batch_id']; 
    $actualGrams = (float)$_POST['actual_grams']; 
    $packQty     = (int)$_POST['pack_qty'];

    if ($batchId > 0 && $packQty > 0) {
        $stmt = $db->prepare("INSERT INTO production_yields (batch_id, target_kg, actual_grams, total_packs_produced) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iddi", $batchId, $targetKg, $actualGrams, $packQty);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'>✅ Yield logged successfully!</div>";
        } else {
            $message = "<div class='alert danger'>❌ Database Error: " . $db->error . "</div>";
        }
    } else {
        $message = "<div class='alert danger'>⚠️ Please ensure all fields are filled correctly.</div>";
    }
}

// 2. FETCH ACTIVE BATCHES FOR DROPDOWN
$batches = $db->query("SELECT id, batch_number, product_name FROM production_batches ORDER BY production_datetime DESC");

// 3. FETCH HISTORY WITH CALCULATIONS
$historyQuery = "SELECT 
                    pb.batch_number, 
                    pb.product_name, 
                    py.id,
                    py.target_kg,
                    py.actual_grams,
                    py.total_packs_produced,
                    py.created_at,
                    -- Efficiency: (Target converted to grams / Actual grams) * 100
                    ((py.target_kg * 1000) / NULLIF(py.actual_grams, 0) * 100) as efficiency_rate,
                    -- Total Weight: (Actual grams * Qty) / 1000 to get total Kg
                    ((py.actual_grams * py.total_packs_produced) / 1000) as total_weight_kg
                 FROM production_yields py
                 JOIN production_batches pb ON py.batch_id = pb.id
                 ORDER BY py.created_at DESC LIMIT 15";
$yieldLogs = $db->query($historyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aldi Foods | Packs Yield</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { 
            --bg-main: #121212; 
            --card-bg: #1e1e1e; 
            --primary-green: #2ecc71; 
            --muted-text: #888888;
            --border-color: #2a2a2a;
        }

        body { 
            background: var(--bg-main); 
            color: #ffffff; 
            font-family: 'Inter', sans-serif; 
            margin: 0;
        }

        .main-content { padding: 40px; }

        .glass-card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 30px; 
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .section-title { 
            color: var(--primary-green); 
            border-left: 4px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 25px;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input, select { 
            background: #252525; 
            border: 1px solid var(--border-color); 
            color: #ffffff; 
            padding: 12px; 
            border-radius: 6px; 
            width: 100%; 
            box-sizing: border-box; 
            font-size: 0.95rem;
            outline: none;
        }

        input:focus { border-color: var(--primary-green); background: #2a2a2a; }

        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        label { font-weight: 500; font-size: 0.75rem; color: var(--muted-text); display: block; margin-bottom: 8px; text-transform: uppercase; }

        .btn-submit { 
            background: var(--primary-green); 
            color: #000; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 6px; 
            font-weight: 700; 
            cursor: pointer; 
            margin-top: 20px;
            text-transform: uppercase;
        }

        .btn-submit:hover { opacity: 0.9; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { 
            text-align: left; 
            padding: 15px; 
            color: var(--muted-text); 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.7rem; 
            text-transform: uppercase;
        }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }

        .efficiency-pill { padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .good { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .warning { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .bad { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert.success { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.2); }
        .alert.danger { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.2); }
    </style>
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-bottom: 30px;">🌿 Packs Yield Recording</h1>
        
        <?= $message ?>

        <div class="glass-card">
            <h3 class="section-title">Log New Production Yield</h3>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label>Select Production Batch</label>
                    <select name="batch_id" required>
                        <option value="">-- Choose Batch --</option>
                        <?php while($b = $batches->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_number']) ?> — <?= htmlspecialchars($b['product_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="grid-inputs">
                    <div>
                        <label>Target Weight (Kg)</label>
                        <input type="number" name="target_kg" step="0.001" placeholder="e.g. 1.0" required>
                    </div>
                    <div>
                        <label>Actual Weight (Grams)</label>
                        <input type="number" name="actual_grams" step="0.01" placeholder="e.g. 985" required>
                    </div>
                    <div>
                        <label>Total Units Packed</label>
                        <input type="number" name="pack_qty" placeholder="e.g. 120" required>
                    </div>
                </div>

                <button type="submit" name="log_yield" class="btn-submit">Save Yield Data</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 0;">
            <div style="padding: 20px 25px;">
                <h3 class="section-title" style="margin-bottom: 0;">Yield History</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch Info</th>
                        <th>Target vs Actual</th>
                        <th>Qty Packed</th>
                        <th>Total Yield</th>
                        <th>Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($yieldLogs && $yieldLogs->num_rows > 0): ?>
                        <?php while($log = $yieldLogs->fetch_assoc()): 
                            $rate = $log['efficiency_rate'];
                            $class = ($rate >= 99) ? 'good' : (($rate >= 95) ? 'warning' : 'bad');
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($log['batch_number']) ?></strong><br>
                                    <small style="color:var(--muted-text)"><?= htmlspecialchars($log['product_name']) ?></small>
                                </td>
                                <td>
                                    <span style="color:var(--muted-text)"><?= number_format($log['target_kg'], 2) ?>kg</span> 
                                    <span style="margin: 0 5px; color:#444;">→</span>
                                    <strong><?= number_format($log['actual_grams'], 0) ?>g</strong>
                                </td>
                                <td><?= number_format($log['total_packs_produced']) ?> units</td>
                                <td><strong style="color:var(--primary-green)"><?= number_format($log['total_weight_kg'], 2) ?> Kg</strong></td>
                                <td>
                                    <span class="efficiency-pill <?= $class ?>">
                                        <?= number_format($rate, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted-text);">No logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
