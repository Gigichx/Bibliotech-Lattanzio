<?php
require_once __DIR__ . '/includes/auth.php';
if (isAuthenticated()) {
    header('Location: ' . getRedirectAfterLogin());
    exit;
}
header('Location: /login.php');
exit;