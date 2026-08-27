<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM supervisors WHERE id = ?');
$stmt->execute([$id]);
$supervisor = $stmt->fetch();
if (!$supervisor) {
    flash('danger', 'Encadreur introuvable.');
    redirect('/supervisors/index.php');
}

$theses_stmt = $pdo->prepare('SELECT t.*, s.first_name AS student_first, s.last_name AS student_last FROM theses t JOIN students s ON s.id = t.student_id WHERE t.supervisor_id = ? ORDER BY t.created_at DESC');
$theses_stmt->execute([$id]);
$theses = $theses_stmt->fetchAll();

$page_title = 'Détails de l\'encadreur';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($supervisor['first_name'] . ' ' . $supervisor['last_name']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>
<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e($supervisor['email']) ?></dd>
        <dt class="col-sm-3">Téléphone</dt><dd class="col-sm-9"><?= e($supervisor['telephone'] ?? '—') ?></dd>
        <dt class="col-sm-3">Spécialité</dt><dd class="col-sm-9"><?= e($supervisor['specialite'] ?? '—') ?></dd>
        <dt class="col-sm-3">Institution</dt><dd class="col-sm-9"><?= e($supervisor['institution'] ?? '—') ?></dd>
        <dt class="col-sm-3">Grade</dt><dd class="col-sm-9"><?= e($supervisor['grade'] ?? '—') ?></dd>
    </dl>
</div></div>
<div class="card">
    <div class="card-header fw-semibold">Mémoires encadrés</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Titre</th><th>Étudiant</th><th>Statut</th></tr></thead>
            <tbody>
                <?php if (empty($theses)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Aucun mémoire encadré.</td></tr>
                <?php endif; ?>
                <?php foreach ($theses as $t): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/theses/show.php?id=<?= $t['id'] ?>"><?= e($t['titre']) ?></a></td>
                        <td><?= e($t['student_first'] . ' ' . $t['student_last']) ?></td>
                        <td><?= status_badge($t['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
