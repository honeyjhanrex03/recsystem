<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) {
    header("Location: protocols");
    exit();
}

// Fetch Protocol Details
$stmtP = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmtP->execute([$protocol_id]);
$protocol = $stmtP->fetch();

if (!$protocol) {
    header("Location: protocols");
    exit();
}

// Guard: REC Chair must pick a review type before assigning reviewers
if ($protocol['review_type'] === 'pending') {
    header("Location: confirm_protocol?id=" . $protocol_id . "&msg=pick_review_type");
    exit();
}

// Fetch All Active Members, Secretaries, and include Staff just in case (as some act as reviewers)
$stmtM = $pdo->query("SELECT * FROM admins WHERE role IN ('rec_member', 'rec_secretary', 'rec_staff') AND status = 'active' ORDER BY name ASC");
$members = $stmtM->fetchAll();

// Fetch current assignments for this protocol to pre-check them
$stmtCA = $pdo->prepare("SELECT reviewer_id FROM reviewer_assignments WHERE protocol_id = ?");
$stmtCA->execute([$protocol_id]);
$current_assignments = $stmtCA->fetchAll(PDO::FETCH_COLUMN);

// Fetch current chair info
$chair_user_id = $_SESSION['user_id'];
$chair_name = $_SESSION['name'] ?? 'REC Chair';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_now'])) {
    $selected_members = $_POST['members'] ?? [];
    $include_chair = isset($_POST['include_chair']) ? (int) $_POST['include_chair'] : 0;
    $deadline = $_POST['deadline'];

    // Build full list: members + optionally the chair
    $all_reviewers = array_map('intval', $selected_members);
    if ($include_chair && !in_array($chair_user_id, $all_reviewers)) {
        $all_reviewers[] = (int) $chair_user_id;
    }

    // Remove duplicates
    $all_reviewers = array_unique($all_reviewers);

    if (count($all_reviewers) < 3) {
        $error = "At least 3 reviewers are required per protocol (you may include yourself).";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Clear existing assignments if any (allows for re-assignment/correction)
            $stmtD = $pdo->prepare("DELETE FROM reviewer_assignments WHERE protocol_id = ?");
            $stmtD->execute([$protocol_id]);

            // 2. Insert new assignments with automatic 14-day deadline from today
            $deadline_date = date('Y-m-d', strtotime('+14 days'));
            $primary_id = (int)($_POST['primary_reviewer'] ?? 0);
            
            $stmtA = $pdo->prepare("INSERT INTO reviewer_assignments (protocol_id, reviewer_id, deadline, is_primary) VALUES (?, ?, ?, ?)");
            foreach ($all_reviewers as $rid) {
                $isP = ($rid === $primary_id) ? 1 : 0;
                $stmtA->execute([$protocol_id, $rid, $deadline_date, $isP]);
            }

            // 3. Update protocol status (review_type already set by confirm_protocol.php)
            $stmtU = $pdo->prepare("UPDATE protocols SET status = 'assigned' WHERE protocol_id = ?");
            $stmtU->execute([$protocol_id]);

            // 4. Audit Log
            $stmtL = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
            $names = [];
            foreach ($all_reviewers as $rid) {
                foreach ($members as $m) {
                    if ($m['admin_id'] == $rid) {
                        $names[] = $m['name'];
                        break;
                    }
                }
                if ($rid == $chair_user_id)
                    $names[] = $chair_name . " (REC Chair)";
            }
            $deadlineFormatted = date('M d, Y', strtotime('+14 days'));
            $stmtL->execute([$chair_user_id, $protocol_id, "Assigned " . count($all_reviewers) . " reviewers (" . implode(', ', $names) . "). Deadline auto-set to $deadlineFormatted (14 days)."]);

            // 5. Notify Reviewers (In-app + Email)
            require_once '../includes/notifications_helper.php';
            foreach ($all_reviewers as $rid) {
                // Fetch reviewer email and name for the email API
                $revEmail = '';
                $revName = '';
                foreach ($members as $m) {
                    if ($m['admin_id'] == $rid) {
                        $revEmail = $m['email'];
                        $revName = $m['name'];
                        break;
                    }
                }
                if ($rid == $chair_user_id) {
                    $revEmail = $_SESSION['email'];
                    $revName = $chair_name;
                }
                
                notifyUser($pdo, $rid, 'admin', 'Protocol Assigned for Review', 
                    "You have been assigned to review the protocol: {$protocol['rec_code']} - {$protocol['title']}. Evaluation Deadline: $deadlineFormatted.", 
                    "rec_member/review?id=" . $protocol_id,
                    $revEmail, $revName);
            }

            // Notify Author
            notifyUser($pdo, $protocol['created_by'], 'author', 'Reviewers Assigned', 
                "Reviewers have been assigned to your protocol \"{$protocol['title']}\". Evaluation is now underway.", 
                "shared_view?id=" . $protocol_id);

            // Notify Staff
            $stmtS = $pdo->prepare("SELECT admin_id FROM admins WHERE role = 'rec_staff' AND status = 'active'");
            $stmtS->execute();
            $staff = $stmtS->fetchAll();
            foreach ($staff as $s) {
                notifyUser($pdo, $s['admin_id'], 'admin', 'Reviewers Assigned', 
                    "Reviewers have been assigned to protocol: \"{$protocol['title']}\" by the REC Chair.", 
                    "rec_staff/update_status?id=" . $protocol_id);
            }

            $pdo->commit();
            $success = count($all_reviewers) . " reviewer(s) assigned successfully. Evaluation deadline auto-set to $deadlineFormatted (14 days from today).";
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
        $workspaceTitle = "Assign Reviewers";
        $workspaceSubtitle = "Select board members to review this protocol";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <a href="protocols" class="btn btn-link text-navy p-0 mb-3 text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i> Back to Protocols
                    </a>
                    <h2 class="fw-bold text-navy">Assign Reviewers</h2>
                    <p class="text-muted">Protocol: <span
                            class="text-primary fw-bold"><?php echo htmlspecialchars($protocol['rec_code']); ?></span>
                        — <?php echo htmlspecialchars($protocol['title']); ?></p>
                </div>
            </div>

            <!-- Workflow Tracker -->
            <?php include '../includes/workflow_tracker.php'; ?>

            <div class="row mt-4">
                <div class="col-lg-8">
                    <form action="assign?id=<?php echo $protocol_id; ?>" method="POST">

                        <!-- Review Framework Presets -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-navy text-white">
                            <div class="card-body p-4">
                                <h6 class="text-gold fw-bold text-uppercase small mb-3">Setup</h6>
                                <h4 class="fw-bold mb-3">Quick Setup</h4>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-light rounded-pill px-3 fw-bold" onclick="applyExpedited()">
                                        <i class="fas fa-bolt me-1"></i> Expedited (REC Chair + 2 Members)
                                    </button>
                                    <button type="button" class="btn btn-outline-light rounded-pill px-3 fw-bold" onclick="applyFullReview()">
                                        <i class="fas fa-users me-1"></i> Full Review (REC Chair + All Members)
                                    </button>
                                    <a href="decision?id=<?php echo $protocol_id; ?>&framework=exempt" class="btn btn-gold rounded-pill px-3 fw-bold">
                                        <i class="fas fa-check-circle me-1"></i> Mark as Exempt
                                    </a>
                                </div>
                                <input type="hidden" name="review_type" id="review_type_field" value="<?php echo htmlspecialchars($protocol['review_type']); ?>">
                                <p class="mt-3 small opacity-75 mb-0">Selecting a preset will automatically check the required personnel below and set the review framework. You can still customize manually. 
                                <br><strong>Note:</strong> Minimum 3 reviewers are required by ethics policy.</p>
                            </div>
                        </div>

                        <!-- REC Chair Self-Assignment -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-crown me-2 text-warning"></i>Assign Yourself</h5>
                                <small class="text-muted">As REC Chair, you can also join as a reviewer.</small>
                            </div>
                            <div class="card-body p-3">
                                <label class="list-group-item p-3 d-flex align-items-center rounded-3 border"
                                    style="cursor:pointer;">
                                        <input class="form-check-input me-3 reviewer-check" type="checkbox"
                                        name="include_chair" id="chairCheck" value="<?php echo $chair_user_id; ?>"
                                        <?php echo in_array($chair_user_id, $current_assignments) ? 'checked' : ''; ?>
                                        style="width:20px;height:20px;">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($chair_name); ?> <span
                                                class="badge bg-warning-light text-warning ms-2">REC Chair</span></div>
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
                                            — REC Chair, REC (Self-Assignment)</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- REC Member Selection -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Select REC Members</h5>
                                    <small class="text-muted">Minimum of 3 reviewers required.</small>
                                </div>
                                <div class="text-end small text-muted">
                                    Mark one as <strong>Primary</strong>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (count($members) > 0): ?>
                                        <?php foreach ($members as $m): 
                                            $isAssigned = in_array($m['admin_id'], $current_assignments);
                                            // Check if already primary
                                            $stmtPCheck = $pdo->prepare("SELECT is_primary FROM reviewer_assignments WHERE protocol_id = ? AND reviewer_id = ?");
                                            $stmtPCheck->execute([$protocol_id, $m['admin_id']]);
                                            $isPrimary = $stmtPCheck->fetchColumn();
                                        ?>
                                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                                <label class="d-flex align-items-center flex-grow-1" style="cursor:pointer;">
                                                    <input class="form-check-input me-3 reviewer-check" type="checkbox"
                                                        name="members[]" value="<?php echo $m['admin_id']; ?>"
                                                        <?php echo $isAssigned ? 'checked' : ''; ?>
                                                        style="width:20px;height:20px;">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($m['name']); ?>
                                                            <?php if($m['role'] === 'rec_secretary'): ?>
                                                                <span class="badge bg-info-light text-info ms-1" style="font-size:0.6rem;">REC SECRETARY</span>
                                                            <?php elseif($m['role'] === 'rec_staff'): ?>
                                                                <span class="badge bg-secondary-light text-muted ms-1" style="font-size:0.6rem;">REC STAFF</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($m['email']); ?></small>
                                                    </div>
                                                </label>
                                                <div class="ms-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input primary-radio" type="radio" name="primary_reviewer" value="<?php echo $m['admin_id']; ?>" <?php echo $isPrimary ? 'checked' : ''; ?> title="Mark as Primary Reviewer">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="fas fa-user-slash fa-2x mb-2 opacity-25"></i>
                                            <p class="mb-0">No active REC members found. Please ask Admin to create member accounts.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Deadline -->
                        <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 mb-4">
                            <h6 class="fw-bold"><i class="fas fa-clock me-2"></i> Automatic Deadline</h6>
                            <p class="mb-0 small">A <strong>14-day evaluation deadline</strong> will be automatically set from today's date once you assign the reviewers. The Staff can still adjust the deadline later if needed.</p>
                        </div>

                        <!-- Counter badge -->
                        <div class="mb-3">
                            <span class="badge bg-navy px-3 py-2 fs-6" id="reviewerCount">0 reviewer(s) selected</span>
                            <span class="ms-2 text-muted small" id="reviewerWarning"></span>
                        </div>

                        <div class="d-grid mb-5">
                            <button type="submit" name="assign_now"
                                class="btn btn-navy py-3 fw-bold shadow-sm rounded-pill">
                                <i class="fas fa-user-check me-2"></i> Confirm Reviewer Assignment
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm bg-primary-light text-primary rounded-4 p-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> Why at Least 3 Reviewers?</h5>
                        <p class="small mb-3">The REC policy mandates a multi-peer review system to ensure unbiased and
                            thorough ethics evaluation. Each protocol must be independently assessed by at least three
                            reviewers.</p>
                        <p class="small mb-0">As REC Chair, you may include yourself as one of the reviewers to reach the
                            required minimum.</p>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4 mt-3">
                        <h6 class="fw-bold text-navy mb-3"><i class="fas fa-route me-2 text-gold"></i> Review Workflow
                        </h6>
                        <ol class="list-unstyled mb-0">
                            <li class="d-flex align-items-start mb-2"><span
                                    class="badge bg-success-light text-success me-2 mt-1">✓</span><small>Staff submitted
                                    protocol</small></li>
                            <li class="d-flex align-items-start mb-2"><span
                                    class="badge bg-warning-light text-warning me-2 mt-1">→</span><small
                                    class="fw-bold">REC Chair assigns reviewers <em>(current step)</em></small></li>
                            <li class="d-flex align-items-start mb-2"><span
                                    class="badge bg-secondary text-white me-2 mt-1">3</span><small>3 reviewers complete
                                    Form 10 & 12</small></li>
                            <li class="d-flex align-items-start"><span
                                    class="badge bg-secondary text-white me-2 mt-1">4</span><small>Staff prints REC
                                    forms</small></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <script>Swal.fire({ icon: 'error', title: 'Assignment Error', text: '<?php echo addslashes($error); ?>' });</script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Reviewers Assigned!', text: '<?php echo addslashes($success); ?>' }).then(() => {
            window.location.href = 'protocols';
        });
    </script>
<?php endif; ?>

<script>
    window.addEventListener('DOMContentLoaded', event => {


        // Live reviewer counter
        const checks = document.querySelectorAll('.reviewer-check');
        const countBadge = document.getElementById('reviewerCount');
        const warning = document.getElementById('reviewerWarning');

        function updateCount() {
            let count = 0;
            checks.forEach(c => { if (c.checked) count++; });
            countBadge.textContent = count + ' reviewer(s) selected';
            if (count < 3) {
                warning.textContent = '⚠ Select at least ' + (3 - count) + ' more';
                warning.style.color = '#dc3545';
            } else {
                warning.textContent = '✓ Minimum met';
                warning.style.color = '#198754';
            }
        }
        checks.forEach(c => c.addEventListener('change', updateCount));
        updateCount();
    });

    function applyExpedited() {
        const chair = document.getElementById('chairCheck');
        const members = document.querySelectorAll('.reviewer-check:not(#chairCheck)');
        
        // Set type
        document.getElementById('review_type_field').value = 'expedited';
        
        // Reset all
        document.querySelectorAll('.reviewer-check').forEach(c => c.checked = false);
        
        // Select REC Chair
        if(chair) chair.checked = true;
        
        // Select first 2 members
        let count = 0;
        members.forEach(m => {
            if(count < 2) {
                m.checked = true;
                count++;
            }
        });
        
        // Trigger UI update (manual event trigger)
        document.querySelector('.reviewer-check').dispatchEvent(new Event('change', { bubbles: true }));
        Swal.fire({ icon: 'info', title: 'Expedited Preset', text: 'Review type set to EXPEDITED. REC Chair + 2 REC Members selected.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
    }

    function applyFullReview() {
        // Set type
        document.getElementById('review_type_field').value = 'full_board';
        
        // Select everyone
        document.querySelectorAll('.reviewer-check').forEach(c => c.checked = true);
        
        document.querySelector('.reviewer-check').dispatchEvent(new Event('change', { bubbles: true }));
        Swal.fire({ icon: 'info', title: 'Full Review Preset', text: 'Review type set to FULL BOARD. All REC Members + REC Chair selected.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
    }
</script>

<?php include '../includes/footer.php'; ?>
