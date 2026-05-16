<?php
session_start();
require_once 'config/database.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/");
    exit();
}

    $bodyClass = 'landing-page';
    include 'includes/header.php';
    ?>

    <!-- Error Toast / Alert -->
    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
        <div class="position-fixed top-0 end-0 p-4" style="z-index: 9999;">
            <div class="card border-0 shadow-lg animate-up bg-white overflow-hidden" style="width: 350px; border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box bg-soft-red text-red rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-shield-alt fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-navy mb-1">Access Denied</h6>
                            <p class="text-muted small mb-0">Direct directory browsing is restricted for security reasons.</p>
                        </div>
                    </div>
                </div>
                <div class="progress rounded-0" style="height: 4px; background: rgba(0,0,0,0.05);">
                    <div class="progress-bar bg-red animate-shrink" style="width: 100%;"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <style>
        .animate-shrink {
            animation: shrink 4s linear forwards;
        }
        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>

    <script>
        // Auto-hide the error alert after 4 seconds
        document.addEventListener('DOMContentLoaded', () => {
            const errorAlert = document.querySelector('.animate-up.bg-white');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.transition = 'all 0.5s ease';
                    errorAlert.style.opacity = '0';
                    errorAlert.style.transform = 'translateY(-20px)';
                    setTimeout(() => errorAlert.remove(), 500);
                }, 4000);
            }
        });
    </script>

<div class="landing-hero" style="background: url('assets/images/researchers.jpg') center/cover no-repeat; min-height: 100vh; position: relative;">
    <!-- Dark overlay for better text readability -->
    <div class="hero-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(26, 43, 75, 0.85); z-index: 0;"></div>

    <!-- Public Header -->
    <nav class="navbar navbar-expand-lg border-bottom px-2 px-md-4 py-3 position-relative z-1" style="border-color: rgba(255,255,255,0.15) !important; background: rgba(0,0,0,0.2) !important; backdrop-filter: blur(5px);">
        <div class="container-fluid align-items-center">
            <a href="index" class="d-flex align-items-center text-decoration-none text-white">
                <img src="assets/images/logo.png" alt="DNSC" width="45" height="45" class="me-2 me-md-3 bg-white rounded-circle p-1">
                <div class="lh-1">
                    <h5 class="fw-bold mb-1 responsive-title" style="font-size: 16px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">DNSC REC</h5>
                    <span class="responsive-subtitle" style="font-size: 12px; opacity: 1; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Research Ethics Committee</span>
                </div>
            </a>
            
            <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars fa-lg text-white"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav gap-2 gap-lg-4 mt-3 mt-lg-0 align-items-lg-center">

                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold opacity-75 hover-opacity" href="fees" style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">REC Fees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-bold opacity-75 hover-opacity" href="team" style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">The Committee</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light rounded-pill px-4 fw-bold text-navy shadow-sm mt-2 mt-lg-0" href="author_login">
                            <i class="fas fa-sign-in-alt me-2 text-primary"></i> Portal Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container position-relative z-1 d-flex flex-column justify-content-center py-5" style="min-height: calc(100vh - 80px);">
        <div class="row justify-content-center text-center">
            <div class="col-lg-10 animate-up">
                
                <h1 class="display-4 fw-bold text-white mb-4" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                    Research Ethics Committee<br>Review Application System
                </h1>
                <p class="lead text-white mb-5" style="opacity: 0.9;">
                    A streamlined, secure, and transparent protocol submission portal for ensuring the highest ethical standards in DNSC research.
                </p>

                <div class="d-flex flex-column flex-md-row justify-content-center gap-3 px-3">
                    <a href="<?php echo BASE_URL; ?>author_login" class="btn btn-light btn-lg px-4 px-md-5 py-3 fw-bold shadow rounded-pill text-navy hover-up" style="font-size: 1rem;">
                        <i class="fas fa-user-circle me-2 text-primary"></i> Committee Portal
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>track" class="btn btn-outline-light btn-lg px-4 px-md-5 py-3 fw-bold shadow rounded-pill hover-up" style="font-size: 1rem; border-width: 2px;">
                        <i class="fas fa-search me-2"></i> Track Submission
                    </a>

                </div>

                <div class="mt-5 pt-4 border-top border-light" style="opacity: 1;">
                    <div class="mb-4 text-center">
                        <span class="badge rounded-pill bg-gold text-navy px-3 py-2 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1.5px;">Official Department Information</span>
                    </div>
                    <div class="row g-4 text-white text-md-start text-center">
                        <div class="col-md-4">
                            <h6 class="fw-bold text-gold small mb-3 text-uppercase" style="letter-spacing:1px;">Location</h6>
                            <p class="small mb-0 opacity-75">
                                <i class="fas fa-map-marker-alt me-2 text-gold"></i> DNSC, Brgy. New Visayas,<br>Panabo City, 8105
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-gold small mb-3 text-uppercase" style="letter-spacing:1px;">Contact Us</h6>
                            <p class="small mb-1 opacity-75">
                                <i class="fas fa-phone-alt me-2 text-gold"></i> 0995 573 8237
                            </p>
                            <p class="small mb-0 opacity-75">
                                <i class="fas fa-envelope me-2 text-gold"></i> rec@dnsc.edu.ph
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-gold small mb-3 text-uppercase" style="letter-spacing:1px;">Connect</h6>
                            <p class="mb-2">
                                <a href="https://dnsc.edu.ph/" target="_blank" class="text-white text-decoration-none small opacity-75 hover-gold">
                                    <i class="fas fa-globe me-2 text-gold"></i> DNSC Official Website
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="https://www.facebook.com/profile?id=61566750576733" target="_blank" class="text-white text-decoration-none small opacity-75 hover-gold">
                                    <i class="fab fa-facebook me-2 text-gold"></i> REC Facebook Page
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .landing-hero {
        background-attachment: fixed !important;
    }
    .hover-gold:hover {
        color: var(--gold) !important;
        opacity: 1 !important;
    }
    .landing-page .navbar {
        transition: all 0.4s ease;
    }
    .btn-light:hover, .hover-up:not(.btn-outline-light):hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
        transition: all 0.3s ease;
    }
    .btn-outline-light:hover {
        background-color: #ffffff !important;
        color: var(--navy) !important;
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }
    .btn-outline-light {
        color: #ffffff !important;
        border-color: #ffffff !important;
    }

    /* Responsive adjustments for short viewports or small monitors */
    @media (max-height: 800px) {
        .display-4 {
            font-size: 2.2rem !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.2;
        }
        .lead {
            font-size: 0.95rem !important;
            margin-bottom: 3rem !important;
        }
        .btn-lg {
            padding: 0.6rem 1.2rem !important;
            font-size: 0.9rem !important;
        }
        .mt-5, .mt-4 {
            margin-top: 1.25rem !important;
        }
        .pt-4 {
            padding-top: 0.75rem !important;
        }
        .mb-4.text-center {
            margin-bottom: 1rem !important;
        }
    }

    @media (max-width: 768px) {
        .display-4 {
            font-size: 2rem !important;
        }
        .lead {
            font-size: 1rem !important;
            margin-bottom: 2rem !important;
        }
    }

    @media (max-width: 576px) {
        .display-4 {
            font-size: 1.7rem !important;
        }
        .container {
            padding-top: 2rem !important;
            padding-bottom: 4rem !important;
        }
        .navbar-expand-lg {
            padding: 0.5rem 0.75rem !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>
