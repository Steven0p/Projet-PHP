<?php
$current = $_SERVER['SCRIPT_NAME'];
function nav_active(string $segment, string $current): string
{
    return str_contains($current, '/' . $segment . '/') ? ' active' : '';
}
$links = [
    'dashboard'     => ['Tableau de bord', 'bi-speedometer2'],
    'statistiques'  => ['Statistiques', 'bi-bar-chart-line'],
    'students'      => ['Étudiants', 'bi-people'],
    'theses'        => ['Mémoires', 'bi-journal-text'],
    'defenses'      => ['Soutenances', 'bi-calendar-event'],
    'results'       => ['Résultats', 'bi-award'],
    'corrections'   => ['Corrections', 'bi-pencil-square'],
    'supervisors'   => ['Encadreurs', 'bi-person-badge'],
    'jury-members'  => ['Membres de jury', 'bi-people-fill'],
    'rooms'         => ['Salles', 'bi-door-open'],
    'programs'      => ['Programmes', 'bi-diagram-3'],
    'faculties'     => ['Facultés', 'bi-bank'],
];
?>
<aside class="app-sidebar">
    <ul class="nav flex-column">
        <?php foreach ($links as $segment => [$label, $icon]): ?>
            <li class="nav-item">
                <a class="nav-link<?= nav_active($segment, $current) ?>" href="<?= BASE_URL ?>/<?= $segment ?>/index.php">
                    <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if (current_user_role() === 'admin'): ?>
            <li class="nav-item mt-2 border-top pt-2">
                <a class="nav-link<?= nav_active('users', $current) ?>" href="<?= BASE_URL ?>/users/index.php">
                    <i class="bi bi-shield-lock"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= nav_active('activity-log', $current) ?>" href="<?= BASE_URL ?>/activity-log/index.php">
                    <i class="bi bi-clock-history"></i> Journal des activités
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>
