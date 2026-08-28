<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
$stmt->execute([$id]);
$program = $stmt->fetch();
if (!$program) {
    flash('danger', 'Programme introuvable.');
    redirect('/programs/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM programs WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('delete', 'program', $id, 'Suppression du programme ' . $program['nom']);
        flash('success', 'Programme supprimé.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer ce programme : des étudiants y sont rattachés.');
    }
    redirect('/programs/index.php');
}

$page_title = 'Supprimer un programme';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer le programme <strong><?= e($program['nom']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
