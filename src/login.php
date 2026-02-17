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
                    $mail->Host       = getenv('MAILTRAP_HOST') ?: 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('MAILTRAP_USER');
                    $mail->Password   = getenv('MAILTRAP_PASSWORD');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = (int)(getenv('MAILTRAP_PORT') ?: 2525);
                    $mail->CharSet    = 'UTF-8';
                    
                    $mail->setFrom('noreply@bibliotech.local', 'BiblioTech');
                    $mail->addAddress($email, $user['nome']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'BiblioTech - Il tuo link di accesso';
                    $mail->Body = "
                        <html>
                        <body>
                            <div>
                                <h1>BiblioTech</h1>
                            </div>
                            <div>
                                <h2>Ciao {$user['nome']},</h2>
                                <p>Hai richiesto di accedere a BiblioTech. Clicca sul pulsante qui sotto:</p>
                                <p>
                                    <a href='{$magic_link}'>
                                        Accedi a BiblioTech
                                    </a>
                                </p>
                                <p>
                                    Oppure copia e incolla questo link nel browser:<br>
                                    <span>{$magic_link}</span>
                                </p>
                                <hr>
                                <p>
                                    Questo link è valido per <strong>15 minuti</strong> e può essere utilizzato <strong>una sola volta</strong>.<br>
                                    Se non hai richiesto questo accesso, ignora questa email.
                                </p>
                            </div>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Ciao {$user['nome']},\n\nClicca su questo link per accedere a BiblioTech:\n{$magic_link}\n\nValido 15 minuti, monouso.";
                    
                    $mail->send();
                    $success = "Ti abbiamo inviato il link di accesso all'email {$email}. Controlla la tua casella (anche spam).";
                    
                } catch (Exception $e) {
                    error_log("Errore invio email: {$mail->ErrorInfo}");
                    $error = 'Errore nell\'invio dell\'email. Riprova più tardi.';
                }
                
            } else {
                $success = 'Se l\'email è registrata nel sistema, riceverai un link per accedere.';
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
    <title>Accedi - BiblioTech</title>
</head>
<body>
    <div>
        <div>
            <div>
                <div></div>
                <h2>BiblioTech</h2>
                <p>Sistema di Gestione Biblioteca</p>
                
                <?php if ($error): ?>
                    <div role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div role="alert">
                        <strong>Email inviata!</strong><br>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST" action="">
                    <div>
                        <label for="email">Indirizzo Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="tua.email@example.com"
                               required 
                               autofocus>
                        <div>Inserisci l'email con cui sei registrato al sistema</div>
                    </div>
                    
                    <div>
                        <button type="submit">
                            Invia Link di Accesso
                        </button>
                    </div>
                </form>
                <?php else: ?>
                    <div>
                        <a href="/login.php">
                            Richiedi un altro link
                        </a>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div>
                    <small>
                        Nessuna password richiesta · Link sicuro monouso · Valido 15 minuti
                    </small>
                </div>
                
                <!-- Account di test visibili solo in ambiente di sviluppo -->
                <div>
                    <small>
                        <strong>Account di test disponibili:</strong><br>
                        <code>mario.rossi@example.com</code> — studente<br>
                        <code>laura.bianchi@example.com</code> — studente<br>
                        <code>giuseppe.verdi@example.com</code> — studente<br>
                        <code>anna.biblioteca@example.com</code> — bibliotecario
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>