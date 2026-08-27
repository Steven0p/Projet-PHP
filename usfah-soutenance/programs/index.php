<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$faculty_id = (int) get_param('faculty_id', 0);

$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = 'p.nom LIKE ?';
    $params[] = "%$search%";
}
if ($faculty_id > 0) {
    $conditions[] = 'p.faculty_id = ?';
    $params[] = $faculty_id;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM programs p $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT p.*, f.nom AS faculty_nom FROM programs p
    JOIN faculties f ON f.id = p.faculty_id
    $where ORDER BY p.nom ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$programs = $stmt->fetchAll();

$faculties = $pdo->query('SELECT id, nom FROM faculties ORDER BY nom')->fetchAll();

$page_title = 'Programmes';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Programmes</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un programme</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="search" class="form-control" placeholder="Rechercher par nom" value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
        <select name="faculty_id" class="form-select">
            <option value="0">Toutes les facultés</option>
            <?php foreach ($faculties as $f): ?>
                <option value="<?= $f['id'] ?>" <?= $faculty_id === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Faculté</th><th>Type</th><th>Niveau</th><th>Durée</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($programs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun programme trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($programs as $p): ?>
                    <tr>
                        <td><?= e($p['nom']) ?></td>
                        <td><?= e($p['faculty_nom']) ?></td>
                        <td><?= e(ucfirst($p['type'])) ?></td>
                        <td><?= e($p['niveau'] ?? '—') ?></td>
                        <td><?= e($p['duree'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search, 'faculty_id' => $faculty_id]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
