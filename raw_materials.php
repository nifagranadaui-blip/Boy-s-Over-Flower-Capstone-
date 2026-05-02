<?php
// 1. Database Connection & Requirements
require_once '../includes/db.php';
requireUserLogin(); 

$db = getDB();
$message = "";

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_batch'])) {
    $batchNo  = trim($_POST['batch_number']); 
    $product  = $_POST['product_name'];
    $m_names  = $_POST['material_name']; 
    $m_kgs    = $_POST['material_kg']; 
    
    $currentUser = $_SESSION['user_name'] ?? 'System'; 

    $checkStmt = $db->prepare("SELECT id FROM production_batches WHERE batch_number = ? LIMIT 1");
    $checkStmt->bind_param("s", $batchNo);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert danger'>⚠️ Error: Batch #$batchNo already exists!</div>";
    } else {
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("INSERT INTO production_batches (batch_number, product_name, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $batchNo, $product, $currentUser);
            $stmt->execute();
            $batchId = $db->insert_id;

            $stmtMat = $db->prepare("INSERT INTO batch_materials (batch_id, material_name, original_kg) VALUES (?, ?, ?)");
            
            foreach ($m_names as $index => $name) {
                if (!empty($name)) {
                    $qty = !empty($m_kgs[$index]) ? $m_kgs[$index] : 0;
                    $stmtMat->bind_param("isd", $batchId, $name, $qty);
                    $stmtMat->execute();
                }
            }

            $db->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } catch (Exception $e) {
            $db->rollback();
            $message = "<div class='alert danger'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

if(isset($_GET['success'])) {
    $message = "<div class='alert success'>✅ Record saved successfully!</div>";
}

$historyQuery = "SELECT pb.batch_number, pb.product_name, pb.created_by, pb.production_datetime,
                 GROUP_CONCAT(CONCAT(bm.material_name, ' (', bm.original_kg, 'kg)') SEPARATOR ' • ') as materials 
                 FROM production_batches pb
                 LEFT JOIN batch_materials bm ON pb.id = bm.batch_id
                 GROUP BY pb.id
                 ORDER BY pb.production_datetime DESC LIMIT 15";
$history = $db->query($historyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aldi Foods | Raw Materials</title>
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
            font-weight: 700; 
            color: var(--primary-green); 
            border-left: 4px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 25px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .input-group label { font-size: 0.8rem; color: var(--muted-text); text-transform: uppercase; margin-bottom: 8px; display: block; }

        input { 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            background: #252525; 
            color: white; 
            border-radius: 8px; 
            width: 100%; 
            box-sizing: border-box;
        }
        input:focus { border-color: var(--primary-green); outline: none; background: #2a2a2a; }

        .material-item { display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; margin-bottom: 12px; }
        
        .btn-add { 
            background: rgba(255,255,255,0.03); 
            color: var(--muted-text); 
            border: 1px dashed var(--border-color); 
            width: 100%; 
            padding: 12px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: 0.2s;
        }
        .btn-add:hover { background: rgba(255,255,255,0.06); color: #fff; }

        .btn-save { 
            background: var(--primary-green); 
            color: #000; 
            padding: 14px 40px; 
            border-radius: 8px; 
            border: none; 
            font-weight: 700; 
            cursor: pointer; 
            text-transform: uppercase;
        }

        .remove-btn { color: #e74c3c; cursor: pointer; border: none; background: none; font-size: 1.4rem; padding: 0 10px; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { 
            text-align: left; 
            color: var(--muted-text); 
            padding: 15px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
        }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }

        .user-badge { font-size: 0.75rem; color: var(--muted-text); display: block; margin-top: 4px; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; }
        .alert.success { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.2); }
        .alert.danger { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.2); }
    </style>
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    <div class="main-content">
        <h1 style="margin-bottom: 30px;">Raw Materials Management</h1>
        
        <?= $message ?>

        <div class="glass-card">
            <form method="POST" id="productionForm">
                <h3 class="section-title">Batch Essentials</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Batch Number</label>
                        <input type="text" name="batch_number" placeholder="e.g. ALDI-BT-101" required>
                    </div>
                    <div class="input-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" placeholder="Product Name" required>
                    </div>
                </div>

                <h3 class="section-title">Raw Materials List</h3>
                <div id="materials-container">
                    <div class="material-item">
                        <input type="text" name="material_name[]" placeholder="Material Name" required>
                        <input type="number" step="0.01" name="material_kg[]" placeholder="Kg" required>
                        <div style="width: 44px;"></div>
                    </div>
                </div>
                
                <button type="button" class="btn-add" onclick="addMaterialField()">+ Add Additional Material Row</button>
                
                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" name="save_batch" class="btn-save">Save Batch</button>
                </div>
            </form>
        </div>

        <div class="glass-card" style="padding: 0;">
            <div style="padding: 20px 25px;">
                <h3 class="section-title" style="margin-bottom: 0;">Production Activity Logs</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch & Author</th>
                        <th>Composition</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history && $history->num_rows > 0): ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary-green);"><?= htmlspecialchars($row['batch_number']) ?></div>
                                    <div style="font-size: 0.85rem; color: #eee;"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <span class="user-badge">User: <?= htmlspecialchars($row['created_by']) ?></span>
                                </td>
                                <td style="font-size: 0.85rem; color: #bbb; max-width: 350px; line-height: 1.4;">
                                    <?= htmlspecialchars($row['materials'] ?? 'No materials logged') ?>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <div style="color: #eee;"><?= date('M d, Y', strtotime($row['production_datetime'])) ?></div>
                                    <div style="color: var(--muted-text);"><?= date('h:i A', strtotime($row['production_datetime'])) ?></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; padding: 40px; color: var(--muted-text);">No production activity recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function addMaterialField() {
    const container = document.getElementById('materials-container');
    const div = document.createElement('div');
    div.className = 'material-item';
    div.innerHTML = `
        <input type="text" name="material_name[]" placeholder="Additional Material" required>
        <input type="number" step="0.01" name="material_kg[]" placeholder="Kg" required>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
}
</script>
</body>
</html>