<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$user_id = (int) get_param('user_id', 0);
$action = trim((string) get_param('action', ''));
$entity_type = trim((string) get_param('entity_type', ''));
$date = trim((string) get_param('date', ''));

$actions = ['create' => 'Création', 'update' => 'Modification', 'delete' => 'Suppression', 'login' => 'Connexion', 'logout' => 'Déconnexion'];
$entity_types = [
    'faculty' => 'Faculté', 'program' => 'Programme', 'student' => 'Étudiant', 'supervisor' => 'Encadreur',
    'jury_member' => 'Membre de jury', 'room' => 'Salle', 'thesis' => 'Mémoire', 'defense' => 'Soutenance',
    'result' => 'Résultat', 'correction' => 'Correction', 'user' => 'Utilisateur', 'session' => 'Session',
];

$conditions = [];
$params = [];
if ($user_id > 0) { $conditions[] = 'user_id = ?'; $params[] = $user_id; }
if ($action !== '') { $conditions[] = 'action = ?'; $params[] = $action; }
if ($entity_type !== '') { $conditions[] = 'entity_type = ?'; $params[] = $entity_type; }
if ($date !== '') { $conditions[] = 'DATE(created_at) = ?'; $params[] = $date; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log $where");
$count_stmt->execute($params);
$pagination = paginate((int) $count_stmt->fetchColumn(), 25);

$stmt = $pdo->prepare("SELECT * FROM activity_log $where ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$users = $pdo->query('SELECT id, first_name, last_name FROM users ORDER BY last_name')->fetchAll();

$action_badges = ['create' => 'success', 'update' => 'primary', 'delete' => 'danger', 'login' => 'secondary', 'logout' => 'secondary'];

$page_title = 'Journal des activités';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Journal des activités</h1>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="user_id" class="form-select">
            <option value="0">Tous les utilisateurs</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $user_id === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="action" class="form-select">
            <option value="">Toutes les actions</option>
            <?php foreach ($actions as $val => $label): ?>
                <option value="<?= $val ?>" <?= $action === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="entity_type" class="form-select">
            <option value="">Tous les modules</option>
            <?php foreach ($entity_types as $val => $label): ?>
                <option value="<?= $val ?>" <?= $entity_type === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><input type="date" name="date" class="form-control" value="<?= e($date) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Filtrer</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date / Heure</th><th>Utilisateur</th><th>Action</th><th>Module</th><th>Description</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune activité enregistrée.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                        <td><?= e($log['user_name']) ?></td>
                        <td><span class="badge text-bg-<?= $action_badges[$log['action']] ?? 'secondary' ?>"><?= e($actions[$log['action']] ?? $log['action']) ?></span></td>
                        <td><?= e($entity_types[$log['entity_type']] ?? $log['entity_type']) ?></td>
                        <td><?= e($log['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= pagination_links($pagination, ['user_id' => $user_id, 'action' => $action, 'entity_type' => $entity_type, 'date' => $date]) ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
