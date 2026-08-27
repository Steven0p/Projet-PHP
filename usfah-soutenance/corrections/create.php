<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$errors = [];
$data = [
    'thesis_id' => (int) get_param('thesis_id', 0), 'description' => '', 'date_limite' => '', 'statut' => 'a_faire',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['thesis_id'] = (int) post('thesis_id', 0);
    $data['description'] = trim((string) post('description', ''));
    $data['date_limite'] = trim((string) post('date_limite', ''));
    $data['statut'] = post('statut', 'a_faire');

    if ($data['thesis_id'] <= 0) $errors[] = 'Le mémoire est obligatoire.';
    if ($data['description'] === '') $errors[] = 'La description est obligatoire.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO corrections (thesis_id, description, date_limite, statut) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['thesis_id'], $data['description'], $data['date_limite'] ?: null, $data['statut']]);

        if ($data['statut'] !== 'validee') {
            $pdo->prepare("UPDATE theses SET statut = 'a_corriger' WHERE id = ?")->execute([$data['thesis_id']]);
        }

        flash('success', 'Correction enregistrée avec succès.');
        redirect('/corrections/index.php');
    }
}

$theses = $pdo->query("
    SELECT t.id, t.titre, s.first_name, s.last_name FROM theses t
    JOIN students s ON s.id = t.student_id
    ORDER BY t.titre
")->fetchAll();
$statuts = ['a_faire', 'en_cours', 'soumise', 'validee'];

$page_title = 'Ajouter une correction';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Ajouter une correction</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Mémoire *</label>
        <select name="thesis_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($theses as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $data['thesis_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['titre'] . ' — ' . $t['first_name'] . ' ' . $t['last_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Description de la correction demandée *</label>
        <textarea name="description" class="form-control" rows="4" required><?= old($data, 'description') ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Date limite</label>
            <input type="date" name="date_limite" class="form-control" value="<?= old($data, 'date_limite') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= $data['statut'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
