<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_role(['admin']);

$errors = [];
$data = ['first_name' => '', 'last_name' => '', 'email' => '', 'role' => 'responsable_academique', 'statut' => 'actif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['first_name'] = trim((string) post('first_name', ''));
    $data['last_name'] = trim((string) post('last_name', ''));
    $data['email'] = trim((string) post('email', ''));
    $data['role'] = post('role', 'responsable_academique');
    $data['statut'] = post('statut', 'actif');
    $password = (string) post('password', '');
    $password_confirm = (string) post('password_confirm', '');

    if ($data['first_name'] === '') $errors[] = 'Le prénom est obligatoire.';
    if ($data['last_name'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Un email valide est obligatoire.';
    if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($password !== $password_confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
    if (!in_array($data['role'], ['admin', 'responsable_academique'], true)) $errors[] = 'Rôle invalide.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Cet email est déjà utilisé.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, statut) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], password_hash($password, PASSWORD_DEFAULT), $data['role'], $data['statut']]);
        log_activity('create', 'user', (int) $pdo->lastInsertId(), 'Création de l\'utilisateur ' . $data['first_name'] . ' ' . $data['last_name'] . ' (' . $data['role'] . ')');
        flash('success', 'Utilisateur créé avec succès.');
        redirect('/users/index.php');
    }
}

$page_title = 'Ajouter un utilisateur';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Ajouter un utilisateur</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Prénom *</label><input type="text" name="first_name" class="form-control" value="<?= old($data, 'first_name') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Nom *</label><input type="text" name="last_name" class="form-control" value="<?= old($data, 'last_name') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= old($data, 'email') ?>" required></div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Rôle *</label>
            <select name="role" class="form-select">
                <option value="responsable_academique" <?= $data['role'] === 'responsable_academique' ? 'selected' : '' ?>>Responsable académique</option>
                <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <div class="col-md-6 mb-3"><label class="form-label">Mot de passe *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Confirmer le mot de passe *</label><input type="password" name="password_confirm" class="form-control" required minlength="8"></div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <option value="actif" <?= $data['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="inactif" <?= $data['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
