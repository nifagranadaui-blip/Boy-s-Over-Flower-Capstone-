<?php
require_once '../includes/db.php';
requireAdminLogin();

$db = getDB();
$currentPage = 'analytics.php';

// --- 1. AUTOMATION LOGIC (Daily Calculations) ---
$todayQuery = $db->query("
    SELECT 
        SUM((SELECT SUM(original_kg) FROM batch_materials WHERE batch_id = pb.id)) as day_in,
        SUM((SELECT SUM(kg_extracted) FROM material_extractions WHERE batch_id = pb.id)) as day_out,
        (SELECT SUM(total_packs_produced) FROM pack_yields WHERE DATE(created_at) = CURDATE()) as day_packs
    FROM production_batches pb
    WHERE DATE(pb.production_datetime) = CURDATE()
")->fetch_assoc();

$snapshotIn    = $todayQuery['day_in'] ?? 0;
$snapshotOut   = $todayQuery['day_out'] ?? 0;
$snapshotPacks = $todayQuery['day_packs'] ?? 0;
$snapshotLoss  = $snapshotIn - $snapshotOut;
$snapshotEff   = ($snapshotIn > 0) ? ($snapshotOut / $snapshotIn) * 100 : 0;

// Save/Update today's snapshot automatically
$stmt = $db->prepare("
    INSERT INTO daily_analytics_snapshots 
    (snapshot_date, total_input_kg, total_output_kg, total_packs_produced, total_loss_kg, efficiency_percentage)
    VALUES (CURDATE(), ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    total_input_kg = VALUES(total_input_kg),
    total_output_kg = VALUES(total_output_kg),
    total_packs_produced = VALUES(total_packs_produced),
    total_loss_kg = VALUES(total_loss_kg),
    efficiency_percentage = VALUES(efficiency_percentage)
");
$stmt->bind_param("ddddd", $snapshotIn, $snapshotOut, $snapshotPacks, $snapshotLoss, $snapshotEff);
$stmt->execute();

// --- 2. PERIODIC RAW MATERIAL REPORTS ---
function getPeriodUsage($db, $interval) {
    $sql = "SELECT SUM(total_input_kg) as total_in, SUM(total_loss_kg) as total_loss 
            FROM daily_analytics_snapshots 
            WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL $interval)";
    return $db->query($sql)->fetch_assoc();
}

$usageWeek    = getPeriodUsage($db, '7 DAY');
$usageMonth   = getPeriodUsage($db, '30 DAY');
$usageQuarter = getPeriodUsage($db, '90 DAY');
$usageYear    = getPeriodUsage($db, '365 DAY');

// --- 3. PREDICTION ANALYSES (Next Day Forecast) ---
$history7 = $db->query("SELECT total_packs_produced FROM daily_analytics_snapshots ORDER BY snapshot_date DESC LIMIT 7")->fetch_all(MYSQLI_ASSOC);
$avgPacks = count($history7) > 0 ? array_sum(array_column($history7, 'total_packs_produced')) / count($history7) : 0;
$forecastPacks = $avgPacks * 1.05; // Predicting a 5% growth based on average

// --- 4. DATA INTERPRETATION & INDICATORS ---
$lossThreshold = 10.00; // Define 10kg as a high-loss alert
$interpretation = "Production is stable.";
$statusColor = "var(--success-green)";

if ($snapshotLoss > $lossThreshold) {
    $interpretation = "High Material Loss Detected: Likely due to equipment scaling errors or moisture variance in raw materials.";
    $statusColor = "var(--danger)";
} elseif ($snapshotEff < 85 && $snapshotIn > 0) {
    $interpretation = "Low Efficiency Warning: Check extraction processes for manual handling waste.";
    $statusColor = "var(--warning-amber)";
}

// Chart Data
$history = $db->query("SELECT * FROM daily_analytics_snapshots ORDER BY snapshot_date DESC LIMIT 7");
$labels = []; $lossData = []; $packData = [];
while($row = $history->fetch_assoc()) {
    $labels[] = date('M d', strtotime($row['snapshot_date']));
    $lossData[] = $row['total_loss_kg'];
    $packData[] = $row['total_packs_produced'];
}
$labels = array_reverse($labels);
$lossData = array_reverse($lossData);
$packData = array_reverse($packData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Analytics — ALDiFOODS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Updated values below to force light backgrounds and dark text */
        body { background: #ffffff !important; color: #121214 !important; }
        .interpretation-card { background: #ffffff; color: #121214; padding: 15px; border-radius: 8px; border-left: 5px solid; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .usage-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; }
        .usage-item { background: #ffffff; color: #121214; padding: 10px; border-radius: 5px; text-align: center; border: 1px solid #e2e8f0; }
        .usage-item small { color: #64748b; }
        .table-card { background: #ffffff !important; color: #121214 !important; border: 1px solid #e2e8f0 !important; }
        .stat-card { background: #ffffff !important; color: #121214 !important; border: 1px solid #e2e8f0 !important; }
    </style>
</head>
<body>
<div class="layout" style="background: #ffffff; color: #121214;">
    <?php include '_sidebar.php'; ?>
    <main class="main-content" style="background: #ffffff; color: #121214;"> 
        <div class="page-header">
            <h1 style="color: #121214;">Automated Intelligence & Reporting</h1>
            <div class="breadcrumb" style="color: #64748b;">Predictive Forecasts & Periodic Material Logs</div>
        </div>

        <!-- System Interpretation Section -->
        <div class="interpretation-card" style="border-color: <?= $statusColor ?>;">
            <h3 style="margin-top:0; color: #121214;">Automated Interpretation</h3>
            <p><?= $interpretation ?></p>
            <small style="color: #64748b;">Current Yield Efficiency: <strong><?= number_format($snapshotEff, 1) ?>%</strong></small>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label" style="color: #64748b;">Tomorrow's Forecast</div>
                <div class="stat-value" style="color: #1a73e8; font-weight: bold;"><?= number_format($forecastPacks) ?></div>
                <div style="font-size: 0.8rem; color: #64748b;">Predicted Unit Volume</div>
            </div>
            <div class="stat-card red">
                <div class="stat-label" style="color: #64748b;">Today's Loss</div>
                <div class="stat-value" style="color: #e05555; font-weight: bold;"><?= number_format($snapshotLoss, 2) ?> kg</div>
                <div style="font-size: 0.8rem; color: #64748b;">Material Unaccounted For</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-label" style="color: #64748b;">30-Day Avg Loss</div>
                <div class="stat-value" style="color: #d97706; font-weight: bold;"><?= number_format($usageMonth['total_loss'] / 30, 2) ?> kg</div>
                <div style="font-size: 0.8rem; color: #64748b;">Monthly Benchmark</div>
            </div>
        </div>

        <!-- Periodic Raw Material Usage Report -->
        <div class="table-card mt-3" style="padding: 1.5rem;">
            <h3 style="color: #121214;">Periodic Raw Material Usage Report</h3>
            <div class="usage-grid">
                <div class="usage-item">
                    <strong>Weekly</strong>
                    <div>In: <?= number_format($usageWeek['total_in'], 1) ?>kg</div>
                    <small>Loss: <?= number_format($usageWeek['total_loss'], 1) ?>kg</small>
                </div>
                <div class="usage-item">
                    <strong>Monthly</strong>
                    <div>In: <?= number_format($usageMonth['total_in'], 1) ?>kg</div>
                    <small>Loss: <?= number_format($usageMonth['total_loss'], 1) ?>kg</small>
                </div>
                <div class="usage-item">
                    <strong>Quarterly</strong>
                    <div>In: <?= number_format($usageQuarter['total_in'], 1) ?>kg</div>
                    <small>Loss: <?= number_format($usageQuarter['total_loss'], 1) ?>kg</small>
                </div>
                <div class="usage-item">
                    <strong>Yearly</strong>
                    <div>In: <?= number_format($usageYear['total_in'], 1) ?>kg</div>
                    <small>Loss: <?= number_format($usageYear['total_loss'], 1) ?>kg</small>
                </div>
            </div>
        </div>

        <div class="grid-2 mt-3">
            <div class="table-card" style="padding:1.5rem;">
                <h3 style="color: #121214;">Production Trend</h3>
                <canvas id="packChart" style="max-height: 250px;"></canvas>
            </div>
            <div class="table-card" style="padding:1.5rem;">
                <h3 style="color: #121214;">Material Loss Variance</h3>
                <canvas id="lossScatter" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <div class="table-card mt-3">
            <h3 style="color: #121214; padding: 1.2rem 1.5rem 0.5rem 1.5rem;">Snapshot Database List</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="color: #64748b; background: #f8f9fa;">Date</th>
                        <th style="color: #64748b; background: #f8f9fa;">Input (kg)</th>
                        <th style="color: #64748b; background: #f8f9fa;">Yield (Packs)</th>
                        <th style="color: #64748b; background: #f8f9fa;">Loss (kg)</th>
                        <th style="color: #64748b; background: #f8f9fa;">Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $historyTable = $db->query("SELECT * FROM daily_analytics_snapshots ORDER BY snapshot_date DESC LIMIT 10");
                    while($h = $historyTable->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td><strong style="color: #121214;"><?= date('M d, Y', strtotime($h['snapshot_date'])) ?></strong></td>
                        <td style="color: #121214;"><?= number_format($h['total_input_kg'], 2) ?></td>
                        <td style="color: #1a73e8; font-weight:bold;"><?= number_format($h['total_packs_produced']) ?></td>
                        <td style="color:var(--danger);"><?= number_format($h['total_loss_kg'], 2) ?></td>
                        <td>
                            <span class="badge <?= $h['efficiency_percentage'] > 90 ? 'badge-green' : 'badge-amber' ?>">
                                <?= number_format($h['efficiency_percentage'], 1) ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
/* Updated baseline configurations for lighter UI views */
Chart.defaults.color = '#64748b'; 
Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(document.getElementById('packChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Packs Produced',
            data: <?= json_encode($packData) ?>,
            backgroundColor: '#3498db',
            borderRadius: 5
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

const scatterData = <?= json_encode($lossData) ?>.map((val, i) => ({ x: i, y: val }));
new Chart(document.getElementById('lossScatter'), {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Loss in KG',
            data: scatterData,
            backgroundColor: '#e05555',
            pointRadius: 8
        }]
    },
    options: {
        scales: {
            x: { ticks: { callback: (val) => <?= json_encode($labels) ?>[val] } },
            y: { title: { display: true, text: 'KG Lost' } }
        }
    }
});
</script>
</body>
</html>
