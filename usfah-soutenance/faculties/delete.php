<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM faculties WHERE id = ?');
$stmt->execute([$id]);
$faculty = $stmt->fetch();

if (!$faculty) {
    flash('danger', 'Faculté introuvable.');
    redirect('/faculties/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM faculties WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Faculté supprimée.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer cette faculté : des programmes ou étudiants y sont rattachés.');
    }
    redirect('/faculties/index.php');
}

$page_title = 'Supprimer une faculté';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer la faculté <strong><?= e($faculty['nom']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
