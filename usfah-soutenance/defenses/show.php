<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT d.*, t.titre, t.id AS thesis_id, s.first_name AS student_first, s.last_name AS student_last, r.nom_numero, r.campus
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    JOIN rooms r ON r.id = d.room_id
    WHERE d.id = ?
');
$stmt->execute([$id]);
$defense = $stmt->fetch();
if (!$defense) {
    flash('danger', 'Soutenance introuvable.');
    redirect('/defenses/index.php');
}

$jury_stmt = $pdo->prepare('
    SELECT jm.first_name, jm.last_name, dj.role FROM defense_jury dj
    JOIN jury_members jm ON jm.id = dj.jury_member_id
    WHERE dj.defense_id = ?
');
$jury_stmt->execute([$id]);
$jury = $jury_stmt->fetchAll();

$result_stmt = $pdo->prepare('SELECT * FROM results WHERE defense_id = ?');
$result_stmt->execute([$id]);
$result = $result_stmt->fetch();

$page_title = 'Détails de la soutenance';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Soutenance — <?= e($defense['titre']) ?></h1>
    <div>
        <a href="fiche_pdf.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Fiche PDF</a>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Étudiant</dt><dd class="col-sm-9"><?= e($defense['student_first'] . ' ' . $defense['student_last']) ?></dd>
        <dt class="col-sm-3">Mémoire</dt><dd class="col-sm-9"><a href="<?= BASE_URL ?>/theses/show.php?id=<?= $defense['thesis_id'] ?>"><?= e($defense['titre']) ?></a></dd>
        <dt class="col-sm-3">Date</dt><dd class="col-sm-9"><?= format_date($defense['date']) ?></dd>
        <dt class="col-sm-3">Heure</dt><dd class="col-sm-9"><?= e(substr($defense['heure'], 0, 5)) ?></dd>
        <dt class="col-sm-3">Salle</dt><dd class="col-sm-9"><?= e($defense['nom_numero']) ?> (<?= e($defense['campus'] ?? '—') ?>)</dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($defense['statut']) ?></dd>
    </dl>
</div></div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Composition du jury</div>
    <ul class="list-group list-group-flush">
        <?php foreach ($jury as $j): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= e($j['first_name'] . ' ' . $j['last_name']) ?></span>
                <span class="text-muted"><?= e(ucfirst($j['role'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        Résultat
        <?php if (!$result && $defense['statut'] === 'realisee'): ?>
            <a href="<?= BASE_URL ?>/results/create.php?defense_id=<?= $id ?>" class="btn btn-sm btn-primary">Enregistrer un résultat</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($result): ?>
            <dl class="row mb-0">
                <dt class="col-sm-3">Note finale</dt><dd class="col-sm-9"><?= e((string) ($result['note_finale'] ?? '—')) ?></dd>
                <dt class="col-sm-3">Mention</dt><dd class="col-sm-9"><?= e($result['mention'] ? ucwords(str_replace('_', ' ', $result['mention'])) : '—') ?></dd>
                <dt class="col-sm-3">Décision</dt><dd class="col-sm-9"><?= status_badge($result['decision']) ?></dd>
                <dt class="col-sm-3">Commentaires</dt><dd class="col-sm-9"><?= nl2br(e($result['commentaires_jury'] ?? '—')) ?></dd>
            </dl>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun résultat enregistré<?= $defense['statut'] !== 'realisee' ? ' (la soutenance doit être marquée « réalisée » avant d\'enregistrer un résultat).' : '.' ?></p>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
