<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM supervisors $where ORDER BY last_name ASC");
$stmt->execute($params);
$supervisors = $stmt->fetchAll();

$rows = [];
foreach ($supervisors as $s) {
    $rows[] = [$s['first_name'], $s['last_name'], $s['email'], $s['telephone'], $s['specialite'], $s['institution'], $s['grade']];
}

export_csv('encadreurs.csv', ['Prénom', 'Nom', 'Email', 'Téléphone', 'Spécialité', 'Institution', 'Grade'], $rows);
