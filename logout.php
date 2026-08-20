<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

logout();
redirect(base_url('login.php'));
