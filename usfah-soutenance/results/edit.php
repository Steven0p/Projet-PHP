<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) get_param('id', 0);
$stmt = $pdo->prepare('SELECT * FROM results WHERE id = ?');
$stmt->execute([$id]);
$result = $stmt->fetch();
if (!$result) {
    flash('danger', 'Résultat introuvable.');
    redirect('/results/index.php');
}

$errors = [];
$data = $result;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['note_finale'] = trim((string) post('note_finale', ''));
    $data['mention'] = post('mention', '');
    $data['decision'] = post('decision', 'admis');
    $data['commentaires_jury'] = trim((string) post('commentaires_jury', ''));
    $data['date_validation'] = trim((string) post('date_validation', ''));

    if (!in_array($data['decision'], ['admis', 'admis_avec_corrections', 'ajourne'], true)) $errors[] = 'Décision invalide.';
    if ($data['mention'] !== '' && !in_array($data['mention'], ['passable', 'assez_bien', 'bien', 'tres_bien', 'excellent'], true)) $errors[] = 'Mention invalide.';
    if ($data['note_finale'] !== '' && !is_numeric($data['note_finale'])) $errors[] = 'La note finale doit être un nombre.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE results SET note_finale=?, mention=?, decision=?, commentaires_jury=?, date_validation=? WHERE id=?');
        $stmt->execute([
            $data['note_finale'] !== '' ? $data['note_finale'] : null, $data['mention'] ?: null,
            $data['decision'], $data['commentaires_jury'] ?: null, $data['date_validation'] ?: null, $id,
        ]);

        if ($data['decision'] === 'admis') {
            $defense_stmt = $pdo->prepare('SELECT thesis_id FROM defenses WHERE id = ?');
            $defense_stmt->execute([$result['defense_id']]);
            $thesis_id = $defense_stmt->fetchColumn();
            if ($thesis_id) {
                $pdo->prepare("UPDATE theses SET statut = 'soutenu' WHERE id = ?")->execute([$thesis_id]);
            }
        }

        flash('success', 'Résultat mis à jour.');
        redirect('/results/index.php');
    }
}

$page_title = 'Modifier un résultat';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Modifier le résultat</h1>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body">
<form method="post" action="">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Note finale</label>
            <input type="number" step="0.01" min="0" max="100" name="note_finale" class="form-control" value="<?= e((string) ($data['note_finale'] ?? '')) ?>">
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
        <input type="date" name="date_validation" class="form-control" value="<?= e($data['date_validation'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
</form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
