<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isAuthenticated()) {
    header('Location: ' . getRedirectAfterLogin());
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Inserisci un indirizzo email valido.';
    } else {
        try {
            $user = db_fetch_one('SELECT id, nome, email FROM utenti WHERE email = ?', [$email]);
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiration = date('Y-m-d H:i:s', time() + 900);
                
                db_query(
                    'INSERT INTO magic_tokens (id_utente, token, scadenza, usato) VALUES (?, ?, ?, 0)',
                    [$user['id'], $token, $expiration]
                );
                
                $app_url = getenv('APP_URL') ?: 'http://localhost:8085';
                $magic_link = $app_url . '/verify.php?token=' . $token;
                
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = getenv('MAILTRAP_HOST') ?: 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth = true;
                    $mail->Username = getenv('MAILTRAP_USER');
                    $mail->Password = getenv('MAILTRAP_PASSWORD');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = (int)(getenv('MAILTRAP_PORT') ?: 2525);
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom('noreply@bibliotech.local', 'BiblioTech');
                    $mail->addAddress($email, $user['nome']);
                    $mail->isHTML(true);
                    $mail->Subject = 'BiblioTech — Il tuo link di accesso';
                    $mail->Body = "
                        <div style='font-family:Arial,sans-serif;max-width:560px;margin:0 auto;'>
                            <div style='background:#1a3a5c;padding:24px;text-align:center;border-radius:8px 8px 0 0;'>
                                <h1 style='color:#fff;margin:0;font-size:22px;'>📚 BiblioTech</h1>
                            </div>
                            <div style='padding:32px;background:#f7f5f0;border:1px solid #e2ddd6;border-radius:0 0 8px 8px;'>
                                <h2 style='color:#1a3a5c;margin-top:0;'>Ciao {$user['nome']},</h2>
                                <p style='color:#444;'>Hai richiesto di accedere a BiblioTech. Clicca sul pulsante qui sotto:</p>
                                <div style='text-align:center;margin:28px 0;'>
                                    <a href='{$magic_link}' style='background:#1a3a5c;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;display:inline-block;'>Accedi a BiblioTech →</a>
                                </div>
                                <p style='font-size:13px;color:#7a7369;'>Oppure copia questo link:<br><span style='color:#1a3a5c;word-break:break-all;'>{$magic_link}</span></p>
                                <hr style='border:none;border-top:1px solid #e2ddd6;margin:20px 0;'>
                                <p style='font-size:12px;color:#aaa;'>⏱ Valido <strong>15 minuti</strong> · Monouso</p>
                            </div>
                        </div>
                    ";
                    $mail->AltBody = "Ciao {$user['nome']},\n\nLink BiblioTech:\n{$magic_link}\n\nValido 15 minuti, monouso.";
                    $mail->send();
                    $success = "Ti abbiamo inviato il link di accesso a {$email}.";
                } catch (Exception $e) {
                    error_log("Errore invio email: " . $e->getMessage());
                    $error = "Errore nell'invio dell'email. Riprova più tardi.";
                }
            } else {
                $success = "Se l'email è registrata nel sistema, riceverai un link per accedere.";
            }
        } catch (PDOException $e) {
            error_log("Errore DB in login: " . $e->getMessage());
            $error = 'Errore del sistema. Riprova più tardi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi — BiblioTech</title>
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
        
        <h1 style="text-align:center;">BiblioTech</h1>
        <p class="subtitle" style="text-align:center;">Sistema di Gestione Biblioteca</p>
        
        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="bt-alert bt-alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bt-alert bt-alert-success">
                <span>📧</span>
                <div>
                    <strong>Email inviata!</strong><br>
                    <?= htmlspecialchars($success) ?><br>
                    <small>Controlla la tua inbox su Mailtrap.</small>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Form o link per nuovo accesso -->
        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="bt-form-group">
                <label class="bt-label" for="email">Indirizzo Email</label>
                <input
                    type="email"
                    class="bt-input"
                    id="email"
                    name="email"
                    placeholder="tua.email@example.com"
                    required
                    autofocus>
                <div style="font-size:.78rem; color:var(--text-muted); margin-top:.35rem;">
                    Inserisci l'email con cui sei registrato al sistema
                </div>
            </div>
            
            <button type="submit" class="btn-primary-bt w-100 justify-content-center py-2">
                📨 Invia Link di Accesso
            </button>
        </form>
        <?php else: ?>
            <a href="/login.php" class="btn-primary-bt w-100 justify-content-center py-2">
                ← Richiedi un altro link
            </a>
        <?php endif; ?>
        
        <hr class="login-divider">
        
        <div class="text-center mb-3">
            <small style="color:var(--text-muted); font-size:.78rem;">
                🔒 Nessuna password richiesta &middot; Link sicuro monouso &middot; Valido 15 minuti
            </small>
        </div>
        
        <!-- Account di test -->
        <div class="test-accounts">
            <strong>👥 Account di test</strong>
            <code>mario.rossi@example.com</code> — studente<br>
            <code>laura.bianchi@example.com</code> — studente<br>
            <code>giuseppe.verdi@example.com</code> — studente<br>
            <code>anna.biblioteca@example.com</code> — bibliotecario
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>