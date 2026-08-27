<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT d.*, t.titre, t.domaine_recherche, s.first_name AS student_first, s.last_name AS student_last, s.matricule,
           sp.first_name AS sup_first, sp.last_name AS sup_last, r.nom_numero, r.campus
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    LEFT JOIN supervisors sp ON sp.id = t.supervisor_id
    JOIN rooms r ON r.id = d.room_id
    WHERE d.id = ?
');
$stmt->execute([$id]);
$defense = $stmt->fetch();

if (!$defense) {
    flash('danger', 'Soutenance introuvable.');
    redirect('/defenses/index.php');
}

$jury_stmt = $pdo->prepare("
    SELECT jm.first_name, jm.last_name, dj.role FROM defense_jury dj
    JOIN jury_members jm ON jm.id = dj.jury_member_id
    WHERE dj.defense_id = ?
    ORDER BY FIELD(dj.role, 'president', 'examinateur', 'rapporteur')
");
$jury_stmt->execute([$id]);
$jury = $jury_stmt->fetchAll();

$role_labels = ['president' => 'President du jury', 'examinateur' => 'Examinateur', 'rapporteur' => 'Rapporteur'];

$sections = [
    ['heading' => 'Etudiant'],
    ['label' => 'Nom', 'value' => $defense['student_first'] . ' ' . $defense['student_last']],
    ['label' => 'Matricule', 'value' => $defense['matricule']],
    ['heading' => 'Memoire'],
    ['label' => 'Domaine de recherche', 'value' => $defense['domaine_recherche'] ?: 'N/A'],
    ['label' => 'Encadreur', 'value' => $defense['sup_first'] ? $defense['sup_first'] . ' ' . $defense['sup_last'] : 'N/A'],
    ['heading' => 'Soutenance'],
    ['label' => 'Date', 'value' => format_date($defense['date'])],
    ['label' => 'Heure', 'value' => substr($defense['heure'], 0, 5)],
    ['label' => 'Salle', 'value' => $defense['nom_numero'] . ($defense['campus'] ? ' (' . $defense['campus'] . ')' : '')],
    ['label' => 'Statut', 'value' => ucfirst(str_replace('_', ' ', $defense['statut']))],
    ['heading' => 'Composition du jury'],
];
foreach ($jury as $j) {
    $sections[] = [
        'label' => $role_labels[$j['role']] ?? ucfirst($j['role']),
        'value' => $j['first_name'] . ' ' . $j['last_name'],
    ];
}

$pdf_content = build_defense_sheet_pdf(
    "Universite Saint-Francois d'Assise d'Haiti (USFAH)",
    $defense['titre'],
    $sections
);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="fiche-soutenance-' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
exit;
