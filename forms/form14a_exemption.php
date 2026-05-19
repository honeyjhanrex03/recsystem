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


$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 14a - CERTIFICATE OF EXEMPTION FROM REVIEW</title>
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
        <i class="fas fa-print me-2"></i> PRINT CERTIFICATE
    </button>
</div>

<div class="form-page">
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
                    <td style="width:50%; text-align:center; position:relative; vertical-align:bottom;">
                        <div style="position:relative; display:inline-block;">
                            <?php if ($decision_chair_sig): ?>
                                <img src="<?php echo BASE_URL . 'uploads/signatures/' . $decision_chair_sig; ?>" style="position:absolute; bottom:25px; left:50%; transform:translateX(-50%); max-height: 60px; max-width: 140px; pointer-events:none; z-index:10;">
                            <?php endif; ?>
                            <div style="width:250px; border-top:1px solid #000; margin:0 auto; padding-top:5pt;">
                                <strong><?php echo strtoupper($decision_chair); ?></strong><br>
                                <small>DNSC-REC Chair</small>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>
