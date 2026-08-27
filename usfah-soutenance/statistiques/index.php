<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statut_labels = [
    'en_preparation' => 'En préparation', 'soumis' => 'Soumis', 'valide' => 'Validé',
    'a_corriger' => 'À corriger', 'autorise_a_soutenir' => 'Autorisé à soutenir', 'soutenu' => 'Soutenu',
];
$theses_par_statut = array_fill_keys(array_keys($statut_labels), 0);
$stmt = $pdo->query('SELECT statut, COUNT(*) AS total FROM theses GROUP BY statut');
foreach ($stmt->fetchAll() as $row) {
    $theses_par_statut[$row['statut']] = (int) $row['total'];
}

$defense_statut_labels = ['programmee' => 'Programmée', 'reportee' => 'Reportée', 'realisee' => 'Réalisée', 'annulee' => 'Annulée'];
$defenses_par_statut = array_fill_keys(array_keys($defense_statut_labels), 0);
$stmt = $pdo->query('SELECT statut, COUNT(*) AS total FROM defenses GROUP BY statut');
foreach ($stmt->fetchAll() as $row) {
    $defenses_par_statut[$row['statut']] = (int) $row['total'];
}

$stmt = $pdo->query('
    SELECT f.nom, COUNT(s.id) AS total
    FROM faculties f
    LEFT JOIN students s ON s.faculty_id = f.id
    GROUP BY f.id, f.nom
    ORDER BY total DESC
');
$etudiants_par_faculte = $stmt->fetchAll();

$decision_labels = ['admis' => 'Admis', 'admis_avec_corrections' => 'Admis avec corrections', 'ajourne' => 'Ajourné'];
$resultats_par_decision = array_fill_keys(array_keys($decision_labels), 0);
$stmt = $pdo->query('SELECT decision, COUNT(*) AS total FROM results GROUP BY decision');
foreach ($stmt->fetchAll() as $row) {
    $resultats_par_decision[$row['decision']] = (int) $row['total'];
}

$stmt = $pdo->query("
    SELECT DATE_FORMAT(date, '%Y-%m') AS mois, COUNT(*) AS total
    FROM defenses
    GROUP BY mois
    ORDER BY mois ASC
");
$soutenances_par_mois = $stmt->fetchAll();

$page_title = 'Statistiques';
require __DIR__ . '/../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<h1 class="h3 mb-4">Statistiques</h1>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Mémoires par statut</div>
            <div class="card-body"><canvas id="chartTheses" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Soutenances par statut</div>
            <div class="card-body"><canvas id="chartDefenses" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Étudiants par faculté</div>
            <div class="card-body"><canvas id="chartFacultes" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Résultats par décision</div>
            <div class="card-body"><canvas id="chartResultats" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Soutenances par mois</div>
            <div class="card-body"><canvas id="chartMois" height="120"></canvas></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const palette = ['#1f3a5f', '#3d6fb4', '#6ea8dc', '#f0ad4e', '#5cb85c', '#d9534f', '#9b59b6'];

new Chart(document.getElementById('chartTheses'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values($statut_labels)) ?>,
        datasets: [{ label: 'Mémoires', data: <?= json_encode(array_values($theses_par_statut)) ?>, backgroundColor: palette }],
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
});

new Chart(document.getElementById('chartDefenses'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_values($defense_statut_labels)) ?>,
        datasets: [{ data: <?= json_encode(array_values($defenses_par_statut)) ?>, backgroundColor: palette }],
    },
    options: { responsive: true },
});

new Chart(document.getElementById('chartFacultes'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($etudiants_par_faculte, 'nom')) ?>,
        datasets: [{ label: 'Étudiants', data: <?= json_encode(array_map('intval', array_column($etudiants_par_faculte, 'total'))) ?>, backgroundColor: '#1f3a5f' }],
    },
    options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
});

new Chart(document.getElementById('chartResultats'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_values($decision_labels)) ?>,
        datasets: [{ data: <?= json_encode(array_values($resultats_par_decision)) ?>, backgroundColor: ['#5cb85c', '#f0ad4e', '#d9534f'] }],
    },
    options: { responsive: true },
});

new Chart(document.getElementById('chartMois'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($soutenances_par_mois, 'mois')) ?>,
        datasets: [{ label: 'Soutenances', data: <?= json_encode(array_map('intval', array_column($soutenances_par_mois, 'total'))) ?>, borderColor: '#1f3a5f', backgroundColor: 'rgba(31,58,95,0.15)', fill: true, tension: 0.3 }],
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
