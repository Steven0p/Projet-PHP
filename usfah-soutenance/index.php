<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

redirect(is_logged_in() ? '/dashboard/index.php' : '/auth/login.php');
