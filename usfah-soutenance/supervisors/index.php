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

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM supervisors $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("SELECT * FROM supervisors $where ORDER BY last_name ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$supervisors = $stmt->fetchAll();

$page_title = 'Encadreurs';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Encadreurs</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un encadreur</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Rechercher par nom ou email" value="<?= e($search) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Rechercher</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Email</th><th>Spécialité</th><th>Institution</th><th>Grade</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($supervisors)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun encadreur trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($supervisors as $s): ?>
                    <tr>
                        <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e($s['specialite'] ?? '—') ?></td>
                        <td><?= e($s['institution'] ?? '—') ?></td>
                        <td><?= e($s['grade'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
