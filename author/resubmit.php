<?php
require_once '../includes/auth_check.php';
checkAuth(['author']);
require_once '../config/database.php';

$author_id = $_SESSION['user_id'];
$protocol_id = $_GET['id'] ?? null;

if (!$protocol_id) {
    header("Location: index");
    exit();
}

// Fetch corresponding protocol ensuring it belongs to the author and is in a revisable state
$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ? AND created_by = ?");
$stmt->execute([$protocol_id, $author_id]);
$protocol = $stmt->fetch();

if (!$protocol || $protocol['status'] !== 'needs_revision') {
    header("Location: index?error=not_found_or_not_revisable");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resubmit_btn'])) {
    // Expected files
    $uploaded_files = [];
    $has_error = false;
    
    if (isset($_FILES['revised_files']) && $_FILES['revised_files']['error'][0] != UPLOAD_ERR_NO_FILE) {
        $files = $_FILES['revised_files'];
        $allowed = ['pdf'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == 0) {
                $filename = $files['name'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    $error = "Only PDF files are allowed ($filename).";
                    $has_error = true;
                    break;
                }
                $uploaded_files[] = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name' => 'REVISED_' . $filename
                ];
            }
        }
    } else {
        $error = "Please upload your revised PDF document(s).";
        $has_error = true;
    }

    if (!$has_error) {
        try {
            $pdo->beginTransaction();

            // Mark protocol as revised so staff/chair knows it was updated
            // Update status back to 'revised' conceptually, but REC workflow might require 'staff_review' or 'revised'.
            // In typical systems, it goes back to 'revised' or 'staff_review'. We'll use 'revised'.
            
            $stmtUpdate = $pdo->prepare("UPDATE protocols SET status = 'revised' WHERE protocol_id = ?");
            $stmtUpdate->execute([$protocol_id]);

            if (!is_dir('../uploads/protocols/')) { mkdir('../uploads/protocols/', 0777, true); }

            foreach ($uploaded_files as $f) {
                $newFilename = "REC_REV_" . time() . "_" . $protocol_id . "_" . uniqid() . ".pdf";
                if (move_uploaded_file($f['tmp_name'], "../uploads/protocols/" . $newFilename)) {
                    $stmtF = $pdo->prepare("INSERT INTO protocol_files (protocol_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmtF->execute([$protocol_id, $f['name'], $newFilename]);
                }
            }

            // Save Form 15 Data
            if(isset($_POST['rec_rec'])) {
                $pdo->prepare("DELETE FROM form15_responses WHERE protocol_id = ?")->execute([$protocol_id]);
                $stmtF15 = $pdo->prepare("INSERT INTO form15_responses (protocol_id, author_id, rec_recommendation, author_response, page_reference) VALUES (?,?,?,?,?)");
                $rec_recs = $_POST['rec_rec'];
                $auth_res = $_POST['auth_res'];
                $page_refs = $_POST['page_ref'];
                foreach($rec_recs as $i => $r) {
                    if(!empty($r) || !empty($auth_res[$i])) {
                        $stmtF15->execute([$protocol_id, $author_id, $r, $auth_res[$i], $page_refs[$i]]);
                    }
                }
            }

            $pdo->commit();
            $success = "Revision and Resubmission Form successfully submitted. We will review your updated documents.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch existing files so author can see what's loaded
$filesStmt = $pdo->prepare("SELECT * FROM protocol_files WHERE protocol_id = ? ORDER BY uploaded_at DESC");
$filesStmt->execute([$protocol_id]);
$existing_files = $filesStmt->fetchAll();

include '../includes/header.php';
?>

<div id="wrapper" class="dashboard-page d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Revision Submission";
        $workspaceSubtitle = "REC Form 15: Formal Resubmission & Recommendation Response";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            
            <div class="col-lg-9 mx-auto">
                <div class="mb-4">
                    <a href="index" class="text-decoration-none text-muted"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
                    <h2 class="fw-bold text-navy mt-2">Submit Revision</h2>
                    <p class="text-muted">Protocol: <span class="fw-bold text-navy"><?php echo htmlspecialchars($protocol['rec_code']); ?></span> — <?php echo htmlspecialchars($protocol['title']); ?></p>
                </div>

                <div class="alert alert-warning border-warning shadow-sm mb-4">
                    <h5 class="fw-bold text-dark"><i class="fas fa-exclamation-triangle me-2"></i> Attention Required</h5>
                    <p class="mb-0 text-dark">This protocol was returned for revision. Please review the recommendations carefully and upload your updated PDF files. Reviewers and Staff will look for "REVISED" prefixes on file uploads.</p>
                </div>

                <!-- Show remarks/recommendations if present -->
                <?php if (!empty($protocol['recommendations'])): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="fw-bold mb-0 text-danger"><i class="fas fa-comment-dots me-2"></i> Reviewer / Staff Remarks</h6>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <p class="mb-0 font-monospace text-dark" style="white-space: pre-wrap;"><?php echo htmlspecialchars($protocol['recommendations']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
                    <div class="card-header bg-navy text-white px-4 py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-signature me-2"></i> REC Form 15: Resubmission & Response Shell</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="resubmit?id=<?php echo $protocol_id; ?>" method="POST" enctype="multipart/form-data">
                            
                            <!-- Interactive Form 15 Table -->
                            <div class="mb-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-bold text-navy mb-0">Response to REC Recommendations (Form 15 Shell)</label>
                                    <button type="button" onclick="addRecommendationRow()" class="btn btn-navy btn-sm rounded-pill px-3">
                                        <i class="fas fa-plus me-1"></i> Add Point
                                    </button>
                                </div>
                                <div class="table-responsive rounded-4 border shadow-sm">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3" style="width: 35%;">REC Recommendation / Issue</th>
                                                <th style="width: 45%;">Committee's Response / Implementation</th>
                                                <th class="pe-3" style="width: 20%;">Page Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody id="form15-body">
                                            <?php 
                                            // 1. Fetch existing responses if any
                                            $stmtE = $pdo->prepare("SELECT * FROM form15_responses WHERE protocol_id = ?");
                                            $stmtE->execute([$protocol_id]);
                                            $exist_res = $stmtE->fetchAll();
                                            
                                            // 2. If NO existing responses, try to auto-parse the status remarks
                                            if (empty($exist_res) && !empty($protocol['recommendations'])) {
                                                // Try to split by numbered list or bullets
                                                $lines = preg_split('/\d+\.|\*|-/', $protocol['recommendations']);
                                                $cleanLines = array_filter(array_map('trim', $lines));
                                                
                                                if (count($cleanLines) > 1) {
                                                    foreach ($cleanLines as $line) {
                                                        $exist_res[] = ['rec_recommendation' => $line, 'author_response' => '', 'page_reference' => ''];
                                                    }
                                                } else {
                                                    $exist_res[] = ['rec_recommendation' => trim($protocol['recommendations']), 'author_response' => '', 'page_reference' => ''];
                                                }
                                            }

                                            $rowCount = max(count($exist_res), 3); // Show at least 3 rows
                                            for($i=0; $i<$rowCount; $i++): 
                                                $r = $exist_res[$i] ?? null;
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-3">
                                                    <textarea name="rec_rec[]" class="form-control form-control-sm border-0 bg-light" rows="2" placeholder="Describe the committee's concern..."><?php echo htmlspecialchars($r['rec_recommendation'] ?? ''); ?></textarea>
                                                </td>
                                                <td class="py-3">
                                                    <textarea name="auth_res[]" class="form-control form-control-sm border-0" rows="2" placeholder="Explain your implementation/revision..."><?php echo htmlspecialchars($r['author_response'] ?? ''); ?></textarea>
                                                </td>
                                                <td class="pe-3 py-3">
                                                    <input type="text" name="page_ref[]" class="form-control form-control-sm border-0 bg-light rounded-pill px-3" placeholder="e.g. Page 5" value="<?php echo htmlspecialchars($r['page_reference'] ?? ''); ?>">
                                                </td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 p-3 bg-soft-navy rounded-3 border d-flex align-items-center">
                                    <i class="fas fa-magic text-navy me-3 fa-lg"></i>
                                    <small class="text-navy small"><strong>System Tip:</strong> Be as specific as possible. Mention exact page numbers where the peer reviewers can verify your changes.</small>
                                </div>
                            </div>

                            <hr class="my-5 opacity-25">
                            
                            <div class="mb-5">
                                <label class="form-label fw-bold text-navy mb-3">Upload Revised Documents (Final Files)</label>
                                <div class="upload-zone p-5 border border-dashed rounded-4 bg-light text-center position-relative">
                                    <i class="fas fa-cloud-arrow-up fa-3xl text-navy mb-3 opacity-25"></i>
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-1">Click to browse or Drag and Drop</h6>
                                        <p class="text-muted small">Standard protocol requires all files to be in PDF format.</p>
                                    </div>
                                    <input type="file" name="revised_files[]" class="form-control" accept=".pdf" multiple required 
                                           style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                                    <div id="file-count" class="badge bg-gold text-navy rounded-pill px-3 py-2 d-none">0 Files Selected</div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="resubmit_btn" class="btn btn-navy py-3 px-5 rounded-pill fw-bold shadow-lg transform-hover">
                                    <i class="fas fa-paper-plane me-2"></i> Transmit Formal Revisions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Existing Files Reference -->
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-navy"><i class="fas fa-clock-rotate-left me-2"></i> Submission Document History</h6>
                        <span class="badge bg-soft-navy text-navy rounded-pill"><?php echo count($existing_files); ?> Files</span>
                    </div>
                    <div class="list-group list-group-flush rounded-bottom-4">
                        <?php foreach($existing_files as $f): ?>
                            <div class="list-group-item py-3 px-4 d-flex justify-content-between align-items-center border-start-0 border-end-0">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon bg-soft-red text-red rounded-3 p-2 me-3">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-navy small mb-0"><?php echo htmlspecialchars($f['file_name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Stored as <?php echo $f['file_path']; ?></div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-outline-navy btn-sm px-3 rounded-pill fw-bold" onclick="viewPDF('../uploads/protocols/<?php echo htmlspecialchars(addslashes($f['file_path'])); ?>', '<?php echo htmlspecialchars(addslashes($f['file_name'])); ?>')">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                    <span class="text-muted small opacity-75 ms-2"><?php echo date('M d, Y h:i A', strtotime($f['uploaded_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-page { background: #f8fafc; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
.border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #cbd5e1 !important; transition: all 0.3s ease; }
.upload-zone:hover { border-color: #1a2b4b !important; background-color: #f1f5f9 !important; }
.bg-soft-red { background-color: rgba(239, 68, 68, 0.1); }
.transform-hover { transition: all 0.2s ease; }
.transform-hover:hover { transform: translateY(-3px); }
.file-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
.fa-3xl { font-size: 3rem; }
</style>

<script>
function addRecommendationRow() {
    const tbody = document.getElementById('form15-body');
    const row = document.createElement('tr');
    row.className = 'animate__animated animate__fadeInUp';
    row.innerHTML = `
        <td class="ps-3 py-3">
            <textarea name="rec_rec[]" class="form-control form-control-sm border-0 bg-light" rows="2" placeholder="Describe the concern..."></textarea>
        </td>
        <td class="py-3">
            <textarea name="auth_res[]" class="form-control form-control-sm border-0" rows="2" placeholder="Your response..."></textarea>
        </td>
        <td class="pe-3 py-3">
            <input type="text" name="page_ref[]" class="form-control form-control-sm border-0 bg-light rounded-pill px-3" placeholder="e.g. Page 12">
        </td>
    `;
    tbody.appendChild(row);
}

document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const count = e.target.files.length;
    const badge = document.getElementById('file-count');
    if (count > 0) {
        badge.innerText = count + (count === 1 ? ' File' : ' Files') + ' Selected';
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
});
</script>

<?php if ($error): ?>
    <script>Swal.fire({ icon: 'error', title: 'Upload Failed', text: '<?php echo addslashes($error); ?>' });</script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Resubmitted!', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#1a2b4b' }).then(() => {
            window.location.href = 'index';
        });
    </script>
<?php endif; ?>

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

<?php include '../includes/footer.php'; ?>

