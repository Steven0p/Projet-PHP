<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Étudiant introuvable.');
    redirect('/students/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('delete', 'student', $id, 'Suppression de l\'étudiant ' . $student['matricule'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']);
        flash('success', 'Étudiant supprimé.');
    } catch (PDOException $e) {
        flash('danger', 'Impossible de supprimer cet étudiant : un mémoire lui est rattaché.');
    }
    redirect('/students/index.php');
}

$page_title = 'Supprimer un étudiant';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Confirmer la suppression</h1>
<div class="card"><div class="card-body">
    <p>Êtes-vous sûr de vouloir supprimer l'étudiant <strong><?= e($student['first_name'] . ' ' . $student['last_name']) ?></strong> (<?= e($student['matricule']) ?>) ? Cette action est irréversible.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
