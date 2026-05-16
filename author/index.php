<?php
require_once '../includes/auth_check.php';
checkAuth(['author']);
require_once '../config/database.php';

$author_id = $_SESSION['user_id'];

// Stats
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM protocols WHERE created_by = ?");
$stmtTotal->execute([$author_id]);
$cTotal = $stmtTotal->fetchColumn();

$stmtActive = $pdo->prepare("SELECT COUNT(*) FROM protocols WHERE created_by = ? AND status NOT IN ('approved', 'rejected', 'clearance_released')");
$stmtActive->execute([$author_id]);
$cActive = $stmtActive->fetchColumn();

$stmtRevision = $pdo->prepare("SELECT COUNT(*) FROM protocols WHERE created_by = ? AND status = 'needs_revision'");
$stmtRevision->execute([$author_id]);
$cRevision = $stmtRevision->fetchColumn();

// Fetch protocols for listing
$stmt = $pdo->prepare("SELECT * FROM protocols WHERE created_by = ? ORDER BY created_at DESC");
$stmt->execute([$author_id]);
$protocols = $stmt->fetchAll();

// Check for the most urgent revision
$stmtUrgent = $pdo->prepare("SELECT * FROM protocols WHERE created_by = ? AND status = 'needs_revision' ORDER BY created_at DESC LIMIT 1");
$stmtUrgent->execute([$author_id]);
$urgentRevision = $stmtUrgent->fetch();

include '../includes/header.php';
?>

<div id="wrapper" class="dashboard-page d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="flex-grow-1">
        <?php 
        $workspaceTitle = "Committee Dashboard";
        $workspaceSubtitle = "Submit and track your research for review";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">

            <!-- Welcome & Primary Action -->
            <div class="row align-items-center mb-5 g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3 d-lg-none">
                        <img src="../assets/images/logo.png" width="50" class="bg-white rounded-circle p-1 shadow-sm">
                        <h4 class="fw-bold text-navy mb-0">DNSC REC</h4>
                    </div>
                    <h1 class="display-5 fw-extrabold text-navy mb-2">Welcome back, <span class="text-gold">Researcher!</span></h1>
                    <p class="text-muted fs-5 opacity-75 mb-0">You have <span class="text-navy fw-bold"><?php echo $cActive; ?> active research files</span> undergoing review.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="submit" class="btn btn-navy btn-lg rounded-pill px-5 py-3 shadow-gold border-0 transform-hover fw-bold w-100 w-lg-auto">
                        <i class="fas fa-plus-circle me-2"></i> Submit New Research
                    </a>
                </div>
            </div>

            <!-- URGENT ACTION ALERT -->
            <?php if ($urgentRevision): ?>
            <div class="card border-0 shadow-lg mb-5 overflow-hidden animate-pulse-slow" style="background: linear-gradient(135deg, #fff 0%, #fff5f5 100%); border-left: 6px solid #ef4444 !important;">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="icon-box bg-danger text-white rounded-circle shadow-sm" style="width:70px;height:70px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="fw-bold text-danger mb-1">Action Required: Revision Requested</h4>
                            <p class="text-muted mb-0">Your protocol <strong class="text-navy">"<?php echo htmlspecialchars($urgentRevision['title']); ?>"</strong> has feedback from the REC. Please provide the requested modifications.</p>
                        </div>
                        <div class="col-lg-auto mt-3 mt-lg-0">
                            <a href="resubmit?id=<?php echo $urgentRevision['protocol_id']; ?>" class="btn btn-danger btn-lg px-4 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-edit me-2"></i> Fix & Resubmit Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ANALYTICS CARDS -->
            <div class="row g-4 mb-5">
                <!-- Total Submissions -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden glass-card hover-glow">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                            <span class="badge bg-soft-navy text-navy rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">TOTAL</span>
                            <div class="stat-icon-wrapper bg-soft-navy text-navy mb-3 shadow-sm">
                                <i class="fas fa-folder-tree"></i>
                            </div>
                            <h2 class="display-5 fw-bold text-navy mb-0"><?php echo $cTotal; ?></h2>
                             <p class="text-muted small mb-3">Total submitted</p>
                            <div class="progress rounded-pill" style="height:5px;">
                                <div class="progress-bar bg-navy" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Rounds -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden glass-card hover-glow">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                             <span class="badge bg-soft-gold text-gold rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold uppercase">IN REVIEW</span>
                            <div class="stat-icon-wrapper bg-soft-gold text-gold mb-3 shadow-sm">
                                <i class="fas fa-microscope"></i>
                            </div>
                            <h2 class="display-5 fw-bold text-navy mb-0"><?php echo $cActive; ?></h2>
                             <p class="text-muted small mb-3">Files being reviewed</p>
                            <div class="progress rounded-pill" style="height:5px; background: rgba(197, 160, 89, 0.1);">
                                <div class="progress-bar bg-gold" style="width: <?php echo $cTotal > 0 ? ($cActive/$cTotal)*100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden glass-card hover-glow">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                             <span class="badge bg-soft-red text-red rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">ACTION</span>
                            <div class="stat-icon-wrapper bg-soft-red text-red mb-3 shadow-sm">
                                <i class="fas fa-clipboard-question"></i>
                            </div>
                            <h2 class="display-5 fw-bold text-navy mb-0"><?php echo $cRevision; ?></h2>
                             <p class="text-muted small mb-3">Waiting for your check</p>
                            <div class="progress rounded-pill" style="height:5px; background: rgba(239, 68, 68, 0.1);">
                                <div class="progress-bar bg-red" style="width: <?php echo $cTotal > 0 ? ($cRevision/$cTotal)*100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden bg-navy text-white hover-glow">
                        <div class="p-4 pt-5 position-relative">
                             <span class="badge bg-white text-navy rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">APPROVED</span>
                            <div class="stat-icon-wrapper bg-white text-navy mb-3 shadow-sm">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <?php 
                            $stmtDone = $pdo->prepare("SELECT COUNT(*) FROM protocols WHERE created_by = ? AND status IN ('approved', 'clearance_released')");
                            $stmtDone->execute([$author_id]);
                            $cDone = $stmtDone->fetchColumn();
                            ?>
                            <h2 class="display-5 fw-bold text-white mb-0"><?php echo $cDone; ?></h2>
                             <p class="opacity-75 small mb-3">Approved Research</p>
                            <div class="progress rounded-pill" style="height:5px; background: rgba(255,255,255,0.1);">
                                <div class="progress-bar bg-gold" style="width: <?php echo $cTotal > 0 ? ($cDone/$cTotal)*100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETAILED REGISTRY -->
            <div class="card border-0 shadow-sm rounded-4 mb-5">
                <div class="card-header bg-white border-bottom py-4 px-4 d-flex justify-content-between align-items-center">
                    <div>
                         <h4 class="mb-0 fw-bold text-navy">My Submissions</h4>
                        <p class="text-muted small mb-0">Track the status of your research papers</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width:250px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="registrySearch" class="form-control border-start-0 ps-0" placeholder="Search...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($protocols)): ?>
                        <div class="p-5 text-center text-muted">
                            <div class="mb-4">
                                <i class="fas fa-file-circle-plus fa-5x opacity-10"></i>
                            </div>
                            <h4 class="fw-bold">Your registry is empty</h4>
                            <p class="text-muted fs-5">Start by submitting your first research protocol for ethical review.</p>
                            <a href="submit" class="btn btn-navy rounded-pill px-4 mt-3 py-2">Create Submission</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="protocolsTable">
                                <thead class="bg-light text-uppercase small fw-bold text-muted">
                                    <tr>
                                         <th class="px-4 py-3 border-0">Research Details</th>
                                        <th class="py-3 border-0 d-none d-md-table-cell">Current Status</th>
                                        <th class="py-3 border-0 d-none d-lg-table-cell">Progress</th>
                                        <th class="py-3 text-end pe-4 border-0">Options</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($protocols as $p): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="p-2 rounded-3 bg-soft-navy text-navy me-3" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fas fa-file-contract"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-navy"><?php echo htmlspecialchars($p['rec_code'] ?: $p['tracking_code']); ?></div>
                                                        <div class="text-muted small text-truncate" style="max-width: 350px;">
                                                            <?php echo htmlspecialchars($p['title']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?php
                                                $s = $p['status'];
                                                $phase = 'Submission'; $desc = 'Processing...'; $pClass = 'bg-soft-secondary text-muted';
                                                
                                                if (in_array($s, ['submitted', 'staff_review'])) {
                                                    $phase = 'Submission'; $desc = 'Staff Screening'; $pClass = 'bg-soft-info text-info';
                                                } elseif (in_array($s, ['initial_review', 'confirmed'])) {
                                                    $phase = 'Assignment'; $desc = 'Reviewer Selection'; $pClass = 'bg-soft-primary text-primary';
                                                } elseif (in_array($s, ['assigned', 'under_review'])) {
                                                    $phase = 'Evaluation'; $desc = 'Peer Review'; $pClass = 'bg-soft-gold text-gold';
                                                } elseif ($s === 'completed') {
                                                    $phase = 'Decision'; $desc = 'Final Assessment'; $pClass = 'bg-soft-navy text-navy';
                                                } elseif ($s === 'approved' || $s === 'clearance_released') {
                                                    $phase = 'Completed'; $desc = 'Approved'; $pClass = 'bg-soft-success text-success';
                                                } elseif ($s === 'needs_revision') {
                                                    $phase = 'Revision'; $desc = 'Needs Update'; $pClass = 'bg-soft-red text-red';
                                                } elseif ($s === 'revised') {
                                                    $phase = 'Revision'; $desc = 'Re-submitted'; $pClass = 'bg-soft-gold text-gold';
                                                }
                                                ?>
                                                <span class="badge <?php echo $pClass; ?> rounded-pill px-3 py-2 fw-bold" style="font-size:0.7rem;">
                                                    <i class="fas fa-circle me-1" style="font-size:0.4rem;vertical-align:middle;"></i> <?php echo strtoupper($phase); ?>
                                                </span>
                                                <div class="text-muted x-small mt-1 ps-2"><?php echo $desc; ?></div>
                                            </td>
                                            <td class="d-none d-lg-table-cell" style="min-width: 200px;">
                                                <?php 
                                                $pct = 25;
                                                if ($phase === 'Assignment') $pct = 50;
                                                if ($phase === 'Evaluation') $pct = 75;
                                                if ($phase === 'Decision') $pct = 90;
                                                if ($phase === 'Completed') $pct = 100;
                                                if ($phase === 'Revision') $pct = 40;
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height:6px; background: #e2e8f0;">
                                                        <div class="progress-bar <?php echo $pct == 100 ? 'bg-success' : ($phase === 'Revision' ? 'bg-danger' : 'bg-navy'); ?> rounded-pill" style="width: <?php echo $pct; ?>%; transition:0.8s;"></div>
                                                    </div>
                                                    <span class="ms-3 fw-bold text-navy small"><?php echo $pct; ?>%</span>
                                                </div>
                                                <div class="d-flex justify-content-between x-small text-muted mt-1 px-1">
                                                    <span>Start</span>
                                                    <span>Result</span>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <?php if ($s === 'needs_revision'): ?>
                                                        <a href="resubmit?id=<?php echo $p['protocol_id']; ?>" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm transform-hover">
                                                            <i class="fas fa-wrench me-1"></i> Revise
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="../shared_view?id=<?php echo $p['protocol_id']; ?>" class="btn btn-light btn-sm rounded-pill px-3 border-0 transform-hover" style="background:#f1f5f9;">
                                                         <i class="fas fa-folder-open me-1"></i> View Details
                                                    </a>
                                                    <?php if ($s === 'approved' || $s === 'clearance_released'): ?>
                                                         <div class="dropdown">
                                                             <button class="btn btn-navy btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                 <i class="fas fa-file-export me-1"></i> Post-Approval
                                                             </button>
                                                             <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3">
                                                                 <li><a class="dropdown-item small" href="../forms/form18a_progress_report?id=<?php echo $p['protocol_id']; ?>" target="_blank"><i class="fas fa-chart-line me-2 text-primary"></i> Progress Report (F18a)</a></li>
                                                                 <li><a class="dropdown-item small" href="../forms/form19_final_report?id=<?php echo $p['protocol_id']; ?>" target="_blank"><i class="fas fa-check-double me-2 text-success"></i> Final Report (F19)</a></li>
                                                             </ul>
                                                         </div>
                                                     <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Mobile Quick Nav -->
<div class="mobile-quick-nav d-lg-none shadow-lg glass-card border-top p-2 fixed-bottom">
    <div class="d-flex justify-content-around align-items-center">
        <a href="index" class="text-navy text-decoration-none text-center">
            <i class="fas fa-th-large fs-4 d-block"></i>
            <span class="x-small fw-bold">Home</span>
        </a>
        <a href="submit" class="bg-navy text-white rounded-circle p-3 shadow-gold transform-hover" style="margin-top: -30px; border: 4px solid #fff;">
            <i class="fas fa-plus"></i>
        </a>
        <a href="../shared/profile" class="text-navy text-decoration-none text-center">
            <i class="fas fa-user-circle fs-4 d-block"></i>
            <span class="x-small fw-bold">Profile</span>
        </a>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background-color: #f8fafc;
}

.bg-navy { background-color: #1a2b4b !important; }
.text-navy { color: #1a2b4b !important; }
.fw-extrabold { font-weight: 800 !important; }
.bg-soft-navy { background-color: rgba(26, 43, 75, 0.08) !important; }
.text-gold { color: #c5a059 !important; }
.bg-gold { background-color: #c5a059 !important; }
.bg-soft-gold { background-color: rgba(197, 160, 89, 0.1) !important; }
.border-gold { border-color: #c5a059 !important; }
.shadow-gold { box-shadow: 0 10px 30px -10px rgba(197, 160, 89, 0.4) !important; }
.bg-soft-red { background-color: #ffe4e6 !important; }
.text-red { color: #e11d48 !important; }
.bg-red { background-color: #ef4444 !important; }

.card-glass-accent {
    position: absolute;
    top: 0;
    right: 0;
    width: 60px;
    height: 60px;
    background: linear-gradient(225deg, rgba(197, 160, 89, 0.05) 0%, transparent 70%);
    border-radius: 0 0 0 100%;
}

.dashboard-page {
    min-height: 100vh;
}

.stat-card-premium {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 20px;
    background: #fff;
}
.stat-card-premium:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.stat-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.btn-light-info {
    background-color: #f1f5f9;
    color: #64748b;
    border: none;
}
.btn-light-info:hover {
    background-color: #e2e8f0;
    color: #1a2b4b;
}

.animate-pulse-slow {
    animation: pulse-slow 3s infinite;
}
@keyframes pulse-slow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { transform: scale(1.005); }
}

.transform-hover {
    transition: transform 0.2s ease;
}
.transform-hover:hover {
    transform: scale(1.05);
}

.x-small { font-size: 0.65rem; }
.border-top-dashed { border-top: 1px dashed #e2e8f0; }

.table thead th {
    letter-spacing: 1px;
    color: #94a3b8;
    background: #fdfdfe;
}

#registrySearch:focus {
    box-shadow: none;
    border-color: #cbd5e1;
}

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

@media (min-width: 992px) {
    .table-responsive {
        overflow: visible !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('registrySearch');
    const tableRows = document.querySelectorAll('#protocolsTable tbody tr');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
