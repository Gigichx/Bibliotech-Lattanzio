<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isAuthenticated() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['ruolo']) && 
           isset($_SESSION['nome']);
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

        error_log("Unauthorized access attempt: User {$_SESSION['user_id']} ({$_SESSION['ruolo']}) tried to access " . $_SERVER['REQUEST_URI']);
        

        if ($_SESSION['ruolo'] === 'studente') {
            header('Location: /libri.php?error=access_denied');
        } else {
            header('Location: /gestione_restituzioni.php?error=access_denied');
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
    

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['ruolo'] = $user['ruolo'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['login_time'] = time();
    

    error_log("User logged in: {$user['id']} ({$user['ruolo']})");
}


function getRedirectAfterLogin() {

    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $redirect;
    }
    

    if (isBibliotecario()) {
        return '/gestione_restituzioni.php';
    } else {
        return '/libri.php';
    }
}
