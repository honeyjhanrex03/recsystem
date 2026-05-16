<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_member', 'rec_chair', 'rec_secretary', 'rec_staff']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) { header("Location: protocols"); exit(); }

$stmtCheck = $pdo->prepare("SELECT status, deadline FROM reviewer_assignments WHERE protocol_id = ? AND reviewer_id = ?");
$stmtCheck->execute([$protocol_id, $_SESSION['user_id']]);
$assignment = $stmtCheck->fetch();
if (!$assignment || $assignment['status'] == 'completed') { header("Location: protocols"); exit(); }

$stmtP = $pdo->prepare("SELECT p.*, f.file_path, f.file_name FROM protocols p LEFT JOIN protocol_files f ON p.protocol_id = f.protocol_id WHERE p.protocol_id = ?");
$stmtP->execute([$protocol_id]);
$protocol = $stmtP->fetch();

// ── EXACT Form 10 Questions ───────────────────────────────────────────────
$questions10 = [
    ['q' => 'Does the study have social value?',
     'hint' => '(e.g. scientific value, relevance to national/community needs)'],
    ['q' => 'Is the study background adequate?'],
    ['q' => 'Are the research questions supported by the Review of Literature?'],
    ['q' => 'Are the study objectives Specific, Measurable, Attainable, Realistic, Time-bound?'],
    ['q' => 'Is the research design appropriate?',
     'sub' => [
        'Is the population identified and defined?',
        'Is the selection of study participants described?',
        'Is the sample size justified?',
        'Is the plan for data analysis described?',
        'Are there dummy tables?',
     ]],
    ['q' => 'Does the research need to be carried out with human participants?'],
    ['q' => 'Does the study have a vulnerability issue?'],
    ['q' => 'Are appropriate mechanisms/interventions in place to address the vulnerability issue/s?'],
    ['q' => 'Are there risks/probable harms to the human participants in the study?'],
    ['q' => 'Are there measures to mitigate the risks?'],
    ['q' => 'Is the informed consent procedure/form adequate and culturally appropriate?'],
    ['q' => 'Is/are the investigator/s adequately trained and do they have sufficient experience to undertake the study?'],
    ['q' => 'Is there a disclosure of conflict of interest?'],
    ['q' => 'Are the research facilities adequate?'],
    ['q' => 'Does the protocol include a plan for dissemination of results to relevant stakeholders (e.g., institution, participants, community, policy makers), while ensuring confidentiality and ethical reporting?'],
    ['q' => 'Are there any other concerns in the study?'],
];

// ── EXACT Form 12 Questions ───────────────────────────────────────────────
$questions12 = [
    ['q' => 'Is it necessary to seek the informed consent of the participants?',
     'options' => ['Unable to Assess', 'Yes', 'No'],
     'if_no' => true],
    ['q' => 'If YES, are the participants provided with sufficient information regarding:',
     'sub' => [
        ['label' => 'Purpose of the study?',                                                                         'opts' => ['Yes','No']],
        ['label' => 'Expected duration of participation?',                                                           'opts' => ['Yes','No']],
        ['label' => 'Procedures to be carried out?',                                                                 'opts' => ['Yes','No']],
        ['label' => 'Discomforts and inconveniences?',                                                               'opts' => ['Yes','No']],
        ['label' => 'Risks (including possible discrimination)?',                                                    'opts' => ['Yes','No']],
        ['label' => 'Random assignment to the trial treatments?',                                                    'opts' => ['Not applicable','Yes','No']],
        ['label' => 'Benefits to the participants?',                                                                 'opts' => ['Yes','No']],
        ['label' => 'Alternative treatments/procedures?',                                                            'opts' => ['Not applicable','Yes','No']],
        ['label' => 'Compensation and/or medical treatments in case of injury?',                                     'opts' => ['Yes','No']],
        ['label' => 'Who to contact for pertinent questions and/or for assistance in a research-related injury?',    'opts' => ['Yes','No']],
        ['label' => 'Refusal to participate or discontinuance at any time will involve penalty or loss of benefits to which the subject is entitled?', 'opts' => ['Yes','No']],
        ['label' => 'Extent of confidentiality?',                                                                   'opts' => ['Yes','No']],
     ]],
    ['q' => 'Is the informed consent written or presented in simple language that participants can understand?', 'options' => ['Yes','No']],
    ['q' => 'Does the protocol include an adequate process for ensuring that consent is voluntary?',             'options' => ['Yes','No']],
    ['q' => 'Do you have any other concerns?', 'options' => ['Yes','No']],
];

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    try {
        $pdo->beginTransaction();

        // ── Save Form 10 answers ─────────────────────────────────────────
        $stmt10 = $pdo->prepare("INSERT INTO form10_answers (protocol_id, reviewer_id, question, answer, comment) VALUES (?, ?, ?, ?, ?)");
        foreach ($questions10 as $i => $qdata) {
            $ans     = $_POST['f10_ans'][$i]     ?? '';
            $comment = $_POST['f10_comment'][$i] ?? '';
            $stmt10->execute([$protocol_id, $_SESSION['user_id'], $qdata['q'], $ans, $comment]);

            // Sub-questions (Q5)
            if (!empty($qdata['sub'])) {
                foreach ($qdata['sub'] as $si => $sq) {
                    $sans = $_POST['f10_sub'][$i][$si] ?? '';
                    $stmt10->execute([$protocol_id, $_SESSION['user_id'], 'SUB|'.$qdata['q'].'|'.$sq, $sans, '']);
                }
            }
        }

        // Form 10 Recommendation
        $f10rec   = $_POST['f10_rec']   ?? '';
        $f10notes = trim($_POST['f10_rec_notes'] ?? '');
        if ($f10rec) {
            $pdo->prepare("INSERT INTO reviewer_recommendations (protocol_id, reviewer_id, form_type, recommendation, notes) VALUES (?,?,10,?,?)")
                ->execute([$protocol_id, $_SESSION['user_id'], $f10rec, $f10notes]);
        }

        // ── Save Form 12 answers ─────────────────────────────────────────
        $stmt12 = $pdo->prepare("INSERT INTO form12_answers (protocol_id, reviewer_id, question, answer, comment) VALUES (?, ?, ?, ?, ?)");
        foreach ($questions12 as $i => $qdata) {
            $ans     = $_POST['f12_ans'][$i]     ?? '';
            $comment = $_POST['f12_comment'][$i] ?? '';
            $stmt12->execute([$protocol_id, $_SESSION['user_id'], $qdata['q'], $ans, $comment]);

            if (!empty($qdata['sub'])) {
                foreach ($qdata['sub'] as $si => $sq) {
                    $sans = $_POST['f12_sub'][$i][$si] ?? '';
                    $stmt12->execute([$protocol_id, $_SESSION['user_id'], 'SUB|'.$qdata['q'].'|'.$sq['label'], $sans, '']);
                }
            }
            // "If NO" explanation
            if (!empty($qdata['if_no']) && isset($_POST['f12_if_no'])) {
                $stmt12->execute([$protocol_id, $_SESSION['user_id'], 'IFNO|'.$qdata['q'], $_POST['f12_if_no'], '']);
            }
        }

        // Form 12 Recommendation
        $f12rec   = $_POST['f12_rec']   ?? '';
        $f12notes = trim($_POST['f12_rec_notes'] ?? '');
        if ($f12rec) {
            $pdo->prepare("INSERT INTO reviewer_recommendations (protocol_id, reviewer_id, form_type, recommendation, notes) VALUES (?,?,12,?,?)")
                ->execute([$protocol_id, $_SESSION['user_id'], $f12rec, $f12notes]);
        }

        // ── Optional member file upload ───────────────────────────────────
        if (isset($_FILES['member_file']) && $_FILES['member_file']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['member_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf','doc','docx']) && $_FILES['member_file']['size'] <= 10*1024*1024) {
                $newFn = "MBR_".$_SESSION['user_id']."_".time().".".$ext;
                if (move_uploaded_file($_FILES['member_file']['tmp_name'], "../uploads/protocols/".$newFn)) {
                    $pdo->prepare("INSERT INTO member_files (protocol_id, reviewer_id, file_name, file_path) VALUES (?,?,?,?)")
                        ->execute([$protocol_id, $_SESSION['user_id'], $_FILES['member_file']['name'], $newFn]);
                }
            }
        }

        // ── Mark assignment done ─────────────────────────────────────────
        $pdo->prepare("UPDATE reviewer_assignments SET status='completed' WHERE protocol_id=? AND reviewer_id=?")
            ->execute([$protocol_id, $_SESSION['user_id']]);

        // Audit
        $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?,?,?)")
            ->execute([$_SESSION['user_id'], $protocol_id, "Submitted Form 10 & 12 review for ".$protocol['rec_code']]);

        // ── Notify Chair and Staff ─────────────────────────────────────────
        require_once '../includes/notifications_helper.php';
        $stmtCS = $pdo->prepare("SELECT admin_id FROM admins WHERE role IN ('rec_chair', 'rec_staff') AND status = 'active'");
        $stmtCS->execute();
        $adminToNotify = $stmtCS->fetchAll();

        // Promote to for_decision if all done
        $pending = $pdo->prepare("SELECT COUNT(*) FROM reviewer_assignments WHERE protocol_id=? AND status='pending'");
        $pending->execute([$protocol_id]);
        $remaining = $pending->fetchColumn();

        foreach ($adminToNotify as $adm) {
            $msg = "Reviewer {$_SESSION['name']} has submitted an evaluation for protocol: \"{$protocol['title']}\".";
            if ($remaining == 0) {
                $msg = "ALL reviews have been submitted for protocol: \"{$protocol['title']}\". Ready for decision.";
            }
            notifyUser($pdo, $adm['admin_id'], 'admin', ($remaining == 0 ? 'All Reviews Complete' : 'Review Submitted'), 
                $msg, 
                "rec_chair/decision.php?id=" . $protocol_id);
        }

        if ($remaining == 0) {
            // ── AUTOMATIC CONSOLIDATION & REVISION TRANSITION ────────────────
            // Aggregating comments from all reviewers as requested by the user
            $consolidated = "";
            
            // Create anonymized mapping
            $stmtMap = $pdo->prepare("SELECT reviewer_id FROM reviewer_assignments WHERE protocol_id = ? ORDER BY assigned_at ASC");
            $stmtMap->execute([$protocol_id]);
            $reviewerMap = [];
            $counter = 1;
            foreach ($stmtMap->fetchAll() as $mapRow) {
                $reviewerMap[$mapRow['reviewer_id']] = "Reviewer " . $counter++;
            }
            
            // 1. Fetch Form 10
            $stmt10 = $pdo->prepare("SELECT q.reviewer_id, q.question, q.answer, q.comment FROM form10_answers q WHERE q.protocol_id = ? AND (q.answer != 'Yes' OR (q.comment IS NOT NULL AND q.comment != '')) ORDER BY q.reviewer_id, q.answer_id");
            $stmt10->execute([$protocol_id]);
            $f10 = $stmt10->fetchAll();
            if ($f10) {
                $consolidated .= "BOARD REVIEWER COMMENTS (Form 10):\n";
                foreach ($f10 as $row) {
                    $q = $row['question'];
                    $anonName = $reviewerMap[$row['reviewer_id']] ?? "Reviewer";
                    if (strpos($q, 'SUB|') === 0) { $parts = explode('|', $q); $q = "Criterion: " . ($parts[2] ?? $q); }
                    $consolidated .= "- [{$anonName}] Question: {$q}\n";
                    if ($row['answer'] != 'Yes') $consolidated .= "  [Status] {$row['answer']}\n";
                    if ($row['comment']) $consolidated .= "  [Reviewer Note] " . trim($row['comment']) . "\n";
                    $consolidated .= "\n";
                }
            }

            // 2. Fetch Form 12
            $stmt12 = $pdo->prepare("SELECT q.reviewer_id, q.question, q.answer, q.comment FROM form12_answers q WHERE q.protocol_id = ? AND (q.answer != 'Yes' OR (q.comment IS NOT NULL AND q.comment != '')) ORDER BY q.reviewer_id, q.answer_id");
            $stmt12->execute([$protocol_id]);
            $f12 = $stmt12->fetchAll();
            if ($f12) {
                $consolidated .= "\nCONSENT CHECKLIST CONCERNS (Form 12):\n";
                foreach ($f12 as $row) {
                    $q = $row['question'];
                    $anonName = $reviewerMap[$row['reviewer_id']] ?? "Reviewer";
                    if (strpos($q, 'SUB|') === 0) { $parts = explode('|', $q); $q = "Check: " . ($parts[2] ?? $q); }
                    $consolidated .= "- [{$anonName}] Check: {$q}\n";
                    if ($row['answer'] != 'Yes' && $row['answer'] != '') $consolidated .= "  [Status] {$row['answer']}\n";
                    if ($row['comment']) $consolidated .= "  [Reviewer Note] " . trim($row['comment']) . "\n";
                    $consolidated .= "\n";
                }
            }

            // 3. Recommendations
            $stmtRecOverall = $pdo->prepare("SELECT r.reviewer_id, r.recommendation, r.notes, r.form_type FROM reviewer_recommendations r WHERE r.protocol_id = ?");
            $stmtRecOverall->execute([$protocol_id]);
            $recs = $stmtRecOverall->fetchAll();
            if ($recs) {
                $consolidated .= "\nSUMMARY OF REVIEWER DECISIONS:\n";
                $groupedRecs = [];
                foreach ($recs as $row) {
                    $rid = $row['reviewer_id'];
                    if (!isset($groupedRecs[$rid])) $groupedRecs[$rid] = [];
                    $groupedRecs[$rid][] = $row;
                }
                foreach ($groupedRecs as $rid => $rows) {
                    $anonName = $reviewerMap[$rid] ?? "Reviewer";
                    $uniqueDecisions = array_unique(array_column($rows, 'recommendation'));
                    if (count($uniqueDecisions) === 1) {
                        $consolidated .= "- {$anonName}: " . $uniqueDecisions[0] . "\n";
                        $notes = array_filter(array_unique(array_map('trim', array_column($rows, 'notes'))));
                        if ($notes) $consolidated .= "  Notes: " . implode("; ", $notes) . "\n";
                    } else {
                        foreach ($rows as $row) {
                            $form = ($row['form_type'] == 10) ? 'Protocol' : 'Consent';
                            $consolidated .= "- {$anonName} ({$form}): {$row['recommendation']}\n";
                            if ($row['notes']) $consolidated .= "  Note: " . trim($row['notes']) . "\n";
                        }
                    }
                }
            }

            if (empty($consolidated)) {
                $consolidated = "All reviewers have completed their assessment. Please review the submitted forms and provide any necessary revisions.";
            }

            // Update status to needs_revision and save consolidated comments
            $pdo->prepare("UPDATE protocols SET status='needs_revision', recommendations=? WHERE protocol_id=?")
                ->execute([trim($consolidated), $protocol_id]);

            // Notify Author
            notifyUser($pdo, $protocol['created_by'], 'author', 'Revision Required (Automatic)', 
                "All board reviews for your protocol \"{$protocol['title']}\" are complete. A mandatory revision cycle has been triggered. Please check the 'Needs Revision' status on your dashboard.", 
                "shared_view?id=" . $protocol_id);
                
            // Log this automatic action
            $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (NULL, ?, 'System: Automatically consolidated reviews and returned for revision')")
                ->execute([$protocol_id]);
        }

        $pdo->commit();
        $success = "Your review for {$protocol['rec_code']} has been submitted!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Ethics Protocol Review";
        $workspaceSubtitle = "REC Form 10 & 12 Specialized Evaluation Interface";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <a href="protocols" class="btn btn-link text-navy p-0 mb-2 text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i> Back to My Protocols
            </a>
            <h2 class="fw-bold text-navy">Review Worksheet</h2>
            <p class="text-muted mb-4">
                Protocol: <strong class="text-primary"><?php echo htmlspecialchars($protocol['rec_code']); ?></strong>
                — <?php echo htmlspecialchars($protocol['title']); ?>
            </p>

            <!-- Workflow tracker -->
            <?php include '../includes/workflow_tracker.php'; ?>

            <div class="row mt-4">
                <!-- PDF viewer -->
                <div class="col-lg-5 mb-4 position-sticky align-self-start" style="top: 20px;">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                        <div class="card-header bg-navy text-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-pdf me-2"></i> Protocol Document</span>
                            <button type="button" class="btn btn-sm btn-light py-0 px-2 rounded-3 fw-bold" style="font-size: 0.7rem;" onclick="viewPDF('../uploads/protocols/<?php echo htmlspecialchars(addslashes($protocol['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($protocol['file_name'])); ?>')">
                                <i class="fas fa-expand me-1"></i> Maximize
                            </button>
                        </div>
                        <div style="height:800px;">
                            <?php if ($protocol['file_path']): ?>
                                <iframe src="../uploads/protocols/<?php echo htmlspecialchars($protocol['file_path']); ?>#navpanes=0"
                                    width="100%" height="100%" frameborder="0"></iframe>
                            <?php else: ?>
                                <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                    <p>No document attached.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Supplemental file upload -->
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <h6 class="fw-bold text-navy mb-1">
                            <i class="fas fa-paperclip me-2 text-primary"></i>
                            Upload Supplemental File <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">Optional</span>
                        </h6>
                        <small class="text-muted d-block mb-2">PDF / DOC / DOCX · max 10MB</small>
                        <label for="memberFileInput" class="d-block border border-dashed rounded-3 p-3 text-center text-muted" style="cursor:pointer;">
                            <i class="fas fa-cloud-arrow-up fa-2x mb-1 opacity-25 d-block"></i>
                            <small>Click to browse</small>
                            <div id="fileLabel" class="small text-primary mt-1"></div>
                        </label>
                    </div>
                </div>

                <!-- Review Forms -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-primary-light text-primary position-relative">
                        <div class="d-flex align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><i class="fas fa-info-circle me-2"></i> Review Instructions</h6>
                            <?php 
                            $deadline_ts = strtotime($assignment['deadline']);
                            $days_rem = ceil(($deadline_ts - time()) / 86400);
                            if ($days_rem <= 3 && $days_rem >= 0) {
                                echo '<span class="badge bg-danger ms-auto pulse-red animate-up shadow-sm small"><i class="fas fa-clock me-1"></i> URGENT: DUE IN ' . $days_rem . ' DAYS</span>';
                            } elseif ($days_rem < 0) {
                                echo '<span class="badge bg-dark ms-auto animate-up shadow-sm small"><i class="fas fa-exclamation-triangle me-1"></i> OVERDUE</span>';
                            } else {
                                echo '<span class="badge bg-primary ms-auto animate-up shadow-sm small">' . $days_rem . ' Days Remaining</span>';
                            }
                            ?>
                        </div>
                        <ul class="small mb-0 ps-3">
                            <li>Thoroughly examine the protocol PDF on the left.</li>
                            <li>Answer all mandatory questions in Form 10 and Form 12.</li>
                            <li>Provide specific comments for any 'No' or 'Unable to Assess' responses.</li>
                            <li>Upload any supplemental notes or annotated manuscripts if necessary.</li>
                            <li class="fw-bold mt-2">Submission Deadline: <?php echo date('F d, Y', $deadline_ts); ?></li>
                        </ul>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" id="reviewForm">
                        <input type="file" name="member_file" id="memberFileInput" class="d-none" accept=".pdf,.doc,.docx">

                        <!-- Progress bar -->
                        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                            <div class="d-flex justify-content-between small fw-bold text-navy mb-1">
                                <span>Review Progress</span>
                                <span id="progressLabel">0 answered</span>
                            </div>
                            <div class="progress" style="height:7px;">
                                <div class="progress-bar bg-navy" id="progressBar" style="width:0%;transition:width 0.4s;"></div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════════════════════
                             REC FORM 10
                        ════════════════════════════════════════════════ -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-navy text-white px-3 py-1 fw-bold me-3" style="font-size:0.75rem;">FORM 10</div>
                                    <div>
                                        <div class="fw-bold text-navy">Study/Research Protocol Reviewer Worksheet</div>
                                        <small class="text-muted">Guide questions for reviewing the proposal/protocol</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($questions10 as $i => $qdata): ?>
                                    <div class="p-3 border-bottom question-row" data-form="10" data-idx="<?php echo $i; ?>">
                                        <!-- Question -->
                                        <div class="fw-bold mb-2" style="font-size:0.88rem;">
                                            <?php echo ($i+1).". ".htmlspecialchars($qdata['q']); ?>
                                            <?php if (!empty($qdata['hint'])): ?>
                                                <span class="text-muted fw-normal"> <?php echo htmlspecialchars($qdata['hint']); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Main answer options -->
                                        <div class="d-flex flex-wrap gap-3 mb-2">
                                            <?php foreach (['Unable to Assess','Yes','No'] as $opt): ?>
                                                <?php $rid = 'f10_'.$i.'_'.preg_replace('/\W/','_',$opt); ?>
                                                <div class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input review-radio" type="radio"
                                                        name="f10_ans[<?php echo $i; ?>]"
                                                        value="<?php echo $opt; ?>"
                                                        id="<?php echo $rid; ?>"
                                                        <?php echo (empty($qdata['sub']) ? 'required' : ''); ?>>
                                                    <label class="form-check-label small" for="<?php echo $rid; ?>">
                                                        <?php echo $opt; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Sub-questions (Q5) -->
                                        <?php if (!empty($qdata['sub'])): ?>
                                            <div class="ms-3 mt-3 mb-3 p-3 rounded-4 bg-light border">
                                                <div class="row g-2 align-items-center fw-bold small text-muted mb-2 ps-2">
                                                    <div class="col-md-6">Evaluation Criteria</div>
                                                    <div class="col-md-6 d-flex justify-content-around">
                                                        <div style="width:80px;" class="text-center">Unable</div>
                                                        <div style="width:40px;" class="text-center">Yes</div>
                                                        <div style="width:40px;" class="text-center">No</div>
                                                    </div>
                                                </div>
                                                <?php foreach ($qdata['sub'] as $si => $sq): ?>
                                                    <div class="row g-2 align-items-center py-2 border-top-dashed ps-2">
                                                        <div class="col-md-6 small fw-bold text-navy">
                                                            <i class="fas fa-chevron-right me-2 text-primary opacity-50" style="font-size:0.6rem;"></i>
                                                            <?php echo htmlspecialchars($sq); ?>
                                                        </div>
                                                        <div class="col-md-6 d-flex justify-content-around align-items-center">
                                                            <?php foreach (['Unable to Assess','Yes','No'] as $opt): ?>
                                                                <?php $srid = 'f10_sub_'.$i.'_'.$si.'_'.preg_replace('/\W/','_',$opt); ?>
                                                                <div class="form-check form-check-inline m-0">
                                                                    <input class="form-check-input review-radio" type="radio"
                                                                        name="f10_sub[<?php echo $i; ?>][<?php echo $si; ?>]"
                                                                        value="<?php echo $opt; ?>"
                                                                        id="<?php echo $srid; ?>" style="width:1.2rem;height:1.2rem;">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Comment -->
                                        <div class="mt-2">
                                            <label class="form-label small text-muted mb-1">Comment:</label>
                                            <textarea name="f10_comment[<?php echo $i; ?>]"
                                                class="form-control form-control-sm"
                                                rows="2"
                                                placeholder="Enter your comment here..."></textarea>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Form 10 Recommendation -->
                            <div class="p-4 bg-light border-top">
                                <div class="fw-bold text-navy mb-3">Recommendation:</div>
                                <?php foreach ([
                                    'Approved'       => 'text-success',
                                    'Minor Revision' => 'text-warning',
                                    'Major Revision' => 'text-danger',
                                    'Disapproved'    => 'text-dark',
                                ] as $rec => $cls): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="f10_rec"
                                            value="<?php echo $rec; ?>" id="f10rec_<?php echo preg_replace('/\W/','_',$rec); ?>" required>
                                        <label class="form-check-label fw-bold <?php echo $cls; ?>"
                                            for="f10rec_<?php echo preg_replace('/\W/','_',$rec); ?>">
                                            <?php echo $rec; ?>
                                            <?php if ($rec !== 'Approved'): ?> required<?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mt-2">
                                    <label class="small text-muted mb-1">Notes (for revision/disapproval):</label>
                                    <textarea name="f10_rec_notes" class="form-control form-control-sm" rows="3"
                                        placeholder="Specify revisions required or reasons for disapproval..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════════════════════
                             REC FORM 12
                        ════════════════════════════════════════════════ -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-primary text-white px-3 py-1 fw-bold me-3" style="font-size:0.75rem;">FORM 12</div>
                                    <div>
                                        <div class="fw-bold text-navy">Informed Consent Checklist</div>
                                        <small class="text-muted">Guide questions for reviewing the informed consent process and form</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($questions12 as $i => $qdata): ?>
                                    <div class="p-3 border-bottom">
                                        <div class="fw-bold mb-2" style="font-size:0.88rem;">
                                            <?php echo ($i+1).". ".htmlspecialchars($qdata['q']); ?>
                                        </div>

                                        <!-- Main options (Q1, Q3, Q4, Q5) -->
                                        <?php if (empty($qdata['sub'])): ?>
                                            <div class="d-flex flex-wrap gap-3 mb-2">
                                                <?php $opts = $qdata['options'] ?? ['Unable to Assess','Yes','No'];
                                                foreach ($opts as $opt): ?>
                                                    <?php $rid = 'f12_'.$i.'_'.preg_replace('/\W/','_',$opt); ?>
                                                    <div class="form-check form-check-inline mb-0">
                                                        <input class="form-check-input review-radio" type="radio"
                                                            name="f12_ans[<?php echo $i; ?>]"
                                                            value="<?php echo $opt; ?>"
                                                            id="<?php echo $rid; ?>" required>
                                                        <label class="form-check-label small" for="<?php echo $rid; ?>">
                                                            <?php echo $opt; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <!-- If NO explain field (Q1) -->
                                            <?php if (!empty($qdata['if_no'])): ?>
                                                <div class="mt-1 ms-2">
                                                    <label class="small text-muted">If NO, please explain:</label>
                                                    <textarea name="f12_if_no" class="form-control form-control-sm mt-1" rows="2"></textarea>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Sub-items (Q2) - REVISED TO TABLE FORMAT (Section IV) -->
                                        <?php if (!empty($qdata['sub'])): ?>
                                            <div class="ms-2 mt-3 mb-4">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.85rem;">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th class="ps-3 py-2">Element of Informed Consent</th>
                                                                <?php 
                                                                // Extract unique options across sub-items for column headers
                                                                $uniqueOpts = ['Yes', 'No', 'Not applicable']; 
                                                                foreach($uniqueOpts as $o): ?>
                                                                    <th class="text-center py-2" style="width:100px;"><?php echo $o; ?></th>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($qdata['sub'] as $si => $sq): ?>
                                                                <tr>
                                                                    <td class="ps-3 py-2 fw-medium text-navy">
                                                                        <i class="fas fa-check-circle me-2 opacity-25"></i>
                                                                        <?php echo htmlspecialchars($sq['label']); ?>
                                                                    </td>
                                                                    <?php foreach ($uniqueOpts as $opt): ?>
                                                                        <td class="text-center">
                                                                            <?php $srid = 'f12_sub_'.$i.'_'.$si.'_'.preg_replace('/\W/','_',$opt); ?>
                                                                            <input class="form-check-input review-radio" type="radio"
                                                                                name="f12_sub[<?php echo $i; ?>][<?php echo $si; ?>]"
                                                                                value="<?php echo $opt; ?>"
                                                                                id="<?php echo $srid; ?>" 
                                                                                <?php echo (isset($sq['opts']) && !in_array($opt, $sq['opts'])) ? 'disabled opacity-25' : ''; ?>>
                                                                        </td>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Comment / Other concerns -->
                                        <div class="mt-2">
                                            <label class="form-label small text-muted mb-1">Comment:</label>
                                            <textarea name="f12_comment[<?php echo $i; ?>]"
                                                class="form-control form-control-sm" rows="2"
                                                placeholder="Enter your comment here..."></textarea>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Form 12 Recommendation -->
                            <div class="p-4 bg-light border-top">
                                <div class="fw-bold text-navy mb-3">Recommendation:</div>
                                <?php foreach ([
                                    'Approved'       => 'text-success',
                                    'Minor Revision' => 'text-warning',
                                    'Major Revision' => 'text-danger',
                                    'Disapproved'    => 'text-dark',
                                ] as $rec => $cls): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="f12_rec"
                                            value="<?php echo $rec; ?>" id="f12rec_<?php echo preg_replace('/\W/','_',$rec); ?>" required>
                                        <label class="form-check-label fw-bold <?php echo $cls; ?>"
                                            for="f12rec_<?php echo preg_replace('/\W/','_',$rec); ?>">
                                            <?php echo $rec; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mt-2">
                                    <label class="small text-muted mb-1">Notes (for revision/disapproval):</label>
                                    <textarea name="f12_rec_notes" class="form-control form-control-sm" rows="3"
                                        placeholder="Specify revisions required or reasons for disapproval..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit & Preview -->
                        <div class="row g-2 mb-5">
                            <div class="col-md-6">
                                <button type="button" onclick="showReviewPreview()" class="btn btn-outline-navy w-100 py-3 fw-bold shadow-sm rounded-pill">
                                    <i class="fas fa-eye me-2"></i> Review & Preview PDF
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" id="mainSubmitBtn" class="btn btn-success w-100 py-3 fw-bold shadow-sm rounded-pill">
                                    <i class="fas fa-check-double me-2"></i> Finalize and Submit Review
                                </button>
                            </div>
                            <div class="col-12 text-center">
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-lock me-1"></i> Final submission locks the evaluation. Please preview first.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Preview Modal (Step 12: PDF Preview) -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 pb-4">
            <div class="modal-header bg-navy text-white py-3 border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-pdf me-2"></i> PREVIEW: Evaluation Summary (F10 & F12)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5 pt-4" id="previewPrintArea">
                <!-- DNSC Branding in Preview -->
                <div class="text-center mb-4 d-none d-print-block">
                    <img src="../assets/images/dnsc_logo.png" width="80" class="mb-2">
                    <h5 class="fw-bold text-navy mb-0">Davao del Norte State College</h5>
                    <p class="small text-muted text-uppercase mb-0">Research Ethics Committee</p>
                    <hr>
                </div>

                <div class="row mb-4">
                    <div class="col-md-7">
                        <h4 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($protocol['title']); ?></h4>
                        <span class="badge bg-primary rounded-pill px-3 py-2 mt-1">REC Code: <?php echo htmlspecialchars($protocol['rec_code']); ?></span>
                    </div>
                </div>

                <!-- Summary Content -->
                <div id="previewContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-navy"></i>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-5 gap-2 d-print-none">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold" data-bs-dismiss="modal">Go Back & Edit</button>
                <button type="button" class="btn btn-navy px-4 py-2 rounded-pill fw-bold" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Print to PDF
                </button>
                <button type="button" class="btn btn-success px-5 py-2 rounded-pill fw-bold" onclick="document.getElementById('reviewForm').dispatchEvent(new Event('submit'))">
                    Confirm & Submit Final Review
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #previewPrintArea, #previewPrintArea * { visibility: visible; }
    #previewPrintArea { 
        position: absolute; 
        left: 0; top: 0; 
        width: 100%; border: none; padding: 0 !important; 
    }
    .modal-backdrop, .modal-header, .modal-footer, .d-print-none { display: none !important; }
    .modal { position: absolute; top: 0; left: 0; width: 100%; }
}
</style>

<?php if ($error): ?>
    <script>Swal.fire({ icon:'error', title:'Error', text:'<?php echo addslashes($error); ?>' });</script>
<?php endif; ?>
<?php if ($success): ?>
    <script>
        Swal.fire({ icon:'success', title:'Review Submitted!', text:'<?php echo addslashes($success); ?>' })
            .then(() => { window.location.href = 'protocols'; });
    </script>
<?php endif; ?>

<style>
.border-dashed { border: 2px dashed #cbd5e1 !important; }
.question-row:last-child { border-bottom: 0 !important; }
.form-check-input[type="radio"] { width: 1rem; height: 1rem; }
</style>

<script>
// Data for the preview script mapping
const F10_QUESTIONS = <?php echo json_encode($questions10); ?>;
const F12_QUESTIONS = <?php echo json_encode($questions12); ?>;

function showReviewPreview() {
    const previewArea = document.getElementById('previewContent');
    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);
    
    let html = `
        <div class="eval-preview-section mb-5">
            <h5 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="fas fa-microscope me-2"></i> FORM 10: Study Protocol Implementation</h5>
    `;
    
    // Map Form 10
    F10_QUESTIONS.forEach((q, i) => {
        const ans = formData.get(`f10_ans[${i}]`) || 'Not Answered';
        const comment = formData.get(`f10_comment[${i}]`) || '';
        const recType = (ans == 'Yes') ? 'success' : (ans == 'No' ? 'danger' : 'warning');
        
        html += `
            <div class="mb-4">
                <div class="fw-bold small mb-1">${i+1}. ${q.q}</div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-${recType}-light text-${recType} rounded-pill border">${ans}</span>
                    ${comment ? `<div class="small text-muted border-start ps-3" style="font-style:italic">"${comment}"</div>` : ''}
                </div>
        `;
        
        // Handle Sub-questions if any
        if (q.sub) {
            html += '<div class="ms-4 mt-2 p-2 bg-light rounded-3 small">';
            q.sub.forEach((sq, si) => {
                const sans = formData.get(`f10_sub[${i}][${si}]`) || 'Not Answered';
                html += `<div class="mb-1"><strong>${sq}:</strong> ${sans}</div>`;
            });
            html += '</div>';
        }
        
        html += `</div>`;
    });
    
    // Final Form 10 Recommendation
    const f10rec = formData.get('f10_rec') || 'NOT SET';
    const f10notes = formData.get('f10_rec_notes') || '';
    html += `
        <div class="p-3 bg-light rounded-4 border-dashed mt-4">
            <div class="small fw-bold text-uppercase opacity-50 mb-1">Final Form 10 Recommendation</div>
            <div class="fw-bold text-navy fs-5">${f10rec}</div>
            ${f10notes ? `<div class="mt-2 text-muted small"><strong>Notes:</strong> ${f10notes}</div>` : ''}
        </div>
    `;
    
    html += `</div> <hr class="my-5"> `;

    // Map Form 12
    html += `
        <div class="eval-preview-section mb-4">
            <h5 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="fas fa-clipboard-check me-2"></i> FORM 12: Informed Consent Checklist</h5>
    `;
    
    F12_QUESTIONS.forEach((q, i) => {
        const ans = formData.get(`f12_ans[${i}]`) || 'No specific answer / Not Set';
        const comment = formData.get(`f12_comment[${i}]`) || '';
        
        html += `
            <div class="mb-4">
                <div class="fw-bold small mb-1">${i+1}. ${q.q}</div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary-light text-primary rounded-pill border">${ans}</span>
                    ${comment ? `<div class="small text-muted border-start ps-3" style="font-style:italic">"${comment}"</div>` : ''}
                </div>
        `;
        
        if (q.sub) {
            html += '<div class="ms-4 mt-2 p-2 bg-light rounded-3 small">';
            q.sub.forEach((sq, si) => {
                const sans = formData.get(`f12_sub[${i}][${si}]`) || 'N/A';
                html += `<div class="mb-1"><strong>${sq.label}:</strong> ${sans}</div>`;
            });
            html += '</div>';
        }
        
        html += `</div>`;
    });

    const f12rec = formData.get('f12_rec') || 'NOT SET';
    const f12notes = formData.get('f12_rec_notes') || '';
    html += `
        <div class="p-3 bg-light rounded-4 border-dashed mt-4">
            <div class="small fw-bold text-uppercase opacity-50 mb-1">Final Form 12 Recommendation</div>
            <div class="fw-bold text-navy fs-5">${f12rec}</div>
            ${f12notes ? `<div class="mt-2 text-muted small"><strong>Notes:</strong> ${f12notes}</div>` : ''}
        </div>
    </div>`;

    previewArea.innerHTML = html;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function toggleComment(index) {
    const box = document.getElementById('comment_box_' + index);
    const checkbox = document.getElementById('confirm_comment_' + index);
    box.style.display = checkbox.checked ? 'block' : 'none';
    if (checkbox.checked) box.querySelector('textarea').focus();
}

function toggleComment(index) {
    const box = document.getElementById('comment_box_' + index);
    const checkbox = document.getElementById('confirm_comment_' + index);
    box.style.display = checkbox.checked ? 'block' : 'none';
    if (checkbox.checked) box.querySelector('textarea').focus();
}

document.addEventListener('DOMContentLoaded', () => {


    // File label
    const fi = document.getElementById('memberFileInput');
    document.querySelector('label[for="memberFileInput"]').addEventListener('click', () => fi.click());
    fi.addEventListener('change', () => {
        document.getElementById('fileLabel').textContent = fi.files[0] ? '📎 ' + fi.files[0].name : '';
    });

    // Allow deselecting a radio by clicking the currently-selected option again
    const radios = document.querySelectorAll('input[type="radio"].review-radio');
    const bar    = document.getElementById('progressBar');
    const lbl    = document.getElementById('progressLabel');
    const answered = new Set();
    function updateProgress() {
        answered.clear();
        document.querySelectorAll('input[type="radio"].review-radio:checked')
            .forEach(r => answered.add(r.name));
        const total = new Set([...radios].map(r => r.name)).size;
        const pct = total > 0 ? Math.round((answered.size / total) * 100) : 0;
        bar.style.width = pct + '%';
        lbl.textContent = answered.size + ' of ' + total + ' answered';
    }

    // Track pre-click state so we can toggle off
    radios.forEach(r => {
        r.addEventListener('mousedown', function () {
            this._wasChecked = this.checked;
        });
        r.addEventListener('click', function () {
            if (this._wasChecked) {
                this.checked = false;   // deselect
                this._wasChecked = false;
            }
            updateProgress();
        });
    });
    // Live counters and validation logic as before...
    updateProgress();

    // Premium SweetAlert Submission
    const form = document.getElementById('reviewForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            const firstInvalid = this.querySelector(':invalid');
            if (firstInvalid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Review Incomplete',
                    text: 'Please answer all required questions before submitting.',
                    confirmButtonColor: '#1a2b4b'
                }).then(() => {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // If it's a radio, focus the container or first option
                    if(firstInvalid.type === 'radio') {
                        firstInvalid.parentElement.focus();
                    } else {
                        firstInvalid.focus();
                    }
                });
                return false;
            }
        }

        Swal.fire({
            title: 'Finalize Review?',
            text: "Are you sure you want to submit? This action cannot be undone.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754', // Green for success/confirm
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-check-double me-2"></i> Yes, Finalize Now',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // To ensure $_POST['submit_review'] is set in PHP, 
                // we append a hidden input before submitting
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'submit_review';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
                
                // Show loading state
                Swal.fire({
                    title: 'Submitting...',
                    text: 'Recording your evaluation, please wait.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                form.submit();
            }
        });
    });
});

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
