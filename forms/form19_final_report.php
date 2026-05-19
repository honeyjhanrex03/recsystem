<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['public'])) {
    require_once '../includes/auth_check.php';
    checkAuth(['rec_chair', 'admin', 'rec_staff', 'rec_member']);
}
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) die("No Protocol ID");

$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmt->execute([$protocol_id]);
$p = $stmt->fetch();

if (!$p) die("Protocol not found.");

// Fetch Author details
$stmtA = $pdo->prepare("SELECT signature FROM users WHERE email = ? LIMIT 1");
$stmtA->execute([$p['author_email']]);
$author_user = $stmtA->fetch();
$author_sig = $author_user ? $author_user['signature'] : null;

// Handle AJAX Save Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save_report'])) {
    header('Content-Type: application/json');
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
        $author_id = $_SESSION['user_id'] ?? 22; // default/fallback to current testing user
        $stmtIns = $pdo->prepare("INSERT INTO form19_responses 
            (protocol_id, author_id, ethical_clearance_start, ethical_clearance_end, start_date, end_date, enrolled_participants, required_participants, withdrawn_participants, withdrawal_reason, deviations, issues_encountered, summary_findings, conclusions, dissemination_actions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$protocol_id, $author_id, $clearance_start, $clearance_end, $start_date, $end_date, $enrolled, $required, $withdrawn, $reason, $deviations, $issues, $findings, $conclusions, $dissemination]);
    }
    
    // Log action in audit log
    $actionMsg = "Lead Researcher " . $p['project_leader'] . " saved/updated Final Report (REC Form 19) directly from the printable form editor.";
    $stmtLog = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action, timestamp) VALUES (?, ?, ?, NOW())");
    $stmtLog->execute([$_SESSION['user_id'] ?? 22, $protocol_id, $actionMsg]);

    echo json_encode(['success' => true]);
    exit();
}

// Fetch Final Report answers (Form 19) if submitted
$stmtRep = $pdo->prepare("SELECT * FROM form19_responses WHERE protocol_id = ? LIMIT 1");
$stmtRep->execute([$protocol_id]);
$rep = $stmtRep->fetch();

$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 19 - FINAL REPORT FORM</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="apple-touch-icon" href="../assets/images/logo.png?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; color: #000; line-height: 1.2; margin: 0; padding: 0; background-color: #f0f2f5; }
        .page { width: 210mm; min-height: 297mm; padding: 15mm 20mm; margin: 30px auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.15); box-sizing: border-box; position: relative; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        .logo-box { width: 80px; text-align: center; }
        .title-box { text-align: center; font-weight: bold; font-size: 11pt; }
        .form-info-box table { width: 100%; border-collapse: collapse; }
        .form-info-box td { border: 1px solid black; padding: 2px 5px; font-size: 8pt; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .main-table td { border: 1px solid black; padding: 5px 8px; vertical-align: top; font-size: 9.5pt; }
        .gray-bg { background: #dfdfdf; font-weight: bold; }
        .label-cell { font-weight: normal; width: 180px; }
        .sig-section { margin-top: 40px; }
        .sig-line { border-bottom: 2px solid black; width: 250px; display: inline-block; }
        [contenteditable="true"] {
            outline: none;
            background-color: rgba(254, 243, 199, 0.35);
            border-bottom: 1px dashed #cbd5e1;
            min-height: 1.5em;
            display: block;
            margin-top: 5px;
            padding: 3px 6px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        [contenteditable="true"]:focus {
            background-color: rgba(254, 243, 199, 0.85);
            border-bottom: 1px solid #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.15);
        }
        @media print {
            .no-print { display: none !important; }
            body { background: none; font-size: 8.5pt; line-height: 1.15; }
            .page { margin: 0; box-shadow: none; width: 100%; padding: 8mm 12mm !important; min-height: auto; }
            .main-table { margin-top: 8px; }
            .main-table td { padding: 3px 5px !important; font-size: 8.5pt !important; }
            .header-table { margin-bottom: 8px; }
            .sig-section { margin-top: 15px !important; }
            .sig-section div div { margin-top: 35px !important; }
            [contenteditable="true"] {
                background-color: transparent !important;
                border-bottom: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin-top: 2px !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 12px; align-items: center;">
    <button onclick="saveReportData()" style="padding: 12px 25px; background: #22c55e; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(34,197,94,0.35); display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-save"></i> SAVE REPORT
    </button>
    <button onclick="window.print()" style="padding: 12px 25px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-print"></i> PRINT FORM
    </button>
</div>

<div class="page">

<!-- HEADER GRID -->
<table class="header-table">
    <tr>
        <td rowspan="2" class="logo-box">
            <img src="<?php echo $logoSrc; ?>" width="80">
        </td>
        <td class="title-box">
            RESEARCH ETHICS COMMITTEE
        </td>
        <td class="form-info-box">
            <table>
                <tr>
                    <td width="55%">REC Form No.</td>
                    <td class="text-center"><strong>19</strong></td>
                </tr>
                <tr>
                    <td>Version No.</td>
                    <td class="text-center"><strong>01</strong></td>
                </tr>
                <tr>
                    <td>Date of Effectivity</td>
                    <td class="text-center">June 15, 2022</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="text-center bold" style="padding: 8px; font-weight: bold; font-size: 11pt;">
            FINAL REPORT FORM
        </td>
    </tr>
</table>

<!-- MAIN TABLE -->
<table class="main-table">
    <tr>
        <td colspan="4" class="gray-bg">General Information</td>
    </tr>
    <tr>
        <td class="label-cell">*Title of Study</td>
        <td colspan="3"><strong>"<?php echo strtoupper($p['title']); ?>"</strong></td>
    </tr>
    <tr>
        <td class="label-cell">Version number/date of the EC approved protocol</td>
        <td colspan="3">001 / <?php echo date('F d, Y', strtotime($p['created_at'])); ?></td>
    </tr>
    <tr>
        <td class="label-cell">REC Code (To be provided by REC)</td>
        <td width="150"><?php echo htmlspecialchars($p['rec_code']); ?></td>
        <td width="120" style="font-weight: normal;">*Study Site</td>
        <td width="200">&nbsp;</td>
    </tr>
    <tr>
        <td class="label-cell">*Name of Researcher</td>
        <td><?php echo strtoupper($p['project_leader']); ?></td>
        <td rowspan="2">Contact Information</td>
        <td class="small" style="padding: 0;">
            <table width="100%" style="border-collapse: collapse;">
                <tr><td style="border: none; border-bottom: 1px solid black;">*Tel No:</td></tr>
                <tr><td style="border: none; border-bottom: 1px solid black;">*Mobile No:</td></tr>
                <tr><td style="border: none; border-bottom: 1px solid black;">Fax No:</td></tr>
                <tr><td style="border: none;">*Email:</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="label-cell">*Co-researcher/s (if any)</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="label-cell">*Institution of researcher</td>
        <td><?php echo htmlspecialchars($p['institution'] ?: 'Davao del Norte State College'); ?></td>
    </tr>
    <tr>
        <td class="label-cell">*Address of Institution</td>
        <td>New Visayas, Panabo City, Davao del Norte</td>
    </tr>
    <tr>
        <td class="label-cell">Effective period of ethical clearance</td>
        <td colspan="3">
            From: <span id="cell_clearance_start" contenteditable="true" style="display:inline-block; min-width:120px; margin-top:0; border-bottom:1px solid black;"><?php echo htmlspecialchars($rep['ethical_clearance_start'] ?? ''); ?></span> 
            To: <span id="cell_clearance_end" contenteditable="true" style="display:inline-block; min-width:120px; margin-top:0; border-bottom:1px solid black;"><?php echo htmlspecialchars($rep['ethical_clearance_end'] ?? ''); ?></span>
        </td>
    </tr>
    <tr>
        <td colspan="4" class="gray-bg">Final Report</td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>1. Start of study</strong>
            <div id="cell_start_date" contenteditable="true"><?php echo htmlspecialchars($rep['start_date'] ?? ''); ?></div>
        </td>
        <td colspan="2">
            <strong>2. End of study</strong>
            <div id="cell_end_date" contenteditable="true"><?php echo htmlspecialchars($rep['end_date'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>3. Number of enrolled participants</strong>
            <div id="cell_enrolled" contenteditable="true"><?php echo htmlspecialchars($rep['enrolled_participants'] ?? ''); ?></div>
        </td>
        <td colspan="2">
            <strong>4. Number of required participants</strong>
            <div id="cell_required" contenteditable="true"><?php echo htmlspecialchars($rep['required_participants'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>5. Number of participants who withdrew</strong>
            <div id="cell_withdrawn" contenteditable="true"><?php echo htmlspecialchars($rep['withdrawn_participants'] ?? ''); ?></div>
        </td>
        <td colspan="2">
            <strong>Remarks / Reason for withdrawal</strong>
            <div id="cell_withdrawal_reason" contenteditable="true"><?php echo htmlspecialchars($rep['withdrawal_reason'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="2" height="80">
            <strong>6. Deviations from the approved protocol</strong>
            <div id="cell_deviations" contenteditable="true"><?php echo htmlspecialchars($rep['deviations'] ?? ''); ?></div>
        </td>
        <td colspan="2" height="80">
            <strong>7. Issues/problems encountered.</strong>
            <div id="cell_issues" contenteditable="true"><?php echo htmlspecialchars($rep['issues_encountered'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="4" height="100">
            <strong>8. Summary of findings:</strong>
            <div id="cell_findings" contenteditable="true"><?php echo htmlspecialchars($rep['summary_findings'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="4" height="100">
            <strong>9. Conclusions:</strong>
            <div id="cell_conclusions" contenteditable="true"><?php echo htmlspecialchars($rep['conclusions'] ?? ''); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="4" height="100">
            <strong>10. Actions for dissemination of study results:</strong>
            <div id="cell_dissemination" contenteditable="true"><?php echo htmlspecialchars($rep['dissemination_actions'] ?? ''); ?></div>
        </td>
    </tr>
</table>

<div class="sig-section" style="margin-top: 30px; display: flex; align-items: flex-end; gap: 40px;">
    <div>
        <strong>Signature of Researcher:</strong>
        <div style="position: relative; width: 250px; border-bottom: 2px solid black; text-align: center; margin-top: 65px; padding-bottom: 3px;">
            <?php if ($author_sig): ?>
                <img src="<?php echo BASE_URL . 'uploads/signatures/' . $author_sig; ?>" style="max-height: 75px; max-width: 180px; display: block; margin: 0 auto; position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); pointer-events: none;">
            <?php endif; ?>
            <span style="font-weight: bold; text-transform: uppercase; font-size: 9pt;"><?php echo htmlspecialchars($p['project_leader']); ?></span>
        </div>
    </div>
    <div>
        <strong>Date:</strong>
        <div style="width: 150px; border-bottom: 2px solid black; text-align: center; margin-top: 10px; padding-bottom: 3px; font-weight: bold; font-size: 9.5pt;">
            <?php echo date('F d, Y'); ?>
        </div>
    </div>
</div>
</div>

<script>
function saveReportData() {
    Swal.fire({
        title: 'Saving report details...',
        html: 'Saving final study information securely in the database.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('ajax_save_report', '1');
    formData.append('ethical_clearance_start', document.getElementById('cell_clearance_start').innerText.trim());
    formData.append('ethical_clearance_end', document.getElementById('cell_clearance_end').innerText.trim());
    formData.append('start_date', document.getElementById('cell_start_date').innerText.trim());
    formData.append('end_date', document.getElementById('cell_end_date').innerText.trim());
    formData.append('enrolled_participants', document.getElementById('cell_enrolled').innerText.trim());
    formData.append('required_participants', document.getElementById('cell_required').innerText.trim());
    formData.append('withdrawn_participants', document.getElementById('cell_withdrawn').innerText.trim());
    formData.append('withdrawal_reason', document.getElementById('cell_withdrawal_reason').innerText.trim());
    formData.append('deviations', document.getElementById('cell_deviations').innerText.trim());
    formData.append('issues_encountered', document.getElementById('cell_issues').innerText.trim());
    formData.append('summary_findings', document.getElementById('cell_findings').innerText.trim());
    formData.append('conclusions', document.getElementById('cell_conclusions').innerText.trim());
    formData.append('dissemination_actions', document.getElementById('cell_dissemination').innerText.trim());

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Report Saved!',
                text: 'Final Report (F19) data successfully synchronized to the database!',
                confirmButtonColor: '#1a2b4b'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: 'Could not synchronize final report details to database.',
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'An error occurred while saving the report details.',
            confirmButtonColor: '#ef4444'
        });
    });
}
</script>
</body>
</html>
