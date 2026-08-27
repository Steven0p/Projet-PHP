<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT c.*, t.titre FROM corrections c JOIN theses t ON t.id = c.thesis_id WHERE c.id = ?');
$stmt->execute([$id]);
$correction = $stmt->fetch();
if (!$correction) {
    flash('danger', 'Correction introuvable.');
    redirect('/corrections/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM corrections WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Correction supprimée.');
    redirect('/corrections/index.php');
}

$page_title = 'Supprimer une correction';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer cette correction pour le mémoire <strong><?= e($correction['titre']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
