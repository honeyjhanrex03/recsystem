<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair']);
require_once '../config/database.php';

$protocol_id = $_GET['id'] ?? null;
if (!$protocol_id) die("Invalid Protocol ID");

$stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
$stmt->execute([$protocol_id]);
$protocol = $stmt->fetch();

if (!$protocol) die("Protocol not found");

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_protocol'])) {
    $title = trim($_POST['title']);
    $project_leader = trim($_POST['project_leader']);
    $institution = trim($_POST['institution']);
    $author_email = trim($_POST['author_email']);
    $review_type = $_POST['review_type'] ?? $protocol['review_type'];
    $protocol_type = $_POST['protocol_type'] ?? $protocol['protocol_type'];
    $author_initials = strtoupper(trim($_POST['author_initials'] ?? ''));

    try {
        $update = $pdo->prepare("UPDATE protocols SET title = ?, project_leader = ?, institution = ?, author_email = ?, review_type = ?, protocol_type = ?, author_initials = ? WHERE protocol_id = ?");
        $update->execute([$title, $project_leader, $institution, $author_email, $review_type, $protocol_type, $author_initials, $protocol_id]);
        
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user_id'], $protocol_id, "Staff updated protocol metadata"]);

        $success = "Protocol details updated successfully.";
        
        // Refresh
        $stmt->execute([$protocol_id]);
        $protocol = $stmt->fetch();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Edit Protocol Metadata";
        $workspaceSubtitle = "REC Registry Record Correction & Verification";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="fw-bold text-navy mb-4">Update Registry Record</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-navy small text-uppercase">Title</label>
                                    <textarea name="title" class="form-control" rows="3" required><?php echo htmlspecialchars($protocol['title']); ?></textarea>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Project Leader</label>
                                        <input type="text" name="project_leader" class="form-control" value="<?php echo htmlspecialchars($protocol['project_leader']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Committee Email</label>
                                        <input type="email" name="author_email" class="form-control" value="<?php echo htmlspecialchars($protocol['author_email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Institution</label>
                                        <input type="text" name="institution" class="form-control" value="<?php echo htmlspecialchars($protocol['institution']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Review Track</label>
                                        <select name="review_type" class="form-select">
                                            <option value="pending" <?php echo $protocol['review_type'] == 'pending' ? 'selected' : ''; ?>>Pending (REC Chair Assignment)</option>
                                            <option value="exempt" <?php echo $protocol['review_type'] == 'exempt' ? 'selected' : ''; ?>>Exempt</option>
                                            <option value="expedited" <?php echo $protocol['review_type'] == 'expedited' ? 'selected' : ''; ?>>Expedited</option>
                                            <option value="full_board" <?php echo $protocol['review_type'] == 'full_board' ? 'selected' : ''; ?>>Full Board</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Project Type</label>
                                        <select name="protocol_type" class="form-select">
                                            <option value="INT" <?php echo $protocol['protocol_type'] == 'INT' ? 'selected' : ''; ?>>Internal (DNSC)</option>
                                            <option value="EXT" <?php echo $protocol['protocol_type'] == 'EXT' ? 'selected' : ''; ?>>External</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Initials (F-M-L)</label>
                                        <input type="text" name="author_initials" class="form-control" value="<?php echo htmlspecialchars($protocol['author_initials'] ?? ''); ?>" placeholder="e.g. MMP">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between pt-3 border-top">
                                    <a href="protocols" class="btn btn-link text-muted text-decoration-none">← Return to Registry</a>
                                    <button type="submit" name="update_protocol" class="btn btn-navy px-5 rounded-pill fw-bold shadow">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <script>Swal.fire({ title: 'Record Updated', text: '<?php echo $success; ?>', icon: 'success' }).then(() => { window.location.href = 'protocols'; });</script>
<?php endif; ?>
<?php if ($error): ?>
    <script>Swal.fire({ title: 'Update Failed', text: '<?php echo addslashes($error); ?>', icon: 'error' });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
