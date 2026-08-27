<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statut = trim((string) get_param('statut', ''));
$date = trim((string) get_param('date', ''));

$conditions = [];
$params = [];
if ($statut !== '') { $conditions[] = 'd.statut = ?'; $params[] = $statut; }
if ($date !== '') { $conditions[] = 'd.date = ?'; $params[] = $date; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM defenses d $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT d.*, t.titre, s.first_name AS student_first, s.last_name AS student_last, r.nom_numero
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    JOIN rooms r ON r.id = d.room_id
    $where ORDER BY d.date DESC, d.heure DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$defenses = $stmt->fetchAll();

$statuts = ['programmee', 'reportee', 'realisee', 'annulee'];

$page_title = 'Soutenances';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Soutenances</h1>
    <div>
        <a href="export.php?<?= http_build_query(['statut' => $statut, 'date' => $date]) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Programmer une soutenance</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuts as $s): ?>
                <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><input type="date" name="date" class="form-control" value="<?= e($date) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Mémoire</th><th>Étudiant</th><th>Date</th><th>Heure</th><th>Salle</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($defenses)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucune soutenance trouvée.</td></tr>
                <?php endif; ?>
                <?php foreach ($defenses as $d): ?>
                    <tr>
                        <td><?= e($d['titre']) ?></td>
                        <td><?= e($d['student_first'] . ' ' . $d['student_last']) ?></td>
                        <td><?= format_date($d['date']) ?></td>
                        <td><?= e(substr($d['heure'], 0, 5)) ?></td>
                        <td><?= e($d['nom_numero']) ?></td>
                        <td><?= status_badge($d['statut']) ?></td>
                        <td class="text-end">
                            <a href="show.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['statut' => $statut, 'date' => $date]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
