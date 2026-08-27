<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$errors = [];
$data = ['first_name' => '', 'last_name' => '', 'email' => '', 'telephone' => '', 'specialite' => '', 'institution' => '', 'fonction' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $field => $_) {
        $data[$field] = trim((string) post($field, ''));
    }

    if ($data['first_name'] === '') $errors[] = 'Le prénom est obligatoire.';
    if ($data['last_name'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Un email valide est obligatoire.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO jury_members (first_name, last_name, email, telephone, specialite, institution, fonction) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['telephone'] ?: null, $data['specialite'] ?: null, $data['institution'] ?: null, $data['fonction'] ?: null]);
        flash('success', 'Membre de jury créé avec succès.');
        redirect('/jury-members/index.php');
    }
}

$page_title = 'Ajouter un membre de jury';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Ajouter un membre de jury</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Prénom *</label><input type="text" name="first_name" class="form-control" value="<?= old($data, 'first_name') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Nom *</label><input type="text" name="last_name" class="form-control" value="<?= old($data, 'last_name') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= old($data, 'email') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= old($data, 'telephone') ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Spécialité</label><input type="text" name="specialite" class="form-control" value="<?= old($data, 'specialite') ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Institution</label><input type="text" name="institution" class="form-control" value="<?= old($data, 'institution') ?>"></div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Fonction habituelle</label>
            <select name="fonction" class="form-select">
                <option value="">-- Aucune --</option>
                <?php foreach (['Président', 'Examinateur', 'Rapporteur'] as $f): ?>
                    <option value="<?= e($f) ?>" <?= $data['fonction'] === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
