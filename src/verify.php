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
        db_begin();

        $token_data = db_fetch_one("
            SELECT
                mt.id       AS token_id,
                mt.id_utente,
                mt.scadenza,
                mt.usato,
                u.id,
                u.nome,
                u.email,
                u.ruolo
            FROM magic_tokens mt
            JOIN utenti u ON mt.id_utente = u.id
            WHERE mt.token = ?
            FOR UPDATE
        ", [$token]);

        if (!$token_data) {
            $error = 'Token non valido. Richiedi un nuovo link di accesso.';
        } elseif ($token_data['usato'] == 1) {
            $error = 'Questo link è già stato utilizzato. Richiedi un nuovo link di accesso.';
        } elseif (strtotime($token_data['scadenza']) < time()) {
            $error = 'Questo link è scaduto. Richiedi un nuovo link di accesso.';
        } else {
            db_query('UPDATE magic_tokens SET usato = 1 WHERE id = ?', [$token_data['token_id']]);
            db_commit();

            createUserSession([
                'id'    => $token_data['id'],
                'nome'  => $token_data['nome'],
                'ruolo' => $token_data['ruolo'],
            ]);

            header('Location: ' . getRedirectAfterLogin());
            exit;
        }

        db_rollback();

    } catch (Exception $e) {
        db_rollback();
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
<body class="login-page">
<div class="login-card" style="max-width:480px;">

    <div class="d-flex align-items-center gap-2 mb-4">
        <img src="/assets/IMG/logo.png" alt="BiblioTech" style="height:36px; width:36px; object-fit:contain; border-radius:8px;">
        <div>
            <h1 style="font-size:1.6rem; margin:0; line-height:1;">BiblioTech</h1>
            <p class="subtitle" style="margin:0;">Sistema di Gestione Biblioteca</p>
        </div>
    </div>

    <hr class="login-divider">

    <?php if ($error): ?>
        <div class="text-center mb-4">
            <div style="width:64px;height:64px;background:rgba(192,57,43,0.08);border:1px solid rgba(192,57,43,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.6rem;">❌</div>
            <h2 style="font-size:1.35rem;margin-bottom:0.4rem;">Accesso non riuscito</h2>
            <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">Non è stato possibile verificare il tuo link.</p>
        </div>

        <div class="bt-alert bt-alert-danger mb-4">
            <span>⚠️</span>
            <div><?= htmlspecialchars($error) ?></div>
        </div>

        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:0.9rem 1.1rem;font-size:0.82rem;color:var(--text-muted);margin-bottom:1.5rem;line-height:1.8;">
            <strong style="color:var(--text);display:block;margin-bottom:0.3rem;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.05em;">Possibili cause</strong>
            Il link è già stato utilizzato in precedenza<br>
            Il link è scaduto (validità 15 minuti)<br>
            Il link non è corretto o è stato modificato
        </div>

        <a href="/login.php" class="btn-primary-bt w-100 py-2">Richiedi un nuovo link</a>

    <?php else: ?>
        <div class="text-center">
            <div style="width:64px;height:64px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <div class="spinner-border text-primary" role="status" style="width:1.6rem;height:1.6rem;border-width:2px;">
                    <span class="visually-hidden">Caricamento…</span>
                </div>
            </div>
            <h2 style="font-size:1.35rem;margin-bottom:0.4rem;">Verifica in corso…</h2>
            <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">Stiamo verificando il tuo link.<br>Sarai reindirizzato a breve.</p>
        </div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>