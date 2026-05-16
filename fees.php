<?php
session_start();
require_once 'config/database.php';
$bodyClass = 'landing-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REC Fees - DNSC Research Ethics Committee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg border-bottom px-2 px-md-4 py-3 bg-navy text-white shadow-sm sticky-top">
    <div class="container-fluid align-items-center d-flex justify-content-between">
        <a href="index" class="d-flex align-items-center text-decoration-none text-white hover-opacity">
            <img src="assets/images/logo.png" alt="DNSC" width="45" height="45" class="me-2 me-md-3 bg-white rounded-circle p-1">
            <div class="lh-1">
                <h5 class="fw-bold mb-1 responsive-title" style="font-size: 16px;">DNSC REC</h5>
                <span class="responsive-subtitle" style="font-size: 12px; opacity: 0.8;">Research Ethics Committee</span>
            </div>
        </a>

    </div>
</nav>

<!-- Page Header -->
<div class="bg-navy text-white py-5 position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('assets/images/researchers.jpg') center/cover; opacity: 0.1;"></div>
    <div class="container position-relative z-1 text-center py-4">
        <h1 class="display-4 fw-bold mb-3">Evaluation Rates</h1>
        <p class="lead opacity-75 mb-0" style="max-width: 700px; margin: 0 auto;">Official research ethics review fees for external researchers and institutional projects.</p>
    </div>
</div>

<div class="container py-5">
    <!-- REC FEES Section -->
    <section class="mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: rgba(255, 255, 255, 0.95);">
                    <div class="card-header bg-navy text-white text-center py-3 border-0">
                        <h5 class="fw-bold mb-0 text-gold" style="letter-spacing: 2px;"><i class="fas fa-file-invoice-dollar me-2"></i> EXTERNAL RATES</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="row g-5">
                            <!-- Student Rates -->
                            <div class="col-md-6">
                                <h5 class="fw-bold text-navy mb-4 border-bottom pb-3"><i class="fas fa-user-graduate me-2 text-primary"></i> Rates for non-DNSC students</h5>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark fs-5">Doctoral</span>
                                    <span class="fw-bold text-navy fs-5">₱ 5,000 <small class="text-muted fs-6">+ incidentals*</small></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark fs-5">Masteral</span>
                                    <span class="fw-bold text-navy fs-5">₱ 4,000 <small class="text-muted fs-6">+ incidentals*</small></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark fs-5">Undergraduate</span>
                                    <span class="fw-bold text-navy fs-5">₱ 3,000 <small class="text-muted fs-6">+ incidentals*</small></span>
                                </div>
                                <p class="small text-danger fst-italic mb-0 fw-bold">* Incidentals cover miscellaneous expenses during the review process.</p>
                            </div>
                            
                            <!-- Project Rates -->
                            <div class="col-md-6">
                                <h5 class="fw-bold text-navy mb-4 border-bottom pb-3"><i class="fas fa-project-diagram me-2 text-primary"></i> Rates for non-DNSC projects</h5>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark d-block fs-5">Internationally funded</span>
                                    <span class="fw-bold text-navy fs-5">₱ 8,000</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark d-block fs-5">Locally funded</span>
                                    <span class="fw-bold text-navy fs-5">₱ 5,000</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded-3 shadow-sm border border-light">
                                    <span class="fw-bold text-dark d-block fs-5">Patriotic Research</span>
                                    <span class="fw-bold text-navy fs-5">₱ 1,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-4 text-center border-top">
                        <p class="text-muted small mb-2 text-uppercase fw-bold">For inquiries and payment processing, contact us at:</p>
                        <a href="mailto:rec@dnsc.edu.ph" class="btn btn-outline-navy rounded-pill px-4 fw-bold shadow-sm hover-up">
                            <i class="fas fa-envelope me-2"></i> rec@dnsc.edu.ph
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
