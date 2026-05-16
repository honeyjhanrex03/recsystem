<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff']);
require_once '../config/database.php';
require_once '../includes/notifications_helper.php';

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Protocol ID.");
}

$protocol_id = (int)$_GET['id'];
$action = $_GET['action'] ?? '';

// Fetch protocol info
$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmt->execute([$protocol_id]);
$protocol = $stmt->fetch();

if (!$protocol) {
    die("Protocol not found.");
}

// Fetch protocol files
$stmtF = $pdo->prepare("SELECT * FROM protocol_files WHERE protocol_id = ? ORDER BY uploaded_at DESC");
$stmtF->execute([$protocol_id]);
$allFiles = $stmtF->fetchAll();

$user_id = $_SESSION['user_id'];
$current_status = $protocol['status'];
$new_status = null;
$log_message = null;

// Handle actual action
if ($action === 'start_review' && $current_status === 'submitted') {
    $new_status = 'staff_review';
    $log_message = "Staff screening started.";
    
    // Notify Author
    notifyUser($pdo, $protocol['created_by'], 'author', 'Screening Started', 
        "REC Staff has started screening your protocol: \"{$protocol['title']}\".", 
        "shared_view?id=" . $protocol_id);
} elseif ($action === 'return' && in_array($current_status, ['staff_review', 'assigned', 'under_review'])) {
    $new_status = 'needs_revision';
    $log_message = "Returned to author for revision. Notes: " . ($_POST['recommendations'] ?? 'None');
    
    // Save recommendations
    $stmtRec = $pdo->prepare("UPDATE protocols SET recommendations = ? WHERE protocol_id = ?");
    $stmtRec->execute([$_POST['recommendations'] ?? '', $protocol_id]);

    // Notify Author
    notifyUser($pdo, $protocol['created_by'], 'author', 'Revision Required', 
        "Your protocol \"{$protocol['title']}\" has been returned for revision. See notes for details.", 
        "shared_view?id=" . $protocol_id);
} elseif ($action === 'forward' && $current_status === 'staff_review') {
    $new_status = 'initial_review';
    
    // Generate REC Code here as requested by user
    $year = date('Y');
    
    // Get next sequence number for the current year
    $stmtSeq = $pdo->prepare("SELECT MAX(sequence_number) FROM protocols WHERE rec_code NOT LIKE 'PENDING-%' AND YEAR(created_at) = ?");
    $stmtSeq->execute([$year]);
    $next_seq = ($stmtSeq->fetchColumn() ?: 0) + 1;
    
    $type_val = $_POST['protocol_type'] ?? 'INT.';
    // DB protocol_type column expects 'INT' or 'EXT' without dot probably, but let's keep what we are given for code
    $db_type = str_replace('.', '', $type_val);
    $initials = $protocol['author_initials'] ?: 'REF';
    
    // Format: Year–Sequence–External/Internal–Name Initials (e.g. 2026-003-EXT.-MMP)
    // Sequence is padded to 3 digits
    $rec_code = sprintf("%s-%03d-%s-%s", $year, $next_seq, $type_val, $initials);
    
    // Update the protocol with the generated code and sequence number
    $updateRec = $pdo->prepare("UPDATE protocols SET rec_code = ?, sequence_number = ?, protocol_type = ? WHERE protocol_id = ?");
    $updateRec->execute([$rec_code, $next_seq, $db_type, $protocol_id]);
    
    // Update $protocol array so emails use the new code
    $protocol['rec_code'] = $rec_code;
    $protocol['tracking_code'] = $rec_code; // since they are the same now
    
    $log_message = "Forwarded to REC Chair for Initial Review. Generated official code: $rec_code";

    // Notify REC Chair
    $stmtChair = $pdo->prepare("SELECT admin_id FROM admins WHERE role = 'rec_chair' AND status = 'active'");
    $stmtChair->execute();
    $chairs = $stmtChair->fetchAll();
    foreach ($chairs as $c) {
        notifyUser($pdo, $c['admin_id'], 'admin', 'Protocol Forwarded to Chair', 
            "New protocol forwarded for assessment: \"{$protocol['title']}\" (REC Code: $rec_code)", 
            "rec_chair/assign?id=" . $protocol_id);
    }

    // Notify Author
    notifyUser($pdo, $protocol['created_by'], 'author', 'Screening Passed', 
        "Your protocol has passed staff screening and is now with the REC Chair. Official REC Code: $rec_code", 
        "shared_view?id=" . $protocol_id);
} elseif ($action === 'resubmit' && $current_status === 'needs_revision') {
    // Check if it has reviewers assigned (post-review resubmission)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reviewer_assignments WHERE protocol_id = ?");
    $stmtCheck->execute([$protocol_id]);
    if ($stmtCheck->fetchColumn() > 0) {
        $new_status = 'revised';
        $log_message = "Author resubmitted revised protocol after board review.";
    } else {
        $new_status = 'staff_review';
        $log_message = "Author resubmitted revised protocol for staff screening.";
    }

    // Notify Staff
    $stmtS = $pdo->prepare("SELECT admin_id FROM admins WHERE role = 'rec_staff' AND status = 'active'");
    $stmtS->execute();
    $staffList = $stmtS->fetchAll();
    foreach ($staffList as $s) {
        notifyUser($pdo, $s['admin_id'], 'admin', 'Author Resubmitted', 
            "A revised protocol has been resubmitted: \"{$protocol['title']}\".", 
            "rec_staff/update_status?id=" . $protocol_id);
    }
} elseif ($action === 'save_checklist' && $current_status === 'staff_review') {
    // Save Form 13 Checklist
    $categories = $_POST['cat'] ?? [];
    $statuses   = $_POST['status'] ?? [];
    $remarks    = $_POST['remarks'] ?? [];
    
    // Clear old
    $pdo->prepare("DELETE FROM form13_answers WHERE protocol_id = ?")->execute([$protocol_id]);
    
    // Insert new
    $stmtC = $pdo->prepare("INSERT INTO form13_answers (protocol_id, staff_id, category, is_submitted, remarks) VALUES (?,?,?,?,?)");
    foreach($categories as $i => $cat) {
        $sVal = $statuses[$i] ?? 'No';
        $rVal = $remarks[$i] ?? '';
        $stmtC->execute([$protocol_id, $user_id, $cat, $sVal, $rVal]);
    }
    
    $pdo->prepare("UPDATE protocols SET form13_status = 'completed' WHERE protocol_id = ?")->execute([$protocol_id]);
    
    // Notify Author
    notifyUser($pdo, $protocol['created_by'], 'author', 'Checklist Completed', 
        "REC Staff has completed the document checklist for your protocol \"{$protocol['title']}\".", 
        "shared_view?id=" . $protocol_id);
    
    header("Location: update_status?id=$protocol_id&success=Checklist Saved");
    exit();
} elseif ($action === 'update_deadline' && in_array($current_status, ['assigned', 'under_review'])) {
    if (isset($_POST['new_deadline'])) {
        $stmtD = $pdo->prepare("UPDATE reviewer_assignments SET deadline = ? WHERE protocol_id = ?");
        $stmtD->execute([$_POST['new_deadline'], $protocol_id]);
        
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
        $log->execute([$user_id, $protocol_id, "REC Staff updated review deadline to " . $_POST['new_deadline']]);

        // Notify Author
        notifyUser($pdo, $protocol['created_by'], 'author', 'Review Deadline Set', 
            "The official review deadline for your protocol \"{$protocol['title']}\" has been set to " . date('M d, Y', strtotime($_POST['new_deadline'])), 
            "shared_view?id=" . $protocol_id);
        
        // Notify Author via Email (Step: Set Evaluation Deadline)
        if (!empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "Review Deadline Established: " . ($protocol['rec_code'] ?: $protocol['tracking_code']);
            $deadline_formatted = date('F d, Y', strtotime($_POST['new_deadline']));
            
            $emailBody = "
                <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                    <h2 style='color: #0f172a;'>Protocol Evaluation Timeline</h2>
                    <p>Dear {$protocol['project_leader']},</p>
                    <p>The REC REC Staff has established the official peer review deadline for your protocol: <strong>{$protocol['title']}</strong>.</p>
                    
                    <div style='background: #f0f9ff; padding: 20px; border-radius: 12px; border-left: 5px solid #0ea5e9; margin: 20px 0;'>
                        <strong style='display: block; margin-bottom: 5px;'>Expected Evaluation Date:</strong>
                        <span style='font-size: 1.2rem; font-weight: bold; color: #0369a1;'>{$deadline_formatted}</span>
                    </div>

                    <p>Our reviewers will aim to complete the ethical evaluation by this date. You will receive further notifications once the board has reached a decision.</p>
                    
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='font-size: 0.8rem; color: #64748b;'>This is an automated notification from the DNSC Research Ethics Committee Registry & Advisory System (DNSC REC).</p>
                </div>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }

        header("Location: protocols?success=Deadline Updated");
        exit();
    }

} elseif ($action === 'release_clearance' && $protocol['status'] === 'approved') {
    try {
        $pdo->beginTransaction();
        
        $new_status = 'clearance_released';
        $log_message = "REC REC Staff officially released the Ethical Clearance (Form 25).";
        
        $stmtUpdate = $pdo->prepare("UPDATE protocols SET status = ? WHERE protocol_id = ?");
        $stmtUpdate->execute([$new_status, $protocol_id]);
        
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
        $log->execute([$user_id, $protocol_id, $log_message]);

        // Notify Author
        notifyUser($pdo, $protocol['created_by'], 'author', 'Ethical Clearance Issued', 
            "Congratulations! The Ethical Clearance for your protocol \"{$protocol['title']}\" has been officially released.", 
            "shared_view?id=" . $protocol_id);
        
        // Notify Author via Email (Final Step)
        if (!empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "ETHICAL CLEARANCE ISSUED: " . ($protocol['rec_code'] ?: $protocol['tracking_code']);
            
            $emailBody = "
                <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                    <h2 style='color: #10b981;'>Congratulations!</h2>
                    <p>Dear {$protocol['project_leader']},</p>
                    <p>The DNSC Research Ethics Committee has officially released the <strong>Ethical Clearance (REC Form 25)</strong> for your protocol:</p>
                    
                    <div style='background: #f0fdf4; padding: 25px; border-radius: 12px; border: 1px solid #bbf7d0; margin: 20px 0;'>
                        <strong style='display: block; color: #166534; font-size: 1.1rem; margin-bottom: 5px;'>{$protocol['title']}</strong>
                        <span style='color: #15803d; font-weight: bold;'>REC Code: {$protocol['rec_code']}</span>
                    </div>

                    <p>You can now log in to the DNSC REC dashboard to download and print your official certificate.</p>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='https://dnsc-rec.edu.ph/author/dashboard' style='background: #1a2b4b; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold;'>Go to Dashboard</a>
                    </div>

                    <p style='margin-top: 30px;'>Please ensure all ethically-approved procedures are strictly followed during the conduct of your research.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='font-size: 0.8rem; color: #64748b;'>This is the final notification for the ethical review process of this protocol.</p>
                </div>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }

        $pdo->commit();
        header("Location: protocols?success=Clearance Released Successfully");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// Process status change
if ($new_status) {

    try {
        $pdo->beginTransaction();

        // Update status only. Review type is handled by REC Chair during confirmation.
        $updateStmt = $pdo->prepare("UPDATE protocols SET status = ? WHERE protocol_id = ?");
        $updateStmt->execute([$new_status, $protocol_id]);

        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
        $logStmt->execute([$user_id, $protocol_id, $log_message]);

        $pdo->commit();
        
        // Notify author if returned for revision
        if ($new_status === 'needs_revision' && !empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "Protocol Revision Required: {$protocol['rec_code']}";
            $recs = $_POST['recommendations'] ?? 'Please check the tracker for details.';
            $emailBody = "
                <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                    <h2 style='color: #0f172a;'>Action Required: Revision Needed</h2>
                    <p>Dear {$protocol['project_leader']},</p>
                    <p>During the screening of your protocol <strong>{$protocol['title']}</strong>, our staff found missing requirements.</p>
                    <div style='background:#fff1f2; padding:20px; border-left:5px solid #e11d48; border-radius: 8px; margin:20px 0;'>
                        <strong style='display: block; margin-bottom: 5px;'>Staff Recommendations:</strong>
                        " . nl2br(htmlspecialchars($recs)) . "
                    </div>
                    <p>Please log in to the DNSC REC system and upload the required <strong>RAC Form 15 (Resubmission Form)</strong> along with your revised documents.</p>
                    <p>Best regards,<br>DNSC REC Staff</p>
                </div>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }

        // Notify author if forwarded to REC Chair (passed staff screening)
        if ($new_status === 'initial_review' && !empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "Screening Update: Protocol Forwarded to REC Chair";
            
            $emailBody = "
                <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                    <h2 style='color: #0f172a;'>Staff Screening Passed</h2>
                    <p>Dear {$protocol['project_leader']},</p>
                    <p>Great news! Your protocol <strong>{$protocol['title']}</strong> has successfully passed the initial REC Staff screening.</p>
                    <p>It has now been forwarded to the <strong>REC Chairperson</strong> for initial assessment and determination of the review type (Exempt, Expedited, or Full Review).</p>
                    <p>The official REC code for your protocol is now: <strong>{$protocol['rec_code']}</strong></p>
                    <p>You will receive another notification once the REC Chair has determined the review track for your study.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='font-size: 0.8rem; color: #64748b;'>Track your status: <strong>{$protocol['rec_code']}</strong></p>
                </div>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }

        header("Location: protocols?success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error updating status: " . $e->getMessage());
    }
}

include '../includes/header.php';
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <div class="container-fluid p-4 p-md-5">
            <h2 class="fw-bold text-navy mb-4">Update Protocol Status</h2>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="bg-navy-light p-3 rounded-circle">
                            <i class="fas fa-fingerprint fa-2x text-navy"></i>
                        </div>
                    </div>
                    <h6 class="text-muted fw-bold text-uppercase small" style="letter-spacing:1px;">Committee Tracking Code</h6>
                    <h3 class="fw-bold text-navy"><?php echo htmlspecialchars($protocol['tracking_code']); ?></h3>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($protocol['rec_code']); ?> - <?php echo htmlspecialchars($protocol['title']); ?></h5>
                    <p>Current Status: <span class="badge bg-secondary"><?php echo strtoupper(str_replace('_', ' ', $current_status)); ?></span></p>
                    
                    <hr>

                    <!-- Submitted Documents Section for Staff -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-navy mb-3"><i class="fas fa-file-pdf me-2"></i> Submitted Documents for Screening</h6>
                        <?php if (count($allFiles) > 0): ?>
                            <div class="list-group list-group-flush rounded-4 border overflow-hidden">
                                <?php foreach ($allFiles as $f): ?>
                                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-navy small"><?php echo htmlspecialchars($f['file_name']); ?></div>
                                            <span class="badge bg-soft-navy text-navy rounded-pill x-small border px-2 py-1"><?php echo strtoupper($f['document_type'] ?? 'OTHER'); ?></span>
                                            <small class="text-muted ms-2" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($f['uploaded_at'])); ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-navy btn-sm px-3 rounded-pill fw-bold" onclick="viewPDF('../uploads/protocols/<?php echo htmlspecialchars(addslashes($f['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($f['file_name'])); ?>')">
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                            <a href="../uploads/protocols/<?php echo htmlspecialchars($f['file_path']); ?>" target="_blank" class="btn btn-navy btn-sm rounded-pill px-3">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted bg-light rounded-4 border border-dashed">
                                <i class="fas fa-exclamation-circle mb-2"></i>
                                <p class="small mb-0">No documents found for this protocol.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <?php if ($current_status === 'submitted'): ?>
                        <div class="alert alert-info border-0 rounded-4">
                            <h6><i class="fas fa-info-circle me-2"></i> Initial Screening Request</h6>
                            <p class="mb-0 small">The author has submitted this protocol. Click below to begin the staff screening step.</p>
                        </div>
                        <a href="update_status?id=<?php echo $protocol_id; ?>&action=start_review" class="btn btn-primary d-block w-100">
                            Begin REC Staff Screening <i class="fas fa-arrow-right ms-2"></i>
                        </a>

                    <?php elseif ($current_status === 'staff_review'): ?>
                         <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4">
                            <h6 class="fw-bold"><i class="fas fa-search me-2"></i> REC Form 13: REC Staff Screening</h6>
                            <p class="mb-0 small">Please perform a systematic check of all submitted documents. This checklist will populate the official **REC Form 13**.</p>
                        </div>
                        
                        <!-- Interactive Form 13 Checklist -->
                        <form action="update_status?id=<?php echo $protocol_id; ?>&action=save_checklist" method="POST" id="checklistForm">
                            <div class="table-responsive rounded-4 mb-4">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3 py-3" style="width: 40%;">Category</th>
                                            <th class="text-center py-3">Submitted?</th>
                                            <th class="py-3">Remarks / Findings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $docs = [
                                            "Research Protocol",
                                            "Informed Consent / Assent Consent",
                                            "Guide Questionnaire",
                                            "Curriculum Vitae",
                                            "Letter Request",
                                            "Endorsement"
                                        ];
                                        
                                        // Fetch existing
                                        $stmtE = $pdo->prepare("SELECT * FROM form13_answers WHERE protocol_id = ?");
                                        $stmtE->execute([$protocol_id]);
                                        $existing = [];
                                        while($r = $stmtE->fetch()) {
                                            $existing[$r['category']] = $r;
                                        }

                                        foreach($docs as $i => $doc): 
                                            $curr = $existing[$doc] ?? null;
                                            $currStat = $curr['is_submitted'] ?? 'No';
                                            $currRem = $curr['remarks'] ?? '';
                                        ?>
                                        <tr>
                                            <td class="ps-3 py-3">
                                                <input type="hidden" name="cat[<?php echo $i; ?>]" value="<?php echo $doc; ?>">
                                                <span class="fw-bold text-navy"><?php echo $doc; ?></span>
                                            </td>
                                            <td class="text-center py-3">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <input type="radio" class="btn-check" name="status[<?php echo $i; ?>]" id="yes_<?php echo $i; ?>" value="Yes" <?php echo ($currStat=='Yes'?'checked':''); ?>>
                                                    <label class="btn btn-outline-success px-3" for="yes_<?php echo $i; ?>">Yes</label>
                                                    
                                                    <input type="radio" class="btn-check" name="status[<?php echo $i; ?>]" id="no_<?php echo $i; ?>" value="No" <?php echo ($currStat=='No'?'checked':''); ?>>
                                                    <label class="btn btn-outline-danger px-3" for="no_<?php echo $i; ?>">No</label>

                                                    <input type="radio" class="btn-check" name="status[<?php echo $i; ?>]" id="na_<?php echo $i; ?>" value="N/A" <?php echo ($currStat=='N/A'?'checked':''); ?>>
                                                    <label class="btn btn-outline-secondary px-3" for="na__<?php echo $i; ?>">N/A</label>
                                                </div>
                                            </td>
                                            <td class="py-2">
                                                <input type="text" name="remarks[<?php echo $i; ?>]" class="form-control form-control-sm border-0 bg-light" 
                                                       placeholder="Add notes..." value="<?php echo htmlspecialchars($currRem); ?>">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex gap-3 mb-5">
                                <button type="submit" class="btn btn-navy flex-grow-1 py-3 fw-bold rounded-4 shadow-sm">
                                    <i class="fas fa-save me-2"></i> Save Checklist Progress
                                </button>
                            </div>
                        </form>

                        <div class="row gx-3">
                            <div class="col-md-6 mb-2">
                                <button type="button" class="btn btn-outline-danger w-100 py-3 rounded-4" data-bs-toggle="modal" data-bs-target="#returnModal">
                                    <i class="fas fa-undo-alt me-2"></i> Return for Revision
                                </button>
                            </div>
                            <div class="col-md-6">
                               <button type="button" class="btn btn-success w-100 py-3 rounded-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#forwardModal"
                                       <?php echo ($protocol['form13_status'] != 'completed' ? 'disabled' : ''); ?>>
                                    <i class="fas fa-paper-plane me-2"></i> Authorize & Forward to REC Chair
                                </button>
                                <?php if($protocol['form13_status'] != 'completed'): ?>
                                    <div class="text-center mt-2 small text-danger"><i class="fas fa-lock me-1"></i> Save checklist first to enable forwarding</div>
                                <?php endif; ?>
                            </div>
                        </div>



                    <?php elseif ($current_status === 'needs_revision'): ?>
                        <div class="alert alert-danger border-0 rounded-4">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i> Awaiting Committee Revision</h6>
                            <p class="mb-0 small">This protocol was returned to the committee. Once the committee has updated the required information (you may edit the protocol via the edit button on the dashboard), mark it as resubmitted to continue the screening process.</p>
                        </div>
                        <a href="update_status?id=<?php echo $protocol_id; ?>&action=resubmit" class="btn btn-info text-white d-block w-100">
                            <i class="fas fa-sync-alt me-2"></i> Committee Resubmitted
                        </a>

                    <?php elseif (in_array($current_status, ['assigned', 'under_review'])): ?>
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-navy text-white py-3 border-0">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i> Manage Evaluation Deadline</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php
                                $stmtFwd = $pdo->prepare("SELECT timestamp FROM audit_logs WHERE protocol_id = ? AND action LIKE '%Forwarded to REC Chair%' ORDER BY timestamp ASC LIMIT 1");
                                $stmtFwd->execute([$protocol_id]);
                                $forwarded_date = $stmtFwd->fetchColumn();
                                if (!$forwarded_date) {
                                    $forwarded_date = date('Y-m-d H:i:s'); // fallback to today if not found
                                }
                                
                                $rt = strtolower($protocol['review_type'] ?? 'expedited');
                                if ($rt == 'exempt') {
                                    $time_string = "+5 days";
                                    $type_label = "Exemption (5 days)";
                                } elseif ($rt == 'full_board' || $rt == 'full') {
                                    $time_string = "+6 weeks";
                                    $type_label = "Full Review (6 weeks / 21 working days)";
                                } else {
                                    $time_string = "+14 days";
                                    $type_label = "Expedited Review (2 weeks)";
                                }
                                $suggested_deadline = date('Y-m-d', strtotime($forwarded_date . ' ' . $time_string));
                                $forwarded_display = date('M d, Y', strtotime($forwarded_date));
                                ?>
                                <div class="alert alert-primary border-0 rounded-4 mb-4">
                                    <h6 class="fw-bold"><i class="fas fa-info-circle me-1"></i> Evaluation Deadlines</h6>
                                    <p class="mb-2 small">Deadline counting starts after initial checking by REC staff (<strong><?php echo $forwarded_display; ?></strong>). This protocol is categorized as <strong><?php echo $type_label; ?></strong>.</p>
                                    <ul class="mb-0 small">
                                        <li><strong>Exemption:</strong> 5 days</li>
                                        <li><strong>Expedited:</strong> 2 weeks</li>
                                        <li><strong>Full Review:</strong> 6 weeks (21 working days)</li>
                                    </ul>
                                </div>
                                
                                <?php
                                $stmtD = $pdo->prepare("SELECT deadline FROM reviewer_assignments WHERE protocol_id = ? LIMIT 1");
                                $stmtD->execute([$protocol_id]);
                                $current_db_deadline = $stmtD->fetchColumn();
                                
                                // Default to the suggested deadline if not set
                                $default_val = $current_db_deadline ?: $suggested_deadline;
                                ?>

                                <form action="update_status?id=<?php echo $protocol_id; ?>&action=update_deadline" method="POST">
                                    <div class="form-group mb-4">
                                        <label class="fw-bold text-navy small mb-2 d-block">Protocol Submission Deadline</label>
                                        <input type="date" name="new_deadline" class="form-control form-control-lg border-2 shadow-sm rounded-3" 
                                               value="<?php echo $default_val; ?>" required>
                                        <small class="text-muted mt-2 d-block"><i class="fas fa-clock me-1"></i> Suggested Deadline: <strong><?php echo date('M d, Y', strtotime($suggested_deadline)); ?></strong> (based on <?php echo $type_label; ?>)</small>
                                    </div>
                                    <button type="submit" class="btn btn-navy w-100 py-3 fw-bold rounded-pill shadow-lg hover-up">
                                        <i class="fas fa-save me-2"></i> Set Official Review Deadline
                                    </button>
                                </form>
                                <div class="text-center mt-3">
                                    <a href="protocols" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Return to Registry</a>
                                </div>
                                <!-- Automation Notice: Manual consolidation is no longer needed as the system handles this automatically upon final review submission -->
                                <div class="mt-4 pt-3 border-top text-center">
                                    <p class="small text-muted mb-0"><i class="fas fa-robot me-1 text-primary"></i> <strong>Workflow Automated:</strong> This protocol will automatically move to 'Needs Revision' once the final reviewer submits their evaluation.</p>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($current_status === 'confirmed'): ?>
                        <div class="alert alert-success border-0 rounded-4">
                            <h6><i class="fas fa-check-double me-2"></i> REC Chair Confirmed</h6>
                            <p class="mb-0 small">The REC Chair has confirmed this submission as complete and will soon assign reviewers.</p>
                        </div>
                        <a href="protocols" class="btn btn-outline-secondary w-100 mt-2">Back to Registry</a>

                    <?php else: ?>
                        <div class="alert alert-secondary border-0 rounded-4">
                            <p class="mb-0 small">This protocol is currently in the <strong><?php echo strtoupper(str_replace('_', ' ', $current_status)); ?></strong> phase. Status updates are handled by other actors (REC Chair or Reviewers) from this point onward.</p>
                        </div>
                        <a href="protocols" class="btn btn-outline-secondary w-100 mt-2">Return to List</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

</script>

<?php if (in_array($current_status, ['staff_review', 'assigned', 'under_review'])): ?>
<!-- Forward Modal -->
<div class="modal fade" id="forwardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="update_status?id=<?php echo $protocol_id; ?>&action=forward" method="POST">
                <div class="modal-header bg-navy text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-paper-plane me-2"></i> Forward to REC Chair</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">You are about to forward this protocol to the REC Chair for <strong>Initial Assessment & Confirmation</strong>.</p>
                    <p class="text-muted small">Please select the project type to generate the official REC Code:</p>
                    
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body">
                            <label class="fw-bold text-navy small mb-2 d-block">Project Type <span class="text-danger">*</span></label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="protocol_type" id="typeInt" value="INT." required>
                                <label class="form-check-label" for="typeInt">
                                    <strong>INTERNAL</strong> (From DNSC or inside DNSC)
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="protocol_type" id="typeExt" value="EXT." required>
                                <label class="form-check-label" for="typeExt">
                                    <strong>EXTERNAL</strong> (Outside DNSC)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-navy w-100 py-3 rounded-3 fw-bold shadow-sm">
                        Forward for REC Chair's Assessment <i class="fas fa-paper-plane ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return for Revision Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="update_status?id=<?php echo $protocol_id; ?>&action=return" method="POST">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-undo-alt me-2"></i> Return for Revision</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">As REC Staff (Step 5), specify the missing requirements or document deficiencies below. These will be sent to the researcher.</p>
                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-bold text-navy small">Missing Requirements / Recommendations</label>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="consolidateReviewerComments()">
                                <i class="fas fa-magic me-1"></i> Consolidate Reviewer Comments
                            </button>
                        </div>
                        <textarea name="recommendations" id="recommendationsArea" class="form-control border-2" rows="7" placeholder="e.g. RAC Form 1 is not signed; Protocol missing section 3..." required></textarea>
                        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i> Tip: Consolidating will automatically pull feedback from all reviewers (Step 14).</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">Send Notification & Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function consolidateReviewerComments() {
    const btn = event.currentTarget;
    const area = document.getElementById('recommendationsArea');
    const protocolId = <?php echo $protocol_id; ?>;
    
    // UI Loading State
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Consolidating...';
    try {
        const response = await fetch('ajax_consolidate_comments?id=' + protocolId);
        const data = await response.json();
        
        if (data.success) {
            // Append or Overwrite? 
            // In these systems, usually adding to existing is safer, but cleaning first is cleaner if done multiple times.
            // Let's prompt or just overwrite if it's empty.
            if (area.value.trim() !== "") {
                const confirmed = await Swal.fire({
                    title: 'Existing Notes Found',
                    text: "Would you like to overwrite your current notes or append the reviewer comments to them?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Overwrite',
                    cancelButtonText: 'Append',
                    reverseButtons: true
                });
                
                if (confirmed.isConfirmed) {
                    area.value = data.comments;
                } else if (confirmed.dismiss === Swal.DismissReason.cancel) {
                    area.value += "\n\n" + data.comments;
                }
            } else {
                area.value = data.comments;
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Consolidated!',
                text: 'Reviewer comments have been pulled into the resubmission form.',
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Fetch Error', text: data.message });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Network Error', text: error.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

<?php endif; ?>

<script>
function viewPDF(filepath, filename) {
    document.getElementById('pdfViewerTitle').innerHTML = '<i class="fas fa-file-pdf me-2 text-danger"></i> ' + filename;
    document.getElementById('pdfIframe').src = filepath;
    document.getElementById('modalDownloadBtn').href = filepath;
    var pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    pdfModal.show();
}

// Clear iframe src when modal is hidden
document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('pdfIframe').src = '';
});
</script>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header bg-navy text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="pdfViewerTitle"><i class="fas fa-file-pdf me-2 text-danger"></i> Document Viewer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="height: 80vh;">
                <iframe id="pdfIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer bg-white border-0 py-2">
                <a id="modalDownloadBtn" href="#" download class="btn btn-navy px-4 rounded-pill"><i class="fas fa-download me-2"></i> Download PDF</a>
                <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

