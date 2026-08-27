<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) {
    flash('danger', 'Salle introuvable.');
    redirect('/rooms/index.php');
}

$defenses_stmt = $pdo->prepare('
    SELECT d.*, t.titre FROM defenses d JOIN theses t ON t.id = d.thesis_id
    WHERE d.room_id = ? ORDER BY d.date DESC
');
$defenses_stmt->execute([$id]);
$defenses = $defenses_stmt->fetchAll();

$page_title = 'Détails de la salle';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($room['nom_numero']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Campus</dt><dd class="col-sm-9"><?= e($room['campus'] ?? '—') ?></dd>
        <dt class="col-sm-3">Capacité</dt><dd class="col-sm-9"><?= e((string) ($room['capacite'] ?? '—')) ?></dd>
        <dt class="col-sm-3">Disponibilité</dt><dd class="col-sm-9"><?= status_badge($room['disponibilite']) ?></dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= e($room['description'] ?? '—') ?></dd>
    </dl>
</div></div>
<div class="card">
    <div class="card-header fw-semibold">Soutenances dans cette salle</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Mémoire</th><th>Date</th><th>Heure</th><th>Statut</th></tr></thead>
            <tbody>
                <?php if (empty($defenses)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Aucune soutenance programmée.</td></tr>
                <?php endif; ?>
                <?php foreach ($defenses as $d): ?>
                    <tr>
                        <td><?= e($d['titre']) ?></td>
                        <td><?= format_date($d['date']) ?></td>
                        <td><?= e(substr($d['heure'], 0, 5)) ?></td>
                        <td><?= status_badge($d['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
