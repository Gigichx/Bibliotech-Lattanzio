<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$libro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error    = '';
$success  = '';

if (!$libro_id) {
    header('Location: /libri.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow']) && isStudente()) {
    try {
        db_begin();

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

        $stmt = db_query(
            'UPDATE libri SET copie_disponibili = copie_disponibili - 1 WHERE id = ? AND copie_disponibili > 0',
            [$libro_id]
        );

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            throw new Exception('Prestito non riuscito. Il libro potrebbe non essere più disponibile.');
        }

        db_commit();
        $success = 'Prestito effettuato con successo! Puoi visualizzarlo nella sezione "I Miei Prestiti".';

    } catch (Exception $e) {
        db_rollback();
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
        'SELECT p.*, u.nome AS utente_nome
         FROM prestiti p
         JOIN utenti u ON p.id_utente = u.id
         WHERE p.id_libro = ?
         ORDER BY p.data_prestito DESC
         LIMIT 10',
        [$libro_id]
    );

} catch (Exception $e) {
    error_log("Error fetching book: " . $e->getMessage());
    header('Location: /libri.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($libro['titolo']) ?> — BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/assets/IMG/logo.png">
</head>
<body class="page-wrapper">

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="page-title-bar">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <div>
                <h1><?= htmlspecialchars($libro['titolo']) ?></h1>
                <p class="subtitle">di <?= htmlspecialchars($libro['autore']) ?></p>
            </div>
            <?php if ($libro['copie_disponibili'] > 0): ?>
                <span class="badge bg-success fs-6">Disponibile</span>
            <?php else: ?>
                <span class="badge bg-danger fs-6">Non disponibile</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="container fade-up">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/libri.php">Catalogo</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($libro['titolo']) ?></li>
            </ol>
        </nav>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <div class="col-md-8">

                <div class="book-cover-block">
                    <span class="cover-icon">📘</span>
                    <h2><?= htmlspecialchars($libro['titolo']) ?></h2>
                    <div class="cover-author">di <?= htmlspecialchars($libro['autore']) ?></div>
                </div>

                <?php if (isStudente()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php if ($user_has_active_loan): ?>
                                <div class="alert alert-info mb-0">
                                    <strong>Hai già questo libro in prestito.</strong><br>
                                    <a href="/prestiti.php">Visualizza i tuoi prestiti</a>
                                </div>
                            <?php elseif ($libro['copie_disponibili'] > 0): ?>
                                <p class="text-muted mb-3">
                                    <?= $libro['copie_disponibili'] ?> cop<?= $libro['copie_disponibili'] > 1 ? 'ie' : 'ia' ?>
                                    disponibil<?= $libro['copie_disponibili'] > 1 ? 'i' : 'e' ?>
                                    su <?= $libro['copie_totali'] ?> totali
                                </p>
                                <form method="POST" action=""
                                      onsubmit="return confirm('Confermi di voler prendere in prestito questo libro?');">
                                    <input type="hidden" name="borrow" value="1">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Prendi in Prestito
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted mb-3">
                                    Tutte le <?= $libro['copie_totali'] ?> copie sono attualmente in prestito.
                                </p>
                                <button class="btn btn-secondary btn-lg" disabled>Non disponibile</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isBibliotecario() && !empty($prestiti_history)): ?>
                    <div class="card">
                        <div class="card-header">Storico Prestiti</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Utente</th>
                                            <th>Data Prestito</th>
                                            <th>Data Restituzione</th>
                                            <th>Stato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestiti_history as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['utente_nome']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($p['data_prestito'])) ?></td>
                                                <td>
                                                    <?= $p['data_restituzione']
                                                        ? date('d/m/Y', strtotime($p['data_restituzione']))
                                                        : '—' ?>
                                                </td>
                                                <td>
                                                    <?php if ($p['data_restituzione']): ?>
                                                        <span class="badge bg-secondary">Restituito</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">In prestito</span>
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
                    <div class="card-header">Informazioni</div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <div class="meta-row">
                                <dt>Autore</dt>
                                <dd><?= htmlspecialchars($libro['autore']) ?></dd>
                            </div>
                            <div class="meta-row">
                                <dt>Copie Totali</dt>
                                <dd><?= $libro['copie_totali'] ?></dd>
                            </div>
                            <div class="meta-row">
                                <dt>Copie Disponibili</dt>
                                <dd class="<?= $libro['copie_disponibili'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $libro['copie_disponibili'] ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <?php if (isBibliotecario()): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <a href="/libro_form.php?id=<?= $libro['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                                Modifica Libro
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="site-footer">
        <div class="container text-center">
            <small>BiblioTech — Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>