<?php
session_start();

// Determine where to send the user BEFORE destroying the session
$role = $_SESSION['role'] ?? 'staff';
$redirect = ($role === 'author') ? 'author_login' : 'systemadministrator';

session_unset();
session_destroy();

header("Location: " . $redirect);
exit();
?>
