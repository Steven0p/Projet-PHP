<?php
// Adapter BASE_URL selon le sous-dossier du projet dans htdocs/www (ex: '/usfah-soutenance')
define('BASE_URL', '');

$db_host = 'localhost';
$db_name = 'usfah_soutenance';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Erreur de connexion à la base de données. Vérifiez config/database.php.');
}
