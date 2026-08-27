<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM corrections WHERE id = ?');
$stmt->execute([$id]);
$correction = $stmt->fetch();
if (!$correction) {
    flash('danger', 'Correction introuvable.');
    redirect('/corrections/index.php');
}

$errors = [];
$data = $correction;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['description'] = trim((string) post('description', ''));
    $data['date_limite'] = trim((string) post('date_limite', ''));
    $data['statut'] = post('statut', 'a_faire');
    $data['date_validation'] = trim((string) post('date_validation', ''));

    if ($data['description'] === '') $errors[] = 'La description est obligatoire.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE corrections SET description=?, date_limite=?, statut=?, date_validation=? WHERE id=?');
        $stmt->execute([$data['description'], $data['date_limite'] ?: null, $data['statut'], $data['date_validation'] ?: null, $id]);

        if ($data['statut'] === 'validee') {
            $pdo->prepare("UPDATE theses SET statut = 'valide' WHERE id = ?")->execute([$correction['thesis_id']]);
        }

        flash('success', 'Correction mise à jour.');
        redirect('/corrections/index.php');
    }
}

$statuts = ['a_faire', 'en_cours', 'soumise', 'validee'];

$page_title = 'Modifier une correction';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier la correction</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Description de la correction demandée *</label>
        <textarea name="description" class="form-control" rows="4" required><?= old($data, 'description') ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Date limite</label>
            <input type="date" name="date_limite" class="form-control" value="<?= e($data['date_limite'] ?? '') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= $data['statut'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Date de validation</label>
            <input type="date" name="date_validation" class="form-control" value="<?= e($data['date_validation'] ?? '') ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
