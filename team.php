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
    <title>The Committee - DNSC REC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .member-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border-radius: 15px; border: none; overflow: hidden; background: linear-gradient(145deg, #ffffff, #f0f4f8); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .member-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(26, 43, 75, 0.15); }
        .avatar-container { border: 4px solid var(--navy); border-radius: 50%; padding: 3px; display: inline-block; background: white; margin-bottom: 15px; }
        .chairperson-card { background: linear-gradient(135deg, #1a2b4b 0%, #2a4365 100%); color: white; }
        .chairperson-card .avatar-container { border-color: var(--gold); }
        .chairperson-card .text-navy { color: white !important; }
        .chairperson-card .text-muted { color: rgba(255,255,255,0.8) !important; }
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
        <h1 class="display-4 fw-bold mb-3">Meet the Board</h1>
        <p class="lead opacity-75 mb-0" style="max-width: 700px; margin: 0 auto;">The interdisciplinary team ensuring the highest ethical standards in DNSC research.</p>
    </div>
</div>

<div class="container py-5">
    
    <!-- COMMITTEE MEMBERS Section -->
    <section class="py-2">
        <?php
        function getAvatar(string $name, ?string $image = null): string {
            if ($image && file_exists('uploads/profiles/' . $image)) {
                return 'uploads/profiles/' . $image;
            }
            return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=1a2b4b&color=fff&size=150&font-size=0.33&bold=true";
        }

        // Fetch all active committee members
        $stmt = $pdo->query("SELECT * FROM admins WHERE status = 'active' ORDER BY 
            CASE 
                WHEN role = 'rec_chair' THEN 1 
                WHEN role = 'rec_secretary' THEN 2 
                WHEN role = 'rec_member' THEN 3 
                WHEN role = 'rec_staff' THEN 4 
                ELSE 5 
            END ASC, name ASC");
        $members = $stmt->fetchAll();

        $chair = null;
        $other_members = [];

        foreach ($members as $m) {
            if ($m['role'] === 'rec_chair') {
                $chair = $m;
            } else {
                $other_members[] = $m;
            }
        }
        ?>

        <div class="row g-4 align-items-center">
            <!-- REC Chair Column (Left) -->
            <div class="col-lg-3">
                <?php if ($chair): ?>
                    <div class="card member-card chairperson-card p-4 h-100 text-center shadow-lg" style="min-height: 500px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="card-body">
                            <div class="avatar-container mb-4">
                                <img src="<?php echo getAvatar($chair['name'], $chair['profile_image']); ?>" alt="REC Chair" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($chair['name']); ?></h3>
                            <span class="badge bg-gold text-navy px-3 py-2 rounded-pill mb-4 fw-bold fs-6">CHAIRPERSON</span>
                            <p class="mb-1 opacity-75 fs-5"><?php echo htmlspecialchars($chair['academic_rank'] ?? 'REC Chairperson'); ?></p>
                            <p class="mb-0 opacity-75 small"><?php echo htmlspecialchars($chair['academic_degree'] ?? ''); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card member-card chairperson-card p-4 h-100 text-center shadow-lg d-flex align-items-center justify-content-center" style="min-height: 500px;">
                        <div class="opacity-50 text-center">
                            <i class="fas fa-user-tie fa-4x mb-3"></i>
                            <p>Chairperson position pending assignment</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Members Grid Column (Right) -->
            <div class="col-lg-9">
                <div class="row g-3">
                    <?php if (empty($other_members)): ?>
                        <div class="col-12 text-center py-5">
                            <p class="text-muted">Other committee members will appear here once active.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($other_members as $m): 
                            $roleLabel = 'REC MEMBER';
                            $roleClass = 'text-primary';
                            if ($m['role'] === 'rec_secretary') {
                                $roleLabel = 'REC SECRETARY';
                            } elseif ($m['role'] === 'rec_staff') {
                                $roleLabel = 'STAFF';
                                $roleClass = 'text-success';
                            }
                        ?>
                            <div class="col-md-3 col-6 text-center">
                                <div class="card member-card p-3 h-100 shadow-sm animate-up">
                                    <div class="card-body p-1">
                                        <div class="avatar-container mb-2">
                                            <img src="<?php echo getAvatar($m['name'], $m['profile_image']); ?>" alt="<?php echo $roleLabel; ?>" class="rounded-circle img-fluid" style="width: 75px; height: 75px; object-fit: cover;">
                                        </div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.8rem;"><?php echo htmlspecialchars($m['name']); ?></h6>
                                        <span class="<?php echo $roleClass; ?> small fw-bold d-block mb-2" style="font-size: 0.65rem;"><?php echo $roleLabel; ?></span>
                                        <p class="text-muted mb-0" style="font-size: 0.65rem; line-height: 1.2;">
                                            <?php echo htmlspecialchars($m['academic_rank'] ?? ''); ?><br>
                                            <?php echo htmlspecialchars($m['academic_degree'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
