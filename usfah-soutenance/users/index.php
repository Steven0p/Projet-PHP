<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'toggle_status') {
    verify_csrf();
    $id = (int) post('id', 0);
    if ($id === (int) $_SESSION['user_id']) {
        flash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
    } else {
        $stmt = $pdo->prepare("UPDATE users SET statut = IF(statut = 'actif', 'inactif', 'actif') WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Statut de l\'utilisateur mis à jour.');
    }
    redirect('/users/index.php');
}

$users = $pdo->query('SELECT * FROM users ORDER BY last_name ASC')->fetchAll();

$page_title = 'Utilisateurs';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Utilisateurs</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un utilisateur</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['role'] === 'admin' ? 'Administrateur' : 'Responsable académique') ?></td>
                        <td><?= status_badge($u['statut']) ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <?= $u['statut'] === 'actif' ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                            <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
