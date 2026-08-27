<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user_role(), $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-danger">Accès refusé : vous n\'avez pas les droits nécessaires pour cette action.</div>';
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role']       = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
