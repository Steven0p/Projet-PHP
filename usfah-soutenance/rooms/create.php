<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$errors = [];
$data = ['nom_numero' => '', 'campus' => '', 'capacite' => '', 'disponibilite' => 'disponible', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['nom_numero'] = trim((string) post('nom_numero', ''));
    $data['campus'] = trim((string) post('campus', ''));
    $data['capacite'] = trim((string) post('capacite', ''));
    $data['disponibilite'] = post('disponibilite', 'disponible');
    $data['description'] = trim((string) post('description', ''));

    if ($data['nom_numero'] === '') $errors[] = 'Le nom ou numéro de la salle est obligatoire.';
    if ($data['capacite'] !== '' && !ctype_digit($data['capacite'])) $errors[] = 'La capacité doit être un nombre.';
    if (!in_array($data['disponibilite'], ['disponible', 'indisponible'], true)) $errors[] = 'Disponibilité invalide.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO rooms (nom_numero, campus, capacite, disponibilite, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['nom_numero'], $data['campus'] ?: null, $data['capacite'] !== '' ? (int) $data['capacite'] : null, $data['disponibilite'], $data['description'] ?: null]);
        flash('success', 'Salle créée avec succès.');
        redirect('/rooms/index.php');
    }
}

$page_title = 'Ajouter une salle';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Ajouter une salle</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Nom ou numéro *</label><input type="text" name="nom_numero" class="form-control" value="<?= old($data, 'nom_numero') ?>" required></div>
    <div class="mb-3"><label class="form-label">Campus</label><input type="text" name="campus" class="form-control" value="<?= old($data, 'campus') ?>"></div>
    <div class="mb-3"><label class="form-label">Capacité</label><input type="number" min="1" name="capacite" class="form-control" value="<?= old($data, 'capacite') ?>"></div>
    <div class="mb-3">
        <label class="form-label">Disponibilité</label>
        <select name="disponibilite" class="form-select">
            <option value="disponible" <?= $data['disponibilite'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="indisponible" <?= $data['disponibilite'] === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= old($data, 'description') ?></textarea></div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
