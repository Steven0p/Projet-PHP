<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$errors = [];
$data = [
    'defense_id' => (int) get_param('defense_id', 0), 'note_finale' => '', 'mention' => '',
    'decision' => 'admis', 'commentaires_jury' => '', 'date_validation' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['defense_id'] = (int) post('defense_id', 0);
    $data['note_finale'] = trim((string) post('note_finale', ''));
    $data['mention'] = post('mention', '');
    $data['decision'] = post('decision', 'admis');
    $data['commentaires_jury'] = trim((string) post('commentaires_jury', ''));
    $data['date_validation'] = trim((string) post('date_validation', ''));

    if ($data['defense_id'] <= 0) $errors[] = 'La soutenance est obligatoire.';
    if (!in_array($data['decision'], ['admis', 'admis_avec_corrections', 'ajourne'], true)) $errors[] = 'Décision invalide.';
    if ($data['note_finale'] !== '' && !is_numeric($data['note_finale'])) $errors[] = 'La note finale doit être un nombre.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM defenses WHERE id = ? AND statut = 'realisee'");
        $stmt->execute([$data['defense_id']]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Un résultat ne peut être enregistré que pour une soutenance marquée « réalisée ».';
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM results WHERE defense_id = ?');
        $stmt->execute([$data['defense_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Un résultat existe déjà pour cette soutenance.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO results (defense_id, note_finale, mention, decision, commentaires_jury, date_validation) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['defense_id'], $data['note_finale'] !== '' ? $data['note_finale'] : null, $data['mention'] ?: null,
            $data['decision'], $data['commentaires_jury'] ?: null, $data['date_validation'] ?: null,
        ]);

        if ($data['decision'] === 'admis') {
            $defense_stmt = $pdo->prepare('SELECT thesis_id FROM defenses WHERE id = ?');
            $defense_stmt->execute([$data['defense_id']]);
            $thesis_id = $defense_stmt->fetchColumn();
            if ($thesis_id) {
                $pdo->prepare("UPDATE theses SET statut = 'soutenu' WHERE id = ?")->execute([$thesis_id]);
            }
        }

        flash('success', 'Résultat enregistré avec succès.');
        redirect('/results/index.php');
    }
}

$defenses = $pdo->query("
    SELECT d.id, t.titre, s.first_name, s.last_name, d.date
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    WHERE d.statut = 'realisee' AND d.id NOT IN (SELECT defense_id FROM results)
    ORDER BY d.date DESC
")->fetchAll();

$page_title = 'Enregistrer un résultat';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Enregistrer un résultat</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>

<?php if (empty($defenses)): ?>
    <div class="alert alert-warning">Aucune soutenance réalisée n'est en attente de résultat.</div>
<?php endif; ?>

<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Soutenance *</label>
        <select name="defense_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($defenses as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $data['defense_id'] === (int) $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['titre'] . ' — ' . $d['first_name'] . ' ' . $d['last_name'] . ' (' . format_date($d['date']) . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Note finale</label>
            <input type="number" step="0.01" min="0" max="100" name="note_finale" class="form-control" value="<?= old($data, 'note_finale') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Mention</label>
            <select name="mention" class="form-select">
                <option value="">-- Aucune --</option>
                <?php foreach (['passable' => 'Passable', 'assez_bien' => 'Assez bien', 'bien' => 'Bien', 'tres_bien' => 'Très bien', 'excellent' => 'Excellent'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $data['mention'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Décision *</label>
            <select name="decision" class="form-select" required>
                <?php foreach (['admis' => 'Admis', 'admis_avec_corrections' => 'Admis avec corrections', 'ajourne' => 'Ajourné'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $data['decision'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Commentaires du jury</label>
        <textarea name="commentaires_jury" class="form-control" rows="3"><?= old($data, 'commentaires_jury') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Date de validation</label>
        <input type="date" name="date_validation" class="form-control" value="<?= old($data, 'date_validation') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
