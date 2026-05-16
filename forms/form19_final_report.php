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

$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 19 - FINAL REPORT FORM</title>
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
        @media print {
            .no-print { display: none; }
            body { background: none; }
            .page { margin: 0; box-shadow: none; width: 100%; padding: 0.5in; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="padding: 12px 30px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
        PRINT FORM
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
        <td colspan="3">From: <span style="text-decoration: underline;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> To: <span style="text-decoration: underline;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
    </tr>
    <tr>
        <td colspan="4" class="gray-bg">Final Report</td>
    </tr>
    <tr>
        <td colspan="2">1. Start of study</td>
        <td colspan="2">2. End of study</td>
    </tr>
    <tr>
        <td colspan="2">3. Number of enrolled participants</td>
        <td colspan="2">4. Number of required participants</td>
    </tr>
    <tr>
        <td colspan="2">5. Number of participants who withdrew</td>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2">6. Deviations from the approved protocol</td>
        <td colspan="2">7. Issues/problems encountered.</td>
    </tr>
    <tr>
        <td colspan="4" height="60">8. Summary of findings:</td>
    </tr>
    <tr>
        <td colspan="4" height="60">9. Conclusions:</td>
    </tr>
    <tr>
        <td colspan="4" height="60">10. Actions for dissemination of study results:</td>
    </tr>
</table>

<div class="sig-section">
    <strong>Signature of Researcher:</strong> <div class="sig-line"></div>
    <span style="display: inline-block; width: 100px;"></span>
    <strong>Date:</strong> <div class="sig-line" style="width: 150px;"></div>
</div>
</div>

</body>
</html>
