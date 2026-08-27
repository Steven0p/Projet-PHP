<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM jury_members WHERE id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) {
    flash('danger', 'Membre de jury introuvable.');
    redirect('/jury-members/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM jury_members WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Membre de jury supprimé.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer ce membre : il est assigné à une ou plusieurs soutenances.');
    }
    redirect('/jury-members/index.php');
}

$page_title = 'Supprimer un membre de jury';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer <strong><?= e($member['first_name'] . ' ' . $member['last_name']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
