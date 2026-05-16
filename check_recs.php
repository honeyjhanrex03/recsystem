<?php
require_once 'config/database.php';
$stmt = $pdo->prepare("SELECT * FROM reviewer_recommendations WHERE protocol_id = ?");
$stmt->execute([12]);
$recs = $stmt->fetchAll();
echo "Recommendations for Protocol 12:\n";
print_r($recs);

$stmtP = $pdo->prepare("SELECT recommendations FROM protocols WHERE protocol_id = ?");
$stmtP->execute([12]);
$pRecs = $stmtP->fetchColumn();
echo "\nConsolidated recommendations in protocols table:\n";
echo $pRecs . "\n";
?>
