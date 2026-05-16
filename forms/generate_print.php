<?php
if (!isset($_GET['public'])) {
    require_once '../includes/auth_check.php';
    checkAuth(['rec_staff', 'rec_chair', 'admin', 'rec_member', 'rec_secretary']);
} else {
    session_start();
}
require_once '../config/database.php';

// Absolute path to logo for reliable print/PDF rendering
$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) {
    header("Location: ../rec_staff/protocols");
    exit();
}

// Protocol info
$stmtP = $pdo->prepare("SELECT p.*, u.name as submitted_by, (SELECT signature FROM users WHERE email = p.author_email LIMIT 1) as author_sig FROM protocols p LEFT JOIN admins u ON p.created_by = u.admin_id WHERE p.protocol_id = ?");
$stmtP->execute([$protocol_id]);
$protocol = $stmtP->fetch();
if (!$protocol) {
    die("Protocol not found.");
}

// Reviewers
$stmtR = $pdo->prepare("
    SELECT ra.*, u.name as reviewer_name, u.email as reviewer_email, u.signature as reviewer_sig
    FROM reviewer_assignments ra
    JOIN admins u ON ra.reviewer_id = u.admin_id
    WHERE ra.protocol_id = ?
    ORDER BY ra.status ASC
");
$stmtR->execute([$protocol_id]);
$reviewers = $stmtR->fetchAll();

// ── Role-based Filter: Only administrative roles see EVERYTHING ──
if (isset($_SESSION['role']) && !in_array($_SESSION['role'], ['rec_chair', 'admin', 'rec_staff'])) {
    $reviewers = array_filter($reviewers, function($rv) {
        return (int)$rv['reviewer_id'] === (int)$_SESSION['user_id'];
    });
}

// Final decision
$stmtD = $pdo->prepare("SELECT fd.*, u.name as chair_name, u.signature as chair_sig FROM final_decisions fd LEFT JOIN admins u ON fd.chair_id = u.admin_id WHERE fd.protocol_id = ? ORDER BY fd.decision_date DESC LIMIT 1");
$stmtD->execute([$protocol_id]);
$decision = $stmtD->fetch();

// Fetch Active REC Staff
$stmtS = $pdo->prepare("SELECT name, signature FROM admins WHERE role = 'rec_staff' AND status = 'active' LIMIT 1");
$stmtS->execute();
$staff = $stmtS->fetch();
$secretariat_name = $staff ? $staff['name'] : "DNSC REC REC Staff";
$secretariat_sig = $staff ? $staff['signature'] : null;

// Fetch Active REC Chair
$stmtC = $pdo->prepare("SELECT name, signature FROM admins WHERE role = 'rec_chair' AND status = 'active' LIMIT 1");
$stmtC->execute();
$chair = $stmtC->fetch();
$chair_name = $chair ? $chair['name'] : "DNSC REC Chairperson";
$chair_sig = $chair ? $chair['signature'] : null;

// The chair who actually made the decision
$decision_chair = ($decision && !empty($decision['chair_name'])) ? $decision['chair_name'] : $chair_name;
$decision_chair_sig = ($decision && !empty($decision['chair_sig'])) ? $decision['chair_sig'] : $chair_sig;

function renderSignature(?string $sig_filename, string $width = '150px'): string {
    if (!$sig_filename) return '<div style="height:50px;"></div>';
    $path = '../uploads/signatures/' . $sig_filename;
    if (file_exists($path)) {
        return '
        <div style="height:50px; position:relative; text-align:center;">
            <img src="' . BASE_URL . 'uploads/signatures/' . $sig_filename . '" 
                 style="width:' . $width . '; height:auto; position:absolute; bottom:-10px; left:50%; transform:translateX(-50%); z-index:10; pointer-events:none;">
        </div>';
    }
    return '<div style="height:50px;"></div>';
}

function getForm10(PDO $pdo, int|string $protocol_id, int|string $reviewer_id): array
{
    $s = $pdo->prepare("SELECT question, answer, comment FROM form10_answers WHERE protocol_id=? AND reviewer_id=? ORDER BY answer_id ASC");
    $s->execute([$protocol_id, $reviewer_id]);
    $rows = $s->fetchAll();
    $main = [];
    $subs = [];
    foreach ($rows as $r) {
        if (strpos($r['question'], 'SUB|') === 0) {
            $subs[] = $r;
        } else {
            $main[] = $r;
        }
    }
    return ['main' => $main, 'subs' => $subs];
}

// Helper: get form12 answers for a reviewer
function getForm12(PDO $pdo, int|string $protocol_id, int|string $reviewer_id): array
{
    $s = $pdo->prepare("SELECT question, answer, comment FROM form12_answers WHERE protocol_id=? AND reviewer_id=? ORDER BY answer_id ASC");
    $s->execute([$protocol_id, $reviewer_id]);
    $rows = $s->fetchAll();
    $main = [];
    $subs = [];
    $ifno = '';
    foreach ($rows as $r) {
        if (strpos($r['question'], 'SUB|') === 0) {
            $subs[] = $r;
        } elseif (strpos($r['question'], 'IFNO|') === 0) {
            $ifno = $r['answer'];
        } else {
            $main[] = $r;
        }
    }
    return ['main' => $main, 'subs' => $subs, 'ifno' => $ifno];
}

// Helper: get recommendation for a reviewer+form
function getRec(PDO $pdo, int|string $protocol_id, int|string $reviewer_id, int|string $form_type): mixed
{
    $s = $pdo->prepare("SELECT recommendation, notes FROM reviewer_recommendations WHERE protocol_id=? AND reviewer_id=? AND form_type=? LIMIT 1");
    $s->execute([$protocol_id, $reviewer_id, $form_type]);
    return $s->fetch();
}

// Helper: render a checkbox cell
function chk(mixed $val, mixed $option): string
{
    $checked = trim(strtolower($val)) === trim(strtolower($option));
    return '<span style="margin-right:8px;">' . ($checked ? '☑' : '☐') . ' ' . htmlspecialchars($option) . '</span>';
}

// Exact Form 10 questions
$questions10 = [
    ['q' => 'Does the study have social value?', 'hint' => '(e.g. scientific value, relevance to national/community needs)'],
    ['q' => 'Is the study background adequate?'],
    ['q' => 'Are the research questions supported by the Review of Literature?'],
    ['q' => 'Are the study objectives Specific, Measurable, Attainable, Realistic, Time-bound?'],
    [
        'q' => 'Is the research design appropriate?',
        'sub' => [
            'Is the population identified and defined?',
            'Is the selection of study participants described?',
            'Is the sample size justified?',
            'Is the plan for data analysis described?',
            'Are there dummy tables?',
        ]
    ],
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

// Exact Form 12 questions
$questions12 = [
    ['q' => 'Is it necessary to seek the informed consent of the participants?', 'if_no' => true, 'opts' => ['Unable to Assess', 'Yes', 'No']],
    [
        'q' => 'If YES, are the participants provided with sufficient information regarding:',
        'sub' => [
            ['label' => 'Purpose of the study?', 'opts' => ['Yes', 'No']],
            ['label' => 'Expected duration of participation?', 'opts' => ['Yes', 'No']],
            ['label' => 'Procedures to be carried out?', 'opts' => ['Yes', 'No']],
            ['label' => 'Discomforts and inconveniences?', 'opts' => ['Yes', 'No']],
            ['label' => 'Risks (including possible discrimination)?', 'opts' => ['Yes', 'No']],
            ['label' => 'Random assignment to the trial treatments?', 'opts' => ['Not applicable', 'Yes', 'No']],
            ['label' => 'Benefits to the participants?', 'opts' => ['Yes', 'No']],
            ['label' => 'Alternative treatments/procedures?', 'opts' => ['Not applicable', 'Yes', 'No']],
            ['label' => 'Compensation and/or medical treatments in case of injury?', 'opts' => ['Yes', 'No']],
            ['label' => 'Who to contact for pertinent questions and/or for assistance in a research-related injury?', 'opts' => ['Yes', 'No']],
            ['label' => 'Refusal to participate or discontinuance at any time will involve penalty or loss of benefits to which the subject is entitled?', 'opts' => ['Yes', 'No']],
            ['label' => 'Extent of confidentiality?', 'opts' => ['Yes', 'No']],
        ]
    ],
    ['q' => 'Is the informed consent written or presented in simple language that participants can understand?', 'opts' => ['Yes', 'No']],
    ['q' => 'Does the protocol include an adequate process for ensuring that consent is voluntary?', 'opts' => ['Yes', 'No']],
    ['q' => 'Do you have any other concerns?', 'opts' => ['Yes', 'No']],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>REC Forms — <?php echo htmlspecialchars($protocol['rec_code']); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/logo.png">

    <style>
        @page { 
            size: A4 portrait; 
            margin: 25.4mm; /* Native 1-inch margins for every physical page */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #f4f7f6; /* Screen preview background */
            padding: 40px 0;
        }

        .form-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            padding: 25.4mm; /* Padding for SCREEN PREVIEW only */
            background: #fff;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Only break before a page if it follows another one */
        .form-page + .form-page,
        .page-break {
            page-break-before: always;
        }

        @media print {
            body { 
                background: #fff; 
                padding: 0; 
                margin: 0;
            }
            .form-page { 
                margin: 0; 
                box-shadow: none; 
                width: 100%; 
                padding: 0.5in 0 !important; /* Force a consistent top/bottom buffer in print */
                min-height: auto;
            }
        }

        /* ── Header table ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12pt;
        }

        .header-table td,
        .header-table th {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        .logo-cell {
            width: 70px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000;
        }

        .logo-cell img {
            width: 60px;
            height: auto;
        }

        .form-title-cell {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            border: 1px solid #000;
            padding: 6px;
        }

        .form-meta-cell {
            font-size: 9.5pt;
        }

        /* ── Info table ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10pt;
            vertical-align: top;
        }

        .info-label {
            font-weight: normal;
            white-space: nowrap;
            width: 100px;
        }

        .info-value {
            font-weight: bold;
        }

        /* ── Section header ── */
        .section-header {
            background: #000;
            color: #fff;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 10pt;
            margin: 0;
            page-break-after: avoid;
        }

        /* ── Question table ── */
        .q-table {
            width: 100%;
            border-collapse: separate; /* Required for break-inside to work reliably in some engines */
            border-spacing: 0;
            margin-bottom: 0;
            page-break-inside: auto;
            break-inside: auto;
        }

        .q-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
            break-inside: avoid;
            break-after: auto;
        }

        .q-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            page-break-inside: avoid;
            break-inside: avoid;
            font-size: 10pt;
            vertical-align: top;
        }

        .q-text {
            width: 55%;
        }

        .q-opts {
            white-space: nowrap;
        }

        .q-sub {
            padding-left: 20px;
        }

        .q-sub li {
            margin-bottom: 4px;
        }

        .comment-row td {
            padding: 4px 8px;
            font-size: 9.5pt;
            font-style: italic;
            border: 1px solid #000;
        }

        /* ── Recommendation ── */
        .rec-section {
            margin-top: 16pt;
            padding: 10px;
            border: 1px solid #000;
            page-break-inside: avoid;
        }

        .rec-title {
            font-weight: bold;
            margin-bottom: 6pt;
        }

        .rec-line {
            border-bottom: 1px solid #000;
            margin: 6pt 0;
            min-height: 18pt;
        }

        /* ── Signature ── */
        .sig-table {
            width: 100%;
            margin-top: 24pt;
        }

        .sig-table td {
            padding: 4px 0;
            vertical-align: bottom;
            font-size: 10pt;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 30pt;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always !important;
                break-before: page !important;
            }

            .form-page {
                margin: 0 !important;
                padding: 12mm 15mm !important;
                width: 210mm !important;
                min-height: 297mm !important;
                box-shadow: none !important;
                border: none !important;
            }

            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }
    </style>
</head>

<body>

    <!-- Print button -->
    <div class="no-print" style="text-align:center; padding:12px; background:#1a2b4b;">
        <button onclick="window.print()"
            style="background:#fff; color:#1a2b4b; border:none; padding:10px 28px; font-weight:bold; border-radius:20px; font-size:14px; cursor:pointer;">
            🖨️ &nbsp;Print / Save as PDF
        </button>
        <button onclick="window.close(); setTimeout(function(){ history.back(); }, 10);"
            style="background:transparent; color:#fff; border:1px solid #fff; padding:10px 20px; font-weight:bold; border-radius:20px; font-size:13px; cursor:pointer; margin-left:8px;">
            ✕ Close
        </button>
    </div>



    <?php $revIndex = 0; foreach ($reviewers as $rv):
        $r10 = getForm10($pdo, $protocol_id, $rv['reviewer_id']);
        $r12 = getForm12($pdo, $protocol_id, $rv['reviewer_id']);
        $rec10 = getRec($pdo, $protocol_id, $rv['reviewer_id'], 10);
        $rec12 = getRec($pdo, $protocol_id, $rv['reviewer_id'], 12);

        // Index main answers by question text
        $ans10 = [];
        foreach ($r10['main'] as $a) {
            $ans10[$a['question']] = $a;
        }
        $ans12 = [];
        foreach ($r12['main'] as $a) {
            $ans12[$a['question']] = $a;
        }
        // Index sub answers
        $sub10 = [];
        foreach ($r10['subs'] as $a) {
            $sub10[$a['question']] = $a['answer'];
        }
        $sub12 = [];
        foreach ($r12['subs'] as $a) {
            $sub12[$a['question']] = $a['answer'];
        }
        ?>

        <!-- ══════════════════════════════════════════════════════════════
     REC FORM 10 — <?php echo htmlspecialchars($rv['reviewer_name']); ?>
════════════════════════════════════════════════════════════════ -->
        <div class="form-page <?php echo ($revIndex > 0) ? 'page-break' : ''; ?>">

            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2">
                        <img src="<?php echo $logoSrc; ?>" alt="DNSC Logo">
                    </td>
                    <td class="form-title-cell" rowspan="2" style="width:45%; font-size: 11pt;">
                        STUDY RESEARCH/ PROTOCOL REVIEWER<br>WORKSHEET
                    </td>
                    <td style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:4px 8px; text-align: center;">
                        RESEARCH ETHICS COMMITTEE
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:0;">
                        <table style="width:100%; border-collapse:collapse; border: none;">
                            <tr>
                                <td style="border:none; border-right: 1px solid black; border-bottom: 1px solid black; padding:3px 6px; font-size:9pt;" width="60%">REC Form No.</td>
                                <td style="border:none; border-bottom: 1px solid black; padding:3px 6px; font-weight:bold; text-align: center;">10</td>
                            </tr>
                            <tr>
                                <td style="border:none; border-right: 1px solid black; border-bottom: 1px solid black; padding:3px 6px; font-size:9pt;">Version No.</td>
                                <td style="border:none; border-bottom: 1px solid black; padding:3px 6px; font-weight:bold; text-align: center;">01</td>
                            </tr>
                            <tr>
                                <td style="border:none; border-right: 1px solid black; padding:3px 6px; font-size:9pt;">Date of Effectivity</td>
                                <td style="border:none; padding:3px 6px; font-weight:bold; text-align: center; font-size: 8pt;">June 15, 2022</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Protocol Info -->
            <table class="info-table" style="margin-top: 15px;">
                <tr>
                    <td class="info-label" style="width:120px; vertical-align: middle;">Title of Study</td>
                    <td colspan="3" class="info-value" style="font-size: 11pt;"><?php echo htmlspecialchars($protocol['title']); ?></td>
                </tr>
                <tr>
                    <td class="info-label" style="vertical-align: middle;">REC Code</td>
                    <td class="info-value" style="width:30%; vertical-align: middle;"><?php echo htmlspecialchars($protocol['rec_code']); ?></td>
                    <td class="info-label" style="width:100px; text-align: center; vertical-align: middle;">Type of<br>Review</td>
                    <td class="info-value" style="text-align: center; vertical-align: middle; font-size: 11pt;">
                        <?php 
                        $rtMap = ['pending'=>'PENDING', 'exempt'=>'EXEMPTED', 'expedited'=>'EXPEDITED REVIEW', 'full_board'=>'FULL REVIEW'];
                        echo $rtMap[$protocol['review_type']] ?? strtoupper(str_replace('_', ' ', $protocol['review_type'])); 
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="info-label" style="vertical-align: middle;">Reviewer</td>
                    <td class="info-value" style="vertical-align: middle;"><?php echo htmlspecialchars($rv['reviewer_name']); ?></td>
                    <td style="padding: 0; width: 100px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr><td style="border: none; border-bottom: 1px solid black; height: 20px;"></td></tr>
                            <tr><td style="border: none; font-size: 9pt; text-align: center;">Date Received</td></tr>
                        </table>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; border-bottom: 1px solid black; border-right: 1px solid black; text-align: center; font-size: 9pt; width: 50%;">Primary reviewer</td>
                                <td style="border: none; border-bottom: 1px solid black; text-align: center; font-size: 9pt;">
                                    <?php echo chk($rv['is_primary'] ? 'Yes' : 'No', 'Yes'); ?> <?php echo chk($rv['is_primary'] ? 'Yes' : 'No', 'No'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border: none; height: 20px; text-align: center; vertical-align: middle; font-weight: bold;">
                                    <?php echo date('F d, Y', strtotime($rv['assigned_at'])); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Section Title -->
            <div class="section-header" style="background: #d9d9d9; color: black; border: 1px solid black; border-bottom: none; font-weight: normal;">Guide questions for reviewing the proposal / protocol</div>

            <!-- Questions -->
            <table class="q-table" style="border-top: 1px solid black;">
                <tbody>
                <?php 
                $pageOneLimit = 5; // Split after question 5 as per image
                foreach ($questions10 as $i => $qdata):
                    $row = $ans10[$qdata['q']] ?? null;
                    $answer = $row['answer'] ?? '';
                    $comment = $row['comment'] ?? '';
                    
                    if ($i == $pageOneLimit): ?>
                </tbody>
            </table>
            <div style="position: absolute; bottom: 0.5in; right: 0.5in;">129</div>
        </div>

        <div class="form-page page-break">
            <table class="q-table" style="border-top: 1px solid black;">
                <tbody>
                <?php endif; ?>

                    <tr>
                        <td style="width:55%; font-size:10pt; padding:8px; border:1px solid #000; vertical-align:top;">
                            <strong><?php echo ($i + 1) . ". " . htmlspecialchars($qdata['q']); ?></strong>
                            <br><span style="font-size:9.5pt;">Comment: <?php echo htmlspecialchars($qdata['hint'] ?? ''); ?></span>

                            <?php if (!empty($qdata['sub'])): ?>
                                <ul style="margin-top:8px; padding-left:25px; list-style-type: disc;">
                                    <?php foreach ($qdata['sub'] as $si => $sq):
                                        $subKey = 'SUB|' . $qdata['q'] . '|' . $sq;
                                        $subAns = $sub10[$subKey] ?? '';
                                        ?>
                                        <li style="margin-bottom:8px; position: relative;">
                                            <?php echo htmlspecialchars($sq); ?>
                                            <div style="position: absolute; right: -80%; top: 0; white-space: nowrap;">
                                                <?php echo chk($subAns, 'Unable to Assess'); ?>
                                                <?php echo chk($subAns, 'Yes'); ?>
                                                <?php echo chk($subAns, 'No'); ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <?php if ($comment): ?>
                                <div style="margin-top: 10px; font-style: italic; font-size: 9.5pt; color: #444;">
                                    <?php echo nl2br(htmlspecialchars($comment)); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <?php if (empty($qdata['sub'])): ?>
                            <td style="white-space:nowrap; border:1px solid #000; padding:8px; vertical-align:top; text-align: center;">
                                <?php echo chk($answer, 'Unable to Assess'); ?>
                                <?php echo chk($answer, 'Yes'); ?>
                                <?php echo chk($answer, 'No'); ?>
                            </td>
                        <?php else: ?>
                            <td style="border:1px solid #000; padding:8px; vertical-align:top;">
                                <!-- Checkboxes handled inside the sub-list for alignment -->
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="position: absolute; bottom: 0.5in; right: 0.5in;">130</div>
        </div>

        <!-- RECOMMENDATION PAGE -->
        <div class="form-page page-break">
            <div style="margin-top: 40px; font-size: 11pt;">
                <div style="display: flex; margin-bottom: 25px;">
                    <strong style="width: 150px;">Recommendation:</strong>
                    <div>
                        <div style="margin-bottom: 15px;"><?php echo chk($rec10['recommendation'] ?? '', 'Approved'); ?> <strong>Approved</strong></div>
                        
                        <div style="margin-bottom: 15px;">
                            <?php echo chk($rec10['recommendation'] ?? '', 'Minor Revision'); ?> <strong>Minor revision/s required</strong>
                            <div style="margin-top: 10px; border-bottom: 1px solid black; width: 450px; height: 25px;"></div>
                            <div style="margin-top: 10px; border-bottom: 1px solid black; width: 450px; height: 25px;"></div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <?php echo chk($rec10['recommendation'] ?? '', 'Major Revision'); ?> <strong>Major revision/s required</strong>
                            <div style="margin-top: 10px; border-bottom: 1px solid black; width: 450px; height: 25px;"></div>
                            <div style="margin-top: 10px; border-bottom: 1px solid black; width: 450px; height: 25px;"></div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <?php echo chk($rec10['recommendation'] ?? '', 'Disapproved'); ?> <strong>Disapproved</strong>
                            <div style="margin-left: 30px; margin-top: 10px;">
                                Reasons for disapproval:
                                <div style="margin-top: 10px; border-bottom: 1px solid black; width: 420px; height: 25px;"></div>
                                <div style="margin-top: 10px; border-bottom: 1px solid black; width: 420px; height: 25px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes display if any -->
                <?php if (!empty($rec10['notes'])): ?>
                    <div style="margin-top: 20px; padding: 15px; border: 1px dashed #ccc; font-style: italic;">
                        <strong>Reviewer Notes:</strong><br>
                        <?php echo nl2br(htmlspecialchars($rec10['notes'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 100px; display: flex; justify-content: space-between;">
                <div style="text-align: center; width: 350px;">
                    <div style="border-bottom: 1px solid black; margin-bottom: 5px;">
                        <?php echo renderSignature($rv['reviewer_sig'], '180px'); ?>
                    </div>
                    <div style="font-size: 10pt;">Name and Signature of Reviewer</div>
                </div>
                <div style="text-align: center; width: 200px;">
                    <div style="border-bottom: 1px solid black; margin-bottom: 5px; height: 50px; vertical-align: bottom; display: flex; align-items: flex-end; justify-content: center;">
                        <strong><?php echo date('F d, Y', strtotime($rec10['created_at'] ?? date('Y-m-d'))); ?></strong>
                    </div>
                    <div style="font-size: 10pt;">Review Date</div>
                </div>
            </div>

            <div style="position: absolute; bottom: 0.5in; right: 0.5in;">131</div>
        </div><!-- /.form-page Recommendation -->



        <!-- ══════════════════════════════════════════════════════════════
     REC FORM 12 — <?php echo htmlspecialchars($rv['reviewer_name']); ?>
════════════════════════════════════════════════════════════════ -->
        <div class="form-page page-break">

            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2">
                        <img src="<?php echo $logoSrc; ?>" alt="DNSC Logo">
                    </td>
                    <td class="form-title-cell" rowspan="2" style="width:45%;">
                        INFORMED CONSENT CHECKLIST
                    </td>
                    <td style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:4px 8px;">
                        RESEARCH ETHICS COMMITTEE
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:0;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">REC Form No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">12</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Version No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">01</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Date of Effectivity
                                </td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">June 15, 2022</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Protocol Info -->
            <table class="info-table">
                <tr>
                    <td class="info-label" style="width:110px;">Title of Study</td>
                    <td colspan="3" class="info-value"><?php echo htmlspecialchars($protocol['title']); ?></td>
                </tr>
                <tr>
                    <td class="info-label">REC Code</td>
                    <td class="info-value" style="width:28%;"><?php echo htmlspecialchars($protocol['rec_code']); ?></td>
                    <td class="info-label" style="width:90px;">REC Code</td>
                    <td class="info-value"><?php echo htmlspecialchars($protocol['rec_code']); ?></td>
                </tr>
                <tr>
                    <td class="info-label">Proponent</td>
                    <td class="info-value"><?php echo htmlspecialchars($protocol['project_leader']); ?></td>
                    <td class="info-label">Proponent</td>
                    <td class="info-value"><?php echo htmlspecialchars($protocol['project_leader']); ?></td>
                </tr>
                <tr>
                    <td class="info-label">Reviewer</td>
                    <td class="info-value"><?php echo htmlspecialchars($rv['reviewer_name']); ?></td>
                    <td class="info-label" style="font-size:9pt;">Primary reviewer</td>
                    <td style="white-space:nowrap; font-size:10pt;">
                        ☑ Yes &nbsp;&nbsp; ☐ No
                    </td>
                </tr>
            </table>

            <!-- Section Title -->
            <div class="section-header">Guide questions for reviewing the informed consent process and form</div>

            <!-- Questions -->
            <table class="q-table">
                <thead>
                    <tr>
                        <td colspan="2" style="border:none; height:15pt; padding:0;"></td>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($questions12 as $i => $qdata):
                    $row = $ans12[$qdata['q']] ?? null;
                    $answer = $row['answer'] ?? '';
                    $comment = $row['comment'] ?? '';
                    $opts = $qdata['opts'] ?? ['Unable to Assess', 'Yes', 'No'];
                    ?>
                    <tr>
                        <td
                            style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:6px 8px; vertical-align:top; width:55%;">
                            <?php echo ($i + 1) . ". " . htmlspecialchars($qdata['q']); ?>

                            <?php if (!empty($qdata['if_no'])): ?>
                                <div style="font-weight:normal; font-size:9.5pt; margin-top:4px;">
                                    <em>If NO, please explain:</em><br>
                                    <?php echo htmlspecialchars($r12['ifno']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($qdata['sub'])): ?>
                                <ul style="margin-top:8px; padding-left:16px; font-weight:normal; font-size:10pt;">
                                    <?php foreach ($qdata['sub'] as $si => $sq):
                                        $subKey = 'SUB|' . $qdata['q'] . '|' . $sq['label'];
                                        $subAns = $sub12[$subKey] ?? '';
                                        ?>
                                        <li style="margin-bottom:5px; font-size:9.5pt;">
                                            <?php echo htmlspecialchars($sq['label']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>

                        <?php if (empty($qdata['sub'])): ?>
                            <td style="white-space:nowrap; border:1px solid #000; padding:6px 8px; vertical-align:top;">
                                <?php foreach ($opts as $opt): ?>
                                    <?php echo chk($answer, $opt); ?><br>
                                <?php endforeach; ?>
                            </td>
                        <?php else: ?>
                            <td style="border:1px solid #000; padding:6px 8px; vertical-align:top; font-size:10pt;">
                                <?php foreach ($qdata['sub'] as $si => $sq):
                                    $subKey = 'SUB|' . $qdata['q'] . '|' . $sq['label'];
                                    $subAns = $sub12[$subKey] ?? '';
                                    ?>
                                    <div style="margin-bottom:5px;">
                                        <?php foreach ($sq['opts'] as $opt): ?>
                                            <?php echo chk($subAns, $opt); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php if ($comment): ?>
                        <tr>
                            <td colspan="2" style="padding:4px 8px; font-size:9.5pt; border:1px solid #000; background:#fafafa;">
                                <strong>Comment:</strong> <?php echo nl2br(htmlspecialchars($comment)); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Keep Recommendation and Signature together -->
            <div style="page-break-inside: avoid; break-inside: avoid; padding-top: 60pt;">
                <!-- Recommendation Section (Manual Alignment) -->
                <div class="rec-section">
                    <div class="rec-title">Recommendation:</div>
                    <?php 
                    $recOptions12 = [
                        ['label' => 'Approved', 'val' => 'Approved'],
                        ['label' => 'Minor revisions required', 'val' => 'Minor Revision'],
                        ['label' => 'Major revisions required', 'val' => 'Major Revision'],
                        ['label' => 'Disapproved', 'val' => 'Disapproved'],
                    ];
                    foreach ($recOptions12 as $opt):
                        $isChosen = ($rec12 && $rec12['recommendation'] === $opt['val']);
                    ?>
                        <div style="margin-bottom:6pt; font-size:10pt;">
                            <?php echo $isChosen ? '☑' : '☐'; ?> <?php echo $opt['label']; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($rec12 && $rec12['notes']): ?>
                        <div style="margin-top:8pt; padding:10px; border:1px solid #eee; background:#fcfcfc; font-size:9.5pt;">
                            <strong>Reviewer Remarks/Reasons:</strong><br>
                            <?php echo nl2br(htmlspecialchars($rec12['notes'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:40pt;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:50%;"></td>
                            <td style="width:50%; text-align:center;">
                                <?php echo renderSignature($rv['reviewer_sig'], '150px'); ?>
                                <div style="width:250px; border-top:1.5pt solid #000; margin:0 auto; padding-top:3pt;">
                                    <strong style="font-size:11pt; letter-spacing:0.5px;"><?php echo strtoupper($rv['reviewer_name']); ?></strong><br>
                                    <span style="font-size:9pt; color:#000;">Signature over Printed Name</span><br>
                                    <span style="font-size:9pt; color:#000;">Reviewer</span>
                                </div>
                                <div style="margin-top:4pt; font-size:10pt;">
                                    Date: <strong><?php echo date('F d, Y', strtotime($rec12['created_at'] ?? date('Y-m-d'))); ?></strong>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /.form-page Form 12 -->

    <?php $revIndex++; endforeach; // end foreach reviewer ?>

    <?php if ($protocol['review_type'] === 'exempt' && in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
        <!-- ══════════════════════════════════════════════════════════════
             REC FORM 14a — CERTIFICATE OF EXEMPTION FROM REVIEW
        ════════════════════════════════════════════════════════════════ -->
        <div class="form-page <?php echo ($revIndex > 0) ? 'page-break' : ''; ?>">
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2">
                        <img src="<?php echo $logoSrc; ?>" alt="DNSC Logo">
                    </td>
                    <td class="form-title-cell" rowspan="2" style="width:45%;">
                        CERTIFICATE OF EXEMPTION FROM REVIEW
                    </td>
                    <td style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:4px 8px;">
                        RESEARCH ETHICS COMMITTEE
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:0;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">REC Form No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">14a</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Version No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">01</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Date of Effectivity</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">June 15, 2022</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div style="margin-top:20pt; margin-bottom: 20pt; font-size:11pt; line-height:1.5;">
                <div style="margin-bottom: 15pt; text-align: right;">
                    Date: <strong><?php echo date('F d, Y', strtotime($decision['decision_date'] ?? date('Y-m-d'))); ?></strong>
                </div>

                <div style="margin-bottom: 15pt;">
                    <div style="font-weight:bold;"><?php echo strtoupper($protocol['project_leader']); ?></div>
                    <div><?php echo htmlspecialchars($protocol['institution'] ?: 'Davao del Norte State College'); ?></div>
                </div>

                <div style="margin-bottom: 15pt;">
                    <strong>RE:</strong> "<?php echo htmlspecialchars($protocol['title']); ?>"<br>
                    <strong>REC Code:</strong> <?php echo htmlspecialchars($protocol['rec_code']); ?>
                </div>

                <div style="margin-bottom: 20pt; font-weight:bold; text-align:center; font-size: 13pt; text-decoration: underline;">
                    CERTIFICATE OF EXEMPTION FROM REVIEW
                </div>

                <div style="margin-bottom: 15pt;">
                    Dear <?php 
                    $parts = explode(',', $protocol['project_leader']);
                    echo trim($parts[0]);
                    ?>,
                </div>

                <p style="margin-bottom:15pt; text-align:justify;">
                    This is to acknowledge submission of the following documents (include version numbers and dates):
                </p>
                <ul style="margin-bottom:15pt; padding-left:40pt; list-style-type: disc;">
                    <?php
                    $stmtFiles = $pdo->prepare("SELECT file_name, created_at FROM protocol_files WHERE protocol_id = ?");
                    $stmtFiles->execute([$protocol_id]);
                    $files = $stmtFiles->fetchAll();
                    if (count($files) > 0) {
                        foreach ($files as $file) {
                            echo '<li style="margin-bottom:5pt;">' . htmlspecialchars($file['file_name']) . ' (Submitted: ' . date('M d, Y', strtotime($file['created_at'])) . ')</li>';
                        }
                    } else {
                        echo '<li>No specific documents recorded.</li>';
                    }
                    ?>
                </ul>

                <p style="margin-bottom:15pt; text-align:justify;">
                    After a preliminary review of the submitted documents, the Research Ethics Committee deemed it appropriate that the above proposal be <strong>EXEMPTED FROM REVIEW</strong>.
                </p>

                <p style="margin-bottom:15pt; text-align:justify;">
                    This means that the study may be implemented without undergoing an expedited or full review. Neither will the proponents be required to submit further documents to the committee as long as there is no amendment nor alteration in the protocol that will change the nature of the study nor the level of risk involved.
                </p>

                <p style="margin-bottom:20pt; text-align:justify;">
                    Notwithstanding this exemption, the proponent is required to submit a Final Report upon completion of the study.
                </p>

                <div style="margin-top: 40pt;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:50%;">
                                Very truly yours,
                            </td>
                            <td style="width:50%; text-align:center;">
                                <?php echo renderSignature($decision_chair_sig, '160px'); ?>
                                <div style="width:260px; border-top:1.5pt solid #000; margin:0 auto; padding-top:3pt;">
                                    <strong style="font-size:11pt; letter-spacing:0.5px;"><?php echo strtoupper($decision_chair); ?></strong><br>
                                    <span style="font-size:9pt; color:#000;">DNSC-REC Chair</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php $revIndex++; ?>
    <?php endif; ?>

    <?php if (in_array($protocol['review_type'], ['expedited', 'full_board']) && in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
        <!-- ══════════════════════════════════════════════════════════════
             REC FORM 16 — APPROVAL LETTER TO THE STUDY PROTOCOL
        ════════════════════════════════════════════════════════════════ -->
        <div class="form-page <?php echo ($revIndex > 0) ? 'page-break' : ''; ?>">
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2">
                        <img src="<?php echo $logoSrc; ?>" alt="DNSC Logo">
                    </td>
                    <td class="form-title-cell" rowspan="2" style="width:45%;">
                        APPROVAL LETTER TO THE STUDY PROTOCOL
                    </td>
                    <td style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:4px 8px;">
                        RESEARCH ETHICS COMMITTEE
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:0;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">REC Form No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">16</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Version No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">01</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Date of Effectivity</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">June 15, 2022</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div style="margin-top:20pt; margin-bottom: 20pt; font-size:11pt; line-height:1.5;">
                <div style="margin-bottom: 20pt; text-align: right;">
                    Date: <span style="text-decoration: underline;">&nbsp;&nbsp;<?php echo date('F d, Y', strtotime($decision['decision_date'] ?? date('Y-m-d'))); ?>&nbsp;&nbsp;</span>
                </div>

                <p style="margin-bottom:20pt; text-align:justify;">
                    This is to certify that the following protocol and related documents have been reviewed and is hereby granted approval by the DNSC Research Ethics Committee for implementation.
                </p>

                <table style="width:100%; border-collapse:collapse; margin-bottom:20pt; font-size:10pt;">
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:30%;">REC CODE</td>
                        <td style="border:1px solid #000; padding:8px; width:20%;"><?php echo htmlspecialchars($protocol['rec_code']); ?></td>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:30%;">PROTOCOL NUMBER</td>
                        <td style="border:1px solid #000; padding:8px; width:20%;"><?php echo htmlspecialchars($protocol['tracking_code'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold;">PROTOCOL TITLE</td>
                        <td colspan="3" style="border:1px solid #000; padding:8px; font-weight:bold;"><?php echo strtoupper($protocol['title']); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold;">NAME</td>
                        <td style="border:1px solid #000; padding:8px;"><?php echo strtoupper($protocol['project_leader']); ?></td>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold;">CONTACT NUMBER</td>
                        <td style="border:1px solid #000; padding:8px;"></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold;">DURATION OF APPROVAL</td>
                        <td colspan="3" style="border:1px solid #000; padding:8px;">
                            <?php
                            $approval_start = date('F d, Y', strtotime($decision['decision_date'] ?? date('Y-m-d')));
                            $approval_end = date('F d, Y', strtotime('+1 year', strtotime($decision['decision_date'] ?? date('Y-m-d'))));
                            ?>
                            From <strong><?php echo $approval_start; ?></strong> to <strong><?php echo $approval_end; ?></strong>
                        </td>
                    </tr>
                </table>

                <table style="width:100%; border-collapse:collapse; margin-bottom:20pt; font-size:10pt; text-align:center;">
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%;">DNSC REC CHAIR</td>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%;">SIGNATURE</td>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%;">DATE</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; padding:20px 8px; vertical-align:bottom; position:relative;">
                            <?php echo renderSignature($decision_chair_sig, '120px'); ?>
                            <strong><?php echo strtoupper($decision_chair); ?></strong>
                        </td>
                        <td style="border:1px solid #000; padding:20px 8px;"></td>
                        <td style="border:1px solid #000; padding:20px 8px;"></td>
                    </tr>
                </table>

                <table style="width:100%; border-collapse:collapse; font-size:10pt;">
                    <tr>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:35%;">Received by (Signature over printed name)</td>
                        <td style="border:1px solid #000; padding:8px; width:30%;"></td>
                        <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:15%;">Date Received</td>
                        <td style="border:1px solid #000; padding:8px; width:20%;"></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php $revIndex++; ?>
    <?php endif; ?>

    <?php if (in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
        <!-- ══════════════════════════════════════════════════════════════
             REC FORM 25 — ETHICAL CLEARANCE CERTIFICATE
        ════════════════════════════════════════════════════════════════ -->
        <div class="form-page <?php echo ($revIndex > 0) ? 'page-break' : ''; ?>">
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2">
                        <img src="<?php echo $logoSrc; ?>" alt="DNSC Logo">
                    </td>
                    <td class="form-title-cell" rowspan="2" style="width:45%;">
                        ETHICAL CLEARANCE CERTIFICATE
                    </td>
                    <td style="font-weight:bold; font-size:10pt; border:1px solid #000; padding:4px 8px;">
                        RESEARCH ETHICS COMMITTEE
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:0;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">REC Form No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">25</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Version No.</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">01</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid #000; padding:3px 6px; font-size:9.5pt;">Date of Effectivity</td>
                                <td style="border:1px solid #000; padding:3px 6px; font-weight:bold;">June 15, 2022</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <h2 style="text-align:center; font-weight:bold; margin-top:20pt; margin-bottom:30pt; text-decoration:underline;">ETHICAL CLEARANCE CERTIFICATE</h2>

            <p style="margin-bottom:15pt; line-height:1.6;">This is to certify that the research protocol entitled:</p>
            
            <div style="background:#f8f9fa; padding:15pt; border:1px solid #000; margin-bottom:20pt; text-align:center;">
                <h4 style="font-weight:bold; font-style:italic; margin:0;">"<?php echo htmlspecialchars($protocol['title']); ?>"</h4>
            </div>

            <div style="margin-bottom:30pt; line-height:1.8;">
                <p>Submitted by: <strong><?php echo htmlspecialchars($protocol['project_leader']); ?></strong></p>
                <p>Institution: <strong><?php echo htmlspecialchars($protocol['institution'] ?: 'Davao del Norte State College'); ?></strong></p>
                <p>REC Code: <strong><?php echo htmlspecialchars($protocol['rec_code']); ?></strong></p>
                <p>Status: <strong>APPROVED</strong></p>
                <p>Date Issued: <strong><?php echo date('F d, Y', strtotime($decision['decision_date'] ?? date('Y-m-d'))); ?></strong></p>
            </div>

            <p style="text-indent:30pt; margin-bottom:40pt; line-height:1.6; text-align:justify;">
                Has undergone a thorough ethical review by the Davao del Norte State College Research Ethics Committee (DNSC-REC) and is found to be compliant with national and international ethical standards for research involving human participants.
            </p>

            <div style="margin-top:60pt;">
                <table style="width:100%;">
                    <tr>
                        <td style="width:50%; text-align:center;">
                            <?php echo renderSignature($secretariat_sig, '140px'); ?>
                            <div style="width:220px; border-top:1.5pt solid #000; margin:0 auto; padding-top:3pt;">
                                <strong style="font-size:11pt; letter-spacing:0.5px;"><?php echo strtoupper($secretariat_name); ?></strong><br>
                                <span style="font-size:9pt; color:#000;">REC Staff</span>
                            </div>
                        </td>
                        <td style="width:50%; text-align:center;">
                            <?php echo renderSignature($decision_chair_sig, '160px'); ?>
                            <div style="width:260px; border-top:1.5pt solid #000; margin:0 auto; padding-top:3pt;">
                                <strong style="font-size:11pt; letter-spacing:0.5px;"><?php echo strtoupper($decision_chair); ?></strong><br>
                                <span style="font-size:9pt; color:#000;">REC Chairperson</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top:50pt; font-size:9pt; color:#666; border-top:1px dashed #000; padding-top:10pt;">
                <p>Note: This clearance is valid for one (1) year from the date of issuance. The proponent is required to submit a <strong>Progress Report</strong> mid-way and a <strong>Final Report</strong> upon completion of the study.</p>
            </div>
        </div>
        <?php $revIndex++; ?>
    <?php endif; ?>

</body>

</html>
