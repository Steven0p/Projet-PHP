<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

if (is_logged_in()) {
    redirect('/dashboard/index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) post('email', ''));
    $password = (string) post('password', '');

    if ($email === '' || $password === '') {
        $errors[] = 'Veuillez renseigner votre email et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email ou mot de passe incorrect.';
        } elseif ($user['statut'] !== 'actif') {
            $errors[] = 'Ce compte est désactivé. Contactez un administrateur.';
        } else {
            login_user($user);
            log_activity('login', 'session', (int) $user['id'], 'Connexion de ' . $user['first_name'] . ' ' . $user['last_name']);
            redirect('/dashboard/index.php');
        }
    }
}

$page_title = 'Connexion';
require __DIR__ . '/../includes/header.php';
?>
<div class="login-wrapper">
    <div class="card login-card shadow">
        <div class="card-body p-4">
            <h1 class="h4 mb-1 text-center">USFAH Soutenance Manager</h1>
            <p class="text-muted text-center mb-4">Connectez-vous à votre compte</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
