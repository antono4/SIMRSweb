<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect(base_url('login.php'));
    }
}

function require_role(string ...$roles): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak: Anda tidak memiliki hak untuk halaman ini.');
    }
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama'],
            'role'     => $user['role'],
        ];
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
