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
$p = $stmt->fetch();

if (!$p) die("Protocol not found.");

$logoSrc = BASE_URL . 'assets/images/dnsc_logo.png';

function renderSignature(?string $sig_filename, string $width = '150px'): string {
    if (!$sig_filename) return '<div style="height:40px;"></div>';
    return '<img src="' . BASE_URL . 'uploads/signatures/' . $sig_filename . '" style="width:' . $width . '; height:auto; display:block; margin-bottom:-55px; margin-left:50px; position:relative; z-index:10; pointer-events:none; filter: multiply(0.8); opacity: 0.9;">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 13 - CHECKLIST OF SUBMITTED DOCUMENTS</title>
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
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        .logo-box { width: 80px; text-align: center; }
        .title-box { text-align: center; font-weight: bold; font-size: 11pt; }
        .form-info-box table { width: 100%; border-collapse: collapse; }
        .form-info-box td { border: 1px solid black; padding: 2px 5px; font-size: 8pt; }
        
        .study-info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .study-info-table td { border: 1px solid black; padding: 6px 8px; vertical-align: top; font-size: 9.5pt; }
        .info-label { font-size: 8.5pt; display: block; color: #555; margin-bottom: 2px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th, .data-table td { border: 1px solid black; padding: 5px 8px; vertical-align: middle; font-size: 9.5pt; }
        .data-table th { background: #f2f2f2; text-align: center; font-size: 9.5pt; }
        .text-center { text-align: center; }
        
        .sig-section { margin-top: 50px; font-size: 10pt; }
        .sig-line { border-bottom: 1.5px solid black; width: 250px; margin-top: 40px; margin-bottom: 2px; }
        
        .page-num-footer { position: absolute; bottom: 15mm; right: 20mm; color: #aaa; font-size: 9pt; }
        
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
                    <td class="text-center"><strong>13</strong></td>
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
        <td colspan="2" class="text-center fw-bold" style="padding: 10px; font-weight: bold; font-size: 12pt;">
            CHECKLIST OF SUBMITTED DOCUMENTS
        </td>
    </tr>
</table>

<!-- STUDY INFO -->
<table class="study-info-table">
    <tr>
        <td colspan="3">
            <span class="info-label">Title of Study:</span>
            <strong><?php echo htmlspecialchars($p['title']); ?></strong>
        </td>
        <td width="30%">
            <span class="info-label">Date:</span>
            <strong><?php echo date('F d, Y', strtotime($p['created_at'])); ?></strong>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <span class="info-label">Name of Project Leader:</span>
            <strong><?php echo htmlspecialchars($p['project_leader']); ?></strong>
        </td>
        <td>&nbsp;</td>
    </tr>
</table>

<!-- DOCUMENTS TABLE -->
<table class="data-table">
    <thead>
        <tr>
            <th rowspan="2" width="40"></th>
            <th rowspan="2">Documents</th>
            <th colspan="2">Submitted</th>
            <th rowspan="2" width="180">Remarks</th>
        </tr>
        <tr>
            <th width="45">Yes</th>
            <th width="45">No</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        // Fetch interactive answers
        $stmtI = $pdo->prepare("SELECT * FROM form13_answers WHERE protocol_id = ?");
        $stmtI->execute([$protocol_id]);
        $ansMap = [];
        while($r = $stmtI->fetch()) {
            $ansMap[$r['category']] = $r;
        }

        $docs = [
            "Research Protocol",
            "Informed Consent / Assent Consent",
            "Guide Questionnaire",
            "Curriculum Vitae",
            "Letter Request",
            "Endorsement"
        ];

        foreach($docs as $i => $doc): 
            $curr = $ansMap[$doc] ?? null;
            $isYes = ($curr && $curr['is_submitted'] == 'Yes');
            $isNo  = ($curr && $curr['is_submitted'] == 'No');
            $isNA  = ($curr && $curr['is_submitted'] == 'N/A');
            $rem   = $curr['remarks'] ?? '';

            // Fallback to auto-check if no interactive data exists yet
            if (!$curr) {
                $stmtF = $pdo->prepare("SELECT file_name FROM protocol_files WHERE protocol_id = ?");
                $stmtF->execute([$protocol_id]);
                $files = $stmtF->fetchAll(PDO::FETCH_COLUMN);
                $keywords = [
                    "Research Protocol" => ["protocol", "proposal", "study"],
                    "Informed Consent / Assent Consent" => ["consent", "icf", "assent"],
                    "Guide Questionnaire" => ["questionnaire", "survey", "instrument", "tool"],
                    "Curriculum Vitae" => ["cv", "vitae", "resume"],
                    "Letter Request" => ["letter", "request"],
                    "Endorsement" => ["endorsement", "endorsed"],
                ];
                if (isset($keywords[$doc])) {
                    foreach($files as $fn) {
                        foreach($keywords[$doc] as $k) {
                            if(str_contains(strtolower($fn), $k)) { $isYes = true; $rem = $fn; break; }
                        }
                        if($isYes) break;
                    }
                }
            }
        ?>
        <tr>
            <td class="text-center"><?php echo ($i+1); ?></td>
            <td style="font-size: 10pt;"><?php echo $doc; ?></td>
            <td class="text-center"><?php echo $isYes ? '☑' : ''; ?></td>
            <td class="text-center"><?php echo $isNo ? '☑' : ''; ?></td>
            <td style="font-size: 9pt;"><?php 
                if ($isNA) echo '<span style="color:#666;">[N/A]</span> ';
                echo htmlspecialchars($rem); 
            ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
// Staff info & Answer Date
$stmtS = $pdo->prepare("SELECT name, signature FROM admins WHERE role = 'rec_staff' AND status = 'active' LIMIT 1");
$stmtS->execute();
$staff = $stmtS->fetch();
$staff_name = $staff ? $staff['name'] : "DNSC REC STAFF";
$staff_sig  = $staff ? $staff['signature'] : null;

// Get the actual date the checklist was completed
$stmtDate = $pdo->prepare("SELECT created_at FROM form13_answers WHERE protocol_id = ? ORDER BY id DESC LIMIT 1");
$stmtDate->execute([$protocol_id]);
$ansDate = $stmtDate->fetchColumn();
$displayDate = $ansDate ? date('F d, Y', strtotime($ansDate)) : date('F d, Y'); 
?>

<!-- FOOTER SIGNATURE -->
<div class="sig-section" style="margin-top: 60px;">
    <p style="margin-bottom: 10px; font-weight: normal;">Reviewed by:</p>
    
    <?php echo renderSignature($staff_sig, '160px'); ?>
    <div class="sig-line" style="margin-top: 45px;"></div>
    <div style="padding-left: 5px;">
        <strong><?php echo strtoupper($staff_name); ?></strong><br>
        <span style="font-size: 9pt; color: #333;">REC Staff</span><br>
        <div style="margin-top: 8px; font-size: 9.5pt;">Date Received: <strong><?php echo $displayDate; ?></strong></div>
    </div>
</div>

    <div class="page-num-footer">136</div>
</div>

</body>
</html>
