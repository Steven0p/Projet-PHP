<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$matricule = trim((string) get_param('matricule', ''));
$nom = trim((string) get_param('nom', ''));
$faculty_id = (int) get_param('faculty_id', 0);
$program_id = (int) get_param('program_id', 0);

$conditions = [];
$params = [];
if ($matricule !== '') { $conditions[] = 's.matricule LIKE ?'; $params[] = "%$matricule%"; }
if ($nom !== '') { $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ?)"; $params[] = "%$nom%"; $params[] = "%$nom%"; }
if ($faculty_id > 0) { $conditions[] = 's.faculty_id = ?'; $params[] = $faculty_id; }
if ($program_id > 0) { $conditions[] = 's.program_id = ?'; $params[] = $program_id; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students s $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 10);

$stmt = $pdo->prepare("
    SELECT s.*, f.nom AS faculty_nom, p.nom AS program_nom
    FROM students s
    LEFT JOIN faculties f ON f.id = s.faculty_id
    LEFT JOIN programs p ON p.id = s.program_id
    $where ORDER BY s.last_name ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$faculties = $pdo->query('SELECT id, nom FROM faculties ORDER BY nom')->fetchAll();
$programs = $pdo->query('SELECT id, nom, faculty_id FROM programs ORDER BY nom')->fetchAll();

$page_title = 'Étudiants';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Étudiants</h1>
    <div>
        <a href="export.php?<?= http_build_query(['matricule' => $matricule, 'nom' => $nom, 'faculty_id' => $faculty_id, 'program_id' => $program_id]) ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter un étudiant</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="matricule" class="form-control" placeholder="Matricule" value="<?= e($matricule) ?>"></div>
    <div class="col-auto"><input type="text" name="nom" class="form-control" placeholder="Nom ou prénom" value="<?= e($nom) ?>"></div>
    <div class="col-auto">
        <select name="faculty_id" class="form-select">
            <option value="0">Toutes les facultés</option>
            <?php foreach ($faculties as $f): ?>
                <option value="<?= $f['id'] ?>" <?= $faculty_id === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="program_id" class="form-select">
            <option value="0">Tous les programmes</option>
            <?php foreach ($programs as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $program_id === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Matricule</th><th>Nom</th><th>Faculté</th><th>Programme</th><th>Année</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucun étudiant trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['matricule']) ?></td>
                        <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                        <td><?= e($s['faculty_nom'] ?? '—') ?></td>
                        <td><?= e($s['program_nom'] ?? '—') ?></td>
                        <td><?= e($s['annee_academique']) ?></td>
                        <td><?= status_badge($s['statut']) ?></td>
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
<div class="mt-3"><?= pagination_links($pagination, ['matricule' => $matricule, 'nom' => $nom, 'faculty_id' => $faculty_id, 'program_id' => $program_id]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
