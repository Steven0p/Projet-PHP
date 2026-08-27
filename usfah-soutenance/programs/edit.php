<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
$stmt->execute([$id]);
$program = $stmt->fetch();
if (!$program) {
    flash('danger', 'Programme introuvable.');
    redirect('/programs/index.php');
}

$errors = [];
$data = $program;
$faculties = $pdo->query('SELECT id, nom FROM faculties ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['nom'] = trim((string) post('nom', ''));
    $data['faculty_id'] = (int) post('faculty_id', 0);
    $data['niveau'] = trim((string) post('niveau', ''));
    $data['duree'] = trim((string) post('duree', ''));
    $data['type'] = post('type', 'licence');

    if ($data['nom'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($data['faculty_id'] <= 0) $errors[] = 'La faculté est obligatoire.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE programs SET nom=?, faculty_id=?, niveau=?, duree=?, type=? WHERE id=?');
        $stmt->execute([$data['nom'], $data['faculty_id'], $data['niveau'], $data['duree'], $data['type'], $id]);
        flash('success', 'Programme mis à jour.');
        redirect('/programs/index.php');
    }
}

$page_title = 'Modifier un programme';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier le programme</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Nom *</label>
        <input type="text" name="nom" class="form-control" value="<?= old($data, 'nom') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Faculté *</label>
        <select name="faculty_id" class="form-select" required>
            <?php foreach ($faculties as $f): ?>
                <option value="<?= $f['id'] ?>" <?= $data['faculty_id'] === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Type *</label>
        <select name="type" class="form-select">
            <?php foreach (['licence' => 'Licence', 'diplome' => 'Diplôme', 'maitrise' => 'Maîtrise'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $data['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Niveau</label>
        <input type="text" name="niveau" class="form-control" value="<?= old($data, 'niveau') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Durée</label>
        <input type="text" name="duree" class="form-control" value="<?= old($data, 'duree') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
