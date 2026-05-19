<?php
require_once '../includes/auth_check.php';
checkAuth(['author']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'progress'; // 'progress' (F18a) or 'final' (F19)

if (!$protocol_id) {
    die("No Protocol ID specified.");
}

// Fetch Protocol and verify ownership
$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ? AND created_by = ?");
$stmt->execute([$protocol_id, $_SESSION['user_id']]);
$p = $stmt->fetch();

if (!$p) {
    die("Protocol not found or you are not authorized to manage this report.");
}

$error = "";
$success = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    if ($type === 'progress') {
        $clearance_period = trim($_POST['ethical_clearance_period'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $expected_end_date = trim($_POST['expected_end_date'] ?? '');
        $enrolled = trim($_POST['enrolled_participants'] ?? '');
        $required = trim($_POST['required_participants'] ?? '');
        $withdrawn = trim($_POST['withdrawn_participants'] ?? '');
        $reason = trim($_POST['withdrawal_reason'] ?? '');
        $deviations = trim($_POST['deviations'] ?? '');
        $new_info = trim($_POST['new_information'] ?? '');
        $issues = trim($_POST['issues_encountered'] ?? '');

        // Check if report already exists, then update or insert
        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM form18a_responses WHERE protocol_id = ?");
        $stmtChk->execute([$protocol_id]);
        $exists = ($stmtChk->fetchColumn() > 0);

        if ($exists) {
            $stmtUpd = $pdo->prepare("UPDATE form18a_responses SET 
                ethical_clearance_period = ?, start_date = ?, expected_end_date = ?, enrolled_participants = ?, 
                required_participants = ?, withdrawn_participants = ?, withdrawal_reason = ?, deviations = ?, 
                new_information = ?, issues_encountered = ? WHERE protocol_id = ?");
            $stmtUpd->execute([$clearance_period, $start_date, $expected_end_date, $enrolled, $required, $withdrawn, $reason, $deviations, $new_info, $issues, $protocol_id]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO form18a_responses 
                (protocol_id, author_id, ethical_clearance_period, start_date, expected_end_date, enrolled_participants, required_participants, withdrawn_participants, withdrawal_reason, deviations, new_information, issues_encountered) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$protocol_id, $_SESSION['user_id'], $clearance_period, $start_date, $expected_end_date, $enrolled, $required, $withdrawn, $reason, $deviations, $new_info, $issues]);
        }
        
        // Log action in audit log
        $actionMsg = "Lead Researcher " . $p['project_leader'] . " officially submitted the online Progress Report (REC Form 18a) to the database.";
        $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action, timestamp) VALUES (?, ?, ?, NOW())");
        $stmtLog->execute([$_SESSION['user_id'], $protocol_id, $actionMsg]);

        $success = "Progress Report (Form 18a) successfully submitted!";
    } else {
        $clearance_start = trim($_POST['ethical_clearance_start'] ?? '');
        $clearance_end = trim($_POST['ethical_clearance_end'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $enrolled = trim($_POST['enrolled_participants'] ?? '');
        $required = trim($_POST['required_participants'] ?? '');
        $withdrawn = trim($_POST['withdrawn_participants'] ?? '');
        $reason = trim($_POST['withdrawal_reason'] ?? '');
        $deviations = trim($_POST['deviations'] ?? '');
        $issues = trim($_POST['issues_encountered'] ?? '');
        $findings = trim($_POST['summary_findings'] ?? '');
        $conclusions = trim($_POST['conclusions'] ?? '');
        $dissemination = trim($_POST['dissemination_actions'] ?? '');

        // Check if report already exists, then update or insert
        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM form19_responses WHERE protocol_id = ?");
        $stmtChk->execute([$protocol_id]);
        $exists = ($stmtChk->fetchColumn() > 0);

        if ($exists) {
            $stmtUpd = $pdo->prepare("UPDATE form19_responses SET 
                ethical_clearance_start = ?, ethical_clearance_end = ?, start_date = ?, end_date = ?, enrolled_participants = ?, 
                required_participants = ?, withdrawn_participants = ?, withdrawal_reason = ?, deviations = ?, 
                issues_encountered = ?, summary_findings = ?, conclusions = ?, dissemination_actions = ? WHERE protocol_id = ?");
            $stmtUpd->execute([$clearance_start, $clearance_end, $start_date, $end_date, $enrolled, $required, $withdrawn, $reason, $deviations, $issues, $findings, $conclusions, $dissemination, $protocol_id]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO form19_responses 
                (protocol_id, author_id, ethical_clearance_start, ethical_clearance_end, start_date, end_date, enrolled_participants, required_participants, withdrawn_participants, withdrawal_reason, deviations, issues_encountered, summary_findings, conclusions, dissemination_actions) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$protocol_id, $_SESSION['user_id'], $clearance_start, $clearance_end, $start_date, $end_date, $enrolled, $required, $withdrawn, $reason, $deviations, $issues, $findings, $conclusions, $dissemination]);
        }

        // Log action in audit log
        $actionMsg = "Lead Researcher " . $p['project_leader'] . " officially submitted the online Final Report (REC Form 19) to the database.";
        $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action, timestamp) VALUES (?, ?, ?, NOW())");
        $stmtLog->execute([$_SESSION['user_id'], $protocol_id, $actionMsg]);

        $success = "Final Report (Form 19) successfully submitted!";
    }
}

// Fetch existing data if any
$reportData = null;
if ($type === 'progress') {
    $stmtF = $pdo->prepare("SELECT * FROM form18a_responses WHERE protocol_id = ?");
    $stmtF->execute([$protocol_id]);
    $reportData = $stmtF->fetch();
} else {
    $stmtF = $pdo->prepare("SELECT * FROM form19_responses WHERE protocol_id = ?");
    $stmtF->execute([$protocol_id]);
    $reportData = $stmtF->fetch();
}

include '../includes/header.php';
?>

<div id="wrapper" class="dashboard-page d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = ($type === 'progress') ? "Submit Progress Report" : "Submit Final Report";
        $workspaceSubtitle = "REC Form " . (($type === 'progress') ? "18a" : "19") . " persistent data registry";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <div class="col-lg-9 mx-auto">
                <div class="mb-4">
                    <a href="index" class="text-decoration-none text-muted"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
                    <h2 class="fw-bold text-navy mt-2"><?php echo ($type === 'progress') ? "Progress Report Submission" : "Final Report Submission"; ?></h2>
                    <p class="text-muted">Enter the official study metrics below. These values will be securely saved and automatically filled in your printable PDF form.</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 rounded-4 shadow-sm p-4 mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-soft-success text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h5 class="fw-bold"><?php echo $success; ?></h5>
                        <p class="text-muted mb-3 small">You can now view or print your officially filled document.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="../forms/form<?php echo ($type === 'progress') ? '18a_progress_report' : '19_final_report'; ?>.php?id=<?php echo $protocol_id; ?>&public=1" target="_blank" class="btn btn-navy rounded-pill px-4 py-2 small fw-bold">
                                <i class="fas fa-print me-1"></i> Open & Print Filled Form
                            </a>
                            <a href="index" class="btn btn-light border rounded-pill px-4 py-2 small fw-bold">Return Home</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                    <div class="bg-navy p-4 text-white">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice me-2"></i> Form Details: <?php echo htmlspecialchars($p['rec_code']); ?></h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="">
                            <div class="p-3 bg-light rounded-4 mb-4 border">
                                <h6 class="fw-bold text-navy mb-2"><i class="fas fa-info-circle me-1"></i> Protocol Summary</h6>
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td width="150" class="fw-bold text-muted">Title:</td>
                                        <td class="fw-bold text-navy"><?php echo htmlspecialchars($p['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">Researcher:</td>
                                        <td><?php echo htmlspecialchars($p['project_leader']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">Institution:</td>
                                        <td><?php echo htmlspecialchars($p['institution']); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <?php if ($type === 'progress'): ?>
                                <!-- PROGRESS REPORT FIELDS (Form 18a) -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-navy">Ethical Clearance Effectivity Period <span class="text-danger">*</span></label>
                                        <input type="text" name="ethical_clearance_period" class="form-control" placeholder="e.g. June 15, 2022 to June 15, 2023" value="<?php echo htmlspecialchars($reportData['ethical_clearance_period'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">1. Start of Study <span class="text-danger">*</span></label>
                                        <input type="text" name="start_date" class="form-control" placeholder="e.g. October 1, 2022" value="<?php echo htmlspecialchars($reportData['start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">2. Expected End of Study <span class="text-danger">*</span></label>
                                        <input type="text" name="expected_end_date" class="form-control" placeholder="e.g. October 1, 2023" value="<?php echo htmlspecialchars($reportData['expected_end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">3. Number of Enrolled Participants <span class="text-danger">*</span></label>
                                        <input type="text" name="enrolled_participants" class="form-control" placeholder="e.g. 150" value="<?php echo htmlspecialchars($reportData['enrolled_participants'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">4. Number of Required Participants <span class="text-danger">*</span></label>
                                        <input type="text" name="required_participants" class="form-control" placeholder="e.g. 150" value="<?php echo htmlspecialchars($reportData['required_participants'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">5. Number of Participants Who Withdrew</label>
                                        <input type="text" name="withdrawn_participants" class="form-control" placeholder="e.g. 3 (leave blank if none)" value="<?php echo htmlspecialchars($reportData['withdrawn_participants'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Reason for Withdrawal</label>
                                        <input type="text" name="withdrawal_reason" class="form-control" placeholder="e.g. Relocated to another city" value="<?php echo htmlspecialchars($reportData['withdrawal_reason'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">6. Deviations from the Approved Protocol (if any)</label>
                                    <textarea name="deviations" class="form-control" rows="3" placeholder="Describe any modifications, change of site, or procedure deviations..."><?php echo htmlspecialchars($reportData['deviations'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">7. New Information (literature or in the conduct of the study) that may significantly change the risk-benefit ratio</label>
                                    <textarea name="new_information" class="form-control" rows="3" placeholder="e.g. New medical guidelines published..."><?php echo htmlspecialchars($reportData['new_information'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">8. Issues/Problems Encountered</label>
                                    <textarea name="issues_encountered" class="form-control" rows="3" placeholder="Describe any challenges, data losses, recruitment delays..."><?php echo htmlspecialchars($reportData['issues_encountered'] ?? ''); ?></textarea>
                                </div>

                            <?php else: ?>
                                <!-- FINAL REPORT FIELDS (Form 19) -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Ethical Clearance Start Date <span class="text-danger">*</span></label>
                                        <input type="text" name="ethical_clearance_start" class="form-control" placeholder="e.g. June 15, 2022" value="<?php echo htmlspecialchars($reportData['ethical_clearance_start'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Ethical Clearance End Date <span class="text-danger">*</span></label>
                                        <input type="text" name="ethical_clearance_end" class="form-control" placeholder="e.g. June 15, 2023" value="<?php echo htmlspecialchars($reportData['ethical_clearance_end'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">1. Start of Study <span class="text-danger">*</span></label>
                                        <input type="text" name="start_date" class="form-control" placeholder="e.g. October 1, 2022" value="<?php echo htmlspecialchars($reportData['start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">2. End of Study <span class="text-danger">*</span></label>
                                        <input type="text" name="end_date" class="form-control" placeholder="e.g. October 1, 2023" value="<?php echo htmlspecialchars($reportData['end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">3. Number of Enrolled Participants <span class="text-danger">*</span></label>
                                        <input type="text" name="enrolled_participants" class="form-control" placeholder="e.g. 150" value="<?php echo htmlspecialchars($reportData['enrolled_participants'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">4. Number of Required Participants <span class="text-danger">*</span></label>
                                        <input type="text" name="required_participants" class="form-control" placeholder="e.g. 150" value="<?php echo htmlspecialchars($reportData['required_participants'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">5. Number of Participants Who Withdrew</label>
                                        <input type="text" name="withdrawn_participants" class="form-control" placeholder="e.g. 3 (leave blank if none)" value="<?php echo htmlspecialchars($reportData['withdrawn_participants'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Reason for Withdrawal</label>
                                        <input type="text" name="withdrawal_reason" class="form-control" placeholder="e.g. Relocated to another city" value="<?php echo htmlspecialchars($reportData['withdrawal_reason'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">6. Deviations from the Approved Protocol (if any)</label>
                                    <textarea name="deviations" class="form-control" rows="2" placeholder="Describe deviations..."><?php echo htmlspecialchars($reportData['deviations'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">7. Issues/Problems Encountered</label>
                                    <textarea name="issues_encountered" class="form-control" rows="2" placeholder="Describe issues..."><?php echo htmlspecialchars($reportData['issues_encountered'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">8. Summary of Findings <span class="text-danger">*</span></label>
                                    <textarea name="summary_findings" class="form-control" rows="4" placeholder="Provide a concise summary of your research findings..." required><?php echo htmlspecialchars($reportData['summary_findings'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">9. Conclusions <span class="text-danger">*</span></label>
                                    <textarea name="conclusions" class="form-control" rows="3" placeholder="Provide your study conclusions..." required><?php echo htmlspecialchars($reportData['conclusions'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">10. Actions for Dissemination of Study Results <span class="text-danger">*</span></label>
                                    <textarea name="dissemination_actions" class="form-control" rows="3" placeholder="e.g. Publication in peer-reviewed journals, presentations in conferences..." required><?php echo htmlspecialchars($reportData['dissemination_actions'] ?? ''); ?></textarea>
                                </div>

                            <?php endif; ?>

                            <button type="submit" name="submit_report" class="btn btn-navy py-3 w-100 fw-bold shadow-sm rounded-pill mt-4">
                                <i class="fas fa-save me-2"></i> Save & Submit Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-page { background: #f8fafc; min-height: 100vh; }
</style>

<?php include '../includes/footer.php'; ?>
