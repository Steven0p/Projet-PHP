<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Étudiant introuvable.');
    redirect('/students/index.php');
}

$errors = [];
$data = $student;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['matricule', 'first_name', 'last_name', 'sexe', 'date_naissance', 'email', 'telephone', 'niveau', 'annee_academique', 'statut'] as $field) {
        $data[$field] = trim((string) post($field, ''));
    }
    $data['faculty_id'] = (int) post('faculty_id', 0);
    $data['program_id'] = (int) post('program_id', 0);

    if ($data['matricule'] === '') $errors[] = 'Le matricule est obligatoire.';
    if ($data['first_name'] === '') $errors[] = 'Le prénom est obligatoire.';
    if ($data['last_name'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Un email valide est obligatoire.';
    if ($data['annee_academique'] === '') $errors[] = 'L\'année académique est obligatoire.';
    if ($data['sexe'] !== '' && !in_array($data['sexe'], ['M', 'F'], true)) $errors[] = 'Sexe invalide.';
    if (!in_array($data['statut'], ['actif', 'inactif'], true)) $errors[] = 'Statut invalide.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE matricule = ? AND id != ?');
        $stmt->execute([$data['matricule'], $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Ce matricule est déjà utilisé.';

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE email = ? AND id != ?');
        $stmt->execute([$data['email'], $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Cet email est déjà utilisé.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE students SET matricule=?, first_name=?, last_name=?, sexe=?, date_naissance=?, email=?, telephone=?, faculty_id=?, program_id=?, niveau=?, annee_academique=?, statut=? WHERE id=?');
        $stmt->execute([
            $data['matricule'], $data['first_name'], $data['last_name'], $data['sexe'] ?: null,
            $data['date_naissance'] ?: null, $data['email'], $data['telephone'] ?: null,
            $data['faculty_id'] ?: null, $data['program_id'] ?: null, $data['niveau'] ?: null,
            $data['annee_academique'], $data['statut'], $id,
        ]);
        log_activity('update', 'student', $id, 'Modification de l\'étudiant ' . $data['matricule'] . ' - ' . $data['first_name'] . ' ' . $data['last_name']);
        flash('success', 'Étudiant mis à jour.');
        redirect('/students/index.php');
    }
}

$faculties = $pdo->query('SELECT id, nom FROM faculties ORDER BY nom')->fetchAll();
$programs = $pdo->query('SELECT id, nom, faculty_id FROM programs ORDER BY nom')->fetchAll();

$page_title = 'Modifier un étudiant';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier l'étudiant</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Matricule *</label>
            <input type="text" name="matricule" class="form-control" value="<?= old($data, 'matricule') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Sexe</label>
            <select name="sexe" class="form-select">
                <option value="M" <?= $data['sexe'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                <option value="F" <?= $data['sexe'] === 'F' ? 'selected' : '' ?>>Féminin</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Prénom *</label>
            <input type="text" name="first_name" class="form-control" value="<?= old($data, 'first_name') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Nom *</label>
            <input type="text" name="last_name" class="form-control" value="<?= old($data, 'last_name') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Date de naissance</label>
            <input type="date" name="date_naissance" class="form-control" value="<?= e($data['date_naissance'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="<?= old($data, 'email') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="<?= old($data, 'telephone') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Année académique *</label>
            <input type="text" name="annee_academique" class="form-control" value="<?= old($data, 'annee_academique') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Faculté</label>
            <select name="faculty_id" id="faculty_id" class="form-select">
                <option value="">-- Choisir --</option>
                <?php foreach ($faculties as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= (int) $data['faculty_id'] === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Programme</label>
            <select name="program_id" id="program_id" class="form-select">
                <option value="">-- Choisir --</option>
                <?php foreach ($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" data-faculty="<?= $p['faculty_id'] ?>" <?= (int) $data['program_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Niveau</label>
            <input type="text" name="niveau" class="form-control" value="<?= old($data, 'niveau') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <option value="actif" <?= $data['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="inactif" <?= $data['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var facultySelect = document.getElementById('faculty_id');
    var programSelect = document.getElementById('program_id');
    var options = Array.from(programSelect.options);
    function filterPrograms() {
        var fid = facultySelect.value;
        options.forEach(function (opt) {
            if (!opt.value) { opt.hidden = false; return; }
            opt.hidden = fid !== '' && opt.dataset.faculty !== fid;
        });
    }
    facultySelect.addEventListener('change', filterPrograms);
    filterPrograms();
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
