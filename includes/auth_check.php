<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enhanced checkAuth for Clean URLs
 */
function checkAuth($allowed_roles = [])
{
    // We can use a simpler approach now with .htaccess
    // If user is not logged in, redirect to the clean 'login' URL
    // We use a relative path logic similar to before but for the clean URL

    $login_url = "systemadministrator";
    if (!file_exists("systemadministrator.php")) {
        $login_url = "../systemadministrator";
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: $login_url");
        exit();
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect to their respective dashboard root if unauthorized
        $dash_url = $_SESSION['role'] . "/";
        if (!is_dir($_SESSION['role'])) {
            $dash_url = "./";
        }
        header("Location: $dash_url?error=unauthorized");
        exit();
    }
}
?>
