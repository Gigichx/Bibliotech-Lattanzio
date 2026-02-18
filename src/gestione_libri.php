<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireRole('bibliotecario');

$search = $_GET['search'] ?? '';

$sql    = "SELECT * FROM libri WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql    .= " AND (titolo LIKE ? OR autore LIKE ?)";
    $term    = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY titolo ASC";

try {
    $libri = db_fetch_all($sql, $params);
} catch (PDOException $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $libri = [];
}

$success = $_GET['success'] ?? '';
$error   = $_GET['err']     ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Libri — BiblioTech</title>
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
                <h1>Gestione Libri</h1>
                <p class="subtitle"><?= count($libri) ?> libr<?= count($libri) === 1 ? 'o' : 'i' ?> nel catalogo</p>
            </div>
            <a href="/libro_form.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                    <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                </svg>
                Aggiungi Libro
            </a>
        </div>
    </div>

    <div class="container fade-up">

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search"
                               placeholder="Cerca per titolo o autore…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Cerca</button>
                        <?php if (!empty($search)): ?>
                            <a href="/gestione_libri.php" class="btn btn-outline-secondary" title="Rimuovi filtri">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($libri)): ?>
            <div class="alert alert-info">
                Nessun libro trovato.
                <?php if (!empty($search)): ?>
                    <a href="/gestione_libri.php" class="alert-link">Azzera la ricerca</a>
                <?php else: ?>
                    <a href="/libro_form.php" class="alert-link">Aggiungi il primo libro</a>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Titolo</th>
                                    <th>Autore</th>
                                    <th class="text-center">Copie Tot.</th>
                                    <th class="text-center">Disponibili</th>
                                    <th class="text-center">Stato</th>
                                    <th class="text-center">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($libri as $libro): ?>
                                    <tr>
                                        <td>
                                            <a href="/libro.php?id=<?= $libro['id'] ?>" style="font-weight:500;">
                                                <?= htmlspecialchars($libro['titolo']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($libro['autore']) ?></td>
                                        <td class="text-center"><?= $libro['copie_totali'] ?></td>
                                        <td class="text-center">
                                            <strong class="<?= $libro['copie_disponibili'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $libro['copie_disponibili'] ?>
                                            </strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($libro['copie_disponibili'] > 0): ?>
                                                <span class="badge bg-success">Disponibile</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Esaurito</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="/libro_form.php?id=<?= $libro['id'] ?>"
                                                   class="btn btn-outline-primary btn-sm">
                                                    Modifica
                                                </a>
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm"
                                                        onclick="confermaElimina(<?= $libro['id'] ?>, '<?= htmlspecialchars(addslashes($libro['titolo'])) ?>')">
                                                    Elimina
                                                </button>
                                            </div>
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
    <script>
        function confermaElimina(id, titolo) {
            if (confirm('Eliminare "' + titolo + '"?\n\nQuesta azione è irreversibile.')) {
                window.location.href = '/libro_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>