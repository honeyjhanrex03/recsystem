<?php
require_once 'includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair', 'admin', 'rec_member', 'rec_secretary', 'author']);
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$protocol_id = $_GET['id'] ?? ($_GET['protocol_id'] ?? null);
if (!$protocol_id) {
    // No ID supplied – send user back to the protocol list with a friendly message
    header('Location: index?msg=missing_id');
    exit();
}

// Fetch protocol details
$stmt = $pdo->prepare("SELECT p.*, u.name as staff_name
                        FROM protocols p
                        LEFT JOIN admins u ON p.created_by = u.admin_id
                        WHERE p.protocol_id = ?");
$stmt->execute([$protocol_id]);
$p = $stmt->fetch();
$protocol = $p; 
if (!$p) {
    die("Protocol not found (invalid ID).");
}

// Fetch ALL protocol files (Initial + Revisions)
$stmtF = $pdo->prepare("SELECT * FROM protocol_files WHERE protocol_id = ? ORDER BY uploaded_at DESC");
$stmtF->execute([$protocol_id]);
$allFiles = $stmtF->fetchAll();

// Fetch Form 15 Responses (Resubmission Point-by-Point)
$stmt15 = $pdo->prepare("SELECT * FROM form15_responses WHERE protocol_id = ? ORDER BY id ASC");
$stmt15->execute([$protocol_id]);
$form15Responses = $stmt15->fetchAll();

// SECURITY: If author, must own the protocol
if ($user_role === 'author' && $p['created_by'] != $user_id) {
    die("Unauthorized access to this protocol drossier.");
}

// Fetch decision
$stmtD = $pdo->prepare("SELECT fd.*, u.name as chair_name FROM final_decisions fd LEFT JOIN admins u ON fd.chair_id = u.admin_id WHERE fd.protocol_id = ?");
$stmtD->execute([$protocol_id]);
$decision = $stmtD->fetch();

// Fetch reviewer assignments with status
$stmtR = $pdo->prepare("SELECT a.reviewer_id, u.name as reviewer_name, u.role, a.status, a.deadline
                         FROM reviewer_assignments a
                         JOIN admins u ON a.reviewer_id = u.admin_id
                         WHERE a.protocol_id = ?
                         ORDER BY (CASE WHEN a.status = 'completed' THEN 0 ELSE 1 END) ASC, a.assigned_at ASC");
$stmtR->execute([$protocol_id]);
$reviewers = $stmtR->fetchAll();

// Fetch member supplemental files
$stmtMF = $pdo->prepare("SELECT mf.*, u.name as reviewer_name
                          FROM member_files mf
                          JOIN admins u ON mf.reviewer_id = u.admin_id
                          WHERE mf.protocol_id = ?");
$stmtMF->execute([$protocol_id]);
$memberFiles = $stmtMF->fetchAll();

// If the user role is author (Committee), obscure the actual names of the reviewers
if ($user_role === 'author') {
    $reviewerMap = [];
    $rCounter = 1;
    // Remap assigned reviewers
    foreach ($reviewers as &$r) {
        $realName = $r['reviewer_name'];
        if (!isset($reviewerMap[$realName])) {
            $reviewerMap[$realName] = "Reviewer " . $rCounter++;
        }
        $r['reviewer_name'] = $reviewerMap[$realName];
        $r['role'] = 'Peer Reviewer'; // Obscure specific roles (like secretary) to ensure complete blinding
    }
    unset($r); // Fix dangling reference
    // Remap member file uploaders
    foreach ($memberFiles as &$mf) {
        $realName = $mf['reviewer_name'];
        if (isset($reviewerMap[$realName])) {
            $mf['reviewer_name'] = $reviewerMap[$realName];
        } else {
            $reviewerMap[$realName] = "Reviewer " . $rCounter++;
            $mf['reviewer_name'] = $reviewerMap[$realName];
        }
    }
    unset($mf); // Fix dangling reference

    // ── ON-THE-FLY ANONYMIZATION OF CONSOLIDATED TEXT ──
    // This handles legacy data that might still have real names in the recommendations field
    if (!empty($p['recommendations'])) {
        foreach ($reviewerMap as $realName => $anonName) {
            $escapedName = preg_quote($realName, '/');
            // Match "- Name" or "[Name]" formats
            $p['recommendations'] = preg_replace("/- {$escapedName}/", "- {$anonName}", $p['recommendations']);
            $p['recommendations'] = preg_replace("/\[{$escapedName}\]/", "[{$anonName}]", $p['recommendations']);
        }
    }
}

include 'includes/header.php';
?>

<div id="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Record Dossier: " . htmlspecialchars($p['rec_code']);
        $workspaceSubtitle = "Comprehensive Submission Data & Workflow Status";
        include 'includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">

            <!-- Header Row -->
            <div class="row mb-4 align-items-center animate-up">
                <div class="col">
                    <button onclick="window.history.back()"
                        class="btn btn-link text-navy p-0 mb-3 text-decoration-none small fw-bold">
                        <i class="fas fa-arrow-left me-2"></i> Return to Registry
                    </button>
                    <h2 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($p['rec_code']); ?></h2>
                    <p class="text-muted mb-0 mt-1"><?php echo htmlspecialchars($p['title']); ?></p>
                </div>
                <?php echo "<!-- ROLE: " . ($_SESSION['role'] ?? 'none') . " -->"; ?>
<?php if (isset($_SESSION['role']) && (in_array($_SESSION['role'], ['rec_chair', 'admin', 'staff', 'member', 'rec_secretary']))): ?>
    <a href="forms/generate_print?id=<?php echo $p['protocol_id']; ?>" class="btn btn-navy shadow-sm" style="margin-left:10px;">View Evaluation</a>
<?php endif; ?>
            </div>

            <!-- ── Workflow Tracker ── -->
            <?php include 'includes/workflow_tracker.php'; ?>

            <!-- ── Main Content ── -->
            <div class="row g-4 mt-2">

                <!-- Left Column: Protocol details + files -->
                <div class="col-lg-8 animate-up" style="animation-delay:0.1s">

                    <!-- Protocol Info -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5">
                            <h6 class="text-gold fw-bold text-uppercase small mb-3" style="letter-spacing:2px;">Research
                                Title</h6>
                            <h3 class="fw-bold text-navy" style="line-height:1.4;">
                                <?php echo htmlspecialchars($p['title']); ?></h3>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light border-start border-4 border-navy">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">Principal Investigator
                                        </div>
                                        <div class="fw-bold text-navy">
                                            <?php echo htmlspecialchars($p['project_leader']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">Affiliated Institution
                                        </div>
                                        <div class="fw-bold text-navy">
                                            <?php echo htmlspecialchars($p['institution']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">Review Framework</div>
                                        <div class="fw-bold text-navy text-uppercase">
                                            <?php 
                                            $rtMap = ['pending'=>'Pending Review Type', 'exempt'=>'Exempted from Review', 'expedited'=>'Expedited Review', 'full_board'=>'Full Review'];
                                            echo mb_strtoupper($rtMap[$p['review_type']] ?? str_replace('_', ' ', $p['review_type'])); 
                                            ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-light">
                                        <div class="text-muted small text-uppercase fw-bold mb-1">Submitted By</div>
                                        <div class="fw-bold text-navy"><?php echo htmlspecialchars($p['staff_name']); ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo date('F d, Y', strtotime($p['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Board Recommendations (Visible to Author during revision phase or to Staff/Chair) -->
                    <?php if (!empty($p['recommendations'])): ?>
                        <div class="card border-0 shadow-sm mb-4 border-start border-4 border-warning animate-up" style="animation-delay: 0.05s;">
                            <div class="card-header bg-white border-0 py-4 px-4 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-comment-medical me-2 text-warning"></i> Board Recommendations & Comments</h5>
                                    <p class="text-muted small mb-0 mt-1">Consolidated feedback from the Ethics Committee</p>
                                </div>
                                <span class="badge bg-warning-light text-warning rounded-pill px-3 py-2 fw-bold">ACTION REQUIRED</span>
                            </div>
                            <div class="card-body p-4 p-md-5 bg-white">
                                <div class="p-4 rounded-4 bg-light border border-warning-subtle" style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; line-height: 1.6; font-size: 0.95rem; color: #1e293b;"><?php echo htmlspecialchars($p['recommendations']); ?></div>
                                
                                <?php if ($user_role === 'author' && $p['status'] === 'needs_revision'): ?>
                                    <div class="mt-4 p-3 rounded-4 bg-navy-light text-navy d-flex align-items-center gap-3">
                                        <i class="fas fa-info-circle fa-lg"></i>
                                        <div class="small">
                                            Please address the points above in your resubmission. You are required to submit a <strong>Point-by-Point Response (Form 15)</strong> along with your revised documents.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Protocol Documents History -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-4 px-4 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-folder-open me-2 text-warning"></i> Submission Files</h5>
                            <span class="badge bg-light text-navy border rounded-pill px-3"><?php echo count($allFiles); ?> Files</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (count($allFiles) > 0): ?>
                                    <?php foreach ($allFiles as $idx => $f): ?>
                                        <?php 
                                        $isRevised = (strpos($f['file_path'], 'REC_REV_') !== false);
                                        $isLatest = ($idx === 0);
                                        ?>
                                        <div class="list-group-item p-4 <?php echo $isLatest ? 'bg-light-info' : ''; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-box <?php echo $isRevised ? 'bg-info' : 'bg-navy'; ?> text-white me-3 shadow-sm"
                                                    style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas <?php echo $isRevised ? 'fa-file-signature' : 'fa-file-pdf'; ?> fa-lg"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <h6 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($f['file_name']); ?></h6>
                                                        <span class="badge bg-soft-navy text-navy rounded-pill x-small border fw-bold px-2 py-1"><?php echo strtoupper($f['document_type'] ?? 'OTHER'); ?></span>
                                                        <?php if ($isRevised): ?>
                                                            <span class="badge bg-info rounded-pill x-small">REVISED</span>
                                                        <?php endif; ?>
                                                        <?php if ($idx === 0): ?>
                                                            <span class="badge bg-success rounded-pill x-small">LATEST</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small mt-1">
                                                        Uploaded on <?php echo date('M d, Y h:i A', strtotime($f['uploaded_at'])); ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-navy btn-sm px-3 rounded-pill fw-bold" onclick="viewPDF('uploads/protocols/<?php echo htmlspecialchars(addslashes($f['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($f['file_name'])); ?>')">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </button>
                                                    <a href="uploads/protocols/<?php echo htmlspecialchars($f['file_path']); ?>"
                                                        download class="btn btn-navy btn-sm px-3 rounded-pill">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-5 text-center text-muted">
                                        <i class="fas fa-ghost fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">No documents found for this protocol.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Resubmission Response (Form 15) Section -->
                    <?php if (count($form15Responses) > 0): ?>
                        <div class="card border-0 shadow-sm mb-4 border-start border-4 border-info">
                            <div class="card-header bg-white border-0 py-4 px-4 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-reply-all me-2 text-info"></i> Resubmission Response (REC Form 15)</h5>
                                    <p class="text-muted small mb-0 mt-1">Researcher's point-by-point response to board recommendations</p>
                                </div>
                                <a href="forms/form15_resubmission_form?id=<?php echo $protocol_id; ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3">
                                    <i class="fas fa-print me-1"></i> Print F15
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4" style="width: 30%;">REC Recommendation</th>
                                                <th style="width: 45%;">Committee's Response</th>
                                                <th class="text-center" style="width: 15%;">Page Ref.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($form15Responses as $res): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="small fw-bold text-navy bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($res['rec_recommendation'])); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="small text-dark italic p-2"><?php echo nl2br(htmlspecialchars($res['author_response'])); ?></div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-navy-light text-navy">Page <?php echo htmlspecialchars($res['page_reference']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- REC Member Supplemental Files -->
                    <?php if (count($memberFiles) > 0): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 py-3 px-4 border-bottom">
                                <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-paperclip me-2 text-primary"></i>
                                    Reviewer Supplemental Files</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($memberFiles as $mf): ?>
                                        <div class="list-group-item d-flex align-items-center py-3 px-4">
                                            <i class="fas fa-file-alt text-primary me-3 fa-lg"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?php echo htmlspecialchars($mf['file_name']); ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.75rem;">Uploaded by
                                                    <?php echo htmlspecialchars($mf['reviewer_name']); ?></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light border px-3" onclick="viewPDF('uploads/protocols/<?php echo htmlspecialchars(addslashes($mf['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($mf['file_name'])); ?>')">
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Reviewer Panel -->
                    <?php if (count($reviewers) > 0): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3 px-4 border-bottom">
                                <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-users me-2"></i> Assigned Reviewers</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($reviewers as $r): ?>
                                        <div
                                            class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['reviewer_name']); ?>&background=1a2b4b&color=fff&size=36"
                                                    class="rounded-circle" width="36" height="36">
                                                <div>
                                                    <div class="fw-bold small text-navy">
                                                        <?php echo htmlspecialchars($r['reviewer_name']); ?></div>
                                                    <small class="text-muted text-capitalize"><?php echo $r['role']; ?> &bull;
                                                        Deadline:
                                                        <?php echo date('M d, Y', strtotime($r['deadline'])); ?></small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($r['status'] === 'completed'): ?>
                                                    <?php if (isset($_SESSION['user_id']) && $r['reviewer_id'] == $_SESSION['user_id']): ?>
                                                        <a href="forms/generate_print?id=<?php echo $protocol_id; ?>&reviewer_id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                                            <i class="fas fa-file-check me-1"></i> View My Review
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-light text-success"><i
                                                                class="fas fa-check-circle me-1"></i> Completed</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (isset($_SESSION['user_id']) && $r['reviewer_id'] == $_SESSION['user_id']): ?>
                                                        <a href="rec_member/review?id=<?php echo $protocol_id; ?>" class="btn btn-navy btn-sm rounded-pill px-3 shadow-sm">
                                                            <i class="fas fa-edit me-1"></i> Review
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-light text-warning"><i class="fas fa-clock me-1"></i>
                                                            Pending</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Evaluation Summary -->
                    <!-- Premium Evaluation Summary (Exclusive to REC Chair) -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'rec_chair'): ?>
                        <div class="mt-5 mb-4 d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold text-navy mb-1"><i class="fas fa-microscope text-warning me-2"></i> Evaluation Progress</h4>
                                <p class="text-muted small mb-0">Reviewer submissions and ethical assessment status</p>
                            </div>
                            <span class="badge bg-light text-navy border rounded-pill px-3 py-2">
                                <i class="fas fa-users me-2"></i><?php echo count($reviewers); ?> Reviewers
                            </span>
                        </div>
                        
                        <div class="row g-4">
                            <?php foreach ($reviewers as $r): ?>
                                <?php 
                                $isDone = ($r['status'] === 'completed'); 
                                $statusClass = $isDone ? 'success' : 'warning';
                                $roleClass = ($r['role'] === 'rec_secretary') ? 'info' : 'secondary';
                                ?>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 20px;">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light text-navy rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px; height:45px; font-weight:bold;">
                                                        <?php 
                                                            $names = explode(' ', $r['reviewer_name']);
                                                            echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($r['reviewer_name']); ?></h6>
                                                        <span class="badge bg-<?php echo $roleClass; ?>-light text-<?php echo $roleClass; ?> x-small rounded-pill text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                                            <?php echo htmlspecialchars($r['role']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="badge bg-<?php echo $statusClass; ?>-light text-<?php echo $statusClass; ?> rounded-pill px-3">
                                                    <i class="fas <?php echo $isDone ? 'fa-check-circle' : 'fa-clock fa-spin'; ?> me-1"></i>
                                                    <?php echo ucfirst(htmlspecialchars($r['status'])); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="bg-light rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Target Deadline</span>
                                                <span class="fw-bold text-navy small"><?php echo date('M d, Y', strtotime($r['deadline'])); ?></span>
                                            </div>
                                            
                                            <a href="forms/generate_print?id=<?php echo $protocol_id; ?>&reviewer_id=<?php echo $r['reviewer_id'] ?? ''; ?>" 
                                               class="btn <?php echo $isDone ? 'btn-navy' : 'btn-outline-secondary disabled'; ?> w-100 rounded-pill py-2 small fw-bold">
                                                <i class="fas fa-file-alt me-2"></i> <?php echo $isDone ? 'Analyze Full Evaluation' : 'Awaiting Submission'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Decision Panel -->
                <div class="col-lg-4 animate-up" style="animation-delay:0.2s">
                    <?php if ($decision): ?>
                        <div class="card border-0 shadow-sm bg-navy text-white overflow-hidden mb-4">
                            <div class="p-4">
                                <h6 class="text-gold fw-bold text-uppercase small mb-3" style="letter-spacing:2px;">Board
                                    Decision</h6>
                                <?php
                                $decIcons = [
                                    'Approved' => 'fa-thumbs-up text-success',
                                    'Minor Revision' => 'fa-pen text-warning',
                                    'Major Revision' => 'fa-exclamation-triangle text-danger',
                                    'Disapproved' => 'fa-thumbs-down text-danger',
                                ];
                                $decIcon = $decIcons[$decision['final_decision']] ?? 'fa-gavel';
                                ?>
                                <div class="display-6 fw-bold mb-3">
                                    <i class="fas <?php echo $decIcon; ?> me-2 small"></i>
                                    <?php echo htmlspecialchars($decision['final_decision']); ?>
                                </div>
                                <div class="mb-4">
                                    <h6 class="small opacity-50 text-uppercase fw-bold mb-2">Remarks</h6>
                                    <p class="mb-0" style="line-height:1.8; opacity:0.85;">
                                        <?php echo nl2br(htmlspecialchars($decision['remarks'])); ?>
                                    </p>
                                </div>
                                <div class="border-top border-light opacity-50 pt-3 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Signed By</span>
                                        <span
                                            class="fw-bold"><?php echo htmlspecialchars($decision['chair_name']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Date</span>
                                        <span><?php echo date('F d, Y', strtotime($decision['decision_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (in_array($p['status'], ['completed', 'needs_revision', 'approved', 'clearance_released'])): ?>
                            <a href="forms/generate_print?id=<?php echo $p['protocol_id']; ?>"
                                class="btn btn-navy w-100 py-3 fw-bold shadow-sm rounded-pill mt-3">
                                <i class="fas fa-print me-2"></i> Download Official REC Forms
                            </a>
                        <?php endif; ?>

                    <?php else: ?>
                        <div
                            class="card border-0 shadow-sm p-4 text-center bg-light h-auto d-flex flex-column justify-content-center">
                            <div class="mx-auto mb-4"
                                style="width:80px;height:80px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-microscope fa-2x text-navy opacity-25"></i>
                            </div>
                            <h5 class="fw-bold text-navy mb-2">
                                <?php if (in_array($p['status'], ['submitted', 'staff_review'])): ?>
                                    Awaiting Staff Screening
                                <?php elseif (in_array($p['status'], ['initial_review', 'confirmed'])): ?>
                                    Awaiting Reviewer Assignment
                                <?php elseif ($p['status'] === 'assigned'): ?>
                                    Waiting for Reviewers to Start
                                <?php elseif ($p['status'] === 'under_review'): ?>
                                    Under Ethical Review
                                <?php else: ?>
                                    Awaiting Board Decision
                                <?php endif; ?>
                            </h5>
                            <p class="text-muted small mb-0">
                                <?php if (in_array($p['status'], ['submitted', 'staff_review'])): ?>
                                    REC Staff is currently screening the documents for completeness.
                                <?php elseif (in_array($p['status'], ['initial_review', 'confirmed'])): ?>
                                    The REC Chair is reviewing the panel composition for this protocol.
                                <?php elseif ($p['status'] === 'assigned'): ?>
                                    Reviewers have been notified. Evaluations are expected to start soon.
                                <?php elseif ($p['status'] === 'under_review'): ?>
                                    REC members are currently evaluating the scientific and ethical aspects.
                                <?php else: ?>
                                    <?php if ($totalReviewers > 0 && $doneReviewers >= $totalReviewers): ?>
                                        All reviewers have submitted. Awaiting REC Chair's final decision.
                                    <?php else: ?>
                                        Evaluations in progress. Awaiting board consolidation.
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

<script>
function viewPDF(filepath, filename) {
    document.getElementById('pdfViewerTitle').innerHTML = '<i class="fas fa-file-pdf me-2 text-danger"></i> ' + filename;
    document.getElementById('pdfIframe').src = filepath;
    document.getElementById('modalDownloadBtn').href = filepath;
    var pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    pdfModal.show();
}

// Clear iframe src when modal is hidden to prevent caching/memory issues
document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('pdfIframe').src = '';
});
</script>

<?php include 'includes/footer.php'; ?>
