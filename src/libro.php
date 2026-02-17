<?php


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';


requireAuth();

$libro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = '';
$success = '';

if (!$libro_id) {
    header('Location: /libri.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow']) && isStudente()) {
    try {

        $pdo->beginTransaction();
        

        $libro = db_fetch_one(
            'SELECT * FROM libri WHERE id = ? AND copie_disponibili > 0 FOR UPDATE',
            [$libro_id]
        );
        
        if (!$libro) {
            throw new Exception('Libro non disponibile per il prestito.');
        }
        

        $existing_loan = db_fetch_one(
            'SELECT id FROM prestiti WHERE id_utente = ? AND id_libro = ? AND data_restituzione IS NULL',
            [getCurrentUserId(), $libro_id]
        );
        
        if ($existing_loan) {
            throw new Exception('Hai già preso in prestito questo libro.');
        }
        

        db_query(
            'INSERT INTO prestiti (id_utente, id_libro, data_prestito) VALUES (?, ?, CURDATE())',
            [getCurrentUserId(), $libro_id]
        );
        

        $result = db_query(
            'UPDATE libri SET copie_disponibili = copie_disponibili - 1 WHERE id = ? AND copie_disponibili > 0',
            [$libro_id]
        );
        

        if ($result->rowCount() === 0) {
            throw new Exception('Prestito non riuscito. Il libro potrebbe non essere più disponibile.');
        }
        

        $pdo->commit();
        
        $success = 'Prestito effettuato con successo! Puoi visualizzarlo nella sezione "I Miei Prestiti".';
        
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log("Borrow error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}


try {
    $libro = db_fetch_one('SELECT * FROM libri WHERE id = ?', [$libro_id]);
    
    if (!$libro) {
        header('Location: /libri.php');
        exit;
    }
    

    $user_has_active_loan = false;
    if (isStudente()) {
        $loan = db_fetch_one(
            'SELECT id FROM prestiti WHERE id_utente = ? AND id_libro = ? AND data_restituzione IS NULL',
            [getCurrentUserId(), $libro_id]
        );
        $user_has_active_loan = (bool)$loan;
    }
    

    $prestiti_history = db_fetch_all(
        'SELECT p.*, u.nome as utente_nome, u.ruolo as utente_ruolo 
         FROM prestiti p 
         JOIN utenti u ON p.id_utente = u.id 
         WHERE p.id_libro = ? 
         ORDER BY p.data_prestito DESC 
         LIMIT 10',
        [$libro_id]
    );
    
} catch (PDOException $e) {
    error_log("Error fetching book details: " . $e->getMessage());
    header('Location: /libri.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($libro['titolo']) ?> - BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/IMG/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/IMG/logo.png">
    <link rel="apple-touch-icon" href="/assets/IMG/logo.png">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/libri.php">📚 BiblioTech</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/libri.php">Catalogo</a>
                    </li>
                    <?php if (isStudente()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/prestiti.php">I Miei Prestiti</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isBibliotecario()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/gestione_restituzioni.php">Gestione Restituzioni</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            👤 <?= htmlspecialchars(getCurrentUserName()) ?>
                            <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars(getCurrentUserRole()) ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/libri.php">Catalogo</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($libro['titolo']) ?></li>
                    </ol>
                </nav>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title mb-3"><?= htmlspecialchars($libro['titolo']) ?></h1>
                        <h5 class="card-subtitle mb-4 text-muted">
                            di <?= htmlspecialchars($libro['autore']) ?>
                        </h5>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Disponibilità</h6>
                                <?php if ($libro['copie_disponibili'] > 0): ?>
                                    <span class="badge bg-success fs-6">
                                        ✓ Disponibile
                                    </span>
                                    <p class="mt-2">
                                        <?= $libro['copie_disponibili'] ?> cop<?= $libro['copie_disponibili'] > 1 ? 'ie' : 'ia' ?> 
                                        disponibil<?= $libro['copie_disponibili'] > 1 ? 'i' : 'e' ?> 
                                        su <?= $libro['copie_totali'] ?> totali
                                    </p>
                                <?php else: ?>
                                    <span class="badge bg-danger fs-6">
                                        ✗ Non disponibile
                                    </span>
                                    <p class="mt-2">
                                        Tutte le <?= $libro['copie_totali'] ?> copie sono attualmente in prestito
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isStudente()): ?>
                            <hr>
                            <?php if ($user_has_active_loan): ?>
                                <div class="alert alert-info">
                                    <strong>ℹ️ Hai già questo libro in prestito</strong><br>
                                    Visualizza i tuoi prestiti nella sezione <a href="/prestiti.php">I Miei Prestiti</a>
                                </div>
                            <?php elseif ($libro['copie_disponibili'] > 0): ?>
                                <form method="POST" action="" onsubmit="return confirm('Confermi di voler prendere in prestito questo libro?');">
                                    <input type="hidden" name="borrow" value="1">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        📖 Prendi in Prestito
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg" disabled>
                                    Non disponibile
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isBibliotecario() && !empty($prestiti_history)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Storico Prestiti</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utente</th>
                                            <th>Data Prestito</th>
                                            <th>Data Restituzione</th>
                                            <th>Stato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestiti_history as $prestito): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prestito['utente_nome']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                                <td>
                                                    <?= $prestito['data_restituzione'] 
                                                        ? date('d/m/Y', strtotime($prestito['data_restituzione'])) 
                                                        : '-' ?>
                                                </td>
                                                <td>
                                                    <?php if ($prestito['data_restituzione']): ?>
                                                        <span class="badge bg-secondary">Restituito</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">In prestito</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Informazioni</h6>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>ID Libro</dt>
                            <dd><?= $libro['id'] ?></dd>
                            
                            <dt>Titolo</dt>
                            <dd><?= htmlspecialchars($libro['titolo']) ?></dd>
                            
                            <dt>Autore</dt>
                            <dd><?= htmlspecialchars($libro['autore']) ?></dd>
                            
                            <dt>Copie Totali</dt>
                            <dd><?= $libro['copie_totali'] ?></dd>
                            
                            <dt>Copie Disponibili</dt>
                            <dd><?= $libro['copie_disponibili'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center text-muted">
            <small>BiblioTech - Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
