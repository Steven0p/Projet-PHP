<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$errors = [];
$data = [
    'thesis_id' => (int) get_param('thesis_id', 0), 'date' => '', 'heure' => '', 'room_id' => '',
    'president_id' => '', 'examinateur_id' => '', 'rapporteur_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['thesis_id'] = (int) post('thesis_id', 0);
    $data['date'] = trim((string) post('date', ''));
    $data['heure'] = trim((string) post('heure', ''));
    $data['room_id'] = (int) post('room_id', 0);
    $data['president_id'] = (int) post('president_id', 0);
    $data['examinateur_id'] = (int) post('examinateur_id', 0);
    $data['rapporteur_id'] = (int) post('rapporteur_id', 0);

    if ($data['thesis_id'] <= 0) $errors[] = 'Le mémoire est obligatoire.';
    if ($data['date'] === '') $errors[] = 'La date est obligatoire.';
    if ($data['heure'] === '') $errors[] = 'L\'heure est obligatoire.';
    if ($data['room_id'] <= 0) $errors[] = 'La salle est obligatoire.';
    if ($data['president_id'] <= 0 || $data['examinateur_id'] <= 0 || $data['rapporteur_id'] <= 0) {
        $errors[] = 'Les trois membres du jury (président, examinateur, rapporteur) sont obligatoires.';
    } elseif (count(array_unique([$data['president_id'], $data['examinateur_id'], $data['rapporteur_id']])) < 3) {
        $errors[] = 'Un même membre de jury ne peut pas cumuler deux rôles pour cette soutenance.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM theses WHERE id = ? AND statut = 'autorise_a_soutenir'");
        $stmt->execute([$data['thesis_id']]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Ce mémoire n\'est pas autorisé à soutenir.';
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM defenses WHERE thesis_id = ?');
        $stmt->execute([$data['thesis_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Une soutenance est déjà programmée pour ce mémoire.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM defenses WHERE room_id = ? AND date = ? AND heure = ?');
        $stmt->execute([$data['room_id'], $data['date'], $data['heure']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Cette salle est déjà réservée pour une autre soutenance à cette date et cette heure.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO defenses (thesis_id, date, heure, room_id, statut) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$data['thesis_id'], $data['date'], $data['heure'], $data['room_id'], 'programmee']);
            $defense_id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO defense_jury (defense_id, jury_member_id, role) VALUES (?, ?, ?)');
            $stmt->execute([$defense_id, $data['president_id'], 'president']);
            $stmt->execute([$defense_id, $data['examinateur_id'], 'examinateur']);
            $stmt->execute([$defense_id, $data['rapporteur_id'], 'rapporteur']);

            $pdo->commit();
            flash('success', 'Soutenance programmée avec succès.');
            redirect('/defenses/index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Impossible de programmer cette soutenance : conflit de salle ou de créneau.';
        }
    }
}

$theses = $pdo->query("
    SELECT t.id, t.titre, s.first_name, s.last_name FROM theses t
    JOIN students s ON s.id = t.student_id
    WHERE t.statut = 'autorise_a_soutenir' AND t.id NOT IN (SELECT thesis_id FROM defenses)
    ORDER BY t.titre
")->fetchAll();
$rooms = $pdo->query("SELECT id, nom_numero FROM rooms WHERE disponibilite = 'disponible' ORDER BY nom_numero")->fetchAll();
$jury_members = $pdo->query('SELECT id, first_name, last_name FROM jury_members ORDER BY last_name')->fetchAll();

$page_title = 'Programmer une soutenance';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Programmer une soutenance</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>

<?php if (empty($theses)): ?>
    <div class="alert alert-warning">Aucun mémoire n'est actuellement autorisé à soutenir et sans soutenance déjà programmée.</div>
<?php endif; ?>

<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Mémoire *</label>
        <select name="thesis_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($theses as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $data['thesis_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['titre'] . ' — ' . $t['first_name'] . ' ' . $t['last_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Date *</label>
            <input type="date" name="date" class="form-control" value="<?= old($data, 'date') ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Heure *</label>
            <input type="time" name="heure" class="form-control" value="<?= old($data, 'heure') ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Salle *</label>
            <select name="room_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $data['room_id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nom_numero']) ?></option>
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
                    <option value="<?= $j['id'] ?>" <?= $data['president_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Examinateur *</label>
            <select name="examinateur_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($jury_members as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $data['examinateur_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Rapporteur *</label>
            <select name="rapporteur_id" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($jury_members as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $data['rapporteur_id'] === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['first_name'] . ' ' . $j['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Programmer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
