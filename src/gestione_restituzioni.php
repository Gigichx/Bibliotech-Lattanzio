<?php


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';


requireRole('bibliotecario');

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prestito_id'])) {
    $prestito_id = filter_input(INPUT_POST, 'prestito_id', FILTER_VALIDATE_INT);
    
    if ($prestito_id) {
        try {

            $pdo->beginTransaction();
            

            $prestito = db_fetch_one(
                'SELECT p.*, l.titolo as libro_titolo 
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

            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            error_log("Return error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}


$filter_user = $_GET['user'] ?? '';
$filter_book = $_GET['book'] ?? '';
$show_all = isset($_GET['show_all']);


try {
    $sql = "
        SELECT 
            p.id,
            p.data_prestito,
            u.id as utente_id,
            u.nome as utente_nome,
            u.email as utente_email,
            l.id as libro_id,
            l.titolo as libro_titolo,
            l.autore as libro_autore,
            DATEDIFF(CURDATE(), p.data_prestito) as giorni_prestito
        FROM prestiti p
        JOIN utenti u ON p.id_utente = u.id
        JOIN libri l ON p.id_libro = l.id
        WHERE p.data_restituzione IS NULL
    ";
    
    $params = [];
    
    if (!empty($filter_user)) {
        $sql .= " AND u.nome LIKE ?";
        $params[] = "%{$filter_user}%";
    }
    
    if (!empty($filter_book)) {
        $sql .= " AND l.titolo LIKE ?";
        $params[] = "%{$filter_book}%";
    }
    
    $sql .= " ORDER BY p.data_prestito ASC";
    
    $prestiti_attivi = db_fetch_all($sql, $params);
    

    $stats = db_fetch_one('
        SELECT 
            COUNT(*) as totale_prestiti_attivi,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), data_prestito) > 30 THEN 1 END) as prestiti_in_ritardo,
            COUNT(CASE WHEN DATEDIFF(CURDATE(), data_prestito) BETWEEN 21 AND 30 THEN 1 END) as prestiti_scadenza_vicina
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
                u.nome as utente_nome,
                l.titolo as libro_titolo,
                l.autore as libro_autore,
                DATEDIFF(p.data_restituzione, p.data_prestito) as durata_prestito
            FROM prestiti p
            JOIN utenti u ON p.id_utente = u.id
            JOIN libri l ON p.id_libro = l.id
            WHERE p.data_restituzione IS NOT NULL
            ORDER BY p.data_restituzione DESC
            LIMIT 20
        ');
    }
    
} catch (PDOException $e) {
    error_log("Error fetching loans: " . $e->getMessage());
    $prestiti_attivi = [];
    $prestiti_recenti = [];
    $stats = ['totale_prestiti_attivi' => 0, 'prestiti_in_ritardo' => 0, 'prestiti_scadenza_vicina' => 0];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Restituzioni - BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/IMG/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/IMG/logo.png">
    <link rel="apple-touch-icon" href="/assets/IMG/logo.png">
    <style>
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-5px); }
        .overdue-row { background-color:
        .warning-row { background-color:
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
                        <a class="nav-link active" href="/gestione_restituzioni.php">Gestione Restituzioni</a>
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
        <h1 class="mb-4">Gestione Restituzioni</h1>

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

        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Prestiti Attivi</h5>
                        <h2 class="mb-0"><?= $stats['totale_prestiti_attivi'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">In Ritardo (>30 giorni)</h5>
                        <h2 class="mb-0"><?= $stats['prestiti_in_ritardo'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Scadenza Vicina (21-30 giorni)</h5>
                        <h2 class="mb-0"><?= $stats['prestiti_scadenza_vicina'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" 
                               class="form-control" 
                               name="user" 
                               placeholder="Filtra per utente..."
                               value="<?= htmlspecialchars($filter_user) ?>">
                    </div>
                    <div class="col-md-5">
                        <input type="text" 
                               class="form-control" 
                               name="book" 
                               placeholder="Filtra per libro..."
                               value="<?= htmlspecialchars($filter_book) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtra</button>
                    </div>
                    <?php if (!empty($filter_user) || !empty($filter_book)): ?>
                        <div class="col-12">
                            <a href="/gestione_restituzioni.php" class="btn btn-sm btn-outline-secondary">
                                Rimuovi Filtri
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Prestiti Attivi</h5>
                <a href="?show_all=1" class="btn btn-sm btn-outline-primary">
                    Mostra anche i prestiti restituiti
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($prestiti_attivi)): ?>
                    <div class="alert alert-info mb-0">
                        Nessun prestito attivo al momento.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Utente</th>
                                    <th>Libro</th>
                                    <th>Autore</th>
                                    <th>Data Prestito</th>
                                    <th>Giorni</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestiti_attivi as $prestito): ?>
                                    <?php
                                        $row_class = '';
                                        $status_badge = '';
                                        
                                        if ($prestito['giorni_prestito'] > 30) {
                                            $row_class = 'overdue-row';
                                            $status_badge = '<span class="badge bg-danger">In ritardo</span>';
                                        } elseif ($prestito['giorni_prestito'] > 21) {
                                            $row_class = 'warning-row';
                                            $status_badge = '<span class="badge bg-warning text-dark">Scade presto</span>';
                                        } else {
                                            $status_badge = '<span class="badge bg-success">OK</span>';
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
                                        <td><?= htmlspecialchars($prestito['libro_autore']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($prestito['data_prestito'])) ?></td>
                                        <td><strong><?= $prestito['giorni_prestito'] ?></strong></td>
                                        <td><?= $status_badge ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Confermi la restituzione di questo libro?');">
                                                <input type="hidden" name="prestito_id" value="<?= $prestito['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    ✓ Registra Restituzione
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if (!empty($prestiti_recenti)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Restituzioni Recenti</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
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
