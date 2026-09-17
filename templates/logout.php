<?php

declare(strict_types=1);

require_once __DIR__ . '/configs/config.php';
authRevokeRememberToken($pdo);

// Remove todos os dados da sessão atual.
$_SESSION = [];

// Invalida também o cookie da sessão no navegador.
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();

// A landing page pública fica na raiz do projeto.
header('Location: ../index.php', true, 303);
exit;
