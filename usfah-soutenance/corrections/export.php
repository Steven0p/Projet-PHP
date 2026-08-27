<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statut = trim((string) get_param('statut', ''));
$where = '';
$params = [];
if ($statut !== '') {
    $where = 'WHERE c.statut = ?';
    $params[] = $statut;
}

$stmt = $pdo->prepare("
    SELECT c.*, t.titre, s.first_name, s.last_name
    FROM corrections c
    JOIN theses t ON t.id = c.thesis_id
    JOIN students s ON s.id = t.student_id
    $where ORDER BY c.date_limite ASC
");
$stmt->execute($params);
$corrections = $stmt->fetchAll();

$rows = [];
foreach ($corrections as $c) {
    $rows[] = [
        $c['titre'],
        $c['first_name'] . ' ' . $c['last_name'],
        $c['description'],
        $c['date_limite'],
        $c['statut'],
        $c['date_validation'],
    ];
}

export_csv('corrections.csv', ['Mémoire', 'Étudiant', 'Description', 'Date limite', 'Statut', 'Date de validation'], $rows);
