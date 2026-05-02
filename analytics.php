<?php
require_once '../includes/db.php';
requireAdminLogin();

$db = getDB();
$currentPage = 'analytics.php';

// --- 1. AUTOMATION LOGIC (Daily Calculations) ---
// We calculate today's specific loss and pack count
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

// --- 2. CHART DATA (Historical 7-Day View) ---
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
    <title>Daily Production Metrics — ALDiFOODS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="layout">
    <?php include '_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Daily Automated Records</h1>
            <div class="breadcrumb">Tracking Raw Material Loss & Pack Volume</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">Today's Packs Produced</div>
                <div class="stat-value"><?= number_format($snapshotPacks) ?></div>
                <div style="font-size: 0.8rem; opacity: 0.8;">Total Units Finished</div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Today's Material Loss</div>
                <div class="stat-value"><?= number_format($snapshotLoss, 2) ?> kg</div>
                <div style="font-size: 0.8rem; opacity: 0.8;">Unrecovered Raw Material</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-label">Daily Efficiency</div>
                <div class="stat-value"><?= number_format($snapshotEff, 1) ?>%</div>
                <div style="font-size: 0.8rem; opacity: 0.8;">Input vs Extracted</div>
            </div>
        </div>

        <div class="grid-2 mt-3">
            <div class="table-card" style="padding:1.5rem;">
                <h3>Daily Packs Produced (Bar Chart)</h3>
                <canvas id="packChart" style="max-height: 250px;"></canvas>
            </div>
            <div class="table-card" style="padding:1.5rem;">
                <h3>Daily Material Loss (Scatter Plot)</h3>
                <canvas id="lossScatter" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <div class="table-card mt-3">
            <h3>Automated Daily History</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Packs Made</th>
                        <th>Raw Material Lost (kg)</th>
                        <th>Total Extracted (kg)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $historyTable = $db->query("SELECT * FROM daily_analytics_snapshots ORDER BY snapshot_date DESC LIMIT 10");
                    while($h = $historyTable->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= date('D, M d', strtotime($h['snapshot_date'])) ?></strong></td>
                        <td style="color:var(--primary-blue); font-weight:bold;"><?= number_format($h['total_packs_produced']) ?> packs</td>
                        <td style="color:var(--danger);"><?= number_format($h['total_loss_kg'], 2) ?> kg</td>
                        <td><?= number_format($h['total_output_kg'], 2) ?> kg</td>
                        <td>
                            <span class="badge <?= $h['efficiency_percentage'] > 90 ? 'badge-green' : 'badge-amber' ?>">
                                <?= number_format($h['efficiency_percentage'], 1) ?>% Yield
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
Chart.defaults.color = '#7a7a8a';
Chart.defaults.font.family = "'Inter', sans-serif";

// 1. Pack Production Bar Chart
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

// 2. Material Loss Scatter Plot
// We use the index as X-axis to show the trend of loss over time
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