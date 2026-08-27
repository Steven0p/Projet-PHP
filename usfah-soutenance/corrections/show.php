<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT c.*, t.titre, s.first_name, s.last_name
    FROM corrections c
    JOIN theses t ON t.id = c.thesis_id
    JOIN students s ON s.id = t.student_id
    WHERE c.id = ?
');
$stmt->execute([$id]);
$correction = $stmt->fetch();
if (!$correction) {
    flash('danger', 'Correction introuvable.');
    redirect('/corrections/index.php');
}

$page_title = 'Détails de la correction';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Correction — <?= e($correction['titre']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Étudiant</dt><dd class="col-sm-9"><?= e($correction['first_name'] . ' ' . $correction['last_name']) ?></dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(e($correction['description'])) ?></dd>
        <dt class="col-sm-3">Date limite</dt><dd class="col-sm-9"><?= format_date($correction['date_limite']) ?></dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($correction['statut']) ?></dd>
        <dt class="col-sm-3">Date de validation</dt><dd class="col-sm-9"><?= format_date($correction['date_validation']) ?></dd>
    </dl>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
