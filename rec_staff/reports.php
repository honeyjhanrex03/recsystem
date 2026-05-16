<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair', 'rec_secretary']);
require_once '../config/database.php';
include '../includes/header.php';

$uid = $_SESSION['user_id'];
// Fetch distribution for staff (Global view for the office)
$typeStats = $pdo->query("SELECT review_type, COUNT(*) as count FROM protocols GROUP BY review_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$allStatus = $pdo->query("SELECT status, COUNT(*) as count FROM protocols GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare type data
$types = ['exempt', 'expedited', 'full_board'];
$typeData = [];
foreach ($types as $t)
    $typeData[] = $typeStats[$t] ?? 0;

// Logical grouping for status chart
$statData = [
    'submitted' => ($allStatus['submitted'] ?? 0) + ($allStatus['staff_review'] ?? 0),
    'under_review' => ($allStatus['initial_review'] ?? 0) + ($allStatus['assigned'] ?? 0) + ($allStatus['under_review'] ?? 0),
    'for_decision' => ($allStatus['confirmed'] ?? 0) + ($allStatus['revised'] ?? 0),
    'completed' => ($allStatus['approved'] ?? 0) + ($allStatus['clearance_released'] ?? 0),
    'returned' => ($allStatus['needs_revision'] ?? 0) + ($allStatus['rejected'] ?? 0)
];

// Reformat for Chart.js
$displayStatData = array_values($statData);
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Activity Analytics";
        $workspaceSubtitle = "Staff Submission Metrics & Workflow Efficiency";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <h2 class="fw-bold text-navy mb-4">My Activity Overview</h2>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-navy mb-4 small text-uppercase">My Protocols by Type</h5>
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-navy mb-4 small text-uppercase">Review Progress</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Chart(document.getElementById('typeChart'), {
            type: 'pie',
            data: {
                labels: ['Exempted from Review', 'Expedited Review', 'Full Review'],
                datasets: [{
                    data: <?php echo json_encode($typeData); ?>,
                    backgroundColor: ['#1a2b4b', '#DC3545', '#FFC107']
                }]
            }
        });

        new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: ['Submitted', 'Under Review', 'For Decision', 'Completed', 'Returned'],
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode($displayStatData); ?>,
                    backgroundColor: '#1a2b4b'
                }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
