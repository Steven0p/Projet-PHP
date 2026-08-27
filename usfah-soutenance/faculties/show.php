<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM faculties WHERE id = ?');
$stmt->execute([$id]);
$faculty = $stmt->fetch();

if (!$faculty) {
    flash('danger', 'Faculté introuvable.');
    redirect('/faculties/index.php');
}

$programs_stmt = $pdo->prepare('SELECT * FROM programs WHERE faculty_id = ? ORDER BY nom');
$programs_stmt->execute([$id]);
$programs = $programs_stmt->fetchAll();

$page_title = 'Détails de la faculté';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($faculty['nom']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><?= e($faculty['code']) ?></dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= e($faculty['description'] ?? '—') ?></dd>
        <dt class="col-sm-3">Responsable</dt><dd class="col-sm-9"><?= e($faculty['responsable'] ?? '—') ?></dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($faculty['statut']) ?></dd>
    </dl>
</div></div>

<div class="card">
    <div class="card-header fw-semibold">Programmes rattachés</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Nom</th><th>Type</th><th>Niveau</th><th>Durée</th></tr></thead>
            <tbody>
                <?php if (empty($programs)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Aucun programme rattaché.</td></tr>
                <?php endif; ?>
                <?php foreach ($programs as $p): ?>
                    <tr>
                        <td><?= e($p['nom']) ?></td>
                        <td><?= e(ucfirst($p['type'])) ?></td>
                        <td><?= e($p['niveau'] ?? '—') ?></td>
                        <td><?= e($p['duree'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
