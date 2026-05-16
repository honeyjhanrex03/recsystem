<?php
session_start();
require_once 'config/database.php';

$rec_code = $_GET['code'] ?? '';
$protocol = null;
$error = "";
$success = "";

// Only handle simple tracking logic
if (isset($_GET['track_btn'])) {
    $rec_code = trim($_GET['code']);

    if (empty($rec_code)) {
        $error = "Please enter your REC Code.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM protocols WHERE rec_code = ? OR tracking_code = ? LIMIT 1");
        $stmt->execute([$rec_code, $rec_code]);
        $protocol = $stmt->fetch();

        if (!$protocol) {
            $error = "No protocol found with that Code. Please check your REC Code and try again.";
        }
    }
}

include 'includes/header.php';
?>

<div style="background:#f1f5f9; min-height: 100vh; padding-bottom: 50px;">
    <!-- Public Header -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm px-2 px-md-4 py-3">
        <div class="container-fluid align-items-center d-flex">
            <a href="index" class="d-flex align-items-center text-decoration-none">
                <img src="assets/images/logo.png" alt="DNSC" width="45" height="45" class="me-2 me-md-3">
                <div class="lh-1">
                    <h5 class="fw-bold text-navy mb-1 responsive-title" style="font-size: 16px;">DNSC REC</h5>
                    <span class="text-muted responsive-subtitle" style="font-size: 12px;">Research Ethics Committee</span>
                </div>
            </a>
            <div class="ms-auto d-flex gap-1">
                <a href="<?php echo BASE_URL; ?>submit_protocol" class="btn btn-outline-navy btn-sm px-2 px-md-4 fw-bold shadow-sm rounded-pill btn-responsive-sm"><i class="fas fa-plus me-1 me-md-2"></i> <span class="d-none d-sm-inline">New </span>Submit</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 animate-up">

                <div class="text-center mb-4">
                    <span class="badge bg-navy-light text-navy px-3 py-2 rounded-pill fw-bold text-uppercase tracking-wider mb-2">Public Tracking System</span>
                    <h2 class="fw-bold text-navy mb-3">Track Your Protocol Progress</h2>
                    <p class="text-muted fs-6" style="max-width: 600px; margin: 0 auto;">Check the real-time review status of your submitted research protocol. Please enter your designated <strong>REC Code</strong> below.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <div class="card-body p-4 p-md-5">
                        <form action="track" method="GET" class="d-flex flex-column flex-md-row gap-3">
                            <input type="text" name="code" class="form-control form-control-lg bg-light border-0 px-4" placeholder="e.g. 2026-003-EXT.-MMP" value="<?php echo htmlspecialchars($rec_code); ?>" required>
                            <button type="submit" name="track_btn" class="btn btn-navy px-5 fw-bold shadow-sm py-3 py-md-0" style="min-width: 150px;">
                                <i class="fas fa-magnifying-glass me-2"></i> Trace Status
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($protocol): ?>
                    <div class="card border-0 shadow-lg rounded-4 animate-up overflow-hidden" style="animation-delay: 0.1s;">
                        <div class="card-body p-4 p-md-5 position-relative">
                            <!-- Background Decor -->
                            <div class="position-absolute top-0 end-0 opacity-10 m-3 mt-4 me-4">
                                <i class="fas fa-radar fa-6x text-navy"></i>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted fw-bold small mb-1" style="letter-spacing: 2px;">Research Title</h6>
                                <h3 class="fw-bold text-navy pe-5" style="line-height: 1.4;"><?php echo htmlspecialchars($protocol['title']); ?></h3>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <?php $has_official = (strpos($protocol['rec_code'], 'PENDING') === false); ?>
                                    <h6 class="text-muted fw-bold small mb-1 text-uppercase">REC Code</h6>
                                    <div class="text-navy fw-bold fs-5 bg-gold-light d-inline-block px-3 py-1 rounded-3 border-start border-4 border-gold shadow-sm">
                                        <?php echo htmlspecialchars($protocol['rec_code']); ?>
                                    </div>
                                    <?php if (!$has_official): ?>
                                        <div class="text-muted small italic mt-1">
                                            <i class="fas fa-hourglass-half me-1"></i> Awaiting final code assignment
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 border-start ps-md-4">
                                    <h6 class="text-muted fw-bold small mb-1 text-uppercase">Lead Researcher</h6>
                                    <div class="text-dark fw-bold"><?php echo htmlspecialchars($protocol['project_leader']); ?></div>
                                </div>
                            </div>
                            <hr class="opacity-10 my-4">

                            <div>
                                <div class="mt-4">
                                    <h6 class="text-navy fw-bold small mb-4 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-route me-2"></i> Review Progress Journey</h6>

                                    <?php
                                    $workflowSteps = [
                                        ['label' => 'Committee Submission', 'status' => ['submitted'], 'icon' => 'fa-paper-plane'],
                                        ['label' => 'Initial check / REC staff', 'status' => ['staff_review', 'needs_revision'], 'icon' => 'fa-clipboard-check'],
                                        ['label' => 'Initial review by REC Chair', 'status' => ['initial_review'], 'icon' => 'fa-user-tie'],
                                        ['label' => 'Reviewer assignment', 'status' => ['confirmed'], 'icon' => 'fa-user-plus'],
                                        ['label' => 'Peer review', 'status' => ['assigned', 'under_review', 'completed'], 'icon' => 'fa-users-viewfinder'],
                                        ['label' => 'Resubmission (if req.)', 'status' => ['revised'], 'icon' => 'fa-file-export'],
                                        ['label' => 'Final decision', 'status' => ['approved', 'rejected'], 'icon' => 'fa-gavel'],
                                        ['label' => 'Ethical clearance release', 'status' => ['clearance_released'], 'icon' => 'fa-certificate'],
                                    ];

                                    $currentStatus = $protocol['status'];
                                    $activeIndex = -1;

                                    // Special handling for 'needs_revision' which can occur twice
                                    // If it has assignments, it's a post-review revision (Step 6)
                                    if ($currentStatus === 'needs_revision') {
                                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reviewer_assignments WHERE protocol_id = ?");
                                        $stmtCount->execute([$protocol['protocol_id']]);
                                        $assignmentCount = $stmtCount->fetchColumn();

                                        if ($assignmentCount > 0) {
                                            $activeIndex = 5; // Step 6: Resubmission (if req.)
                                        }
                                    }

                                    if ($activeIndex === -1) {
                                        foreach ($workflowSteps as $idx => $ws) {
                                            if (in_array($currentStatus, $ws['status'])) {
                                                $activeIndex = $idx;
                                                break;
                                            }
                                        }
                                    }

                                    if ($currentStatus === 'clearance_released') $activeIndex = 7;
                                    ?>

                                    <div class="workflow-stepper">
                                        <?php foreach ($workflowSteps as $idx => $ws): ?>
                                            <?php
                                            $isCompleted = ($idx < $activeIndex);
                                            $isActive = ($idx === $activeIndex);
                                            $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : 'upcoming');
                                            ?>
                                            <div class="step-item <?php echo $stepClass; ?>">
                                                <div class="step-icon">
                                                    <i class="fas <?php echo $ws['icon']; ?>"></i>
                                                    <?php if ($isCompleted): ?>
                                                        <div class="check-badge"><i class="fas fa-check"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="step-content">
                                                    <div class="step-title"><?php echo $ws['label']; ?></div>
                                                    <?php if ($isActive): ?>
                                                        <div class="step-status-tag">Current Phase</div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($idx < count($workflowSteps) - 1): ?>
                                                    <div class="step-line"></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <style>
                                    .workflow-stepper {
                                        display: flex;
                                        flex-direction: column;
                                        position: relative;
                                        padding-left: 10px;
                                    }

                                    .step-item {
                                        display: flex;
                                        align-items: flex-start;
                                        position: relative;
                                        padding-bottom: 25px;
                                    }

                                    .step-icon {
                                        width: 44px;
                                        height: 44px;
                                        border-radius: 12px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        font-size: 1.1rem;
                                        z-index: 2;
                                        position: relative;
                                        transition: 0.3s;
                                        background: #f1f5f9;
                                        color: #94a3b8;
                                        border: 2px solid #e2e8f0;
                                    }

                                    .step-line {
                                        position: absolute;
                                        left: 22px;
                                        top: 44px;
                                        bottom: 0;
                                        width: 2px;
                                        background: #e2e8f0;
                                        z-index: 1;
                                    }

                                    .step-content {
                                        padding-left: 20px;
                                        padding-top: 10px;
                                    }

                                    .step-title {
                                        font-weight: 700;
                                        font-size: 0.95rem;
                                        color: #64748b;
                                    }

                                    .step-status-tag {
                                        display: inline-block;
                                        padding: 2px 10px;
                                        background: #1a2b4b;
                                        color: #fff;
                                        font-size: 0.65rem;
                                        border-radius: 50px;
                                        font-weight: 800;
                                        text-transform: uppercase;
                                        margin-top: 4px;
                                        letter-spacing: 0.5px;
                                    }

                                    .step-item.completed {
                                        opacity: 0.8;
                                    }

                                    .step-item.completed .step-icon {
                                        background: #e0f2fe;
                                        color: #0284c7;
                                        border-color: #0284c7;
                                    }

                                    .step-item.completed .step-line {
                                        background: #0284c7;
                                    }

                                    .step-item.completed .step-title {
                                        color: #0284c7;
                                    }

                                    .check-badge {
                                        position: absolute;
                                        bottom: -5px;
                                        right: -5px;
                                        background: #22c55e;
                                        color: #fff;
                                        width: 18px;
                                        height: 18px;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        font-size: 0.6rem;
                                        border: 2px solid #fff;
                                    }

                                    .step-item.active .step-icon {
                                        background: #1a2b4b;
                                        color: #fff;
                                        border-color: #1a2b4b;
                                        transform: scale(1.1);
                                        box-shadow: 0 4px 10px rgba(26, 43, 75, 0.3);
                                    }

                                    .step-item.active .step-title {
                                        color: #1a2b4b;
                                        font-size: 1.05rem;
                                    }

                                    @media (min-width: 768px) {
                                        .workflow-stepper {
                                            flex-direction: row;
                                            justify-content: space-between;
                                            padding-left: 0;
                                            padding-bottom: 20px;
                                        }

                                        .step-item {
                                            flex-direction: column;
                                            align-items: center;
                                            text-align: center;
                                            flex: 1;
                                            padding-bottom: 0;
                                        }

                                        .step-line {
                                            left: 50%;
                                            top: 22px;
                                            width: 100%;
                                            height: 2px;
                                            bottom: auto;
                                        }

                                        .step-content {
                                            padding-left: 0;
                                            padding-top: 15px;
                                            max-width: 100px;
                                        }

                                        .step-title {
                                            font-size: 0.75rem;
                                        }
                                    }
                                </style>

                                <div class="mt-3 text-muted d-flex justify-content-between align-items-center" style="font-size:12px;">
                                    <span><i class="fas fa-clock me-1"></i> Status Tracker</span>
                                    <?php if ($protocol['status'] === 'clearance_released'): ?>
                                        <span class="text-success fw-bold"><i class="fas fa-certificate me-1"></i> Workflow Completed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Secure Access Prompt (REPLACED REVISION FORM) -->
                            <div class="mt-5 p-4 rounded-4 border bg-white text-center shadow-sm">
                                <div class="bg-light rounded-circle p-3 d-inline-block mb-3" style="width: 70px; height: 70px;">
                                    <i class="fas fa-lock text-navy fa-2x"></i>
                                </div>
                                <h5 class="fw-bold text-navy">Researcher Dashboard Required</h5>
                                <p class="text-muted small mb-4" style="max-width: 500px; margin: 0 auto;">
                                    To view detailed board recommendations, download evaluation forms, or upload revised protocol documents, please log in to your registered account.
                                </p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="<?php echo BASE_URL; ?>login" class="btn btn-navy fw-bold px-4 rounded-pill">
                                        <i class="fas fa-sign-in-alt me-2"></i> Log in to Account
                                    </a>
                                </div>
                            </div>

                            <!-- Footer Assistance -->
                            <hr class="opacity-10 my-5">
                            <div class="row g-4 align-items-center">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-navy mb-1">Need Status Assistance?</h6>
                                    <p class="text-muted small mb-0">For clarifications regarding your protocol current journey phase, please contact our secretariat.</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="d-flex flex-column gap-1">
                                        <a href="mailto:rec@dnsc.edu.ph" class="text-navy fw-bold text-decoration-none small">
                                            <i class="fas fa-envelope me-2"></i> rec@dnsc.edu.ph
                                        </a>
                                        <a href="tel:09955738237" class="text-navy fw-bold text-decoration-none small">
                                            <i class="fas fa-phone-alt me-2"></i> 0995 573 8237
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $error; ?>',
            confirmButtonColor: '#1a2b4b'
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>