<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom LIKE ? OR code LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM faculties $where ORDER BY nom ASC");
$stmt->execute($params);
$faculties = $stmt->fetchAll();

$rows = [];
foreach ($faculties as $f) {
    $rows[] = [$f['nom'], $f['code'], $f['description'], $f['responsable'], $f['statut']];
}

export_csv('facultes.csv', ['Nom', 'Code', 'Description', 'Responsable', 'Statut'], $rows);
