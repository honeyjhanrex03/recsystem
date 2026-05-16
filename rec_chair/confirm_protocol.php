<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Protocol ID.");
}

$protocol_id = (int)$_GET['id'];

// Fetch protocol info
$stmt = $pdo->prepare("SELECT p.*, pf.file_path, pf.file_name 
                      FROM protocols p 
                      LEFT JOIN protocol_files pf ON p.protocol_id = pf.protocol_id 
                      WHERE p.protocol_id = ?");
$stmt->execute([$protocol_id]);
$protocol = $stmt->fetch();

if (!$protocol) {
    die("Protocol not found.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_assessment'])) {
    $review_type = $_POST['review_type'];
    $remarks = trim($_POST['remarks'] ?? '');

    try {
        $pdo->beginTransaction();
        
        $updateStmt = $pdo->prepare("UPDATE protocols SET status = 'confirmed', review_type = ?, recommendations = ? WHERE protocol_id = ?");
        $updateStmt->execute([$review_type, $remarks, $protocol_id]);
        
        $pdo->commit();

        require_once '../includes/notifications_helper.php';
        // Notify Author
        notifyUser($pdo, $protocol['created_by'], 'author', 'Initial Assessment Completed', 
            "The REC Chair has completed the initial assessment. Review Type: " . strtoupper($review_type), 
            "shared_view?id=" . $protocol_id);

        // Notify Staff
        $stmtS = $pdo->prepare("SELECT admin_id FROM admins WHERE role = 'rec_staff' AND status = 'active'");
        $stmtS->execute();
        $staff = $stmtS->fetchAll();
        foreach ($staff as $s) {
            notifyUser($pdo, $s['admin_id'], 'admin', 'Chair Confirmed Protocol', 
                "The REC Chair has confirmed protocol: \"{$protocol['title']}\" and set Review Type to " . strtoupper($review_type), 
                "rec_staff/update_status?id=" . $protocol_id);
        }

        // 4. Notify Author via Email (Step 9: Notify Researcher of Initial Assessment)
        if (!empty($protocol['author_email'])) {
            require_once '../includes/send_email.php';
            $emailSubject = "Initial Assessment Result: " . ($protocol['rec_code'] ?: $protocol['tracking_code']);
            $rtMap = ['exempt' => 'EXEMPTED FROM REVIEW', 'expedited' => 'EXPEDITED REVIEW', 'full_board' => 'FULL BOARD REVIEW'];
            $assignedType = $rtMap[$review_type] ?? strtoupper($review_type);
            
            $emailBody = "
                <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                    <h2 style='color: #0f172a;'>Initial Assessment Completed</h2>
                    <p>Dear {$protocol['project_leader']},</p>
                    <p>The REC Chair has completed the initial assessment of your research protocol: <strong>{$protocol['title']}</strong>.</p>
                    
                    <div style='background: #f8fafc; padding: 20px; border-radius: 12px; border-left: 5px solid #eab308; margin: 20px 0;'>
                        <strong style='display: block; margin-bottom: 5px;'>Review Determination:</strong>
                        <span style='font-size: 1.2rem; font-weight: bold; color: #1e40af;'>{$assignedType}</span>
                    </div>

                    <p><strong>Remarks:</strong><br>" . nl2br(htmlspecialchars($remarks ?: 'No specific remarks provided.')) . "</p>
                    
                    <p>You can track the live progress of your protocol by using your tracking code: <strong style='color: #2563eb;'>{$protocol['tracking_code']}</strong> on our website.</p>
                    
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='font-size: 0.8rem; color: #64748b;'>This is an automated notification from the DNSC Research Ethics Committee Registry & Advisory System (DNSC REC).</p>
                </div>
            ";
            sendEmailAPI($protocol['author_email'], $protocol['project_leader'], $emailSubject, $emailBody);
        }

        header("Location: protocols?success=Protocol Confirmed and Review Type Assigned");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Check Submission";
        $workspaceSubtitle = "Choose the type of review for this research";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4 p-md-5">
            
            <div class="row">
                <!-- Left Column: Protocol Details -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 ps-4">
                            <h5 class="fw-bold mb-0 text-navy">Protocol Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="text-muted small fw-bold text-uppercase">Title</label>
                                <p class="fw-bold text-navy fs-5"><?php echo htmlspecialchars($protocol['title']); ?></p>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold text-uppercase">Lead Researcher</label>
                                    <p class="fw-bold text-navy"><?php echo htmlspecialchars($protocol['project_leader']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold text-uppercase">Institution</label>
                                    <p class="fw-bold text-navy"><?php echo htmlspecialchars($protocol['institution']); ?></p>
                                </div>
                            </div>
                            <hr>
                            <h6 class="fw-bold text-navy mb-3"><i class="fas fa-file-pdf me-2"></i> Submitted Documents</h6>
                            <?php
                            // Re-fetch files just in case of multiples
                            $stmtFiles = $pdo->prepare("SELECT * FROM protocol_files WHERE protocol_id = ?");
                            $stmtFiles->execute([$protocol_id]);
                            $files = $stmtFiles->fetchAll();
                            
                            if ($files): ?>
                                <div class="list-group list-group-flush rounded-3 border">
                                    <?php foreach ($files as $file): ?>
                                        <div class="list-group-item py-3 d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-file-pdf text-danger me-2"></i> <?php echo htmlspecialchars($file['file_name']); ?></span>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-navy btn-sm px-3 rounded-pill fw-bold" onclick="viewPDF('../uploads/protocols/<?php echo htmlspecialchars(addslashes($file['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($file['file_name'])); ?>')">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </button>
                                                <a href="../uploads/protocols/<?php echo $file['file_path']; ?>" target="_blank" class="btn btn-navy btn-sm rounded-pill px-3">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted italic small">No files found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: REC Chair Decision -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px; z-index: 10;">
                        <div class="card-header bg-navy text-white border-0 py-3 ps-4 rounded-top-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i> Pick Type of Review</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-4">Based on the submitted research protocol and institutional criteria, specify the type of review this study will undergo.</p>
                            
                            <form action="" method="POST">
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy small mb-3">Select Review Type <span class="text-danger">*</span></label>
                                    
                                    <div class="review-option mb-3">
                                        <input type="radio" class="btn-check" name="review_type" id="rt_exempt" value="exempt" checked required>
                                        <label class="btn btn-outline-info w-100 text-start p-3 rounded-4 shadow-sm border-2" for="rt_exempt">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 fs-3"><i class="fas fa-tag"></i></div>
                                                <div>
                                                    <div class="fw-bold">EXEMPT FROM REVIEW</div>
                                                    <div class="small opacity-75">Protocols with no human participants or minimal risk data.</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="review-option mb-3">
                                        <input type="radio" class="btn-check" name="review_type" id="rt_expedited" value="expedited">
                                        <label class="btn btn-outline-warning w-100 text-start p-3 rounded-4 shadow-sm border-2" for="rt_expedited">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 fs-3"><i class="fas fa-bolt"></i></div>
                                                <div>
                                                    <div class="fw-bold text-dark">EXPEDITED REVIEW</div>
                                                    <div class="small text-dark opacity-75">Non-invasive or minimal risk protocols (Reviewed by 3 members).</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="review-option mb-3">
                                        <input type="radio" class="btn-check" name="review_type" id="rt_full" value="full_board">
                                        <label class="btn btn-outline-danger w-100 text-start p-3 rounded-4 shadow-sm border-2" for="rt_full">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 fs-3"><i class="fas fa-users"></i></div>
                                                <div>
                                                    <div class="fw-bold">FULL BOARD REVIEW</div>
                                                    <div class="small opacity-75">Protocols involving vulnerable groups or high-risk procedures.</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy small mb-2">Message or Notes (Optional)</label>
                                    <textarea name="remarks" class="form-control border-2 rounded-3" rows="3" placeholder="Add specific notes for the staff or reviewers..."></textarea>
                                </div>

                                <button type="submit" name="confirm_assessment" class="btn btn-navy w-100 py-3 fw-bold rounded-pill shadow-lg hover-up">
                                    Confirm & Select Reviewers <i class="fas fa-check-circle ms-2"></i>
                                </button>
                                
                                <div class="text-center mt-3">
                                    <a href="protocols" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Protocols</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .review-option .btn-check:checked + .btn {
        background-color: transparent !important;
        border-width: 3px !important;
    }
    .review-option .btn-check:checked + .btn-outline-info { color: #0dcaf0; border-color: #0dcaf0; background: rgba(13, 202, 240, 0.05) !important; }
    .review-option .btn-check:checked + .btn-outline-warning { color: #ffc107; border-color: #ffc107; background: rgba(255, 193, 7, 0.05) !important; }
    .review-option .btn-check:checked + .btn-outline-danger { color: #dc3545; border-color: #dc3545; background: rgba(220, 53, 69, 0.05) !important; }
    
    .hover-up:hover {
        transform: translateY(-2px);
        transition: all 0.2s ease;
    }
</style>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header bg-navy text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="pdfViewerTitle"><i class="fas fa-file-pdf me-2 text-danger"></i> Document Viewer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="height: 80vh;">
                <iframe id="pdfIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer bg-white border-0 py-2">
                <a id="modalDownloadBtn" href="#" download class="btn btn-navy px-4 rounded-pill"><i class="fas fa-download me-2"></i> Download PDF</a>
                <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPDF(filepath, filename) {
    document.getElementById('pdfViewerTitle').innerHTML = '<i class="fas fa-file-pdf me-2 text-danger"></i> ' + filename;
    document.getElementById('pdfIframe').src = filepath;
    document.getElementById('modalDownloadBtn').href = filepath;
    var pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    pdfModal.show();
}

// Clear iframe src when modal is hidden
document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('pdfIframe').src = '';
});
</script>

<?php include '../includes/footer.php'; ?>

