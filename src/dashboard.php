<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

if (isBibliotecario()) {

    $stats = db_fetch_one('
        SELECT
            (SELECT COUNT(*) FROM libri) AS totale_libri,
            (SELECT COUNT(*) FROM libri WHERE copie_disponibili > 0) AS libri_disponibili,
            COUNT(*) AS prestiti_attivi,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), data_prestito) > 30 THEN 1 END) AS prestiti_ritardo
        FROM prestiti
        WHERE data_restituzione IS NULL
    ');

    $ultimi_prestiti = db_fetch_all('
        SELECT
            p.data_prestito,
            u.nome AS utente_nome,
            l.titolo AS libro_titolo,
            DATEDIFF(CURDATE(), p.data_prestito) AS giorni,
            p.data_restituzione
        FROM prestiti p
        JOIN utenti u ON p.id_utente = u.id
        JOIN libri  l ON p.id_libro  = l.id
        ORDER BY p.id DESC
        LIMIT 5
    ');

    $copie_in_prestito = (int)$stats['totale_libri'] - (int)$stats['libri_disponibili'];

} else {

    $stats_studente = db_fetch_one('
        SELECT
            COUNT(CASE WHEN data_restituzione IS NULL THEN 1 END) AS prestiti_attivi,
            COUNT(*) AS totale_prestiti
        FROM prestiti
        WHERE id_utente = ?
    ', [getCurrentUserId()]);

    $prestiti_attivi = db_fetch_all('
        SELECT
            p.id,
            p.data_prestito,
            l.id    AS libro_id,
            l.titolo,
            l.autore,
            DATEDIFF(CURDATE(), p.data_prestito) AS giorni_prestito
        FROM prestiti p
        JOIN libri l ON p.id_libro = l.id
        WHERE p.id_utente = ? AND p.data_restituzione IS NULL
        ORDER BY p.data_prestito ASC
        LIMIT 3
    ', [getCurrentUserId()]);

    $restituiti_recenti = db_fetch_all('
        SELECT
            l.titolo,
            l.autore,
            l.id AS libro_id,
            p.data_restituzione
        FROM prestiti p
        JOIN libri l ON p.id_libro = l.id
        WHERE p.id_utente = ? AND p.data_restituzione IS NOT NULL
        ORDER BY p.data_restituzione DESC
        LIMIT 3
    ', [getCurrentUserId()]);

    $libri_disponibili = db_fetch_all('
        SELECT id, titolo, autore, copie_disponibili, copie_totali
        FROM libri
        WHERE copie_disponibili > 0
        ORDER BY titolo ASC
        LIMIT 6
    ');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/assets/IMG/logo.png">
</head>
<body class="page-wrapper">

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="page-title-bar">
        <div class="container">
            <h1>Bentornato, <?= htmlspecialchars(explode(' ', getCurrentUserName())[0]) ?>!</h1>
            <p class="subtitle">
                <?php if (isBibliotecario()): ?>
                    Panoramica della biblioteca — <?= date('d F Y') ?>
                <?php else: ?>
                    La tua area personale — <?= date('d F Y') ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="container fade-up">

        <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                Non hai i permessi per accedere a quella sezione.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isBibliotecario()): ?>

            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3 fade-up fade-up-delay-1">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <div class="stats-card-label">Totale Libri</div>
                            <div class="stats-card-number"><?= $stats['totale_libri'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up fade-up-delay-2">
                    <div class="card stats-card text-white" style="background-color:var(--success);">
                        <div class="card-body">
                            <div class="stats-card-label">Con Copie Disponibili</div>
                            <div class="stats-card-number"><?= $stats['libri_disponibili'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up fade-up-delay-3">
                    <div class="card stats-card bg-primary text-white" style="background-color:#2a5a8c!important;">
                        <div class="card-body">
                            <div class="stats-card-label">Prestiti Attivi</div>
                            <div class="stats-card-number"><?= $stats['prestiti_attivi'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 fade-up fade-up-delay-4">
                    <div class="card stats-card bg-danger text-white">
                        <div class="card-body">
                            <div class="stats-card-label">In Ritardo</div>
                            <div class="stats-card-number"><?= $stats['prestiti_ritardo'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-5 fade-up">
                    <div class="card h-100">
                        <div class="card-header">Disponibilità Catalogo</div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <canvas id="chartDisponibilita" style="max-width:260px; max-height:260px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 fade-up">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            Ultimi 5 Prestiti
                            <a href="/gestione_restituzioni.php" class="btn btn-outline-primary btn-sm">Vedi tutti</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Studente</th>
                                            <th>Libro</th>
                                            <th>Data</th>
                                            <th>Stato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimi_prestiti as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['utente_nome']) ?></td>
                                                <td class="text-truncate" style="max-width:130px;">
                                                    <?= htmlspecialchars($p['libro_titolo']) ?>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($p['data_prestito'])) ?></td>
                                                <td>
                                                    <?php if ($p['data_restituzione']): ?>
                                                        <span class="badge bg-secondary">Restituito</span>
                                                    <?php elseif ($p['giorni'] > 30): ?>
                                                        <span class="badge bg-danger">Ritardo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Attivo</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-3 fade-up">
                <div class="col-12">
                    <h5 class="mb-3" style="font-family:'DM Sans',sans-serif; font-weight:600; color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em;">
                        Accesso Rapido
                    </h5>
                </div>
                <div class="col-md-4">
                    <a href="/gestione_libri.php?action=new" class="quick-link-card">
                        <div class="quick-link-icon" style="background:rgba(26,58,92,0.08); color:var(--primary);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="quick-link-title">Aggiungi Libro</div>
                            <div class="quick-link-sub">Inserisci un nuovo titolo nel catalogo</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/gestione_restituzioni.php" class="quick-link-card">
                        <div class="quick-link-icon" style="background:rgba(26,122,74,0.08); color:var(--success);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="quick-link-title">Registra Restituzione</div>
                            <div class="quick-link-sub">Chiudi un prestito attivo</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/gestione_libri.php" class="quick-link-card">
                        <div class="quick-link-icon" style="background:rgba(183,134,11,0.08); color:var(--warning);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="quick-link-title">Gestione Catalogo</div>
                            <div class="quick-link-sub">Modifica ed elimina libri</div>
                        </div>
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div class="row g-4 mb-4">
                <div class="col-6 col-md-6 fade-up fade-up-delay-1">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <div class="stats-card-label">Prestiti Attivi</div>
                            <div class="stats-card-number"><?= $stats_studente['prestiti_attivi'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6 fade-up fade-up-delay-2">
                    <div class="card stats-card text-white" style="background-color:var(--success);">
                        <div class="card-body">
                            <div class="stats-card-label">Totale Prestiti</div>
                            <div class="stats-card-number"><?= $stats_studente['totale_prestiti'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($prestiti_attivi)): ?>
                <h5 class="section-label mb-3">I Tuoi Prestiti Attivi</h5>
                <div class="row g-3 mb-5 fade-up">
                    <?php foreach ($prestiti_attivi as $p): ?>
                        <?php
                            if ($p['giorni_prestito'] > 30) {
                                $cls   = 'overdue';
                                $badge = '<span class="badge bg-danger">In ritardo</span>';
                            } elseif ($p['giorni_prestito'] > 21) {
                                $cls   = 'warning';
                                $badge = '<span class="badge bg-warning text-dark">Da restituire</span>';
                            } else {
                                $cls   = 'ok';
                                $badge = '<span class="badge bg-success">Attivo</span>';
                            }
                        ?>
                        <div class="col-md-4">
                            <div class="card loan-card <?= $cls ?> h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="card-title mb-0" style="font-size:0.92rem;"><?= htmlspecialchars($p['titolo']) ?></h6>
                                        <?= $badge ?>
                                    </div>
                                    <div class="text-muted mb-3" style="font-size:0.8rem;"><?= htmlspecialchars($p['autore']) ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?= $p['giorni_prestito'] ?> giorn<?= $p['giorni_prestito'] == 1 ? 'o' : 'i' ?></small>
                                        <a href="/libro.php?id=<?= $p['libro_id'] ?>" class="btn btn-outline-primary btn-sm" style="font-size:0.78rem;">Dettagli</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($restituiti_recenti)): ?>
                <h5 class="section-label mb-3">Ultimi Libri Restituiti</h5>
                <div class="row g-3 mb-5 fade-up">
                    <?php foreach ($restituiti_recenti as $r): ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title" style="font-size:0.92rem;"><?= htmlspecialchars($r['titolo']) ?></h6>
                                    <div class="text-muted mb-2" style="font-size:0.8rem;"><?= htmlspecialchars($r['autore']) ?></div>
                                    <small class="text-muted">Restituito il <?= date('d/m/Y', strtotime($r['data_restituzione'])) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mb-3 fade-up">
                <h5 class="section-label mb-0">Libri Disponibili Ora</h5>
                <a href="/libri.php?filter=available" class="btn btn-outline-primary btn-sm">Vedi tutti</a>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 fade-up">
                <?php foreach ($libri_disponibili as $i => $libro): ?>
                    <div class="col fade-up fade-up-delay-<?= min($i + 1, 4) ?>">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?= htmlspecialchars($libro['titolo']) ?></h6>
                                <div class="text-muted mb-3" style="font-size:0.83rem;"><?= htmlspecialchars($libro['autore']) ?></div>
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <span class="badge bg-success badge-availability">
                                        <?= $libro['copie_disponibili'] ?>/<?= $libro['copie_totali'] ?> disponibili
                                    </span>
                                    <a href="/libro.php?id=<?= $libro['id'] ?>" class="btn btn-primary btn-sm">Prendi →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>

    <footer class="site-footer">
        <div class="container text-center">
            <small>BiblioTech — Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isBibliotecario()): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('chartDisponibilita');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Disponibili', 'In Prestito'],
                datasets: [{
                    data: [<?= (int)$stats['libri_disponibili'] ?>, <?= $copie_in_prestito ?>],
                    backgroundColor: ['#1a7a4a', '#1a3a5c'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'DM Sans', size: 13 },
                            padding: 16,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '65%'
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>