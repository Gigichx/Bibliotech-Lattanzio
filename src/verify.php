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
        

        $sql = "
            SELECT 
                mt.id as token_id,
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
        ";
        
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
    <title>Verifica Accesso - BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg,
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .verify-card {
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .icon {
            font-size: 4rem;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verify-card">
            <div class="card">
                <div class="card-body p-5 text-center">
                    <?php if ($error): ?>
                        <div class="icon">❌</div>
                        <h2 class="card-title mb-4">Accesso Non Riuscito</h2>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <a href="/login.php" class="btn btn-primary btn-lg mt-3">
                            Torna al Login
                        </a>
                        
                        <div class="mt-4 p-3 bg-light rounded text-start">
                            <small class="text-muted">
                                <strong>Possibili cause:</strong><br>
                                • Il link è già stato utilizzato<br>
                                • Il link è scaduto (15 minuti di validità)<br>
                                • Il link non è valido<br><br>
                                Richiedi un nuovo link di accesso dalla pagina di login.
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="icon">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>
                        <h2 class="card-title mb-4">Verifica in corso...</h2>
                        <p class="text-muted">Attendere prego.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
