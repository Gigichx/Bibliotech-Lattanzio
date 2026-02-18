<?php

$db_host     = getenv('DB_HOST')     ?: 'db';
$db_name     = getenv('DB_NAME')     ?: 'bibliotech';
$db_user     = getenv('DB_USER')     ?: 'bibliotech_user';
$db_password = getenv('DB_PASSWORD') ?: 'bibliotech_password';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

if (!$conn) {
    error_log("Database Connection Error: " . mysqli_connect_error());
    die("Errore di connessione al database. Riprova più tardi.");
}

mysqli_set_charset($conn, 'utf8mb4');


function db_query($sql, $params = []) {
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare error: " . mysqli_error($conn));
        throw new Exception("Errore nella query.");
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute error: " . mysqli_stmt_error($stmt));
        throw new Exception("Errore nell'esecuzione della query.");
    }

    return $stmt;
}


function db_fetch_one($sql, $params = []) {
    $stmt   = db_query($sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}


function db_fetch_all($sql, $params = []) {
    $stmt   = db_query($sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}


function db_begin() {
    global $conn;
    mysqli_begin_transaction($conn);
}

function db_commit() {
    global $conn;
    mysqli_commit($conn);
}

function db_rollback() {
    global $conn;
    mysqli_rollback($conn);
}