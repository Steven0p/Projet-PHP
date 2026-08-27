<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statut = trim((string) get_param('statut', ''));
$where = '';
$params = [];
if ($statut !== '') {
    $where = 'WHERE c.statut = ?';
    $params[] = $statut;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM corrections c $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT c.*, t.titre, s.first_name, s.last_name
    FROM corrections c
    JOIN theses t ON t.id = c.thesis_id
    JOIN students s ON s.id = t.student_id
    $where ORDER BY c.date_limite ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$corrections = $stmt->fetchAll();

$statuts = ['a_faire', 'en_cours', 'soumise', 'validee'];

$page_title = 'Corrections';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Corrections</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter une correction</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuts as $s): ?>
                <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Mémoire</th><th>Étudiant</th><th>Description</th><th>Date limite</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($corrections)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucune correction trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($corrections as $c): ?>
                    <tr>
                        <td><?= e($c['titre']) ?></td>
                        <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                        <td><?= e(mb_strimwidth($c['description'], 0, 60, '…')) ?></td>
                        <td><?= format_date($c['date_limite']) ?></td>
                        <td><?= status_badge($c['statut']) ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['statut' => $statut]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
