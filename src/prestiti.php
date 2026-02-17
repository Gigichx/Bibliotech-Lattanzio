<?php


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';


requireRole('studente');


try {
    $prestiti_attivi = db_fetch_all(
        'SELECT 
            p.id,
            p.data_prestito,
            l.id as libro_id,
            l.titolo,
            l.autore,
            DATEDIFF(CURDATE(), p.data_prestito) as giorni_prestito
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
            l.id as libro_id,
            l.titolo,
            l.autore,
            DATEDIFF(p.data_restituzione, p.data_prestito) as durata_prestito
         FROM prestiti p
         JOIN libri l ON p.id_libro = l.id
         WHERE p.id_utente = ? AND p.data_restituzione IS NOT NULL
         ORDER BY p.data_restituzione DESC
         LIMIT 10',
        [getCurrentUserId()]
    );
    
} catch (PDOException $e) {
    error_log("Error fetching loans: " . $e->getMessage());
    $prestiti_attivi = [];
    $prestiti_storici = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Prestiti - BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/IMG/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/IMG/logo.png">
    <link rel="apple-touch-icon" href="/assets/IMG/logo.png">
    <style>
        .loan-card { border-left: 4px solid }
        .overdue { border-left-color: red; }
        .warning { border-left-color: orange; }
    </style>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="/prestiti.php">I Miei Prestiti</a>
                    </li>
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
        <h1 class="mb-4">I Miei Prestiti</h1>

        
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="mb-3">
                    Prestiti Attivi 
                    <span class="badge bg-primary"><?= count($prestiti_attivi) ?></span>
                </h3>

                <?php if (empty($prestiti_attivi)): ?>
                    <div class="alert alert-info" role="alert">
                        Non hai prestiti attivi al momento. 
                        <a href="/libri.php" class="alert-link">Esplora il catalogo</a> per prendere un libro in prestito.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($prestiti_attivi as $prestito): ?>
                            <?php

                                $card_class = 'loan-card';
                                $status_badge = '';
                                
                                if ($prestito['giorni_prestito'] > 30) {
                                    $card_class .= ' overdue';
                                    $status_badge = '<span class="badge bg-danger">In ritardo</span>';
                                } elseif ($prestito['giorni_prestito'] > 21) {
                                    $card_class .= ' warning';
                                    $status_badge = '<span class="badge bg-warning text-dark">Da restituire presto</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-success">Attivo</span>';
                                }
                            ?>
                            <div class="col">
                                <div class="card <?= $card_class ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0">
                                                <?= htmlspecialchars($prestito['titolo']) ?>
                                            </h5>
                                            <?= $status_badge ?>
                                        </div>
                                        <h6 class="card-subtitle mb-3 text-muted">
                                            <?= htmlspecialchars($prestito['autore']) ?>
                                        </h6>
                                        
                                        <dl class="row mb-0">
                                            <dt class="col-sm-6">Data prestito:</dt>
                                            <dd class="col-sm-6"><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></dd>
                                            
                                            <dt class="col-sm-6">Giorni di prestito:</dt>
                                            <dd class="col-sm-6">
                                                <strong><?= $prestito['giorni_prestito'] ?></strong> 
                                                giorn<?= $prestito['giorni_prestito'] == 1 ? 'o' : 'i' ?>
                                            </dd>
                                        </dl>
                                        
                                        <hr>
                                        
                                        <a href="/libro.php?id=<?= $prestito['libro_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            Vedi Dettagli Libro
                                        </a>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                💡 Per restituire il libro, rivolgiti al bibliotecario.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if (!empty($prestiti_storici)): ?>
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-3">Storico Prestiti</h3>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                                <td><?= htmlspecialchars($prestito['autore']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($prestito['data_restituzione'])) ?></td>
                                                <td>
                                                    <?= $prestito['durata_prestito'] ?> 
                                                    giorn<?= $prestito['durata_prestito'] == 1 ? 'o' : 'i' ?>
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
        <?php endif; ?>
    </div>

    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center text-muted">
            <small>BiblioTech - Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
