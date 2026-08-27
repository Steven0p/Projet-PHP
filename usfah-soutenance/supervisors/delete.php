<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM supervisors WHERE id = ?');
$stmt->execute([$id]);
$supervisor = $stmt->fetch();
if (!$supervisor) {
    flash('danger', 'Encadreur introuvable.');
    redirect('/supervisors/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM supervisors WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Encadreur supprimé.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer cet encadreur : des mémoires y sont rattachés.');
    }
    redirect('/supervisors/index.php');
}

$page_title = 'Supprimer un encadreur';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer l'encadreur <strong><?= e($supervisor['first_name'] . ' ' . $supervisor['last_name']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
