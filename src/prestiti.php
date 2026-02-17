<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireRole('studente');

try {
    $prestiti_attivi = db_fetch_all(
        'SELECT
            p.id,
            p.data_prestito,
            l.id   AS libro_id,
            l.titolo,
            l.autore,
            DATEDIFF(CURDATE(), p.data_prestito) AS giorni_prestito
         FROM prestiti p
         JOIN libri l ON p.id_libro = l.id
         WHERE p.id_utente = ? AND p.data_restituzione IS NULL
         ORDER BY p.data_prestito DESC',
        [getCurrentUserId()]
    );

    $prestiti_storici = db_fetch_all(
        'SELECT
            p.id,
            p.data_prestito,
            p.data_restituzione,
            l.id   AS libro_id,
            l.titolo,
            l.autore,
            DATEDIFF(p.data_restituzione, p.data_prestito) AS durata_prestito
         FROM prestiti p
         JOIN libri l ON p.id_libro = l.id
         WHERE p.id_utente = ? AND p.data_restituzione IS NOT NULL
         ORDER BY p.data_restituzione DESC
         LIMIT 10',
        [getCurrentUserId()]
    );

} catch (PDOException $e) {
    error_log("Error fetching loans: " . $e->getMessage());
    $prestiti_attivi  = [];
    $prestiti_storici = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Prestiti — BiblioTech</title>
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
                <h1>I Miei Prestiti</h1>
                <p class="subtitle">
                    <?= count($prestiti_attivi) ?> prestit<?= count($prestiti_attivi) === 1 ? 'o' : 'i' ?> attiv<?= count($prestiti_attivi) === 1 ? 'o' : 'i' ?>
                </p>
            </div>
            <a href="/libri.php" class="btn btn-outline-primary btn-sm">📖 Vai al Catalogo</a>
        </div>
    </div>

    <div class="container fade-up">

        <!-- Prestiti attivi -->
        <h3 class="mb-3">
            Prestiti Attivi
            <span class="badge bg-primary ms-2" style="font-size:0.75rem;"><?= count($prestiti_attivi) ?></span>
        </h3>

        <?php if (empty($prestiti_attivi)): ?>
            <div class="alert alert-info mb-5">
                Non hai prestiti attivi al momento.
                <a href="/libri.php" class="alert-link">Esplora il catalogo</a> per prendere un libro in prestito.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-5">
                <?php foreach ($prestiti_attivi as $i => $prestito): ?>
                    <?php
                        if ($prestito['giorni_prestito'] > 30) {
                            $loan_class = 'overdue';
                            $badge      = '<span class="badge bg-danger">In ritardo</span>';
                        } elseif ($prestito['giorni_prestito'] > 21) {
                            $loan_class = 'warning';
                            $badge      = '<span class="badge bg-warning text-dark">Da restituire presto</span>';
                        } else {
                            $loan_class = 'ok';
                            $badge      = '<span class="badge bg-success">Attivo</span>';
                        }
                    ?>
                    <div class="col fade-up fade-up-delay-<?= min($i + 1, 4) ?>">
                        <div class="card loan-card <?= $loan_class ?> h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($prestito['titolo']) ?></h5>
                                    <?= $badge ?>
                                </div>
                                <h6 class="card-subtitle mb-3 text-muted">
                                    <?= htmlspecialchars($prestito['autore']) ?>
                                </h6>

                                <dl class="mb-3">
                                    <div class="meta-row">
                                        <dt>Data prestito</dt>
                                        <dd><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></dd>
                                    </div>
                                    <div class="meta-row">
                                        <dt>Giorni di prestito</dt>
                                        <dd><strong><?= $prestito['giorni_prestito'] ?></strong> giorn<?= $prestito['giorni_prestito'] == 1 ? 'o' : 'i' ?></dd>
                                    </div>
                                </dl>

                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="/libro.php?id=<?= $prestito['libro_id'] ?>" class="btn btn-outline-primary btn-sm">
                                        Vedi Dettagli
                                    </a>
                                    <small class="text-muted">💡 Rivolgiti al bibliotecario per restituirlo.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Storico -->
        <?php if (!empty($prestiti_storici)): ?>
            <h3 class="mb-3">Storico Prestiti</h3>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Titolo</th>
                                    <th>Autore</th>
                                    <th>Data Prestito</th>
                                    <th>Data Restituzione</th>
                                    <th>Durata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestiti_storici as $prestito): ?>
                                    <tr>
                                        <td>
                                            <a href="/libro.php?id=<?= $prestito['libro_id'] ?>">
                                                <?= htmlspecialchars($prestito['titolo']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($prestito['autore']) ?></td>
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