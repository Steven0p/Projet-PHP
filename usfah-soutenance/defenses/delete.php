<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT d.*, t.titre FROM defenses d JOIN theses t ON t.id = d.thesis_id WHERE d.id = ?');
$stmt->execute([$id]);
$defense = $stmt->fetch();
if (!$defense) {
    flash('danger', 'Soutenance introuvable.');
    redirect('/defenses/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM defenses WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('delete', 'defense', $id, 'Suppression de la soutenance pour "' . $defense['titre'] . '"');
        flash('success', 'Soutenance supprimée.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer cette soutenance : un résultat y est déjà rattaché.');
    }
    redirect('/defenses/index.php');
}

$page_title = 'Supprimer une soutenance';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer la soutenance du mémoire <strong><?= e($defense['titre']) ?></strong> ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
