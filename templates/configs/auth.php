<?php

declare(strict_types=1);

const AUTH_COOKIE_NAME = 'librashub_remember';
const AUTH_TOKEN_DAYS = 30;

function authNormalizeEmail(string $email): string
{
    $email = trim($email);
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($email, 'UTF-8');
    }
    return strtolower($email);
}

/**
 * Valida senhas atuais (password_hash) e migra automaticamente formatos
 * legados encontrados em bases antigas do LibrasHub.
 */
function authVerifyUserPassword(PDO $pdo, array $user, string $plainPassword): bool
{
    $userId = (int) ($user['id_usuario'] ?? 0);
    $stored = trim((string) ($user['senha_usuario'] ?? ''));

    if ($userId < 1 || $stored === '' || $plainPassword === '') {
        return false;
    }

    $verified = false;
    $legacy = false;

    // Formato atual: bcrypt/Argon gerado por password_hash().
    $info = password_get_info($stored);
    if (($info['algo'] ?? null) !== null) {
        $verified = password_verify($plainPassword, $stored);
    } else {
        // Compatibilidade somente para contas antigas. Após um login válido,
        // o valor é imediatamente convertido para PASSWORD_DEFAULT.
        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            $verified = hash_equals(strtolower($stored), md5($plainPassword));
            $legacy = $verified;
        } elseif (preg_match('/^[a-f0-9]{40}$/i', $stored)) {
            $verified = hash_equals(strtolower($stored), sha1($plainPassword));
            $legacy = $verified;
        } elseif (preg_match('/^[a-f0-9]{64}$/i', $stored)) {
            $verified = hash_equals(strtolower($stored), hash('sha256', $plainPassword));
            $legacy = $verified;
        } elseif (!str_starts_with($stored, '$')) {
            // Bancos muito antigos podem ter armazenado a senha sem hash.
            $verified = hash_equals($stored, $plainPassword);
            $legacy = $verified;
        }
    }

    if (!$verified) {
        return false;
    }

    $mustRehash = $legacy;
    if (!$legacy && ($info['algo'] ?? null) !== null) {
        $mustRehash = password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    if ($mustRehash) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            try {
                $statement = $pdo->prepare('UPDATE usuario SET senha_usuario = ? WHERE id_usuario = ?');
                $statement->execute([$newHash, $userId]);
            } catch (Throwable $error) {
                // O login continua válido; a migração será tentada novamente depois.
                error_log('Não foi possível atualizar o hash da senha do usuário ' . $userId . ': ' . $error->getMessage());
            }
        }
    }

    return true;
}

function authStartSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function authEnsureTokenTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuario_tokens (
        id_token BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_usuario INT NOT NULL,
        seletor CHAR(24) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expira_em DATETIME NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultimo_uso_em TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id_token),
        UNIQUE KEY uq_usuario_tokens_seletor (seletor),
        KEY idx_usuario_tokens_usuario (id_usuario),
        KEY idx_usuario_tokens_expira (expira_em),
        CONSTRAINT fk_usuario_tokens_usuario FOREIGN KEY (id_usuario)
            REFERENCES usuario (id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function authCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function authForgetCookie(): void
{
    setcookie(AUTH_COOKIE_NAME, '', authCookieOptions(time() - 3600));
    unset($_COOKIE[AUTH_COOKIE_NAME]);
}

function authCreateRememberToken(PDO $pdo, int $userId): void
{
    authEnsureTokenTable($pdo);
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + (AUTH_TOKEN_DAYS * 86400);

    // Remove somente tokens vencidos. Cada navegador mantém seu próprio token,
    // então um novo login não encerra as sessões dos outros dispositivos.
    $pdo->prepare('DELETE FROM usuario_tokens WHERE expira_em < NOW()')->execute();
    $statement = $pdo->prepare('INSERT INTO usuario_tokens (id_usuario, seletor, token_hash, expira_em) VALUES (?, ?, ?, FROM_UNIXTIME(?))');
    $statement->execute([$userId, $selector, hash('sha256', $validator), $expires]);

    $cookie = $selector . ':' . $validator;
    setcookie(AUTH_COOKIE_NAME, $cookie, authCookieOptions($expires));
    $_COOKIE[AUTH_COOKIE_NAME] = $cookie;
}

function authRevokeRememberToken(PDO $pdo): void
{
    $cookie = (string) ($_COOKIE[AUTH_COOKIE_NAME] ?? '');
    [$selector] = array_pad(explode(':', $cookie, 2), 2, '');
    if (preg_match('/^[a-f0-9]{24}$/', $selector)) {
        authEnsureTokenTable($pdo);
        $pdo->prepare('DELETE FROM usuario_tokens WHERE seletor = ?')->execute([$selector]);
    }
    authForgetCookie();
}

function authRestoreSession(PDO $pdo): void
{
    authStartSession();
    if (!empty($_SESSION['usuario_id'])) {
        return;
    }

    $cookie = (string) ($_COOKIE[AUTH_COOKIE_NAME] ?? '');
    [$selector, $validator] = array_pad(explode(':', $cookie, 2), 2, '');
    if (!preg_match('/^[a-f0-9]{24}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
        if ($cookie !== '') authForgetCookie();
        return;
    }

    authEnsureTokenTable($pdo);
    $statement = $pdo->prepare("SELECT t.id_token, t.token_hash, u.id_usuario, u.nm_usuario, u.email_usuario, u.tp_usuario
        FROM usuario_tokens t
        INNER JOIN usuario u ON u.id_usuario = t.id_usuario
        WHERE t.seletor = ? AND t.expira_em > NOW() LIMIT 1");
    $statement->execute([$selector]);
    $record = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$record || !hash_equals((string) $record['token_hash'], hash('sha256', $validator))) {
        if ($record) $pdo->prepare('DELETE FROM usuario_tokens WHERE id_token = ?')->execute([(int) $record['id_token']]);
        authForgetCookie();
        return;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $record['id_usuario'];
    $_SESSION['usuario_nome'] = $record['nm_usuario'];
    $_SESSION['usuario_email'] = $record['email_usuario'];
    $_SESSION['usuario_tipo'] = $record['tp_usuario'];

    $pdo->prepare('DELETE FROM usuario_tokens WHERE id_token = ?')->execute([(int) $record['id_token']]);
    authCreateRememberToken($pdo, (int) $record['id_usuario']);
}
