<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('
    SELECT s.*, f.nom AS faculty_nom, p.nom AS program_nom
    FROM students s
    LEFT JOIN faculties f ON f.id = s.faculty_id
    LEFT JOIN programs p ON p.id = s.program_id
    WHERE s.id = ?
');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Étudiant introuvable.');
    redirect('/students/index.php');
}

$theses_stmt = $pdo->prepare('SELECT * FROM theses WHERE student_id = ? ORDER BY created_at DESC');
$theses_stmt->execute([$id]);
$theses = $theses_stmt->fetchAll();

$page_title = 'Détails de l\'étudiant';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Modifier</a>
        <a href="index.php" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Matricule</dt><dd class="col-sm-9"><?= e($student['matricule']) ?></dd>
        <dt class="col-sm-3">Sexe</dt><dd class="col-sm-9"><?= e($student['sexe'] ?? '—') ?></dd>
        <dt class="col-sm-3">Date de naissance</dt><dd class="col-sm-9"><?= format_date($student['date_naissance']) ?></dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e($student['email']) ?></dd>
        <dt class="col-sm-3">Téléphone</dt><dd class="col-sm-9"><?= e($student['telephone'] ?? '—') ?></dd>
        <dt class="col-sm-3">Faculté</dt><dd class="col-sm-9"><?= e($student['faculty_nom'] ?? '—') ?></dd>
        <dt class="col-sm-3">Programme</dt><dd class="col-sm-9"><?= e($student['program_nom'] ?? '—') ?></dd>
        <dt class="col-sm-3">Niveau</dt><dd class="col-sm-9"><?= e($student['niveau'] ?? '—') ?></dd>
        <dt class="col-sm-3">Année académique</dt><dd class="col-sm-9"><?= e($student['annee_academique']) ?></dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><?= status_badge($student['statut']) ?></dd>
    </dl>
</div></div>

<div class="card">
    <div class="card-header fw-semibold">Mémoires de l'étudiant</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Titre</th><th>Année académique</th><th>Statut</th></tr></thead>
            <tbody>
                <?php if (empty($theses)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">Aucun mémoire enregistré.</td></tr>
                <?php endif; ?>
                <?php foreach ($theses as $t): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/theses/show.php?id=<?= $t['id'] ?>"><?= e($t['titre']) ?></a></td>
                        <td><?= e($t['annee_academique']) ?></td>
                        <td><?= status_badge($t['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
