<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_secretary']);
require_once '../config/database.php';
include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Audit & Reporting";
        $workspaceSubtitle = "REC Secretary Monitoring & Analytics Dashboard";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-5 animate-up" style="background: linear-gradient(135deg, #1a2b4b 0%, #2a3b5b 100%); color: white;">
                <div class="py-5">
                    <div class="mb-4">
                        <i class="fas fa-chart-line fa-5x opacity-50 pulse-gold"></i>
                    </div>
                    <h1 class="fw-bold mb-3" style="letter-spacing: -1px;">Reporting System</h1>
                    <p class="fs-5 opacity-75 mb-4 mx-auto" style="max-width: 600px;">
                        The specialized reporting module for the REC Secretary is currently under development. Detailed metrics on reviewer performance and board decisions will be available here soon.
                    </p>
                    <div class="d-inline-flex align-items-center bg-white bg-opacity-10 rounded-pill px-4 py-2 border border-white border-opacity-25">
                        <span class="spinner-grow spinner-grow-sm text-gold me-2"></span>
                        <span class="small fw-bold text-uppercase tracking-wider">Feature Coming Soon</span>
                    </div>
                </div>
            </div>

            <div class="row mt-4 g-4 animate-up" style="animation-delay: 0.1s">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 opacity-75">
                        <i class="fas fa-users-viewfinder fa-2x text-navy mb-3"></i>
                        <h6 class="fw-bold text-navy">Reviewer Metrics</h6>
                        <small class="text-muted">Track the average response time and evaluation quality of the REC board.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 opacity-75">
                        <i class="fas fa-file-invoice fa-2x text-navy mb-3"></i>
                        <h6 class="fw-bold text-navy">Board Decisions</h6>
                        <small class="text-muted">Generate summaries of approvals, rejections, and protocols pending board action.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 opacity-75">
                        <i class="fas fa-calendar-check fa-2x text-navy mb-3"></i>
                        <h6 class="fw-bold text-navy">Annual Statistics</h6>
                        <small class="text-muted">Automatic yearly report generation for external auditing and compliance.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pulse-gold {
    color: #f1c40f;
    animation: gold-pulse 2s infinite;
}
@keyframes gold-pulse {
    0% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
    100% { opacity: 0.4; transform: scale(1); }
}
</style>

<?php include '../includes/footer.php'; ?>
