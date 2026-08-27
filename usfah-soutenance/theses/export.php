<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$statut = trim((string) get_param('statut', ''));
$annee = trim((string) get_param('annee', ''));

$conditions = [];
$params = [];
if ($search !== '') { $conditions[] = 't.titre LIKE ?'; $params[] = "%$search%"; }
if ($statut !== '') { $conditions[] = 't.statut = ?'; $params[] = $statut; }
if ($annee !== '') { $conditions[] = 't.annee_academique = ?'; $params[] = $annee; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT t.*, s.first_name AS student_first, s.last_name AS student_last, sp.first_name AS sup_first, sp.last_name AS sup_last
    FROM theses t
    JOIN students s ON s.id = t.student_id
    LEFT JOIN supervisors sp ON sp.id = t.supervisor_id
    $where ORDER BY t.created_at DESC
");
$stmt->execute($params);
$theses = $stmt->fetchAll();

$rows = [];
foreach ($theses as $t) {
    $rows[] = [
        $t['titre'],
        $t['student_first'] . ' ' . $t['student_last'],
        $t['sup_first'] ? $t['sup_first'] . ' ' . $t['sup_last'] : '',
        $t['domaine_recherche'],
        $t['date_soumission'],
        $t['annee_academique'],
        $t['statut'],
    ];
}

export_csv('memoires.csv', [
    'Titre', 'Étudiant', 'Encadreur', 'Domaine de recherche', 'Date de soumission', 'Année académique', 'Statut',
], $rows);
