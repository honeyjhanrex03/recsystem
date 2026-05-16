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

// Fetch Decision
$stmtD = $pdo->prepare("SELECT * FROM final_decisions WHERE protocol_id = ? ORDER BY decision_date DESC LIMIT 1");
$stmtD->execute([$protocol_id]);
$decision = $stmtD->fetch();

// REC Chair info
$stmtC = $pdo->prepare("SELECT name FROM admins WHERE role = 'rec_chair' AND status = 'active' LIMIT 1");
$stmtC->execute();
$chair = $stmtC->fetch();
$chair_name = $chair ? $chair['name'] : "DNSC REC CHAIRPERSON";

$headerSrc = BASE_URL . 'assets/images/header.png';
$footerSrc = BASE_URL . 'assets/images/footer.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 25 - ETHICAL CLEARANCE</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; line-height: 1.3; margin: 0; padding: 0; }
        .page-container { width: 8.27in; min-height: 11.69in; margin: 0 auto; position: relative; background: white; padding: 0 0.5in; box-sizing: border-box; }
        
        .header-img { width: 100%; margin-top: 0.2in; }
        .footer-img { width: 100%; position: absolute; bottom: 0.25in; left: 0; padding: 0 0.5in; box-sizing: border-box; }
        
        .control-table { position: absolute; top: 1.1in; right: 0.5in; width: 1.8in; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 8pt; }
        .control-table td { border: 1px solid black; padding: 2px 5px; }
        
        .content { padding: 0.2in 0.4in; margin-top: 0.8in; }
        .center-text { text-align: center; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        
        .letter-date { margin-bottom: 25px; }
        .proponent-block { margin-bottom: 25px; }
        .re-block { margin-bottom: 25px; }
        
        ul { list-style-type: none; padding-left: 0.6in; margin: 15px 0; }
        ul li::before { content: "•"; margin-left: -0.3in; display: inline-block; width: 0.3in; }
        
        .sig-block { margin-top: 50px; }
        
        @media print {
            .no-print { display: none; }
            .page-container { border: none; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #198754; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        PRINT CLEARANCE
    </button>
</div>

<div class="page-container">
    <!-- Header Image -->
    <img src="<?php echo $headerSrc; ?>" class="header-img">

    <!-- Document Control Table -->
    <table class="control-table">
        <tr>
            <td width="60%">REC Form No.</td>
            <td class="center-text"><strong>25</strong></td>
        </tr>
        <tr>
            <td>Version No.</td>
            <td class="center-text"><strong>01</strong></td>
        </tr>
        <tr>
            <td>Date of Effectivity</td>
            <td class="center-text">June 15, 2022</td>
        </tr>
    </table>

    <div class="content">
        <div class="center-text" style="font-size: 15pt; font-style: italic; color: #777; margin-bottom: 10px;">
            Research Ethics Committee
        </div>

        <div class="center-text bold underline" style="font-size: 13pt; margin-bottom: 40px;">
            ETHICAL CLEARANCE
        </div>

        <div class="letter-date">
            <?php echo date('F d, Y'); ?>
        </div>

        <div class="proponent-block">
            <div class="bold"><?php echo strtoupper($p['project_leader']); ?></div>
            <div>Researcher</div>
            <div>Davao del Norte State College</div>
            <div>New Visayas, Panabo City, Davao del Norte</div>
        </div>

        <div class="re-block">
            <span class="bold">RE:</span> “<?php echo htmlspecialchars($p['title']); ?>”<br>
            <span class="bold">REC code: <?php echo htmlspecialchars($p['rec_code']); ?></span>
        </div>

        <div class="bold" style="margin-bottom: 25px;">
            Subject: Ethical Clearance
        </div>

        <div style="margin-bottom: 20px;">
            Dear <span class="bold">Mr./Ms. <?php 
                $parts = explode(',', $p['project_leader']);
                echo trim($parts[0]);
            ?></span>:
        </div>

        <p>This is to acknowledge the submitted documents with dates.</p>
        <ul>
            <li>Study Protocol: “<?php echo htmlspecialchars($p['title']); ?>”</li>
            <li>Curriculum Vitae of the researcher</li>
            <li>Letter Request for Review</li>
            <li>Notification to Commence the Approved Proposal</li>
            <li>Informed Consent Form</li>
            <li>Research Instruments</li>
            <li>Filled REC Form 11</li>
            <li><?php echo date('F d, Y', strtotime($p['created_at'])); ?></li>
        </ul>

        <p style="text-align: justify; margin-top: 20px;">
            The DNSC REC conducted an <strong><?php 
                $rtMap = ['pending' => 'Pending Review Type', 'exempt' => 'Exemption Determination', 'expedited' => 'Expedited Review', 'full_board' => 'Full Board Review'];
                echo $rtMap[$p['review_type']] ?? 'Ethical Review'; 
            ?></strong> of the Proposal on <strong><?php 
                $reviewDate = ($decision && $decision['meeting_date']) ? $decision['meeting_date'] : (($p['submission_confirmed_at'] && $p['submission_confirmed_at'] !== '0000-00-00 00:00:00') ? $p['submission_confirmed_at'] : $p['created_at']);
                echo date('F d, Y', strtotime($reviewDate));
            ?></strong>.
        </p>

        <p style="text-align: justify;">
            The proposal is recommended for approval. This certificate is valid for **one (1) year** until <strong><?php echo date('F d, Y', strtotime($reviewDate . ' +365 days')); ?></strong>. Another certification will be issued upon the completion of your study.
        </p>

        <p style="text-align: justify;">
            Notwithstanding the foregoing, the proponent is hereby required to submit the Final Report within three (3) weeks after the completion of the study.
        </p>

        <div style="margin-top: 40px;">
            Very truly yours,
        </div>

        <div class="sig-block">
            <div class="bold" style="font-size: 12pt;"><?php echo strtoupper($chair_name); ?></div>
            <div>DNSC-REC Chair</div>
        </div>
    </div>

    <!-- Footer Image -->
    <img src="<?php echo $footerSrc; ?>" class="footer-img">
</div>

</body>
</html>
