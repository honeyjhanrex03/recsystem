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

// Fetch Decision Date and Chair who approved it
$stmtD = $pdo->prepare("SELECT fd.*, u.name as chair_name, u.signature as chair_sig FROM final_decisions fd LEFT JOIN admins u ON fd.chair_id = u.admin_id WHERE fd.protocol_id = ? ORDER BY fd.decision_date DESC LIMIT 1");
$stmtD->execute([$protocol_id]);
$decision = $stmtD->fetch();

// Current active REC Chair fallback info
$stmtC = $pdo->prepare("SELECT name, signature FROM admins WHERE role = 'rec_chair' AND status = 'active' LIMIT 1");
$stmtC->execute();
$chair = $stmtC->fetch();
$chair_name = $chair ? $chair['name'] : "DNSC REC CHAIRPERSON";

// Determine dynamic values
$decision_chair = ($decision && !empty($decision['chair_name'])) ? $decision['chair_name'] : $chair_name;
$decision_chair_sig = ($decision && !empty($decision['chair_sig'])) ? $decision['chair_sig'] : ($chair ? $chair['signature'] : null);

// Extract last name for salutation
$leader_name = trim($p['project_leader']);
$lastName = '';
if (str_contains($leader_name, ',')) {
    // Format: "Limbadan, Allan D."
    $parts = explode(',', $leader_name);
    $lastName = trim($parts[0]);
} else {
    // Format: "Allan D. Limbadan"
    $parts = explode(' ', $leader_name);
    $lastName = end($parts);
}

// Map review type for dynamic grammar ("a" or "an")
$rtMap = [
    'pending' => 'Pending Review',
    'exempt' => 'Exempt Review',
    'expedited' => 'Expedited Review',
    'full_board' => 'Full Board Review'
];
$reviewTypeStr = $rtMap[$p['review_type']] ?? 'Ethical Review';
$firstLetter = strtolower(substr($reviewTypeStr, 0, 1));
$article = in_array($firstLetter, ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a';

$headerSrc = BASE_URL . 'assets/images/header.png';
$footerSrc = BASE_URL . 'assets/images/footer.png';

function renderSignature(?string $sig_filename, string $width = '150px'): string {
    if (!$sig_filename) return '<div style="height:35px;"></div>';
    return '<img src="' . BASE_URL . 'uploads/signatures/' . $sig_filename . '" style="width:' . $width . '; height:auto; display:block; margin-bottom:-45px; margin-left:25px; position:relative; z-index:10; pointer-events:none; filter: multiply(0.8); opacity: 0.95;">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REC FORM 25 - ETHICAL CLEARANCE</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="../assets/images/logo.png?v=1.1">
    <link rel="apple-touch-icon" href="../assets/images/logo.png?v=1.1">
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.35; margin: 0; padding: 0; }
        .page-container { width: 8.27in; min-height: 11.69in; margin: 0 auto; position: relative; background: white; padding: 0 0.55in; box-sizing: border-box; }
        
        .header-img { width: 100%; margin-top: 0.25in; }
        .footer-img { width: 100%; position: absolute; bottom: 0.25in; left: 0; padding: 0 0.55in; box-sizing: border-box; }
        
        .content { padding: 0.1in 0.4in; margin-top: 0.15in; }
        .center-text { text-align: center; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        
        .letter-date { margin-bottom: 12px; font-size: 11pt; }
        
        .proponent-block { margin-bottom: 12px; line-height: 1.3; }
        .proponent-name { font-weight: bold; text-transform: uppercase; font-size: 11.5pt; }
        
        .re-block { margin-bottom: 12px; line-height: 1.35; text-align: justify; }
        
        .salutation { margin-bottom: 12px; }
        .salutation em { font-style: italic; }
        
        ul { list-style-type: disc; padding-left: 0.4in; margin: 8px 0; }
        ul li { font-size: 11pt; margin-bottom: 2px; text-align: justify; line-height: 1.3; }
        
        .body-paragraph { text-align: justify; margin-top: 8px; margin-bottom: 8px; line-height: 1.35; }
        
        .sig-block { margin-top: 20px; line-height: 1.3; }
        .sig-name { font-weight: bold; text-transform: uppercase; font-size: 11.5pt; }
        
        @media print {
            .no-print { display: none; }
            body { background: none; }
            .page-container { border: none; box-shadow: none; margin: 0; padding: 0 0.55in; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="padding: 12px 30px; background: #1a2b4b; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
        <i class="fas fa-print me-2"></i> PRINT CLEARANCE
    </button>
</div>

<div class="page-container">
    <!-- Header Image -->
    <img src="<?php echo $headerSrc; ?>" class="header-img">

    <div class="content">
        <div class="center-text bold underline" style="font-size: 13pt; margin-top: 5px; margin-bottom: 20px; letter-spacing: 0.5px;">
            ETHICAL CLEARANCE
        </div>

        <div class="letter-date">
            <?php 
            $certDate = ($decision && $decision['decision_date']) ? $decision['decision_date'] : $p['created_at'];
            echo date('F d, Y', strtotime($certDate)); 
            ?>
        </div>

        <div class="proponent-block">
            <div class="proponent-name"><?php echo htmlspecialchars($p['project_leader']); ?></div>
            <div>Researcher</div>
            <div>Davao del Norte State College</div>
            <div>New Visayas, Panabo City, Davao del Norte</div>
        </div>

        <div class="re-block">
            <strong>RE:</strong> “<?php echo htmlspecialchars($p['title']); ?>”<br>
            <strong>REC code:</strong> <?php echo htmlspecialchars($p['rec_code']); ?>
        </div>

        <div style="margin-bottom: 12px;">
            <strong>Subject:</strong> Ethical Clearance
        </div>

        <div class="salutation">
            Dear <em>Mr./Ms. <?php echo htmlspecialchars($lastName); ?></em>:
        </div>

        <div class="body-paragraph">
            This is to acknowledge the submitted documents with dates.
        </div>

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

        <p class="body-paragraph">
            The DNSC REC conducted <?php echo $article; ?> <strong><?php echo htmlspecialchars($reviewTypeStr); ?></strong> of the Proposal on <strong><?php 
                $reviewDate = ($decision && $decision['meeting_date']) ? $decision['meeting_date'] : (($p['submission_confirmed_at'] && $p['submission_confirmed_at'] !== '0000-00-00 00:00:00') ? $p['submission_confirmed_at'] : $p['created_at']);
                echo date('F d, Y', strtotime($reviewDate));
            ?></strong>.
        </p>

        <p class="body-paragraph">
            The proposal is recommended for approval. Another certification will be issued upon the completion of your study.
        </p>

        <div class="sig-block" style="margin-top: 15px;">
            <div style="margin-bottom: 5px;">Very truly yours,</div>
            
            <?php echo renderSignature($decision_chair_sig, '150px'); ?>
            <div style="margin-top: 25px;">
                <div class="sig-name"><?php echo htmlspecialchars($decision_chair); ?></div>
                <div>DNSC-REC Chair</div>
            </div>
        </div>
    </div>

    <!-- Footer Image -->
    <img src="<?php echo $footerSrc; ?>" class="footer-img">
</div>

</body>
</html>

