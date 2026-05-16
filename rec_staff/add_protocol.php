<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair']);
require_once '../config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_protocol'])) {
    $title = trim($_POST['title']);
    $project_leader = trim($_POST['project_leader']);
    $institution = trim($_POST['institution']);
    $review_type = $_POST['review_type'];

    // Auto-generate REC Code: DNSC-REC-YYYY-SERIAL
    $year = date('Y');
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM protocols WHERE rec_code LIKE ?");
    $stmtCount->execute(["DNSC-REC-$year-%"]);
    $nextId = $stmtCount->fetchColumn() + 1;
    $rec_code = "DNSC-REC-$year-" . str_pad($nextId, 3, '0', STR_PAD_LEFT);

    // File Upload Handling
    if (isset($_FILES['protocol_file']) && $_FILES['protocol_file']['error'] == 0) {
        $allowed = ['pdf'];
        $filename = $_FILES['protocol_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['protocol_file']['size'];

        if (!in_array($ext, $allowed)) {
            $error = "Only PDF files are allowed.";
        } elseif ($filesize > 10 * 1024 * 1024) {
            $error = "File size exceeds 10MB limit.";
        } else {
            // Success - Process insertion
            try {
                $pdo->beginTransaction();

                // 1. Insert Protocol
                $stmt = $pdo->prepare("INSERT INTO protocols (rec_code, title, project_leader, institution, review_type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$rec_code, $title, $project_leader, $institution, $review_type, $_SESSION['user_id']]);
                $protocol_id = $pdo->lastInsertId();

                // 2. Upload and Record File
                $newFilename = "REC_" . time() . "_" . $protocol_id . ".pdf";
                $uploadPath = "../uploads/protocols/" . $newFilename;

                if (move_uploaded_file($_FILES['protocol_file']['tmp_name'], $uploadPath)) {
                    $stmtF = $pdo->prepare("INSERT INTO protocol_files (protocol_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmtF->execute([$protocol_id, $filename, $newFilename]);

                    // 3. Audit Log
                    $stmtA = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
                    $stmtA->execute([$_SESSION['user_id'], $protocol_id, "Submitted new protocol: $rec_code"]);

                    $pdo->commit();
                    $success = "Protocol $rec_code has been submitted successfully!";
                } else {
                    $pdo->rollBack();
                    $error = "Failed to move uploaded file.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please upload the protocol document (PDF).";
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "New Protocol Submission";
        $workspaceSubtitle = "REC Registry Entry & Formal Documentation Upload";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <a href="<?php echo BASE_URL; ?>rec_staff/protocols" class="btn btn-link text-navy p-0 mb-3 text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i> Back to List
                    </a>
                    <h2 class="fw-bold text-navy">Submit New Protocol</h2>
                    <p class="text-muted">Fill in the details to start the ethics review process.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4 p-md-5">
                            <form action="<?php echo BASE_URL; ?>staff/add_protocol" method="POST" enctype="multipart/form-data" id="protocolForm">
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-navy">Protocol Title</label>
                                    <textarea name="title" class="form-control" rows="3"
                                        placeholder="Enter the full title of the research" required></textarea>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Project Leader / Principal
                                            Investigator</label>
                                        <input type="text" name="project_leader" class="form-control"
                                            placeholder="Full Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Affiliated Institution</label>
                                        <input type="text" name="institution" class="form-control"
                                            placeholder="e.g. Davao Del Norte State College" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Review Type</label>
                                        <select name="review_type" class="form-select" required>
                                            <option value="" selected disabled>Select Review Type</option>
                                            <option value="exempt">Exempted from Review</option>
                                            <option value="expedited">Expedited Review</option>
                                            <option value="full_board">Full Review</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy">Protocol Document (PDF Only)</label>
                                        <input type="file" name="protocol_file" class="form-control" accept=".pdf"
                                            required>
                                        <div class="form-text">Max file size: 10MB</div>
                                    </div>
                                </div>

                                <hr class="my-4 opacity-50">

                                <div class="d-grid">
                                    <button type="submit" name="submit_protocol"
                                        class="btn btn-navy py-3 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-2"></i> Confirm and Submit Protocol
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm bg-light rounded-4 h-100 p-4">
                        <h5 class="fw-bold text-navy mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>
                            Submission Guidelines</h5>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fas fa-check-circle text-success mt-1 me-2"></i>
                                <span>Ensure the title matches the research document exactly.</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fas fa-check-circle text-success mt-1 me-2"></i>
                                <span>The REC Code will be automatically generated upon submission.</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fas fa-check-circle text-success mt-1 me-2"></i>
                                <span>Reviewers will only be assigned after the REC Chair approves your submission.</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="fas fa-file-pdf text-danger mt-1 me-2"></i>
                                <span>Upload MUST be in PDF format to ensure document stability.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Submission Error',
            text: '<?php echo $error; ?>',
            confirmButtonColor: '#1a2b4b'
        });
    </script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Submitted!',
            text: '<?php echo $success; ?>',
            confirmButtonColor: '#1a2b4b'
        }).then(() => {
            window.location.href = 'protocols';
        });
    </script>
<?php endif; ?>



<?php include '../includes/footer.php'; ?>
