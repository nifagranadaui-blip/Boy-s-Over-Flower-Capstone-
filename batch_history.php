<?php
require_once '../includes/db.php';
requireAdminLogin(); 

$db = getDB();

// 1. UPDATED MASTER QUERY
$query = "SELECT 
    pb.id, 
    pb.batch_number, 
    pb.product_name, 
    pb.production_datetime,
    pb.created_by, 
    -- 1. Fetch Raw Materials Input List
    (SELECT GROUP_CONCAT(CONCAT(material_name, ' (', FORMAT(original_kg, 2), 'kg)') SEPARATOR ' • ') 
     FROM batch_materials WHERE batch_id = pb.id) as raw_materials_list,
    
    -- 2. Fetch Extracted Materials List
    (SELECT GROUP_CONCAT(CONCAT(material_name, ' (', FORMAT(kg_extracted, 2), 'kg)') SEPARATOR ' • ') 
     FROM material_extractions WHERE batch_id = pb.id) as extraction_list,
     
    -- 3. Numeric Totals for Calculation
    (SELECT SUM(original_kg) FROM batch_materials WHERE batch_id = pb.id) as total_input_kg,
    (SELECT SUM(kg_extracted) FROM material_extractions WHERE batch_id = pb.id) as total_extracted_kg,
    
    SUM(py.total_packs_produced) as total_packs,
    
    -- 4. Calculate Efficiency
    ( (SUM(py.actual_grams * py.total_packs_produced) / 1000) / 
      NULLIF((SELECT SUM(kg_extracted) FROM material_extractions WHERE batch_id = pb.id), 0) * 100 
    ) as efficiency_rate

    FROM production_batches pb
    LEFT JOIN pack_yields py ON pb.id = py.batch_id
    GROUP BY pb.id
    ORDER BY pb.production_datetime DESC";

$results = $db->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Batch History Admin | ALDiFOODS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
        .badge-green { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .badge-amber { background: rgba(241, 196, 15, 0.2); color: #f1c40f; }
        .badge-red { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .text-sm { font-size: 0.75rem; }
        .stat-label { color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-size: 0.7rem; font-weight: bold; }
        
        .material-text { font-size: 0.85rem; line-height: 1.4; color: #111111; display: block; max-width: 300px; font-weight: 500; }
        .loss-text { font-weight: bold; margin-top: 5px; display: block; }
        .user-tag { color: #3498db; font-weight: 600; margin-top: 4px; display: block; font-size: 0.7rem; }

        /* Page Layout Styles */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        /* Updated Print Button Styles to Green Theme */
        .btn-print { background: #2ecc71; color: #ffffff; padding: 10px 18px; border: none; border-radius: 6px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s, transform 0.1s; }
        .btn-print:hover { background: #27ae60; }
        .btn-print:active { transform: scale(0.98); }

        /* --- PRINT STYLES --- */
        @media print {
            body { background: #ffffff; color: #000000; font-size: 12px; }
            ._sidebar, .sidebar, .btn-print, .no-print { display: none !important; }
            .layout { display: block; }
            .main-content { padding: 0; margin: 0; width: 100%; }
            .table-card { box-shadow: none; border: none; padding: 0; }
            .data-table { width: 100%; border-collapse: collapse; }
            .data-table th, .data-table td { border: 1px solid #ddd !important; padding: 8px !important; }
            .badge { border: 1px solid #000; background: none !important; color: #000 !important; }
        }
    </style>
</head>
<body>
<div class="layout">
<?php include '_sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Production Audit</h1>
            <div class="breadcrumb">Real-time batch efficiency and extraction tracking</div>
        </div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">
                <span>🖨️ Print / Save Daily PDF</span>
            </button>
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch Details</th>
                    <th>Raw Material Input</th>
                    <th>Extraction Results</th>
                    <th>Packing Yield</th>
                    <th>Efficiency</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results->num_rows === 0): ?>
                <tr><td colspan="5" style="text-align:center; padding:50px;">No records found.</td></tr>
            <?php else: ?>
            <?php while($row = $results->fetch_assoc()): 
                $inputTotal = $row['total_input_kg'] ?? 0;
                $extractedTotal = $row['total_extracted_kg'] ?? 0;
                $loss = ($inputTotal > 0) ? (($inputTotal - $extractedTotal) / $inputTotal) * 100 : 0;
                $efficiency = $row['efficiency_rate'] ?? 0;
            ?>
                <tr>
                    <td>
                        <div style="color:var(--primary-green); font-weight:800;">#<?= $row['batch_number'] ?></div>
                        <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                        <div class="text-sm text-muted"><?= date('M d, Y', strtotime($row['production_datetime'])) ?></div>
                        <div class="user-tag">
                            <i class="fas fa-user-edit"></i> By: <?= htmlspecialchars($row['created_by'] ?? 'System') ?>
                        </div>
                    </td>
                    
                    <td>
                        <div class="stat-label">Materials</div>
                        <span class="material-text">
                            <?= $row['raw_materials_list'] ? htmlspecialchars($row['raw_materials_list']) : '<i class="text-muted">No materials</i>' ?>
                        </span>
                        <div class="text-sm" style="margin-top:5px; color:#111111; font-weight: 600;">Total: <?= number_format($inputTotal, 2) ?>kg</div>
                    </td>

                    <td>
                        <div class="stat-label">Extracted</div>
                        <span class="material-text">
                            <?= $row['extraction_list'] ? htmlspecialchars($row['extraction_list']) : '<i class="text-muted">No extraction data</i>' ?>
                        </span>
                        <div class="text-sm loss-text" style="color:#111111;">
                            Loss: <span style="color:<?= $loss > 15 ? '#e74c3c' : '#f1c40f' ?>; font-weight: 800;"><?= number_format($loss, 1) ?>%</span> 
                            (<?= number_format($extractedTotal, 2) ?>kg yield)
                        </div>
                    </td>

                    <td>
                        <?php if($row['total_packs']): ?>
                            <div class="stat-label">Total Units</div>
                            <strong style="color: #111111;"><?= number_format($row['total_packs']) ?> Packs</strong>
                        <?php else: ?>
                            <span class="text-muted italic">No packs logged</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if($efficiency > 0): ?>
                            <div class="badge <?= $efficiency > 98 ? 'badge-green' : ($efficiency > 90 ? 'badge-amber' : 'badge-red') ?>">
                                <?= number_format($efficiency, 1) ?>%
                            </div>
                            <div class="text-sm text-muted" style="margin-top:4px;">Yield Efficiency</div>
                        <?php else: ?>
                            <span class="text-muted">--</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>
