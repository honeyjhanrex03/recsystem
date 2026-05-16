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
    <title>REC FORM 15 - RESUBMISSION FORM</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 10pt; color: #000; line-height: 1.1; margin: 0; padding: 0; }
        .page { width: 210mm; min-height: 297mm; padding: 0.5in; box-sizing: border-box; position: relative; background: white; margin: 0 auto; page-break-after: always; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        .logo-box { width: 80px; text-align: center; }
        .title-box { text-align: center; font-weight: bold; font-size: 12pt; }
        .control-table { width: 100%; border-collapse: collapse; }
        .control-table td { border: 1px solid black; padding: 2px 5px; font-size: 8pt; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { border: 1px solid black; padding: 4px 8px; vertical-align: middle; }
        .label-cell { background: #fafafa; font-weight: bold; width: 140px; }
        .section-header { background: #f0f0f0; font-weight: bold; padding: 5px 10px; border: 1px solid black; margin-top: 15px; }

        .revisions-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .revisions-table th, .revisions-table td { border: 1px solid black; padding: 5px 8px; vertical-align: top; }
        .revisions-table th { background: #fafafa; }
        
        .sig-section { margin-top: 40px; }
        .sig-line { border-bottom: 2px solid black; width: 250px; display: inline-block; }
        
        .footer-num { position: absolute; bottom: 0.4in; right: 0.5in; color: #999; font-size: 10pt; }
        
        @media print {
            .no-print { display: none; }
            .page { padding: 0.5in; margin: 0; border: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: right; padding: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #1a2b4b; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        PRINT FORM
    </button>
</div>

<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 12px 25px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <i class="fas fa-print me-2"></i> PRINT FORM
    </button>
</div>

<!-- SINGLE FLUID CONTAINER -->
<div class="page-container" style="width: 210mm; margin: 0 auto; padding: 0.5in; background: white; box-sizing: border-box;">
    
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td rowspan="2" class="logo-box">
                <img src="<?php echo $logoSrc; ?>" width="60">
            </td>
            <td class="title-box">
                DAVAO DEL NORTE STATE COLLEGE<br>
                <small style="font-size: 10pt;">RESEARCH ETHICS COMMITTEE</small>
            </td>
            <td width="200">
                <table class="control-table">
                    <tr>
                        <td width="55%">REC Form No.</td>
                        <td class="text-center"><strong>15</strong></td>
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
            <td colspan="2" class="text-center bold" style="padding: 8px; font-weight: bold; font-size: 13pt; background: #f8f9fa;">
                RESUBMISSION FORM
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; border: 1px solid black; padding: 6px 10px; border-bottom: none; background: #eee; margin-top: 15px;">I. General Information</div>
    <table class="info-table" style="margin-top: 0;">
        <tr>
            <td class="label-cell" width="160">*Title of Study</td>
            <td colspan="3"><strong style="font-size: 11pt;"><?php echo htmlspecialchars($p['title']); ?></strong></td>
        </tr>
        <tr>
            <td class="label-cell">Version number/date</td>
            <td colspan="3">Version no. 01 / <?php echo date('F d, Y', strtotime($p['created_at'])); ?></td>
        </tr>
        <tr>
            <td class="label-cell">REC Code</td>
            <td width="150" class="text-center fw-bold"><?php echo $p['rec_code']; ?></td>
            <td class="label-cell" width="100">*Study Site</td>
            <td class="text-center"><?php echo htmlspecialchars($p['institution'] ?: 'DNSC'); ?></td>
        </tr>
        <tr>
            <td class="label-cell">*Principal Researcher</td>
            <td class="text-center fw-bold"><?php echo strtoupper($p['project_leader']); ?></td>
            <td class="label-cell" rowspan="4">Contact Information</td>
            <td class="small" style="padding: 0;">
                <table width="100%" height="100" style="border-collapse: collapse;">
                    <tr><td style="border: none; border-bottom: 1px solid black; height: 25px;">*Tel No:</td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid black; height: 25px;">*Mobile No:</td></tr>
                    <tr><td style="border: none; border-bottom: 1px solid black; height: 25px;">Fax No:</td></tr>
                    <tr><td style="border: none; height: 25px;">*Email: <?php echo $p['author_email']; ?></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label-cell">*Co-researcher/s (if any)</td>
            <td class="text-center">—</td>
        </tr>
        <tr>
            <td class="label-cell">*Institution of researcher</td>
            <td class="text-center"><?php echo htmlspecialchars($p['institution'] ?: 'Davao del Norte State College'); ?></td>
        </tr>
        <tr>
            <td class="label-cell">*Address of Institution</td>
            <td class="text-center">New Visayas, Panabo City, Davao del Norte</td>
        </tr>
    </table>

    <div style="font-weight: bold; border: 1px solid black; padding: 6px 10px; border-bottom: none; background: #eee; margin-top: 25px;">II. Reviewer Recommendations and Committee's Responses</div>
    <table class="revisions-table" style="margin-top: 0;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th width="35%">REC Recommendation</th>
                <th width="45%">Response of Researcher</th>
                <th width="20%">Section/Page Number</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmtRes = $pdo->prepare("SELECT * FROM form15_responses WHERE protocol_id = ?");
            $stmtRes->execute([$protocol_id]);
            $responses = $stmtRes->fetchAll();
            
            // Show all responses, with a minimum of 8 empty rows if fewer
            $minRows = 8;
            $totalCount = max(count($responses), $minRows);
            
            for($i=0; $i<$totalCount; $i++): 
                $res = $responses[$i] ?? null;
            ?>
            <tr style="page-break-inside: avoid;">
                <td style="min-height: 40px; padding: 10px; font-size: 9.5pt;"><?php echo nl2br(htmlspecialchars($res['rec_recommendation'] ?? '')); ?></td>
                <td style="padding: 10px; font-size: 9.5pt;"><?php echo nl2br(htmlspecialchars($res['author_response'] ?? '')); ?></td>
                <td style="padding: 10px; font-size: 9.5pt; text-align: center;"><?php echo htmlspecialchars($res['page_reference'] ?? ''); ?></td>
            </tr>
            <?php endfor; ?>
            
            <!-- Optional "OTHERS" row if relevant -->
            <tr style="page-break-inside: avoid;">
                <td class="label-cell">OTHERS / ADDITIONAL REMARKS</td>
                <td colspan="2" style="padding: 15px; font-size: 9pt; color: #666;">
                    <em>(Committee may use this space for other clarifications or updates not covered above)</em>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURE SECTION - Attached to flow -->
    <div class="sig-section" style="margin-top: 50px; page-break-inside: avoid;">
        <p style="margin-bottom: 40px;">I hereby certify that the above responses accurately reflect the revisions made to the research protocol based on the committee's recommendations.</p>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="text-align: center;">
                <div class="sig-line" style="width: 300px;"></div><br>
                <strong style="text-transform: uppercase;"><?php echo $p['project_leader']; ?></strong><br>
                <small>Signature over Printed Name of Principal Researcher</small>
            </div>
            
            <div style="text-align: center;">
                <div class="sig-line" style="width: 180px;"></div><br>
                <strong><?php echo date('F d, Y'); ?></strong><br>
                <small>Date Submitted</small>
            </div>
        </div>
    </div>

    <!-- CLEAN FOOTER -->
    <div style="margin-top: 50px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 8pt; color: #999; text-align: center;">
        DNSC Research Ethics Committee - Form 15: Resubmission Form (2022) | Page 1 of 1
    </div>
</div>

<style>
    @media print {
        @page { margin: 0; size: A4 portrait; }
        body { margin: 0; padding: 0; background: white; }
        .no-print { display: none !important; }
        .page-container { margin: 0 !important; width: 100% !important; padding: 0.5in !important; }
        .revisions-table { page-break-after: auto; }
        tr { page-break-inside: avoid !important; }
        thead { display: table-header-group; }
    }
</style>

</body>
</html>
