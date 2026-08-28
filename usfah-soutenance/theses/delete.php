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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM theses WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('delete', 'thesis', $id, 'Suppression du mémoire "' . $thesis['titre'] . '"');
        flash('success', 'Mémoire supprimé.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer ce mémoire : une soutenance ou des corrections y sont rattachées.');
    }
    redirect('/theses/index.php');
}

$page_title = 'Supprimer un mémoire';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer le mémoire <strong><?= e($thesis['titre']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
