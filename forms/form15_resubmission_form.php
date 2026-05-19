<?php
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

$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 15 - RESUBMISSION FORM</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="apple-touch-icon" href="../assets/images/logo.png?v=1.1">
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; color: #000; line-height: 1.2; margin: 0; padding: 0; }
        
        .page-container { width: 210mm; margin: 0 auto; padding: 0.5in; background: white; box-sizing: border-box; min-height: 297mm; position: relative; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0px; }
        .header-table td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        .logo-box { width: 80px; text-align: center; }
        .title-box { text-align: center; font-weight: bold; font-size: 11pt; }
        
        .metadata-table { width: 100%; border-collapse: collapse; }
        .metadata-table td { border: 1px solid black; padding: 2px 5px; font-size: 9pt; height: 20px; }
        
        .section-title { background: #d9d9d9; font-weight: bold; padding: 4px 10px; border: 1px solid black; border-bottom: none; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { border: 1px solid black; padding: 4px 8px; vertical-align: top; font-size: 9pt; }
        .label-cell { width: 150px; }
        
        .revisions-table { width: 100%; border-collapse: collapse; }
        .revisions-table th, .revisions-table td { border: 1px solid black; padding: 8px; vertical-align: top; font-size: 9pt; }
        .revisions-table th { text-align: center; font-weight: normal; height: 30px; }
        
        .sig-section { margin-top: 40px; font-size: 10pt; }
        .sig-line { border-bottom: 2px solid black; width: 250px; display: inline-block; margin-bottom: 5px; }
        
        .footer-page-num { position: absolute; bottom: 0.5in; right: 0.5in; font-size: 10pt; }

        [contenteditable="true"] {
            outline: none;
            background-color: rgba(254, 243, 199, 0.3);
            border-radius: 4px;
            transition: all 0.2s;
            padding: 2px;
        }
        [contenteditable="true"]:focus {
            background-color: rgba(254, 243, 199, 0.85);
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.15);
        }
        
        @media print {
            .no-print { display: none !important; }
            [contenteditable="true"] {
                background-color: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .page-container { margin: 0 !important; width: 100% !important; padding: 0.5in !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 12px 25px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        PRINT FORM
    </button>
</div>

<div class="page-container">
    
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td rowspan="2" class="logo-box">
                <img src="<?php echo $logoSrc; ?>" width="65">
            </td>
            <td class="title-box" style="height: 40px;">
                RESEARCH ETHICS COMMITTEE
            </td>
            <td width="220" style="padding: 0;">
                <table class="metadata-table" style="border: none;">
                    <tr>
                        <td style="border: none; border-right: 1px solid black; border-bottom: 1px solid black;" width="60%">REC Form No.</td>
                        <td style="border: none; border-bottom: 1px solid black; text-align: center; font-weight: bold;">15</td>
                    </tr>
                    <tr>
                        <td style="border: none; border-right: 1px solid black; border-bottom: 1px solid black;">Version No.</td>
                        <td style="border: none; border-bottom: 1px solid black; text-align: center; font-weight: bold;">01</td>
                    </tr>
                    <tr>
                        <td style="border: none; border-right: 1px solid black;">Date of Effectivity</td>
                        <td style="border: none; text-align: center;">June 15, 2022</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="title-box" style="height: 40px; letter-spacing: 1px;">
                RESUBMISSION FORM
            </td>
            <td style="background: #fff;"></td>
        </tr>
    </table>

    <!-- GENERAL INFORMATION -->
    <div class="section-title" style="margin-top: 15px;">General Information</div>
    <table class="info-table">
        <tr>
            <td class="label-cell">*Title of Study</td>
            <td colspan="4" style="font-weight: bold;"><?php echo htmlspecialchars($p['title']); ?></td>
        </tr>
        <tr>
            <td class="label-cell">Version number/date</td>
            <td colspan="4">Version no. 01 / <?php echo date('F d, Y', strtotime($p['created_at'])); ?></td>
        </tr>
        <tr>
            <td class="label-cell">*REC Code<br><small>(To be provided by NEC)</small></td>
            <td width="150" style="font-weight: bold; text-align: center; vertical-align: middle;"><?php echo $p['rec_code']; ?></td>
            <td class="label-cell" width="100" style="vertical-align: middle;">*Study Site</td>
            <td colspan="2" style="text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($p['institution'] ?: 'DNSC'); ?></td>
        </tr>
        <tr>
            <td class="label-cell">*Name of Researcher</td>
            <td style="font-weight: bold; text-align: center; vertical-align: middle;"><?php echo strtoupper($p['project_leader']); ?></td>
            <td class="label-cell" rowspan="2" style="text-align: center; vertical-align: middle;">Contact Information</td>
            <td colspan="2" style="padding: 0;">
                <table width="100%" style="border-collapse: collapse;">
                    <tr><td style="border: none; border-bottom: 1px solid black; padding: 2px 5px;">*Tel No:</td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid black; padding: 2px 5px;">*Mobile No:</td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid black; padding: 2px 5px;">Fax No:</td></tr>
                    <tr><td style="border: none; padding: 2px 5px;">*Email: <?php echo $p['author_email']; ?></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label-cell">*Co-researcher/s (if any)</td>
            <td style="text-align: center; vertical-align: middle;">—</td>
            <td colspan="2" style="border-top: none;"></td> <!-- Placeholder for Contact Information span -->
        </tr>
        <tr>
            <td class="label-cell">*Institution of researcher</td>
            <td colspan="4"><?php echo htmlspecialchars($p['institution'] ?: 'Davao del Norte State College'); ?></td>
        </tr>
        <tr>
            <td class="label-cell">*Address of Institution</td>
            <td colspan="4">New Visayas, Panabo City, Davao del Norte</td>
        </tr>
    </table>

    <!-- REVISIONS TABLE -->
    <table class="revisions-table">
        <thead>
            <tr>
                <th width="30%">REC Recommendations</th>
                <th width="30%">Response of Researcher</th>
                <th width="20%">Section and page number of revisions</th>
                <th width="20%">REC Assessment</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmtRes = $pdo->prepare("SELECT * FROM form15_responses WHERE protocol_id = ?");
            $stmtRes->execute([$protocol_id]);
            $responses = $stmtRes->fetchAll();
            
            $minRows = 8;
            $totalCount = max(count($responses), $minRows);
            
            for($i=0; $i<$totalCount; $i++): 
                $res = $responses[$i] ?? null;
            ?>
            <tr style="height: 100px;">
                <td contenteditable="true"><?php echo nl2br(htmlspecialchars($res['rec_recommendation'] ?? '')); ?></td>
                <td contenteditable="true"><?php echo nl2br(htmlspecialchars($res['author_response'] ?? '')); ?></td>
                <td contenteditable="true" style="text-align: center;"><?php echo htmlspecialchars($res['page_reference'] ?? ''); ?></td>
                <td contenteditable="true" style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($res['rec_assessment'] ?? ''); ?></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
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
                <?php echo date('F d, Y', strtotime($p['created_at'])); ?>
            </div>
        </div>
    </div>

    <div class="footer-page-num">139</div>
</div>

</body>
</html>
l>
