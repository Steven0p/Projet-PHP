<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$stats = [];
$stats['total_students'] = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$stats['total_theses'] = (int) $pdo->query('SELECT COUNT(*) FROM theses')->fetchColumn();
$stats['theses_prep'] = (int) $pdo->query("SELECT COUNT(*) FROM theses WHERE statut = 'en_preparation'")->fetchColumn();
$stats['theses_autorise'] = (int) $pdo->query("SELECT COUNT(*) FROM theses WHERE statut = 'autorise_a_soutenir'")->fetchColumn();
$stats['defenses_programmees'] = (int) $pdo->query("SELECT COUNT(*) FROM defenses WHERE statut = 'programmee'")->fetchColumn();
$stats['defenses_today'] = (int) $pdo->query("SELECT COUNT(*) FROM defenses WHERE date = CURDATE()")->fetchColumn();
$stats['defenses_month'] = (int) $pdo->query("SELECT COUNT(*) FROM defenses WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")->fetchColumn();
$stats['defenses_realisees'] = (int) $pdo->query("SELECT COUNT(*) FROM defenses WHERE statut = 'realisee'")->fetchColumn();
$stats['students_admis'] = (int) $pdo->query("SELECT COUNT(*) FROM results WHERE decision IN ('admis','admis_avec_corrections')")->fetchColumn();

$upcoming = $pdo->query("
    SELECT d.date, d.heure, t.titre, s.first_name, s.last_name, r.nom_numero
    FROM defenses d
    JOIN theses t ON t.id = d.thesis_id
    JOIN students s ON s.id = t.student_id
    JOIN rooms r ON r.id = d.room_id
    WHERE d.statut = 'programmee' AND d.date >= CURDATE()
    ORDER BY d.date ASC, d.heure ASC
    LIMIT 5
")->fetchAll();

$page_title = 'Tableau de bord';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tableau de bord</h1>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Étudiants', $stats['total_students'], 'bi-people'],
        ['Mémoires enregistrés', $stats['total_theses'], 'bi-journal-text'],
        ['Mémoires en préparation', $stats['theses_prep'], 'bi-hourglass-split'],
        ['Autorisés à soutenir', $stats['theses_autorise'], 'bi-check2-circle'],
        ['Soutenances programmées', $stats['defenses_programmees'], 'bi-calendar-event'],
        ['Soutenances aujourd\'hui', $stats['defenses_today'], 'bi-calendar-day'],
        ['Soutenances ce mois-ci', $stats['defenses_month'], 'bi-calendar-month'],
        ['Soutenances réalisées', $stats['defenses_realisees'], 'bi-check2-square'],
        ['Étudiants admis', $stats['students_admis'], 'bi-award'],
    ];
    foreach ($cards as [$label, $value, $icon]):
    ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi <?= $icon ?> fs-2 text-primary"></i>
                <div>
                    <div class="stat-value"><?= $value ?></div>
                    <div class="text-muted small"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header fw-semibold">Prochaines soutenances</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Étudiant</th>
                    <th>Mémoire</th>
                    <th>Salle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcoming)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune soutenance programmée à venir.</td></tr>
                <?php endif; ?>
                <?php foreach ($upcoming as $row): ?>
                    <tr>
                        <td><?= format_date($row['date']) ?></td>
                        <td><?= e(substr($row['heure'], 0, 5)) ?></td>
                        <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= e($row['titre']) ?></td>
                        <td><?= e($row['nom_numero']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
