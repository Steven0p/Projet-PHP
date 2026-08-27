<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM defenses WHERE id = ?');
$stmt->execute([$id]);
$defense = $stmt->fetch();
if (!$defense) {
    flash('danger', 'Soutenance introuvable.');
    redirect('/defenses/index.php');
}

$jury_stmt = $pdo->prepare('SELECT jury_member_id, role FROM defense_jury WHERE defense_id = ?');
$jury_stmt->execute([$id]);
$current_jury = [];
foreach ($jury_stmt->fetchAll() as $row) {
    $current_jury[$row['role']] = $row['jury_member_id'];
}

$errors = [];
$data = $defense;
$data['president_id'] = $current_jury['president'] ?? '';
$data['examinateur_id'] = $current_jury['examinateur'] ?? '';
$data['rapporteur_id'] = $current_jury['rapporteur'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['date'] = trim((string) post('date', ''));
    $data['heure'] = trim((string) post('heure', ''));
    $data['room_id'] = (int) post('room_id', 0);
    $data['statut'] = post('statut', 'programmee');
    $data['president_id'] = (int) post('president_id', 0);
    $data['examinateur_id'] = (int) post('examinateur_id', 0);
    $data['rapporteur_id'] = (int) post('rapporteur_id', 0);

    if ($data['date'] === '') $errors[] = 'La date est obligatoire.';
    if ($data['heure'] === '') $errors[] = 'L\'heure est obligatoire.';
    if ($data['room_id'] <= 0) $errors[] = 'La salle est obligatoire.';
    if (!in_array($data['statut'], ['programmee', 'reportee', 'realisee', 'annulee'], true)) $errors[] = 'Statut invalide.';
    if ($data['president_id'] <= 0 || $data['examinateur_id'] <= 0 || $data['rapporteur_id'] <= 0) {
        $errors[] = 'Les trois membres du jury sont obligatoires.';
    } elseif (count(array_unique([$data['president_id'], $data['examinateur_id'], $data['rapporteur_id']])) < 3) {
        $errors[] = 'Un même membre de jury ne peut pas cumuler deux rôles pour cette soutenance.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM defenses WHERE room_id = ? AND date = ? AND heure = ? AND id != ?');
        $stmt->execute([$data['room_id'], $data['date'], $data['heure'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Cette salle est déjà réservée pour une autre soutenance à cette date et cette heure.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE defenses SET date=?, heure=?, room_id=?, statut=? WHERE id=?');
            $stmt->execute([$data['date'], $data['heure'], $data['room_id'], $data['statut'], $id]);

            $stmt = $pdo->prepare('DELETE FROM defense_jury WHERE defense_id = ?');
            $stmt->execute([$id]);
            $stmt = $pdo->prepare('INSERT INTO defense_jury (defense_id, jury_member_id, role) VALUES (?, ?, ?)');
            $stmt->execute([$id, $data['president_id'], 'president']);
            $stmt->execute([$id, $data['examinateur_id'], 'examinateur']);
            $stmt->execute([$id, $data['rapporteur_id'], 'rapporteur']);

            $pdo->commit();
            flash('success', 'Soutenance mise à jour.');
            redirect('/defenses/index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Impossible de mettre à jour cette soutenance : conflit de salle ou de créneau.';
        }
    }
}

$thesis_stmt = $pdo->prepare('SELECT t.titre, s.first_name, s.last_name FROM theses t JOIN students s ON s.id = t.student_id WHERE t.id = ?');
$thesis_stmt->execute([$defense['thesis_id']]);
$thesis = $thesis_stmt->fetch();

$rooms = $pdo->query('SELECT id, nom_numero FROM rooms ORDER BY nom_numero')->fetchAll();
$jury_members = $pdo->query('SELECT id, first_name, last_name FROM jury_members ORDER BY last_name')->fetchAll();
$statuts = ['programmee', 'reportee', 'realisee', 'annulee'];

$page_title = 'Modifier une soutenance';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier la soutenance</h1>
<p class="text-muted">Mémoire : <strong><?= e($thesis['titre'] ?? '') ?></strong> — <?= e(($thesis['first_name'] ?? '') . ' ' . ($thesis['last_name'] ?? '')) ?></p>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>

<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Date *</label>
            <input type="date" name="date" class="form-control" value="<?= e($data['date']) ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Heure *</label>
            <input type="time" name="heure" class="form-control" value="<?= e(substr($data['heure'], 0, 5)) ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Salle *</label>
            <select name="room_id" class="form-select" required>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= (int) $data['room_id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nom_numero']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= $data['statut'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <hr>
    <h2 class="h6">Composition du jury</h2>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Président *</label>
            <select name="president_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($jury_members as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= (int) $data['president_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Examinateur *</label>
            <select name="examinateur_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($jury_members as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= (int) $data['examinateur_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Rapporteur *</label>
            <select name="rapporteur_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($jury_members as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= (int) $data['rapporteur_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
