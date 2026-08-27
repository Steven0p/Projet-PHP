<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$matricule = trim((string) get_param('matricule', ''));
$nom = trim((string) get_param('nom', ''));
$faculty_id = (int) get_param('faculty_id', 0);
$program_id = (int) get_param('program_id', 0);

$conditions = [];
$params = [];
if ($matricule !== '') { $conditions[] = 's.matricule LIKE ?'; $params[] = "%$matricule%"; }
if ($nom !== '') { $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ?)"; $params[] = "%$nom%"; $params[] = "%$nom%"; }
if ($faculty_id > 0) { $conditions[] = 's.faculty_id = ?'; $params[] = $faculty_id; }
if ($program_id > 0) { $conditions[] = 's.program_id = ?'; $params[] = $program_id; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT s.*, f.nom AS faculty_nom, p.nom AS program_nom
    FROM students s
    LEFT JOIN faculties f ON f.id = s.faculty_id
    LEFT JOIN programs p ON p.id = s.program_id
    $where ORDER BY s.last_name ASC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$rows = [];
foreach ($students as $s) {
    $rows[] = [
        $s['matricule'], $s['first_name'], $s['last_name'], $s['sexe'], $s['date_naissance'],
        $s['email'], $s['telephone'], $s['faculty_nom'], $s['program_nom'], $s['niveau'],
        $s['annee_academique'], $s['statut'],
    ];
}

export_csv('etudiants.csv', [
    'Matricule', 'Prénom', 'Nom', 'Sexe', 'Date de naissance', 'Email', 'Téléphone',
    'Faculté', 'Programme', 'Niveau', 'Année académique', 'Statut',
], $rows);
