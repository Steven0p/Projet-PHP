<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_role(['admin']);

$id = (int) get_param('id', 0);

if ($id === (int) $_SESSION['user_id']) {
    flash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
    redirect('/users/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    flash('danger', 'Utilisateur introuvable.');
    redirect('/users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    log_activity('delete', 'user', $id, 'Suppression de l\'utilisateur ' . $user['first_name'] . ' ' . $user['last_name']);
    flash('success', 'Utilisateur supprimé.');
    redirect('/users/index.php');
}

$page_title = 'Supprimer un utilisateur';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
