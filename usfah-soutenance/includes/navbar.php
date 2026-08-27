<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard/index.php">USFAH Soutenance Manager</a>
        <div class="d-flex align-items-center text-light">
            <span class="me-3">
                <?= e($_SESSION['user_name'] ?? '') ?>
                <span class="badge text-bg-light text-dark ms-1"><?= e(current_user_role() ?? '') ?></span>
            </span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>
