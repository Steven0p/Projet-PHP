<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom_numero LIKE ? OR campus LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("SELECT * FROM rooms $where ORDER BY nom_numero ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$page_title = 'Salles';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Salles</h1>
    <div>
        <a href="export.php?<?= http_build_query(['search' => $search]) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter une salle</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou campus" value="<?= e($search) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Rechercher</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom / Numéro</th><th>Campus</th><th>Capacité</th><th>Disponibilité</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune salle trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><?= e($r['nom_numero']) ?></td>
                        <td><?= e($r['campus'] ?? '—') ?></td>
                        <td><?= e((string) ($r['capacite'] ?? '—')) ?></td>
                        <td><?= status_badge($r['disponibilite']) ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
