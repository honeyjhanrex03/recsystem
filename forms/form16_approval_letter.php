<?php
if (!isset($_GET['public'])) {
    require_once '../includes/auth_check.php';
    checkAuth(['rec_chair', 'admin', 'rec_staff']);
}
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) die("No Protocol ID");

$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmt->execute([$protocol_id]);
$protocol = $stmt->fetch();

if (!$protocol) die("Protocol not found.");

// Fetch Decision Date and Chair
$stmtD = $pdo->prepare("SELECT fd.*, u.name as chair_name, u.signature as chair_sig FROM final_decisions fd LEFT JOIN admins u ON fd.chair_id = u.admin_id WHERE fd.protocol_id = ? ORDER BY fd.decision_date DESC LIMIT 1");
$stmtD->execute([$protocol_id]);
$decision = $stmtD->fetch();

// REC Chair info
$stmtC = $pdo->prepare("SELECT name, signature FROM admins WHERE role = 'rec_chair' AND status = 'active' LIMIT 1");
$stmtC->execute();
$chair = $stmtC->fetch();
$chair_name = $chair ? $chair['name'] : "DNSC REC CHAIRPERSON";
$chair_sig = $chair ? $chair['signature'] : null;

// Use the chair who actually approved it, fallback to current
$decision_chair = ($decision && !empty($decision['chair_name'])) ? $decision['chair_name'] : $chair_name;
$decision_chair_sig = ($decision && !empty($decision['chair_sig'])) ? $decision['chair_sig'] : $chair_sig;

// Fetch Author (Lead Proponent) details from users table
$stmtA = $pdo->prepare("SELECT signature FROM users WHERE email = ? LIMIT 1");
$stmtA->execute([$protocol['author_email']]);
$author_user = $stmtA->fetch();
$author_name = $protocol['project_leader'];
$author_sig = $author_user ? $author_user['signature'] : null;

// Determine dynamic date received
$received_date = ($decision && !empty($decision['decision_date'])) ? date('F d, Y', strtotime($decision['decision_date'])) : date('F d, Y');


$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 16 - APPROVAL LETTER TO THE STUDY PROTOCOL</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="apple-touch-icon" href="../assets/images/logo.png?v=1.1">
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; color: #000; line-height: 1.2; margin: 0; padding: 0; background-color: #f0f2f5; }
        
        .form-page { 
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
        .logo-cell { width: 80px; text-align: center; }
        .logo-cell img { width: 80px; }
        .form-title-cell { text-align: center; font-weight: bold; font-size: 12pt; }
        
        @media print {
            .no-print { display: none; }
            body { background: none; }
            .form-page { margin: 0; box-shadow: none; width: 100%; height: 100%; padding: 0.5in; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="padding: 12px 30px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
        <i class="fas fa-print me-2"></i> PRINT LETTER
    </button>
</div>

<div class="form-page">
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
                <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%; vertical-align:middle;">DNSC REC CHAIR</td>
                <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%; vertical-align:middle;">SIGNATURE</td>
                <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:33%; vertical-align:middle;">DATE</td>
            </tr>
            <tr>
                <td style="border:1px solid #000; padding:15px 8px; vertical-align:middle;"><strong><?php echo strtoupper($decision_chair); ?></strong></td>
                <td style="border:1px solid #000; padding:5px 8px; vertical-align:middle; text-align:center;">
                    <?php if ($decision_chair_sig): ?>
                        <img src="<?php echo BASE_URL . 'uploads/signatures/' . $decision_chair_sig; ?>" style="max-height: 55px; max-width: 140px; display: block; margin: 0 auto; pointer-events:none;">
                    <?php else: ?>
                        &nbsp;
                    <?php endif; ?>
                </td>
                <td style="border:1px solid #000; padding:15px 8px; vertical-align:middle;">
                    <strong><?php echo date('F d, Y', strtotime($decision['decision_date'] ?? date('Y-m-d'))); ?></strong>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; font-size:10pt; text-align:center;">
            <tr>
                <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:35%; text-align:left; vertical-align:middle;">Received by (Signature over printed name)</td>
                <td style="border:1px solid #000; padding:5px 8px; width:30%; vertical-align:middle; text-align:center;">
                    <?php if ($author_sig): ?>
                        <img src="<?php echo BASE_URL . 'uploads/signatures/' . $author_sig; ?>" style="max-height: 40px; max-width: 130px; display: block; margin: 0 auto; pointer-events:none;">
                    <?php endif; ?>
                    <div style="border-top:1px solid #000; margin-top:3px; padding-top:2px; font-weight:bold; font-size:9.5pt;">
                        <?php echo strtoupper($author_name); ?>
                    </div>
                </td>
                <td style="border:1px solid #000; padding:8px; background:#f0f0f0; font-weight:bold; width:15%; text-align:left; vertical-align:middle;">Date Received</td>
                <td style="border:1px solid #000; padding:15px 8px; width:20%; vertical-align:middle;">
                    <strong><?php echo $received_date; ?></strong>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
