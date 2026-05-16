<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair', 'rec_secretary', 'admin']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) {
    echo json_encode(['success' => false, 'message' => 'No ID']);
    exit();
}

try {
    $consolidated = "";

    // Create anonymized mapping
    $stmtMap = $pdo->prepare("SELECT reviewer_id FROM reviewer_assignments WHERE protocol_id = ? ORDER BY assigned_at ASC");
    $stmtMap->execute([$protocol_id]);
    $reviewerMap = [];
    $counter = 1;
    foreach ($stmtMap->fetchAll() as $mapRow) {
        $reviewerMap[$mapRow['reviewer_id']] = "Reviewer " . $counter++;
    }

    // 1. Fetch Form 10 (Protocol Worksheet) negative/noted comments
    $stmt10 = $pdo->prepare("
        SELECT q.reviewer_id, q.question, q.answer, q.comment
        FROM form10_answers q
        WHERE q.protocol_id = ? AND (q.answer != 'Yes' OR (q.comment IS NOT NULL AND q.comment != ''))
        ORDER BY q.reviewer_id, q.answer_id
    ");
    $stmt10->execute([$protocol_id]);
    $f10 = $stmt10->fetchAll();

    if ($f10) {
        $consolidated .= "BOARD REVIEWER COMMENTS (Form 10):\n";
        foreach ($f10 as $row) {
            $q = $row['question'];
            $anonName = $reviewerMap[$row['reviewer_id']] ?? "Reviewer";
            if (strpos($q, 'SUB|') === 0) {
                $parts = explode('|', $q);
                $q = "Criterion: " . ($parts[2] ?? $q);
            }
            $consolidated .= "- [{$anonName}] Question: {$q}\n";
            if ($row['answer'] != 'Yes') $consolidated .= "  [Status] {$row['answer']}\n";
            if ($row['comment']) $consolidated .= "  [Reviewer Note] " . trim($row['comment']) . "\n";
            $consolidated .= "\n";
        }
    }

    // 2. Fetch Form 12 (Informed Consent)
    $stmt12 = $pdo->prepare("
        SELECT q.reviewer_id, q.question, q.answer, q.comment
        FROM form12_answers q
        WHERE q.protocol_id = ? AND (q.answer != 'Yes' OR (q.comment IS NOT NULL AND q.comment != ''))
        ORDER BY q.reviewer_id, q.answer_id
    ");
    $stmt12->execute([$protocol_id]);
    $f12 = $stmt12->fetchAll();

    if ($f12) {
        $consolidated .= "\nCONSENT CHECKLIST CONCERNS (Form 12):\n";
        foreach ($f12 as $row) {
             $q = $row['question'];
             $anonName = $reviewerMap[$row['reviewer_id']] ?? "Reviewer";
            if (strpos($q, 'SUB|') === 0) {
                $parts = explode('|', $q);
                $q = "Check: " . ($parts[2] ?? $q);
            }
            $consolidated .= "- [{$anonName}] Check: {$q}\n";
            if ($row['answer'] != 'Yes' && $row['answer'] != '') $consolidated .= "  [Status] {$row['answer']}\n";
            if ($row['comment']) $consolidated .= "  [Reviewer Note] " . trim($row['comment']) . "\n";
            $consolidated .= "\n";
        }
    }

    // 3. Overall Recommendations from Reviewers
    $stmtRec = $pdo->prepare("
        SELECT r.reviewer_id, r.recommendation, r.notes, r.form_type
        FROM reviewer_recommendations r
        WHERE r.protocol_id = ?
    ");
    $stmtRec->execute([$protocol_id]);
    $recs = $stmtRec->fetchAll();

    if ($recs) {
        $consolidated .= "\nSUMMARY OF REVIEWER DECISIONS:\n";
        $groupedRecs = [];
        foreach ($recs as $row) {
            $rid = $row['reviewer_id'];
            if (!isset($groupedRecs[$rid])) $groupedRecs[$rid] = [];
            $groupedRecs[$rid][] = $row;
        }
        foreach ($groupedRecs as $rid => $rows) {
            $anonName = $reviewerMap[$rid] ?? "Reviewer";
            $uniqueDecisions = array_unique(array_column($rows, 'recommendation'));
            if (count($uniqueDecisions) === 1) {
                $consolidated .= "- {$anonName}: " . $uniqueDecisions[0] . "\n";
                // Concatenate notes if they differ
                $notes = array_filter(array_unique(array_map('trim', array_column($rows, 'notes'))));
                if ($notes) $consolidated .= "  Notes: " . implode("; ", $notes) . "\n";
            } else {
                foreach ($rows as $row) {
                    $form = ($row['form_type'] == 10) ? 'Protocol' : 'Consent';
                    $consolidated .= "- {$anonName} ({$form}): {$row['recommendation']}\n";
                    if ($row['notes']) $consolidated .= "  Note: " . trim($row['notes']) . "\n";
                }
            }
        }
    }

    if (empty($consolidated)) {
        $consolidated = "No specific negative remarks or additional comments found from the reviewers.";
    }

    echo json_encode(['success' => true, 'comments' => trim($consolidated)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
