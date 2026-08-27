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

$stmt = $pdo->prepare("SELECT * FROM jury_members $where ORDER BY last_name ASC");
$stmt->execute($params);
$members = $stmt->fetchAll();

$rows = [];
foreach ($members as $m) {
    $rows[] = [$m['first_name'], $m['last_name'], $m['email'], $m['telephone'], $m['specialite'], $m['institution'], $m['fonction']];
}

export_csv('membres_jury.csv', ['Prénom', 'Nom', 'Email', 'Téléphone', 'Spécialité', 'Institution', 'Fonction'], $rows);
