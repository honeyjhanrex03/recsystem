<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) {
    header("Location: protocols");
    exit();
}

// Fetch Protocol
$stmtP = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmtP->execute([$protocol_id]);
$protocol = $stmtP->fetch();

// Fetch Review Summary & Recommendations
$stmtR = $pdo->prepare("
    SELECT u.name as reviewer_name, a.status, 
           (SELECT recommendation FROM reviewer_recommendations 
            WHERE protocol_id = a.protocol_id AND reviewer_id = a.reviewer_id 
            ORDER BY created_at DESC LIMIT 1) as recommendation
    FROM reviewer_assignments a 
    JOIN admins u ON a.reviewer_id = u.admin_id 
    WHERE a.protocol_id = ?
");
$stmtR->execute([$protocol_id]);
$reviewers = $stmtR->fetchAll();

// Check for pending reviews
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reviewer_assignments WHERE protocol_id = ? AND status = 'pending'");
$stmtCheck->execute([$protocol_id]);
$pendingReviews = $stmtCheck->fetchColumn();

// Check if any reviewer recommended revision
$hasRevisionRecommendation = false;
foreach ($reviewers as $r) {
    if (in_array($r['recommendation'], ['Minor Revision', 'Major Revision'])) {
        $hasRevisionRecommendation = true;
        break;
    }
}

$isRevisedVersion = ($protocol['status'] === 'revised');
$requiresMandatoryRevision = in_array($protocol['review_type'], ['expedited', 'full_board']) && !$isRevisedVersion;

$error = "";
if ($pendingReviews > 0) {
    $error = "Warning: There are still $pendingReviews pending evaluations from REC Members. A final decision cannot be rendered until all members have submitted their results.";
}
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_decision'])) {
    $decision = $_POST['final_decision'];
    $remarks = trim($_POST['remarks']);

    // MANDATORY BLOCK: Cannot decide if reviews are pending
    if ($pendingReviews > 0) {
        $error = "System Block: You cannot finalize a decision while there are pending evaluator results.";
    } 
    // MANDATORY REVISION CHECK: Block 'Approved' if members suggested revisions OR if it's a direct approval for EXP/FULL
    elseif ($decision === 'Approved') {
        if ($hasRevisionRecommendation && !$isRevisedVersion) {
            $error = "System Policy Violation: You cannot grant final approval on an initial submission that has pending revision recommendations. You must return it for revision first.";
        } elseif ($requiresMandatoryRevision) {
            $error = "Procedural Requirement: Expedited and Full Board reviews require at least one revision cycle before final approval can be granted. Please return this protocol for Minor or Major Revision to comply with the workflow.";
        }
    } 
    
    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // 1. Save Decision
            $meeting_date = $_POST['meeting_date'] ?? null;
            $stmtD = $pdo->prepare("INSERT INTO final_decisions (protocol_id, chair_id, meeting_date, final_decision, remarks) VALUES (?, ?, ?, ?, ?)");
            $stmtD->execute([$protocol_id, $_SESSION['user_id'], $meeting_date, $decision, $remarks]);

        // 1b. Save Form 9 Data if expedited
        if($protocol['review_type'] == 'expedited' && isset($_POST['f9_section'])) {
            $pdo->prepare("DELETE FROM form9_answers WHERE protocol_id = ?")->execute([$protocol_id]);
            $stmtF9 = $pdo->prepare("INSERT INTO form9_answers (protocol_id, chair_id, section, decision) VALUES (?,?,?,?)");
            foreach($_POST['f9_section'] as $sec) {
                $decVal = $_POST['f9_decision_'.$sec] ?? 'Approval';
                $stmtF9->execute([$protocol_id, $_SESSION['user_id'], $sec, $decVal]);
            }
        }

        // 2. Update Protocol Status
        if ($decision === 'Approved') {
            $finalStatus = 'approved';
        } elseif ($decision === 'Disapproved') {
            $finalStatus = 'rejected';
        } else {
            // Minor or Major Revision
            $finalStatus = 'needs_revision';
        }
        $stmtU = $pdo->prepare("UPDATE protocols SET status = ?, recommendations = ? WHERE protocol_id = ?");
        $stmtU->execute([$finalStatus, $remarks, $protocol_id]);

        // 3. Audit Log
        $stmtL = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
        $stmtL->execute([$_SESSION['user_id'], $protocol_id, "Made final decision: $decision"]);

        // 5. Update Form 15 Assessment if present
        if (isset($_POST['f15_assessment'])) {
            $stmtF15A = $pdo->prepare("UPDATE form15_responses SET rec_assessment = ? WHERE response_id = ?");
            foreach ($_POST['f15_assessment'] as $resp_id => $assess) {
                $stmtF15A->execute([$assess, $resp_id]);
            }
        }

        $pdo->commit();
        $success = "Final decision has been recorded and protocol status updated to " . strtoupper($finalStatus);
        
        require_once '../includes/notifications_helper.php';
        // Notify Author
        notifyUser($pdo, $protocol['created_by'], 'author', 'Final Decision Rendered', 
            "The REC Board has rendered a final decision on your protocol: \"{$protocol['title']}\". Decision: " . strtoupper($decision), 
            "shared_view?id=" . $protocol_id);

        // Notify Staff
        $stmtS = $pdo->prepare("SELECT admin_id FROM admins WHERE role = 'rec_staff' AND status = 'active'");
        $stmtS->execute();
        $staff = $stmtS->fetchAll();
        foreach ($staff as $s) {
            notifyUser($pdo, $s['admin_id'], 'admin', 'Decision Rendered', 
                "The REC Chair has finalized the decision for protocol: \"{$protocol['title']}\". Result: " . strtoupper($decision), 
                "rec_staff/update_status.php?id=" . $protocol_id);
        }
        
        // 4. Notify Author via Email
        if (!empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "Final Decision: {$protocol['rec_code']}";
            
            $decision_color = ($decision === 'Approved') ? 'green' : 'red';
            $action_text = ($decision === 'Approved') ? 'download your clearance' : 'make the necessary revisions';
            $formatted_remarks = nl2br(htmlspecialchars($remarks));

            $emailBody = "
                <h2>Protocol Review Update</h2>
                <p>Dear {$protocol['project_leader']},</p>
                <p>The DNSC Research Ethics Committee has rendered a final decision on your protocol: <strong>{$protocol['title']}</strong>.</p>
                <p>Decision: <strong style='font-size: 1.2em; color: {$decision_color};'>{$decision}</strong></p>
                <p><strong>Remarks from REC Chair:</strong></p>
                <blockquote style='background: #f9f9f9; padding: 10px; border-left: 4px solid #1a2b4b;'>
                    {$formatted_remarks}
                </blockquote>
                <br>
                <p>Please log in or <strong>{$action_text}</strong> accordingly.</p>
                <p>Best regards,<br>DNSC REC Data Center</p>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Submit Final Decision";
        $workspaceSubtitle = "Record the official board decision for this research";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <a href="protocols" class="btn btn-link text-navy p-0 mb-3 text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i> Back to Review List
                    </a>
                    <h2 class="fw-bold text-navy">Final Decision Panel</h2>
                    <p class="text-muted">Protocol: <span class="fw-bold text-primary">
                            <?php echo $protocol['rec_code']; ?>
                        </span> -
                        <?php echo $protocol['title']; ?>
                    </p>
                </div>
            </div>

            <div class="row">
                <!-- Reviewer Summary -->
                <div class="col-lg-4 mb-4">
                    <?php 
                    $isExpOrFull = in_array($protocol['review_type'], ['expedited', 'full_board']);
                    $showRevisionWarning = $hasRevisionRecommendation || ($isExpOrFull && !$isRevisedVersion);
                    ?>

                    <?php if ($showRevisionWarning): ?>
                        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 animate__animated animate__shakeX">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-exclamation-triangle me-2"></i> Procedural Requirement</h6>
                            <p class="mb-0 small" style="line-height: 1.5;">
                                <?php if ($isExpOrFull && !$isRevisedVersion): ?>
                                    Expedited and Full Board reviews <strong>must</strong> undergo at least one revision cycle. Direct approval is prohibited on initial submissions.
                                <?php else: ?>
                                    Evaluators have identified issues that require revision. System policy requires authors to address these feedback points before final approval.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                             <h6 class="mb-0 fw-bold"><i class="fas fa-users-cog me-2"></i> REC Member Feedback</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($reviewers as $r): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <div class="fw-bold small">
                                                <?php echo $r['reviewer_name']; ?>
                                            </div>
                                            <small class="text-muted">Assigned REC Member</small>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($r['status'] === 'completed'): ?>
                                                <?php 
                                                $rec = $r['recommendation'] ?? 'Evaluated';
                                                $badgeClass = 'bg-success';
                                                if ($rec === 'Minor Revision') $badgeClass = 'bg-warning text-dark';
                                                if ($rec === 'Major Revision') $badgeClass = 'bg-danger';
                                                if ($rec === 'Disapproved') $badgeClass = 'bg-dark';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?> px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.65rem;">
                                                    <?php echo strtoupper($rec); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill" style="font-size: 0.65rem;">
                                                    PENDING
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form 15 Assessment (Resubmission) -->
                <?php 
                $stmtF15 = $pdo->prepare("SELECT * FROM form15_responses WHERE protocol_id = ?");
                $stmtF15->execute([$protocol_id]);
                $f15_responses = $stmtF15->fetchAll();
                if ($f15_responses): 
                ?>
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-navy text-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-tasks me-2"></i> REC FORM 15: Resubmission Assessment</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4" style="width: 30%;">REC Recommendation</th>
                                        <th style="width: 30%;">Author Response</th>
                                        <th style="width: 10%;">Page</th>
                                        <th class="pe-4" style="width: 30%;">REC Assessment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($f15_responses as $resp): ?>
                                    <tr>
                                        <td class="ps-4 py-3 text-muted"><?php echo nl2br(htmlspecialchars($resp['rec_recommendation'])); ?></td>
                                        <td class="py-3"><?php echo nl2br(htmlspecialchars($resp['author_response'])); ?></td>
                                        <td class="py-3 fw-bold text-navy"><?php echo htmlspecialchars($resp['page_reference']); ?></td>
                                        <td class="pe-4 py-3">
                                            <select name="f15_assessment[<?php echo $resp['response_id']; ?>]" class="form-select form-select-sm border-2">
                                                <option value="" <?php echo empty($resp['rec_assessment']) ? 'selected' : ''; ?>>Pending...</option>
                                                <option value="Adequate" <?php echo ($resp['rec_assessment'] == 'Adequate') ? 'selected' : ''; ?>>Adequate</option>
                                                <option value="Inadequate" <?php echo ($resp['rec_assessment'] == 'Inadequate') ? 'selected' : ''; ?>>Inadequate</option>
                                                <option value="Partially Adequate" <?php echo ($resp['rec_assessment'] == 'Partially Adequate') ? 'selected' : ''; ?>>Partially Adequate</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Decision Form -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                        <form action="decision?id=<?php echo $protocol_id; ?>" method="POST">
                            <div class="mb-4">
                                 <label class="form-label fw-bold text-navy">Final Decision</label>
                                
                                <?php if($protocol['review_type'] == 'expedited'): ?>
                                    <!-- Interactive Form 9 Table for Expedited -->
                                    <div class="alert alert-primary border-0 rounded-4 shadow-sm mb-4">
                                         <h6 class="fw-bold"><i class="fas fa-file-invoice me-2"></i> REC Form 09: Expedited Evaluation Details</h6>
                                        <p class="mb-0 small">Please record the result for each section below.</p>
                                    </div>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-bordered align-middle" style="font-size: 0.9rem;">
                                            <thead class="bg-light">
                                                <tr>
                                                     <th>Review Category</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $sections = ["NEW RESEARCH STUDY (Minor Risk)", "NEW RESEARCH STUDY (Minor Revision)", "NEW RESEARCH STUDY (Amendments)"]; 
                                                  foreach($sections as $s): ?>
                                                  <tr>
                                                      <td>
                                                          <input type="hidden" name="f9_section[]" value="<?php echo $s; ?>">
                                                          <strong><?php echo $s; ?></strong>
                                                      </td>
                                                      <td>
                                                          <select name="f9_decision_<?php echo $s; ?>" class="form-select form-select-sm border-2">
                                                              <option value="Approval">Approval</option>
                                                              <option value="Minor Modification">Minor Modification</option>
                                                              <option value="Major Modification">Major Modification</option>
                                                              <option value="Disapproval">Disapproval</option>
                                                              <option value="N/A">Not Applicable</option>
                                                          </select>
                                                      </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <?php if($protocol['review_type'] == 'full_board'): ?>
                                    <!-- Meeting Date for Full Board -->
                                    <div class="alert alert-navy border-0 rounded-4 shadow-sm mb-4 text-white" style="background:#1a2b4b;">
                                         <h6 class="fw-bold mb-1"><i class="fas fa-calendar-check me-2 text-gold"></i> Full Board Meeting Details</h6>
                                        <p class="mb-0 small opacity-75">Please specify the date when the committee convened for this protocol.</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Board Meeting Date</label>
                                        <input type="date" name="meeting_date" class="form-control form-control-lg border-2" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                <?php endif; ?>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="final_decision" id="dec_app"
                                            value="Approved" required>
                                        <label class="btn btn-outline-success w-100 py-3 fw-bold" for="dec_app"><i
                                                class="fas fa-thumbs-up me-2"></i> APPROVED</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="final_decision" id="dec_minor"
                                            value="Minor Revision" required>
                                        <label class="btn btn-outline-warning w-100 py-3 fw-bold" for="dec_minor"><i
                                                class="fas fa-edit me-2"></i> MINOR REVISION</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="final_decision" id="dec_major"
                                            value="Major Revision" required>
                                        <label class="btn btn-outline-danger w-100 py-3 fw-bold" for="dec_major"><i
                                                class="fas fa-exclamation-triangle me-2"></i> MAJOR REVISION</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="final_decision" id="dec_dis"
                                            value="Disapproved" required>
                                        <label class="btn btn-outline-dark w-100 py-3 fw-bold" for="dec_dis"><i
                                                class="fas fa-thumbs-down me-2"></i> DISAPPROVED</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                 <label class="form-label fw-bold text-navy">Final Message for the Researcher</label>
                                <textarea name="remarks" class="form-control" rows="5"
                                    placeholder="Enter detailed feedback or reasons for the decision..."
                                    required></textarea>
                            </div>

                            <div class="d-grid shadow-sm">
                                <button type="submit" name="submit_decision"
                                    class="btn btn-navy py-3 fw-bold rounded-pill shadow">
                                    <i class="fas fa-save me-2"></i> Save Decision & Notify Staff
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <script>Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo $error; ?>' });</script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Decision Saved', text: '<?php echo $success; ?>' }).then(() => { window.location.href = 'protocols'; });
    </script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const selectedDecision = document.querySelector('input[name="final_decision"]:checked');
            
            // BLOCK 1: Pending Evaluations
            if (<?php echo $pendingReviews; ?> > 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Evaluations Incomplete',
                    text: 'You cannot finalize a decision until all assigned REC members have submitted their evaluations.',
                    confirmButtonColor: '#1a2b4b'
                });
                return;
            }

            // BLOCK 2: Revision Obligation
            const requiresMandatoryRevision = <?php echo $requiresMandatoryRevision ? 'true' : 'false'; ?>;
            const hasRevisionRecommendation = <?php echo $hasRevisionRecommendation ? 'true' : 'false'; ?>;
            const isRevisedVersion = <?php echo $isRevisedVersion ? 'true' : 'false'; ?>;

            if (selectedDecision && selectedDecision.value === 'Approved') {
                if (hasRevisionRecommendation && !isRevisedVersion) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'System Policy: Revision Obliged',
                        text: 'Evaluators have recommended revisions. You must return this protocol for "Minor" or "Major Revision" first.',
                        confirmButtonColor: '#1a2b4b'
                    });
                } else if (requiresMandatoryRevision) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'info',
                        title: 'Procedural Requirement',
                        text: 'Expedited and Full Board reviews must go through at least one revision cycle before final approval can be granted.',
                        confirmButtonColor: '#1a2b4b'
                    });
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
