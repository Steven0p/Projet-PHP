<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM jury_members WHERE id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) {
    flash('danger', 'Membre de jury introuvable.');
    redirect('/jury-members/index.php');
}

$defenses_stmt = $pdo->prepare('
    SELECT d.*, dj.role, t.titre
    FROM defense_jury dj
    JOIN defenses d ON d.id = dj.defense_id
    JOIN theses t ON t.id = d.thesis_id
    WHERE dj.jury_member_id = ?
    ORDER BY d.date DESC
');
$defenses_stmt->execute([$id]);
$defenses = $defenses_stmt->fetchAll();

$page_title = 'Détails du membre de jury';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($member['first_name'] . ' ' . $member['last_name']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e($member['email']) ?></dd>
        <dt class="col-sm-3">Téléphone</dt><dd class="col-sm-9"><?= e($member['telephone'] ?? '—') ?></dd>
        <dt class="col-sm-3">Spécialité</dt><dd class="col-sm-9"><?= e($member['specialite'] ?? '—') ?></dd>
        <dt class="col-sm-3">Institution</dt><dd class="col-sm-9"><?= e($member['institution'] ?? '—') ?></dd>
        <dt class="col-sm-3">Fonction</dt><dd class="col-sm-9"><?= e($member['fonction'] ?? '—') ?></dd>
    </dl>
</div></div>
<div class="card">
    <div class="card-header fw-semibold">Soutenances</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Mémoire</th><th>Date</th><th>Rôle</th></tr></thead>
            <tbody>
                <?php if (empty($defenses)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Aucune soutenance assignée.</td></tr>
                <?php endif; ?>
                <?php foreach ($defenses as $d): ?>
                    <tr>
                        <td><?= e($d['titre']) ?></td>
                        <td><?= format_date($d['date']) ?></td>
                        <td><?= e(ucfirst($d['role'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
