<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/audit.php';

if (is_logged_in()) {
    log_activity('logout', 'session', (int) $_SESSION['user_id'], 'Déconnexion de ' . $_SESSION['user_name']);
}

logout_user();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
