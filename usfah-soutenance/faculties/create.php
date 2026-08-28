<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$errors = [];
$data = ['nom' => '', 'code' => '', 'description' => '', 'responsable' => '', 'statut' => 'actif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['nom'] = trim((string) post('nom', ''));
    $data['code'] = trim((string) post('code', ''));
    $data['description'] = trim((string) post('description', ''));
    $data['responsable'] = trim((string) post('responsable', ''));
    $data['statut'] = post('statut', 'actif');

    if ($data['nom'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($data['code'] === '') $errors[] = 'Le code est obligatoire.';
    if (!in_array($data['statut'], ['actif', 'inactif'], true)) $errors[] = 'Statut invalide.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM faculties WHERE code = ?');
        $stmt->execute([$data['code']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Ce code de faculté est déjà utilisé.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO faculties (nom, code, description, responsable, statut) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['nom'], $data['code'], $data['description'], $data['responsable'], $data['statut']]);
        log_activity('create', 'faculty', (int) $pdo->lastInsertId(), 'Création de la faculté ' . $data['nom']);
        flash('success', 'Faculté créée avec succès.');
        redirect('/faculties/index.php');
    }
}

$page_title = 'Ajouter une faculté';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Ajouter une faculté</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Nom *</label>
        <input type="text" name="nom" class="form-control" value="<?= old($data, 'nom') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Code *</label>
        <input type="text" name="code" class="form-control" value="<?= old($data, 'code') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= old($data, 'description') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Responsable</label>
        <input type="text" name="responsable" class="form-control" value="<?= old($data, 'responsable') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select">
            <option value="actif" <?= $data['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
            <option value="inactif" <?= $data['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
