<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$faculty_id = (int) get_param('faculty_id', 0);

$conditions = [];
$params = [];
if ($search !== '') { $conditions[] = 'p.nom LIKE ?'; $params[] = "%$search%"; }
if ($faculty_id > 0) { $conditions[] = 'p.faculty_id = ?'; $params[] = $faculty_id; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT p.*, f.nom AS faculty_nom FROM programs p
    JOIN faculties f ON f.id = p.faculty_id
    $where ORDER BY p.nom ASC
");
$stmt->execute($params);
$programs = $stmt->fetchAll();

$rows = [];
foreach ($programs as $p) {
    $rows[] = [$p['nom'], $p['faculty_nom'], $p['type'], $p['niveau'], $p['duree']];
}

export_csv('programmes.csv', ['Nom', 'Faculté', 'Type', 'Niveau', 'Durée'], $rows);
