<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireRole('bibliotecario');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prestito_id'])) {
    $prestito_id = filter_input(INPUT_POST, 'prestito_id', FILTER_VALIDATE_INT);

    if ($prestito_id) {
        try {
            $pdo->beginTransaction();

            $prestito = db_fetch_one(
                'SELECT p.*, l.titolo AS libro_titolo
                 FROM prestiti p
                 JOIN libri l ON p.id_libro = l.id
                 WHERE p.id = ? AND p.data_restituzione IS NULL
                 FOR UPDATE',
                [$prestito_id]
            );

            if (!$prestito) {
                throw new Exception('Prestito non trovato o già restituito.');
            }

            db_query(
                'UPDATE prestiti SET data_restituzione = CURDATE() WHERE id = ?',
                [$prestito_id]
            );

            db_query(
                'UPDATE libri SET copie_disponibili = copie_disponibili + 1 WHERE id = ?',
                [$prestito['id_libro']]
            );

            $pdo->commit();
            $success = "Restituzione registrata con successo per il libro \"{$prestito['libro_titolo']}\".";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollback();
            error_log("Return error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

$filter_user = $_GET['user']     ?? '';
$filter_book = $_GET['book']     ?? '';
$show_all    = isset($_GET['show_all']);

try {
    $sql    = "
        SELECT
            p.id,
            p.data_prestito,
            u.id    AS utente_id,
            u.nome  AS utente_nome,
            u.email AS utente_email,
            l.id    AS libro_id,
            l.titolo AS libro_titolo,
            l.autore AS libro_autore,
            DATEDIFF(CURDATE(), p.data_prestito) AS giorni_prestito
        FROM prestiti p
        JOIN utenti u ON p.id_utente = u.id
        JOIN libri  l ON p.id_libro  = l.id
        WHERE p.data_restituzione IS NULL
    ";
    $params = [];

    if (!empty($filter_user)) {
        $sql     .= " AND u.nome LIKE ?";
        $params[] = "%{$filter_user}%";
    }
    if (!empty($filter_book)) {
        $sql     .= " AND l.titolo LIKE ?";
        $params[] = "%{$filter_book}%";
    }

    $sql .= " ORDER BY p.data_prestito ASC";

    $prestiti_attivi = db_fetch_all($sql, $params);

    $stats = db_fetch_one('
        SELECT
            COUNT(*)                                                                          AS totale_prestiti_attivi,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), data_prestito) > 30 THEN 1 END)              AS prestiti_in_ritardo,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), data_prestito) BETWEEN 21 AND 30 THEN 1 END) AS prestiti_scadenza_vicina
        FROM prestiti
        WHERE data_restituzione IS NULL
    ');

    $prestiti_recenti = [];
    if ($show_all) {
        $prestiti_recenti = db_fetch_all('
            SELECT
                p.id,
                p.data_prestito,
                p.data_restituzione,
                u.nome  AS utente_nome,
                l.titolo AS libro_titolo,
                l.autore AS libro_autore,
                DATEDIFF(p.data_restituzione, p.data_prestito) AS durata_prestito
            FROM prestiti p
            JOIN utenti u ON p.id_utente = u.id
            JOIN libri  l ON p.id_libro  = l.id
            WHERE p.data_restituzione IS NOT NULL
            ORDER BY p.data_restituzione DESC
            LIMIT 20
        ');
    }

} catch (PDOException $e) {
    error_log("Error fetching loans: " . $e->getMessage());
    $prestiti_attivi  = [];
    $prestiti_recenti = [];
    $stats = ['totale_prestiti_attivi' => 0, 'prestiti_in_ritardo' => 0, 'prestiti_scadenza_vicina' => 0];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Restituzioni — BiblioTech</title>
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
                <h1>Gestione Restituzioni</h1>
                <p class="subtitle">Monitora e registra le restituzioni dei libri</p>
            </div>
            <?php if (!$show_all): ?>
                <a href="?show_all=1<?= $filter_user ? '&user='.urlencode($filter_user) : '' ?><?= $filter_book ? '&book='.urlencode($filter_book) : '' ?>"
                   class="btn btn-outline-primary btn-sm">
                    📋 Mostra anche i restituiti
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container fade-up">

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

        <div class="row g-4 mb-4">
            <div class="col-md-4 fade-up fade-up-delay-1">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-1">Prestiti Attivi</h5>
                        <div style="font-family:'Playfair Display',serif; font-size:2.2rem; font-weight:600; line-height:1;">
                            <?= $stats['totale_prestiti_attivi'] ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-2">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-1">In Ritardo (&gt;30 gg)</h5>
                        <div style="font-family:'Playfair Display',serif; font-size:2.2rem; font-weight:600; line-height:1;">
                            <?= $stats['prestiti_in_ritardo'] ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 fade-up fade-up-delay-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-1">Scadenza Vicina (21–30 gg)</h5>
                        <div style="font-family:'Playfair Display',serif; font-size:2.2rem; font-weight:600; line-height:1;">
                            <?= $stats['prestiti_scadenza_vicina'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input
                            type="text"
                            class="form-control"
                            name="user"
                            placeholder="Filtra per utente…"
                            value="<?= htmlspecialchars($filter_user) ?>">
                    </div>
                    <div class="col-md-5">
                        <input
                            type="text"
                            class="form-control"
                            name="book"
                            placeholder="Filtra per libro…"
                            value="<?= htmlspecialchars($filter_book) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <?php if ($show_all): ?>
                            <input type="hidden" name="show_all" value="1">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary w-100">Filtra</button>
                        <?php if (!empty($filter_user) || !empty($filter_book)): ?>
                            <a href="/gestione_restituzioni.php<?= $show_all ? '?show_all=1' : '' ?>"
                               class="btn btn-outline-secondary" title="Rimuovi filtri">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <h3 class="mb-3">
            Prestiti Attivi
            <span class="badge bg-primary ms-2" style="font-size:0.75rem;"><?= count($prestiti_attivi) ?></span>
        </h3>

        <?php if (empty($prestiti_attivi)): ?>
            <div class="alert alert-info mb-4">
                <?= (!empty($filter_user) || !empty($filter_book))
                    ? 'Nessun risultato con i filtri selezionati.'
                    : 'Nessun prestito attivo al momento.' ?>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utente</th>
                                    <th>Libro</th>
                                    <th>Autore</th>
                                    <th>Data Prestito</th>
                                    <th>Giorni</th>
                                    <th>Stato</th>
                                    <th>Azione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestiti_attivi as $prestito): ?>
                                    <?php
                                        if ($prestito['giorni_prestito'] > 30) {
                                            $row_class = 'overdue-row';
                                            $badge     = '<span class="badge bg-danger">In ritardo</span>';
                                        } elseif ($prestito['giorni_prestito'] > 21) {
                                            $row_class = 'warning-row';
                                            $badge     = '<span class="badge bg-warning text-dark">Scade presto</span>';
                                        } else {
                                            $row_class = '';
                                            $badge     = '<span class="badge bg-success">OK</span>';
                                        }
                                    ?>
                                    <tr class="<?= $row_class ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($prestito['utente_nome']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($prestito['utente_email']) ?></small>
                                        </td>
                                        <td>
                                            <a href="/libro.php?id=<?= $prestito['libro_id'] ?>">
                                                <?= htmlspecialchars($prestito['libro_titolo']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($prestito['libro_autore']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                        <td><strong><?= $prestito['giorni_prestito'] ?></strong></td>
                                        <td><?= $badge ?></td>
                                        <td>
                                            <form method="POST" action="" style="display:inline;"
                                                  onsubmit="return confirm('Confermi la restituzione di questo libro?');">
                                                <input type="hidden" name="prestito_id" value="<?= $prestito['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    ✓ Registra
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($prestiti_recenti)): ?>
            <h3 class="mb-3">Restituzioni Recenti</h3>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utente</th>
                                    <th>Libro</th>
                                    <th>Data Prestito</th>
                                    <th>Data Restituzione</th>
                                    <th>Durata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestiti_recenti as $prestito): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prestito['utente_nome']) ?></td>
                                        <td><?= htmlspecialchars($prestito['libro_titolo']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($prestito['data_restituzione'])) ?></td>
                                        <td>
                                            <strong><?= $prestito['durata_prestito'] ?></strong>
                                            giorn<?= $prestito['durata_prestito'] == 1 ? 'o' : 'i' ?>
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

    <footer class="site-footer">
        <div class="container text-center">
            <small>BiblioTech — Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>