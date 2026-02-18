<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated() {
    return isset($_SESSION['user_id'], $_SESSION['ruolo'], $_SESSION['nome']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }
}

function requireRole($required_roles) {
    requireAuth();
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    if (!in_array($_SESSION['ruolo'], $required_roles)) {
        if ($_SESSION['ruolo'] === 'studente') {
            header('Location: /dashboard.php?error=access_denied');
        } else {
            header('Location: /dashboard.php?error=access_denied');
        }
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['ruolo'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['nome'] ?? null;
}

function isBibliotecario() {
    return getCurrentUserRole() === 'bibliotecario';
}

function isStudente() {
    return getCurrentUserRole() === 'studente';
}

function logout() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

function createUserSession($user) {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['ruolo']      = $user['ruolo'];
    $_SESSION['nome']       = $user['nome'];
    $_SESSION['login_time'] = time();
}

function getRedirectAfterLogin() {
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $redirect;
    }
    return '/dashboard.php';
}