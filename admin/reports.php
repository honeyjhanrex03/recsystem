<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch distribution for reports
$typeStats = $pdo->query("SELECT review_type, COUNT(*) as count FROM protocols GROUP BY review_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$allStatus = $pdo->query("SELECT status, COUNT(*) as count FROM protocols GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare type data
$types = ['expedited', 'exempt', 'full_board'];
$typeData = [];
foreach ($types as $t)
    $typeData[] = $typeStats[$t] ?? 0;

// Logical grouping for status chart
$statData = [
    ($allStatus['submitted'] ?? 0) + ($allStatus['staff_review'] ?? 0),
    ($allStatus['initial_review'] ?? 0) + ($allStatus['assigned'] ?? 0) + ($allStatus['under_review'] ?? 0),
    ($allStatus['confirmed'] ?? 0) + ($allStatus['revised'] ?? 0),
    ($allStatus['approved'] ?? 0) + ($allStatus['clearance_released'] ?? 0),
    ($allStatus['needs_revision'] ?? 0) + ($allStatus['rejected'] ?? 0)
];
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "System Analytics";
        $workspaceSubtitle = "REC Performance Metrics & Institutional Submissions";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-navy mb-0">Statistical Reports</h2>
                <button onclick="window.print()" class="btn btn-outline-navy rounded-pill px-4">
                    <i class="fas fa-file-pdf me-2"></i> Export PDF
                </button>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <h5 class="fw-bold text-navy mb-4 text-uppercase small letter-spacing">Protocols by Review Type
                        </h5>
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                        <h5 class="fw-bold text-navy mb-4 text-uppercase small letter-spacing">Protocols by Lifecycle
                            Status</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-navy mb-4">Monthly Submission Trend</h5>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted opacity-25 mb-3"></i>
                        <p class="text-muted">Insufficient historical data to generate trend analysis. Please submit
                            more protocols to see patterns.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Type Chart
        const ctxType = document.getElementById('typeChart').getContext('2d');
        new Chart(ctxType, {
            type: 'doughnut',
            data: {
                labels: ['Expedited', 'Exempted', 'Full Review'],
                datasets: [{
                    data: <?php echo json_encode($typeData); ?>,
                    backgroundColor: ['#1a2b4b', '#4e6fb3', '#b3c1d9'],
                    borderWidth: 0
            }]
        },
            options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'bar',
        data: {
            labels: ['Submitted', 'Under Review', 'For Decision', 'Completed', 'Returned'],
            datasets: [{
                label: 'Protocols',
                data: <?php echo json_encode($statData); ?>,
                backgroundColor: '#1a2b4b',
                borderRadius: 5
            }]
    },
        options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
