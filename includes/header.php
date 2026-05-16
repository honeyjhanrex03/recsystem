<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DNSC REC | Davao Del Norte State College</title>

    <!-- Meta SEO -->
    <meta name="description"
        content="Davao Del Norte State College Research Ethics Committee Review and Approval System. Professional, secure, and streamlined ethical oversight.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/logo.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/logo.png?v=1.1">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/logo.png?v=1.1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6.4 (Premium icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Montserrat:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Custom Premium Styles (cache-busted) -->
    <?php
    // Use __DIR__ so this works on localhost AND any live host
    $cssFile = dirname(__DIR__) . '/assets/css/style.css';
    $cssVer = file_exists($cssFile) ? filemtime($cssFile) : '1';
    $jsFile = dirname(__DIR__) . '/assets/js/script.js';
    $jsVer = file_exists($jsFile) ? filemtime($jsFile) : '1';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $cssVer; ?>">
    <meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">

    <!-- SweetAlert2 Premium -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Animation Layer -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>

<body class="<?php
if (isset($bodyClass)) {
    echo $bodyClass;
} else {
    echo (basename($_SERVER['PHP_SELF']) == 'systemadministrator.php') ? 'login-page' : 'dashboard-page';
}
?>">
