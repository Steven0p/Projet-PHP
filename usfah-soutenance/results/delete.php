<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT r.*, t.titre FROM results r JOIN defenses d ON d.id = r.defense_id JOIN theses t ON t.id = d.thesis_id WHERE r.id = ?');
$stmt->execute([$id]);
$result = $stmt->fetch();
if (!$result) {
    flash('danger', 'Résultat introuvable.');
    redirect('/results/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM results WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Résultat supprimé.');
    redirect('/results/index.php');
}

$page_title = 'Supprimer un résultat';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer le résultat du mémoire <strong><?= e($result['titre']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
