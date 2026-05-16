<?php
if (!isset($_GET['public'])) {
    require_once '../includes/auth_check.php';
    checkAuth(['rec_chair', 'admin', 'rec_staff']);
}
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) die("No Protocol ID");

$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name FROM protocols p LEFT JOIN users u ON p.created_by = u.user_id WHERE p.protocol_id = ?");
$stmt->execute([$protocol_id]);
$p = $stmt->fetch();

if ($p) {
    $p['p_name'] = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
}

if (!$p) die("Protocol not found.");

// Fetch Reviewers for this protocol
$stmtR = $pdo->prepare("SELECT a.name FROM reviewer_assignments ra JOIN admins a ON ra.reviewer_id = a.admin_id WHERE ra.protocol_id = ?");
$stmtR->execute([$protocol_id]);
$reviewers = $stmtR->fetchAll(PDO::FETCH_COLUMN);
$reviewer_list = implode(', ', $reviewers);

$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';

// Fetch REC Chair Name
$stmtC = $pdo->prepare("SELECT name FROM admins WHERE role = 'rec_chair' AND status = 'active' LIMIT 1");
$stmtC->execute();
$chair = $stmtC->fetch();
$chair_name = $chair ? $chair['name'] : "DNSC REC CHAIRPERSON"; // Fallback to institutional role
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 09 - EVALUATION FORM EXPEDITED REVIEW</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; color: #000; line-height: 1.2; margin: 0; padding: 0; background-color: #f0f2f5; }
        
        .page { 
            width: 210mm; 
            min-height: 297mm; 
            padding: 15mm 20mm; 
            margin: 30px auto; 
            background: white; 
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            box-sizing: border-box;
            position: relative;
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        .logo-box { width: 80px; text-align: center; }
        .title-box { text-align: center; font-weight: bold; font-size: 12pt; }
        
        .form-info-box table { width: 100%; border-collapse: collapse; }
        .form-info-box td { border: 1px solid black; padding: 2px 5px; font-size: 8pt; }
        
        .section-label { font-weight: bold; margin-top: 20px; margin-bottom: 5px; font-size: 10.5pt; text-transform: uppercase; color: #1a2b4b; border-bottom: 2px solid #1a2b4b; display: inline-block; padding-bottom: 2px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table td { border: 1px solid black; padding: 6px 10px; vertical-align: top; font-size: 9.5pt; }
        .label-col { width: 180px; font-weight: bold; background: #f8f9fa; }
        
        .footer-sig { margin-top: 50px; font-size: 10.5pt; }
        .sig-line { border-bottom: 2px solid black; width: 250px; margin-bottom: 5px; }
        
        .page-num-footer { position: absolute; bottom: 15mm; right: 20mm; color: #aaa; font-size: 9pt; }
        
        @media print {
            .no-print { display: none; }
            body { background: none; }
            .page { margin: 0; box-shadow: none; width: 100%; height: 100%; padding: 0.5in; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="padding: 12px 30px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; shadow: 0 4px 12px rgba(0,0,0,0.25);">
        <i class="fas fa-print me-2"></i> PRINT FORM
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
            RESEARCH ETHICS<br>COMMITTEE
        </td>
        <td class="form-info-box">
            <table>
                <tr>
                    <td width="55%">REC Form No.</td>
                    <td class="text-center"><strong>09</strong></td>
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
        <td colspan="2" class="title-box" style="font-size: 12pt; padding: 10px;">
            EVALUATION FORM EXPEDITED REVIEW
        </td>
    </tr>
</table>

<?php
// Fetch interactive answers
$stmtI = $pdo->prepare("SELECT * FROM form9_answers WHERE protocol_id = ?");
$stmtI->execute([$protocol_id]);
$ansMap = [];
while($r = $stmtI->fetch()) {
    $ansMap[$r['section']] = $r['decision'];
}
?>

<!-- SECTION 1: MINOR RISK -->
<div class="section-label">NEW RESEARCH STUDY (Minor Risk)</div>
<table class="data-table">
    <tr>
        <td class="label-col">Research Study Code</td>
        <td><?php echo htmlspecialchars($p['rec_code']); ?></td>
    </tr>
    <tr>
        <td class="label-col">Research Study Submission Date</td>
        <td><?php echo date('F d, Y', strtotime($p['created_at'])); ?></td>
    </tr>
    <tr>
        <td class="label-col">Research Study Title</td>
        <td style="font-style: italic;"><?php echo htmlspecialchars($p['title']); ?></td>
    </tr>
    <tr>
        <td class="label-col">Principal Investigator</td>
        <td><?php echo htmlspecialchars($p['project_leader']); ?></td>
    </tr>
    <tr>
        <td class="label-col">Primary Reviewer/s</td>
        <td><?php echo htmlspecialchars($reviewer_list); ?></td>
    </tr>
    <tr>
        <td class="label-col">Decision</td>
        <td><strong><?php echo $ansMap["NEW RESEARCH STUDY (Minor Risk)"] ?? 'Approval'; ?></strong></td>
    </tr>
</table>

<!-- SECTION 2: MINOR REVISION -->
<div class="section-label">NEW RESEARCH STUDY (Minor Revision)</div>
<table class="data-table">
    <tr>
        <td class="label-col">Research Study Code</td>
        <td><?php echo htmlspecialchars($p['rec_code']); ?></td>
    </tr>
    <tr>
        <td class="label-col">Research Study Submission Date</td>
        <td><?php echo date('F d, Y', strtotime($p['created_at'])); ?></td>
    </tr>
    <tr>
        <td class="label-col">Decision</td>
        <td><strong><?php echo $ansMap["NEW RESEARCH STUDY (Minor Revision)"] ?? 'N/A'; ?></strong></td>
    </tr>
</table>

<!-- SECTION 3: AMMENDMENTS -->
<div class="section-label">NEW RESEARCH STUDY (Amendments)</div>
<table class="data-table">
    <tr>
        <td class="label-col">Research Study Code</td>
        <td><?php echo htmlspecialchars($p['rec_code']); ?></td>
    </tr>
    <tr>
        <td class="label-col">Decision</td>
        <td><strong><?php echo $ansMap["NEW RESEARCH STUDY (Amendments)"] ?? 'N/A'; ?></strong></td>
    </tr>
</table>

<div class="footer-sig">
    <div class="sig-line"></div>
    <strong>REC Chair</strong>
</div>

<div class="page-number">
    128
</div>

</body>
</html>
