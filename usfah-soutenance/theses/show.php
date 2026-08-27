<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT t.*, s.first_name AS student_first, s.last_name AS student_last, s.matricule,
           sp.first_name AS sup_first, sp.last_name AS sup_last
    FROM theses t
    JOIN students s ON s.id = t.student_id
    LEFT JOIN supervisors sp ON sp.id = t.supervisor_id
    WHERE t.id = ?
');
$stmt->execute([$id]);
$thesis = $stmt->fetch();
if (!$thesis) {
    flash('danger', 'Mémoire introuvable.');
    redirect('/theses/index.php');
}

$defense_stmt = $pdo->prepare('SELECT d.*, r.nom_numero FROM defenses d JOIN rooms r ON r.id = d.room_id WHERE d.thesis_id = ?');
$defense_stmt->execute([$id]);
$defense = $defense_stmt->fetch();

$corrections_stmt = $pdo->prepare('SELECT * FROM corrections WHERE thesis_id = ? ORDER BY date_limite ASC');
$corrections_stmt->execute([$id]);
$corrections = $corrections_stmt->fetchAll();

$page_title = 'Détails du mémoire';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($thesis['titre']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Étudiant</dt><dd class="col-sm-9"><a href="<?= BASE_URL ?>/students/show.php?id=<?= $thesis['student_id'] ?>"><?= e($thesis['student_first'] . ' ' . $thesis['student_last']) ?></a> (<?= e($thesis['matricule']) ?>)</dd>
        <dt class="col-sm-3">Encadreur</dt><dd class="col-sm-9"><?= e($thesis['sup_first'] ? $thesis['sup_first'] . ' ' . $thesis['sup_last'] : '—') ?></dd>
        <dt class="col-sm-3">Domaine</dt><dd class="col-sm-9"><?= e($thesis['domaine_recherche'] ?? '—') ?></dd>
        <dt class="col-sm-3">Résumé</dt><dd class="col-sm-9"><?= nl2br(e($thesis['resume'] ?? '—')) ?></dd>
        <dt class="col-sm-3">Date de soumission</dt><dd class="col-sm-9"><?= format_date($thesis['date_soumission']) ?></dd>
        <dt class="col-sm-3">Année académique</dt><dd class="col-sm-9"><?= e($thesis['annee_academique']) ?></dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($thesis['statut']) ?></dd>
    </dl>
</div></div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Soutenance</div>
    <div class="card-body">
        <?php if ($defense): ?>
            <dl class="row mb-0">
                <dt class="col-sm-3">Date</dt><dd class="col-sm-9"><?= format_date($defense['date']) ?> à <?= e(substr($defense['heure'], 0, 5)) ?></dd>
                <dt class="col-sm-3">Salle</dt><dd class="col-sm-9"><?= e($defense['nom_numero']) ?></dd>
                <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($defense['statut']) ?></dd>
            </dl>
            <a href="<?= BASE_URL ?>/defenses/show.php?id=<?= $defense['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">Voir la soutenance</a>
        <?php else: ?>
            <p class="text-muted mb-2">Aucune soutenance programmée pour ce mémoire.</p>
            <a href="<?= BASE_URL ?>/defenses/create.php?thesis_id=<?= $id ?>" class="btn btn-sm btn-primary">Programmer une soutenance</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Corrections</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Description</th><th>Date limite</th><th>Statut</th></tr></thead>
            <tbody>
                <?php if (empty($corrections)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Aucune correction enregistrée.</td></tr>
                <?php endif; ?>
                <?php foreach ($corrections as $c): ?>
                    <tr>
                        <td><?= e($c['description']) ?></td>
                        <td><?= format_date($c['date_limite']) ?></td>
                        <td><?= status_badge($c['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
