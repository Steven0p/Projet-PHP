<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT r.*, d.id AS defense_id, d.date, d.heure, t.titre, t.domaine_recherche,
           s.first_name AS student_first, s.last_name AS student_last, s.matricule,
           sp.first_name AS sup_first, sp.last_name AS sup_last, rm.nom_numero, rm.campus
    FROM results r
    JOIN defenses d ON d.id = r.defense_id
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    LEFT JOIN supervisors sp ON sp.id = t.supervisor_id
    JOIN rooms rm ON rm.id = d.room_id
    WHERE r.id = ?
');
$stmt->execute([$id]);
$result = $stmt->fetch();

if (!$result) {
    flash('danger', 'Résultat introuvable.');
    redirect('/results/index.php');
}

$jury_stmt = $pdo->prepare("
    SELECT jm.first_name, jm.last_name, dj.role FROM defense_jury dj
    JOIN jury_members jm ON jm.id = dj.jury_member_id
    WHERE dj.defense_id = ?
    ORDER BY FIELD(dj.role, 'president', 'examinateur', 'rapporteur')
");
$jury_stmt->execute([$result['defense_id']]);
$jury = $jury_stmt->fetchAll();

$role_labels = ['president' => 'President du jury', 'examinateur' => 'Examinateur', 'rapporteur' => 'Rapporteur'];
$decision_labels = ['admis' => 'Admis', 'admis_avec_corrections' => 'Admis avec corrections', 'ajourne' => 'Ajourne'];
$mention_labels = ['passable' => 'Passable', 'assez_bien' => 'Assez bien', 'bien' => 'Bien', 'tres_bien' => 'Tres bien', 'excellent' => 'Excellent'];

$sections = [
    ['heading' => 'Etudiant'],
    ['label' => 'Nom', 'value' => $result['student_first'] . ' ' . $result['student_last']],
    ['label' => 'Matricule', 'value' => $result['matricule']],
    ['heading' => 'Memoire'],
    ['label' => 'Domaine de recherche', 'value' => $result['domaine_recherche'] ?: 'N/A'],
    ['label' => 'Encadreur', 'value' => $result['sup_first'] ? $result['sup_first'] . ' ' . $result['sup_last'] : 'N/A'],
    ['heading' => 'Soutenance'],
    ['label' => 'Date', 'value' => format_date($result['date'])],
    ['label' => 'Heure', 'value' => substr($result['heure'], 0, 5)],
    ['label' => 'Salle', 'value' => $result['nom_numero'] . ($result['campus'] ? ' (' . $result['campus'] . ')' : '')],
    ['heading' => 'Resultat delibere par le jury'],
    ['label' => 'Note finale', 'value' => $result['note_finale'] !== null ? (string) $result['note_finale'] : 'N/A'],
    ['label' => 'Mention', 'value' => $result['mention'] ? ($mention_labels[$result['mention']] ?? $result['mention']) : 'N/A'],
    ['label' => 'Decision', 'value' => $decision_labels[$result['decision']] ?? $result['decision']],
    ['label' => 'Commentaires du jury', 'value' => $result['commentaires_jury'] ?: 'Aucun'],
    ['label' => 'Date de validation', 'value' => format_date($result['date_validation'])],
];

$signatories = [];
foreach ($jury as $j) {
    $signatories[] = [
        'role' => $role_labels[$j['role']] ?? ucfirst($j['role']),
        'name' => $j['first_name'] . ' ' . $j['last_name'],
    ];
}

$pdf_content = build_proces_verbal_pdf(
    "Universite Saint-Francois d'Assise d'Haiti (USFAH)",
    $result['titre'],
    $sections,
    $signatories
);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="proces-verbal-' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
exit;
