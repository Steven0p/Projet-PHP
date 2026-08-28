<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT r.*, t.titre, s.first_name, s.last_name, d.date, d.heure
    FROM results r
    JOIN defenses d ON d.id = r.defense_id
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    WHERE r.id = ?
');
$stmt->execute([$id]);
$result = $stmt->fetch();
if (!$result) {
    flash('danger', 'Résultat introuvable.');
    redirect('/results/index.php');
}

$page_title = 'Détails du résultat';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Résultat — <?= e($result['titre']) ?></h1>
    <div>
        <a href="proces_verbal_pdf.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i> Imprimer le PV</a>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Étudiant</dt><dd class="col-sm-9"><?= e($result['first_name'] . ' ' . $result['last_name']) ?></dd>
        <dt class="col-sm-3">Date de soutenance</dt><dd class="col-sm-9"><?= format_date($result['date']) ?> à <?= e(substr($result['heure'], 0, 5)) ?></dd>
        <dt class="col-sm-3">Note finale</dt><dd class="col-sm-9"><?= e((string) ($result['note_finale'] ?? '—')) ?></dd>
        <dt class="col-sm-3">Mention</dt><dd class="col-sm-9"><?= e($result['mention'] ? ucwords(str_replace('_', ' ', $result['mention'])) : '—') ?></dd>
        <dt class="col-sm-3">Décision</dt><dd class="col-sm-9"><?= status_badge($result['decision']) ?></dd>
        <dt class="col-sm-3">Commentaires</dt><dd class="col-sm-9"><?= nl2br(e($result['commentaires_jury'] ?? '—')) ?></dd>
        <dt class="col-sm-3">Date de validation</dt><dd class="col-sm-9"><?= format_date($result['date_validation']) ?></dd>
    </dl>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
