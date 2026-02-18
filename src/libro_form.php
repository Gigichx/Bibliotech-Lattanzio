<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireRole('bibliotecario');

$libro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$is_edit  = (bool)$libro_id;
$libro    = null;
$errors   = [];

if ($is_edit) {
    $libro = db_fetch_one('SELECT * FROM libri WHERE id = ?', [$libro_id]);
    if (!$libro) {
        header('Location: /gestione_libri.php?err=Libro+non+trovato.');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo            = trim($_POST['titolo']            ?? '');
    $autore            = trim($_POST['autore']            ?? '');
    $copie_totali      = filter_input(INPUT_POST, 'copie_totali',      FILTER_VALIDATE_INT);
    $copie_disponibili = filter_input(INPUT_POST, 'copie_disponibili', FILTER_VALIDATE_INT);

    if (empty($titolo)) {
        $errors[] = 'Il titolo è obbligatorio.';
    }
    if (empty($autore)) {
        $errors[] = "L'autore è obbligatorio.";
    }
    if ($copie_totali === false || $copie_totali < 1) {
        $errors[] = 'Le copie totali devono essere almeno 1.';
    }
    if ($copie_disponibili === false || $copie_disponibili < 0) {
        $errors[] = 'Le copie disponibili non possono essere negative.';
    }
    if (empty($errors) && $copie_disponibili > $copie_totali) {
        $errors[] = 'Le copie disponibili non possono superare le copie totali.';
    }

    if ($is_edit && empty($errors)) {
        $copie_in_prestito = $libro['copie_totali'] - $libro['copie_disponibili'];

        if ($copie_totali < $copie_in_prestito) {
            $errors[] = "Ci sono {$copie_in_prestito} prestiti attivi: le copie totali non possono scendere sotto {$copie_in_prestito}.";
        }

        if ($copie_disponibili > $copie_totali - $copie_in_prestito) {
            $max = $copie_totali - $copie_in_prestito;
            $errors[] = "Con {$copie_in_prestito} prestiti attivi, le copie disponibili non possono superare {$max}.";
        }
    }

    if (empty($errors)) {
        try {
            if ($is_edit) {
                db_query(
                    'UPDATE libri SET titolo = ?, autore = ?, copie_totali = ?, copie_disponibili = ? WHERE id = ?',
                    [$titolo, $autore, $copie_totali, $copie_disponibili, $libro_id]
                );
                header('Location: /gestione_libri.php?success=' . urlencode("Libro \"$titolo\" aggiornato con successo."));
            } else {
                db_query(
                    'INSERT INTO libri (titolo, autore, copie_totali, copie_disponibili) VALUES (?, ?, ?, ?)',
                    [$titolo, $autore, $copie_totali, $copie_disponibili]
                );
                header('Location: /gestione_libri.php?success=' . urlencode("Libro \"$titolo\" aggiunto con successo."));
            }
            exit;
        } catch (Exception $e) {
            error_log("libro_form error: " . $e->getMessage());
            $errors[] = 'Errore del sistema. Riprova più tardi.';
        }
    }

    $libro = [
        'id'                => $libro_id,
        'titolo'            => $titolo,
        'autore'            => $autore,
        'copie_totali'      => $copie_totali,
        'copie_disponibili' => $copie_disponibili,
    ];
}

$page_title = $is_edit ? 'Modifica Libro' : 'Aggiungi Libro';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — BiblioTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/assets/IMG/logo.png">
</head>
<body class="page-wrapper">

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="page-title-bar">
        <div class="container">
            <h1><?= $page_title ?></h1>
            <p class="subtitle">
                <?= $is_edit ? 'Modifica le informazioni del libro' : 'Inserisci un nuovo titolo nel catalogo' ?>
            </p>
        </div>
    </div>

    <div class="container fade-up">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/gestione_libri.php">Gestione Libri</a></li>
                <li class="breadcrumb-item active"><?= $page_title ?></li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errors as $e): ?>
                            <div>⚠ <?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">

                            <div class="mb-3">
                                <label class="form-label" for="titolo">Titolo <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="titolo"
                                       name="titolo"
                                       value="<?= htmlspecialchars($libro['titolo'] ?? '') ?>"
                                       placeholder="Es. Il Nome della Rosa"
                                       required
                                       autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="autore">Autore <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="autore"
                                       name="autore"
                                       value="<?= htmlspecialchars($libro['autore'] ?? '') ?>"
                                       placeholder="Es. Umberto Eco"
                                       required>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="copie_totali">Copie Totali <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control"
                                           id="copie_totali"
                                           name="copie_totali"
                                           value="<?= htmlspecialchars($libro['copie_totali'] ?? 1) ?>"
                                           min="1"
                                           required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="copie_disponibili">Copie Disponibili <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control"
                                           id="copie_disponibili"
                                           name="copie_disponibili"
                                           value="<?= htmlspecialchars($libro['copie_disponibili'] ?? 1) ?>"
                                           min="0"
                                           required>
                                </div>
                            </div>

                            <?php if ($is_edit): ?>
                                <div class="alert alert-info" style="font-size:0.83rem;">
                                    Le copie disponibili non possono superare le copie totali, e non possono scendere sotto il numero di prestiti attualmente attivi per questo libro.
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?= $is_edit ? 'Salva Modifiche' : 'Aggiungi Libro' ?>
                                </button>
                                <a href="/gestione_libri.php" class="btn btn-outline-secondary">Annulla</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <footer class="site-footer">
        <div class="container text-center">
            <small>BiblioTech — Sistema di Gestione Biblioteca &copy; 2026</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totali = document.getElementById('copie_totali');
        const disponibili = document.getElementById('copie_disponibili');
        totali.addEventListener('input', function () {
            disponibili.max = this.value;
            if (parseInt(disponibili.value) > parseInt(this.value)) {
                disponibili.value = this.value;
            }
        });
    </script>
</body>
</html>