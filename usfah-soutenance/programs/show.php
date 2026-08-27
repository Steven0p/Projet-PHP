<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT p.*, f.nom AS faculty_nom FROM programs p JOIN faculties f ON f.id = p.faculty_id WHERE p.id = ?');
$stmt->execute([$id]);
$program = $stmt->fetch();
if (!$program) {
    flash('danger', 'Programme introuvable.');
    redirect('/programs/index.php');
}

$page_title = 'Détails du programme';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($program['nom']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Faculté</dt><dd class="col-sm-9"><?= e($program['faculty_nom']) ?></dd>
        <dt class="col-sm-3">Type</dt><dd class="col-sm-9"><?= e(ucfirst($program['type'])) ?></dd>
        <dt class="col-sm-3">Niveau</dt><dd class="col-sm-9"><?= e($program['niveau'] ?? '—') ?></dd>
        <dt class="col-sm-3">Durée</dt><dd class="col-sm-9"><?= e($program['duree'] ?? '—') ?></dd>
    </dl>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
