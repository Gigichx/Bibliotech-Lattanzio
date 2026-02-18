<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

requireRole('bibliotecario');

$libro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$libro_id) {
    header('Location: /gestione_libri.php?err=ID+non+valido.');
    exit;
}

try {
    $libro = db_fetch_one('SELECT * FROM libri WHERE id = ?', [$libro_id]);

    if (!$libro) {
        header('Location: /gestione_libri.php?err=Libro+non+trovato.');
        exit;
    }

    $prestiti_attivi = db_fetch_one(
        'SELECT COUNT(*) AS n FROM prestiti WHERE id_libro = ? AND data_restituzione IS NULL',
        [$libro_id]
    );

    if ($prestiti_attivi['n'] > 0) {
        $msg = urlencode("Impossibile eliminare \"{$libro['titolo']}\": ci sono {$prestiti_attivi['n']} prestiti attivi. Registra prima le restituzioni.");
        header("Location: /gestione_libri.php?err={$msg}");
        exit;
    }

    db_query('DELETE FROM libri WHERE id = ?', [$libro_id]);

    $msg = urlencode("Libro \"{$libro['titolo']}\" eliminato con successo.");
    header("Location: /gestione_libri.php?success={$msg}");
    exit;

} catch (PDOException $e) {
    error_log("libro_delete error: " . $e->getMessage());
    header('Location: /gestione_libri.php?err=Errore+del+sistema.+Riprova+più+tardi.');
    exit;
}