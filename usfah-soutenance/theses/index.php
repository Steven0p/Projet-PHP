<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$search = trim((string) get_param('search', ''));
$statut = trim((string) get_param('statut', ''));
$annee = trim((string) get_param('annee', ''));

$conditions = [];
$params = [];
if ($search !== '') { $conditions[] = 't.titre LIKE ?'; $params[] = "%$search%"; }
if ($statut !== '') { $conditions[] = 't.statut = ?'; $params[] = $statut; }
if ($annee !== '') { $conditions[] = 't.annee_academique = ?'; $params[] = $annee; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM theses t $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT t.*, s.first_name AS student_first, s.last_name AS student_last, sp.first_name AS sup_first, sp.last_name AS sup_last
    FROM theses t
    JOIN students s ON s.id = t.student_id
    LEFT JOIN supervisors sp ON sp.id = t.supervisor_id
    $where ORDER BY t.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$theses = $stmt->fetchAll();

$statuts = ['en_preparation', 'soumis', 'valide', 'a_corriger', 'autorise_a_soutenir', 'soutenu'];

$page_title = 'Mémoires';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Mémoires</h1>
    <div>
        <a href="export.php?<?= http_build_query(['search' => $search, 'statut' => $statut, 'annee' => $annee]) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un mémoire</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="search" class="form-control" placeholder="Rechercher par titre" value="<?= e($search) ?>"></div>
    <div class="col-auto">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuts as $s): ?>
                <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><input type="text" name="annee" class="form-control" placeholder="Année académique" value="<?= e($annee) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Titre</th><th>Étudiant</th><th>Encadreur</th><th>Année</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($theses)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun mémoire trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($theses as $t): ?>
                    <tr>
                        <td><?= e($t['titre']) ?></td>
                        <td><?= e($t['student_first'] . ' ' . $t['student_last']) ?></td>
                        <td><?= e($t['sup_first'] ? $t['sup_first'] . ' ' . $t['sup_last'] : '—') ?></td>
                        <td><?= e($t['annee_academique']) ?></td>
                        <td><?= status_badge($t['statut']) ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['search' => $search, 'statut' => $statut, 'annee' => $annee]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
