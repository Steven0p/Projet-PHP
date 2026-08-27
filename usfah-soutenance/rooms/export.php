<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom_numero LIKE ? OR campus LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM rooms $where ORDER BY nom_numero ASC");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$rows = [];
foreach ($rooms as $r) {
    $rows[] = [$r['nom_numero'], $r['campus'], $r['capacite'], $r['disponibilite'], $r['description']];
}

export_csv('salles.csv', ['Nom / Numéro', 'Campus', 'Capacité', 'Disponibilité', 'Description'], $rows);
