<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM theses WHERE id = ?');
$stmt->execute([$id]);
$thesis = $stmt->fetch();
if (!$thesis) {
    flash('danger', 'Mémoire introuvable.');
    redirect('/theses/index.php');
}

$errors = [];
$data = $thesis;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['student_id'] = (int) post('student_id', 0);
    $data['titre'] = trim((string) post('titre', ''));
    $data['resume'] = trim((string) post('resume', ''));
    $data['domaine_recherche'] = trim((string) post('domaine_recherche', ''));
    $data['supervisor_id'] = (int) post('supervisor_id', 0);
    $data['date_soumission'] = trim((string) post('date_soumission', ''));
    $data['annee_academique'] = trim((string) post('annee_academique', ''));
    $data['statut'] = post('statut', 'en_preparation');

    if ($data['student_id'] <= 0) $errors[] = 'L\'étudiant est obligatoire.';
    if ($data['titre'] === '') $errors[] = 'Le titre est obligatoire.';
    if ($data['annee_academique'] === '') $errors[] = 'L\'année académique est obligatoire.';
    if (!in_array($data['statut'], ['en_preparation', 'soumis', 'valide', 'a_corriger', 'autorise_a_soutenir', 'soutenu'], true)) $errors[] = 'Statut invalide.';

    if (empty($errors) && $data['statut'] !== 'soutenu') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM theses WHERE student_id = ? AND statut != 'soutenu' AND id != ?");
        $stmt->execute([$data['student_id'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Cet étudiant a déjà un autre mémoire actif.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE theses SET student_id=?, titre=?, resume=?, domaine_recherche=?, supervisor_id=?, date_soumission=?, annee_academique=?, statut=? WHERE id=?');
        $stmt->execute([
            $data['student_id'], $data['titre'], $data['resume'] ?: null, $data['domaine_recherche'] ?: null,
            $data['supervisor_id'] ?: null, $data['date_soumission'] ?: null, $data['annee_academique'], $data['statut'], $id,
        ]);
        log_activity('update', 'thesis', $id, 'Modification du mémoire "' . $data['titre'] . '"');
        flash('success', 'Mémoire mis à jour.');
        redirect('/theses/index.php');
    }
}

$students = $pdo->query('SELECT id, matricule, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$supervisors = $pdo->query('SELECT id, first_name, last_name FROM supervisors ORDER BY last_name')->fetchAll();
$statuts = ['en_preparation', 'soumis', 'valide', 'a_corriger', 'autorise_a_soutenir', 'soutenu'];

$page_title = 'Modifier un mémoire';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier le mémoire</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Étudiant *</label>
            <select name="student_id" class="form-select" required>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (int) $data['student_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['matricule'] . ' — ' . $s['first_name'] . ' ' . $s['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Encadreur</label>
            <select name="supervisor_id" class="form-select">
                <option value="">-- Choisir --</option>
                <?php foreach ($supervisors as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (int) $data['supervisor_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['first_name'] . ' ' . $s['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Titre *</label>
            <input type="text" name="titre" class="form-control" value="<?= old($data, 'titre') ?>" required>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Résumé</label>
            <textarea name="resume" class="form-control" rows="4"><?= old($data, 'resume') ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Domaine de recherche</label>
            <input type="text" name="domaine_recherche" class="form-control" value="<?= old($data, 'domaine_recherche') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Date de soumission</label>
            <input type="date" name="date_soumission" class="form-control" value="<?= e($data['date_soumission'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Année académique *</label>
            <input type="text" name="annee_academique" class="form-control" value="<?= old($data, 'annee_academique') ?>" required>
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
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
