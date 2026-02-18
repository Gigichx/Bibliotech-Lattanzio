<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$sql    = "SELECT * FROM libri WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql     .= " AND (titolo LIKE ? OR autore LIKE ?)";
    $term     = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
}

if ($filter === 'available') {
    $sql .= " AND copie_disponibili > 0";
} elseif ($filter === 'unavailable') {
    $sql .= " AND copie_disponibili = 0";
}

$sql .= " ORDER BY titolo ASC";

try {
    $libri = db_fetch_all($sql, $params);
} catch (Exception $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $libri = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo Libri — BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/assets/IMG/logo.png">
</head>
<body class="page-wrapper">

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="page-title-bar">
        <div class="container">
            <h1>Catalogo Libri</h1>
            <p class="subtitle">
                <?= count($libri) ?> libr<?= count($libri) === 1 ? 'o' : 'i' ?> trovat<?= count($libri) === 1 ? 'o' : 'i' ?>
                <?php if (!empty($search)): ?>
                    per "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="container">

        <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                Non hai i permessi per accedere a quella sezione.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/libri.php" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search"
                               placeholder="Cerca per titolo o autore…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="filter">
                            <option value="all"         <?= $filter === 'all'         ? 'selected' : '' ?>>Tutti i libri</option>
                            <option value="available"   <?= $filter === 'available'   ? 'selected' : '' ?>>Solo disponibili</option>
                            <option value="unavailable" <?= $filter === 'unavailable' ? 'selected' : '' ?>>Non disponibili</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Cerca</button>
                        <?php if (!empty($search) || $filter !== 'all'): ?>
                            <a href="/libri.php" class="btn btn-outline-secondary" title="Rimuovi filtri">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($libri)): ?>
            <div class="alert alert-info">
                Nessun libro trovato.
                <?= !empty($search) ? 'Prova con altri termini di ricerca.' : '' ?>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($libri as $i => $libro): ?>
                    <div class="col fade-up fade-up-delay-<?= min($i + 1, 4) ?>">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($libro['titolo']) ?></h5>
                                <h6 class="card-subtitle mb-3 text-muted"><?= htmlspecialchars($libro['autore']) ?></h6>
                                <div class="mb-3">
                                    <?php if ($libro['copie_disponibili'] > 0): ?>
                                        <span class="badge bg-success badge-availability">
                                            Disponibile (<?= $libro['copie_disponibili'] ?>/<?= $libro['copie_totali'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-availability">Non disponibile</span>
                                    <?php endif; ?>
                                </div>
                                <a href="/libro.php?id=<?= $libro['id'] ?>" class="btn btn-primary btn-sm mt-auto">
                                    Vedi Dettagli
                                </a>
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
</body>
</html>