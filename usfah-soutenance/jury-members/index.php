<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM jury_members $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("SELECT * FROM jury_members $where ORDER BY last_name ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$members = $stmt->fetchAll();

$page_title = 'Membres de jury';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Membres de jury</h1>
    <div>
        <a href="export.php?<?= http_build_query(['search' => $search]) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un membre</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou email" value="<?= e($search) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Rechercher</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Email</th><th>Spécialité</th><th>Institution</th><th>Fonction</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun membre trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= e($m['first_name'] . ' ' . $m['last_name']) ?></td>
                        <td><?= e($m['email']) ?></td>
                        <td><?= e($m['specialite'] ?? '—') ?></td>
                        <td><?= e($m['institution'] ?? '—') ?></td>
                        <td><?= e($m['fonction'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
