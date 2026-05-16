<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';
include '../includes/header.php';

$stmt = $pdo->query("SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as researcher FROM protocols p LEFT JOIN users u ON p.created_by = u.user_id ORDER BY p.created_at DESC");
$protocols = $stmt->fetchAll();
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Master Protocol Registry";
        $workspaceSubtitle = "REC Governance & System Archives";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4 p-md-5">
            <div class="row mb-5 animate-up">
                <div class="col">
                    <h6 class="text-gold fw-bold text-uppercase small mb-2" style="letter-spacing: 2px;">Archival</h6>
                    <h2 class="fw-bold text-navy mb-0">Master Protocol Registry</h2>
                    <p class="text-muted mb-0">Central repository of all research ethics submissions across the
                        institution.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm animate-up" style="animation-delay: 0.1s">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">REC Reference</th>
                                    <th>Dossier Summary</th>
                                    <th>Submission Agent</th>
                                    <th class="text-center">Pipeline State</th>
                                    <th class="text-end pe-4">Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($protocols as $p): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-navy">
                                            <i class="fas fa-barcode me-2 opacity-50"></i><?php echo $p['rec_code']; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-navy text-truncate" style="max-width: 300px;">
                                                <?php echo $p['title']; ?>
                                            </div>
                                            <div class="small text-muted mt-1"><i class="far fa-calendar-alt me-1"></i>
                                                <?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($p['researcher']); ?>&background=random&size=24"
                                                    class="rounded-circle me-2">
                                                <div class="small fw-bold"><?php echo $p['researcher']; ?></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $stateCls = 'bg-info-light';
                                            if ($p['status'] == 'completed')
                                                $stateCls = 'bg-success-light';
                                            if ($p['status'] == 'for_decision')
                                                $stateCls = 'bg-primary-light';
                                            if ($p['status'] == 'under_review')
                                                $stateCls = 'bg-warning-light';
                                            ?>
                                            <span class="badge <?php echo $stateCls; ?> rounded-pill">
                                                <?php echo strtoupper(str_replace('_', ' ', $p['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="../shared_view?id=<?php echo $p['protocol_id']; ?>"
                                                class="btn btn-sm btn-navy rounded-pill px-3">
                                                Details <i class="fas fa-chevron-right ms-2 small"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($protocols)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">Registry is currently empty.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
