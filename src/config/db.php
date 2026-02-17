<?php



$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'bibliotech';
$db_user = getenv('DB_USER') ?: 'bibliotech_user';
$db_password = getenv('DB_PASSWORD') ?: 'bibliotech_password';
$db_charset = 'utf8mb4';


$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $db_charset"
];

try {

    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {

    error_log("Database Connection Error: " . $e->getMessage());
    

    die("Errore di connessione al database. Riprova più tardi.");
}


function db_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database Query Error: " . $e->getMessage());
        throw $e;
    }
}


function db_fetch_one($sql, $params = []) {
    return db_query($sql, $params)->fetch();
}


function db_fetch_all($sql, $params = []) {
    return db_query($sql, $params)->fetchAll();
}
