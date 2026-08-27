<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom LIKE ? OR code LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM faculties $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$pagination = paginate($total, 10);

$stmt = $pdo->prepare("SELECT * FROM faculties $where ORDER BY nom ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$faculties = $stmt->fetchAll();

$page_title = 'Facultés';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Facultés</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter une faculté</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou code" value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Rechercher</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom</th><th>Code</th><th>Responsable</th><th>Statut</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faculties)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune faculté trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($faculties as $f): ?>
                    <tr>
                        <td><?= e($f['nom']) ?></td>
                        <td><?= e($f['code']) ?></td>
                        <td><?= e($f['responsable'] ?? '—') ?></td>
                        <td><?= status_badge($f['statut']) ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= (int) $f['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
