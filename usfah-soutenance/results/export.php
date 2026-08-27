<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$decision = trim((string) get_param('decision', ''));
$where = '';
$params = [];
if ($decision !== '') {
    $where = 'WHERE r.decision = ?';
    $params[] = $decision;
}

$stmt = $pdo->prepare("
    SELECT r.*, t.titre, s.first_name, s.last_name, d.date
    FROM results r
    JOIN defenses d ON d.id = r.defense_id
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    $where ORDER BY r.date_validation DESC
");
$stmt->execute($params);
$results = $stmt->fetchAll();

$rows = [];
foreach ($results as $r) {
    $rows[] = [
        $r['titre'],
        $r['first_name'] . ' ' . $r['last_name'],
        $r['date'],
        $r['note_finale'],
        $r['mention'],
        $r['decision'],
        $r['date_validation'],
    ];
}

export_csv('resultats.csv', ['Mémoire', 'Étudiant', 'Date de soutenance', 'Note finale', 'Mention', 'Décision', 'Date de validation'], $rows);
