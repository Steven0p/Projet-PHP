<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statut = trim((string) get_param('statut', ''));
$date = trim((string) get_param('date', ''));

$conditions = [];
$params = [];
if ($statut !== '') { $conditions[] = 'd.statut = ?'; $params[] = $statut; }
if ($date !== '') { $conditions[] = 'd.date = ?'; $params[] = $date; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT d.*, t.titre, s.first_name AS student_first, s.last_name AS student_last, r.nom_numero
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    JOIN rooms r ON r.id = d.room_id
    $where ORDER BY d.date DESC, d.heure DESC
");
$stmt->execute($params);
$defenses = $stmt->fetchAll();

$rows = [];
foreach ($defenses as $d) {
    $rows[] = [
        $d['titre'],
        $d['student_first'] . ' ' . $d['student_last'],
        $d['date'],
        substr($d['heure'], 0, 5),
        $d['nom_numero'],
        $d['statut'],
    ];
}

export_csv('soutenances.csv', ['Mémoire', 'Étudiant', 'Date', 'Heure', 'Salle', 'Statut'], $rows);
