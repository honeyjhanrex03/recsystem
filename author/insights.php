<?php
require_once '../includes/auth_check.php';
checkAuth(['author']);
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// 1. Overall Stats
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('approved', 'clearance_released') THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'needs_revision' THEN 1 ELSE 0 END) as revisions,
        SUM(CASE WHEN status IN ('submitted', 'staff_review', 'initial_review', 'under_review') THEN 1 ELSE 0 END) as processing
    FROM protocols 
    WHERE created_by = ?
");
$stats->execute([$user_id]);
$s = $stats->fetch();

// 2. Protocol List with Progress
$protocols = $pdo->prepare("SELECT * FROM protocols WHERE created_by = ? ORDER BY created_at DESC");
$protocols->execute([$user_id]);
$allProtocols = $protocols->fetchAll();

function getProgress(string $status) {
    $steps = [
        'submitted' => 10,
        'staff_review' => 25,
        'needs_revision' => 30,
        'revised' => 40,
        'initial_review' => 50,
        'confirmed' => 60,
        'assigned' => 70,
        'under_review' => 85,
        'approved' => 100,
        'clearance_released' => 100
    ];
    return $steps[$status] ?? 0;
}

include '../includes/header.php';
?>

<div id="wrapper" class="dashboard-page d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Research Portfolio Insights";
        $workspaceSubtitle = "Lifecycle Analytics & Submission Performance Tracking";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            
            <!-- Summary Header -->
            <div class="row g-4 mb-5">
                <!-- Total Projects -->
                <div class="col-md-3">
                    <div class="card border-0 rounded-4 p-4 text-center h-100 position-relative overflow-hidden stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 10px 30px -10px rgba(26,43,75,0.1);">
                        <div class="stat-icon-wrapper mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(26, 43, 75, 0.05); display: flex; align-items: center; justify-content: center; color: #1a2b4b;">
                            <i class="fas fa-folder-open fa-2x"></i>
                        </div>
                        <h2 class="fw-extrabold mb-1" style="color: #1a2b4b; font-size: 2.8rem; letter-spacing: -1.5px;"><?php echo $s['total']; ?></h2>
                        <div class="small fw-bold text-uppercase tracking-wider" style="color: #64748b;">Total Projects</div>
                        <div class="position-absolute" style="top: -20px; right: -20px; font-size: 120px; opacity: 0.03; color: #1a2b4b; pointer-events: none;"><i class="fas fa-folder"></i></div>
                    </div>
                </div>

                <!-- Ethical Clearances -->
                <div class="col-md-3">
                    <div class="card border-0 rounded-4 p-4 text-center h-100 position-relative overflow-hidden stat-card text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 10px 30px -10px rgba(5, 150, 105, 0.6);">
                        <div class="stat-icon-wrapper mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-check-circle fa-2x text-white"></i>
                        </div>
                        <h2 class="fw-extrabold mb-1" style="font-size: 2.8rem; letter-spacing: -1.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo $s['approved']; ?></h2>
                        <div class="small fw-bold text-uppercase tracking-wider" style="color: rgba(255,255,255,0.85);">Ethical Clearances</div>
                        <div class="position-absolute" style="top: -20px; right: -20px; font-size: 120px; opacity: 0.1; pointer-events: none;"><i class="fas fa-certificate"></i></div>
                    </div>
                </div>

                <!-- Active Revisions -->
                <div class="col-md-3">
                    <div class="card border-0 rounded-4 p-4 text-center h-100 position-relative overflow-hidden stat-card text-white" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); box-shadow: 0 10px 30px -10px rgba(217, 119, 6, 0.6);">
                        <div class="stat-icon-wrapper mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-sync-alt fa-2x text-white"></i>
                        </div>
                        <h2 class="fw-extrabold mb-1" style="font-size: 2.8rem; letter-spacing: -1.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo $s['revisions']; ?></h2>
                        <div class="small fw-bold text-uppercase tracking-wider" style="color: rgba(255,255,255,0.85);">Active Revisions</div>
                        <div class="position-absolute" style="top: -20px; right: -20px; font-size: 120px; opacity: 0.1; pointer-events: none;"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>

                <!-- In Review Cycle -->
                <div class="col-md-3">
                    <div class="card border-0 rounded-4 p-4 text-center h-100 position-relative overflow-hidden stat-card text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 10px 30px -10px rgba(37, 99, 235, 0.6);">
                        <div class="stat-icon-wrapper mb-3 mx-auto" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-microscope fa-2x text-white"></i>
                        </div>
                        <h2 class="fw-extrabold mb-1" style="font-size: 2.8rem; letter-spacing: -1.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo $s['processing']; ?></h2>
                        <div class="small fw-bold text-uppercase tracking-wider" style="color: rgba(255,255,255,0.85);">In Review Cycle</div>
                        <div class="position-absolute" style="top: -20px; right: -20px; font-size: 120px; opacity: 0.1; pointer-events: none;"><i class="fas fa-search"></i></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Insights Grid -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                        <div class="card-header bg-navy text-white p-4">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i> Submission Progress Analytics</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 40%;">Study Protocol Details</th>
                                            <th class="py-3" style="width: 30%;">Review Status Pipeline</th>
                                            <th class="py-3 text-center">Completion</th>
                                            <th class="pe-4 py-3 text-end">Tracking</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($allProtocols)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <div class="text-muted"><i class="fas fa-search fa-2x mb-3"></i><br>No submissions found in your portfolio.</div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach($allProtocols as $p): 
                                            $prog = getProgress($p['status']);
                                            $progClass = 'bg-primary';
                                            if($prog == 100) $progClass = 'bg-success';
                                            if($p['status'] == 'needs_revision') $progClass = 'bg-danger';
                                        ?>
                                        <tr>
                                            <td class="ps-4 py-4">
                                                <div class="fw-bold text-navy fs-6 mb-1 text-truncate" style="max-width: 450px;">
                                                    <?php echo strtoupper($p['title']); ?>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-light text-navy border font-monospace small">#<?php echo $p['rec_code'] ?: $p['tracking_code']; ?></span>
                                                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Submited: <?php echo date('M d, Y', strtotime($p['created_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small fw-bold text-navy mb-1">
                                                    <?php echo strtoupper(str_replace('_', ' ', $p['status'])); ?>
                                                </div>
                                                <div class="progress rounded-pill bg-light shadow-none" style="height: 8px;">
                                                    <div class="progress-bar <?php echo $progClass; ?> rounded-pill" style="width: <?php echo $prog; ?>%;"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold text-navy"><?php echo $prog; ?>%</span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="../shared_view?id=<?php echo $p['protocol_id']; ?>" class="btn btn-sm btn-navy rounded-pill px-3 shadow-sm">
                                                    Full Insight <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Analytics Help -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-navy text-white h-100">
                        <h5 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i> Insight Tips</h5>
                        <ul class="small opacity-75 list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle me-2"></i> <strong>Initial Screening:</strong> Completed when REC Staff forwards to REC Chair.</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2"></i> <strong>In Review Cycle:</strong> Your protocol is being evaluated by peer reviewers.</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2"></i> <strong>Approved:</strong> Ethical clearance has been signed and is ready for release.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 border-start border-primary border-4 bg-white h-100">
                         <h5 class="fw-bold mb-3">Portfolio Performance</h5>
                         <p class="small text-muted">The average ethical review turnaround in DNSC REC is <strong>14-21 days</strong>. You will be notified automatically if there are updates or requests for clarification from the board.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.dashboard-page { background: #f8fafc; min-height: 100vh; }
.tracking-wider { letter-spacing: 0.1em; font-size: 0.75rem; }
.hover-up:hover { transform: translateY(-3px); transition: all 0.3s ease; }
.bg-navy-light { background: #e2e8f0; }

/* Enhanced Premium Stat Cards */
.stat-card {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.stat-card:hover {
    transform: translateY(-8px) scale(1.02);
}
.stat-card .stat-icon-wrapper {
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.stat-card:hover .stat-icon-wrapper {
    transform: scale(1.15) rotate(8deg);
    background: rgba(255, 255, 255, 0.25) !important;
}
.stat-card i.position-absolute {
    transition: all 0.6s ease;
}
.stat-card:hover i.position-absolute {
    transform: scale(1.1) rotate(-5deg);
    opacity: 0.15 !important;
}
</style>

<?php include '../includes/footer.php'; ?>
