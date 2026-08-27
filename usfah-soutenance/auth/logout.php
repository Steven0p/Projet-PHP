<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

logout_user();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
