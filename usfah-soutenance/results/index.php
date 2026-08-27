<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$decision = trim((string) get_param('decision', ''));
$where = '';
$params = [];
if ($decision !== '') {
    $where = 'WHERE r.decision = ?';
    $params[] = $decision;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM results r $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT r.*, t.titre, s.first_name, s.last_name, d.date
    FROM results r
    JOIN defenses d ON d.id = r.defense_id
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    $where ORDER BY r.date_validation DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$results = $stmt->fetchAll();

$page_title = 'Résultats';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Résultats des soutenances</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Enregistrer un résultat</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="decision" class="form-select">
            <option value="">Toutes les décisions</option>
            <?php foreach (['admis' => 'Admis', 'admis_avec_corrections' => 'Admis avec corrections', 'ajourne' => 'Ajourné'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $decision === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Mémoire</th><th>Étudiant</th><th>Date soutenance</th><th>Note</th><th>Mention</th><th>Décision</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucun résultat trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= e($r['titre']) ?></td>
                        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= format_date($r['date']) ?></td>
                        <td><?= e((string) ($r['note_finale'] ?? '—')) ?></td>
                        <td><?= e($r['mention'] ? ucwords(str_replace('_', ' ', $r['mention'])) : '—') ?></td>
                        <td><?= status_badge($r['decision']) ?></td>
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
<div class="mt-3"><?= pagination_links($pagination, ['decision' => $decision]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
