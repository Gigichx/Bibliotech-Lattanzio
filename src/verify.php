<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (isAuthenticated()) {
    header('Location: ' . getRedirectAfterLogin());
    exit;
}

$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token mancante. Richiedi un nuovo link di accesso.';
} else {
    try {
        $pdo->beginTransaction();
        
        $sql = "SELECT mt.id as token_id, mt.id_utente, mt.scadenza, mt.usato, u.id, u.nome, u.email, u.ruolo 
                FROM magic_tokens mt 
                JOIN utenti u ON mt.id_utente = u.id 
                WHERE mt.token = ? 
                FOR UPDATE";
        
        $token_data = db_fetch_one($sql, [$token]);
        
        if (!$token_data) {
            $error = 'Token non valido. Richiedi un nuovo link di accesso.';
        } elseif ($token_data['usato'] == 1) {
            $error = 'Questo link è già stato utilizzato. Richiedi un nuovo link di accesso.';
        } elseif (strtotime($token_data['scadenza']) < time()) {
            $error = 'Questo link è scaduto. Richiedi un nuovo link di accesso.';
        } else {
            db_query('UPDATE magic_tokens SET usato = 1 WHERE id = ?', [$token_data['token_id']]);
            $pdo->commit();
            
            createUserSession([
                'id' => $token_data['id'],
                'nome' => $token_data['nome'],
                'ruolo' => $token_data['ruolo']
            ]);
            
            header('Location: ' . getRedirectAfterLogin());
            exit;
        }
        
        $pdo->rollback();
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log("Token verification error: " . $e->getMessage());
        $error = 'Errore del sistema. Riprova più tardi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Accesso — BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/assets/IMG/logo.png">
</head>
<body>

<div class="login-page">
    <div class="login-card">
        
        <!-- Logo -->
        <div style="text-align:center; margin-bottom:1.2rem;">
            <img src="/assets/IMG/logo.png" alt="BiblioTech Logo" style="width:80px; height:80px; object-fit:contain;">
        </div>
        
        <?php if ($error): ?>
            <div style="text-align:center; font-size:4rem; margin-bottom:1rem;">❌</div>
            <h2 style="text-align:center; margin-bottom:1rem;">Accesso Non Riuscito</h2>
            
            <div class="bt-alert bt-alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
            
            <a href="/login.php" class="btn-primary-bt w-100 justify-content-center py-2 mt-3">
                Torna al Login
            </a>
            
            <div class="test-accounts mt-4">
                <strong>Possibili cause:</strong><br>
                • Il link è già stato utilizzato<br>
                • Il link è scaduto (15 minuti di validità)<br>
                • Il link non è valido<br><br>
                Richiedi un nuovo link di accesso dalla pagina di login.
            </div>
        <?php else: ?>
            <div style="text-align:center; font-size:4rem; margin-bottom:1rem;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
            </div>
            <h2 style="text-align:center; margin-bottom:1rem;">Verifica in corso...</h2>
            <p class="text-center text-muted">Attendere prego.</p>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>