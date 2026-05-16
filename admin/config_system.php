<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';
include '../includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, these would be saved to a 'settings' table.
    // For now, we simulate success to show the button is active.
    $success = true;

    $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Updated system configuration settings')");
    $log->execute([$_SESSION['user_id']]);
}
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "System Configuration";
        $workspaceSubtitle = "REC Global Settings & Institutional Parameters";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h3 class="fw-bold text-navy mb-4">Core System Settings</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Institution Name</label>
                                <input type="text" class="form-control" value="Davao del Norte State College"
                                    name="inst_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">REC Chairperson Name</label>
                                <input type="text" class="form-control" value="Dr. Jane Doe" name="chair_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">System Email Protocol</label>
                                <select class="form-select">
                                    <option>SMTP (Google)</option>
                                    <option>PHP Mailer (Local)</option>
                                </select>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-navy px-5 rounded-pill fw-bold">Save
                                    Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <script>
        Swal.fire('Settings Saved', 'The system configuration has been updated successfully.', 'success');
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
