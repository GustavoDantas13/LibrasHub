<?php
/**
 * Configuração de conexão com o banco de dados MySQL.
 * Ajuste os valores abaixo conforme seu ambiente (XAMPP, servidor, etc).
 */

$DB_HOST = getenv("DB_HOST") ?: "localhost";
$DB_PORT = getenv("DB_PORT") ?: "3306";
$DB_NAME = getenv("DB_NAME") ?: "librashub";
$DB_USER = getenv("DB_USER") ?: "root";
$DB_PASS = getenv("DB_PASS") ?: "";

require_once __DIR__ . "/auth.php";
authStartSession();

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    authRestoreSession($pdo);

    if (!empty($_SESSION["usuario_id"])) {
        try {
            $presence = $pdo->prepare("UPDATE usuario SET ultimo_acesso_em = NOW() WHERE id_usuario = ?");
            $presence->execute([(int) $_SESSION["usuario_id"]]);
        } catch (PDOException $presenceError) {
            // Compatibilidade durante a aplicação gradual da migração de perfil.
        }
    }
} catch (PDOException $e) {
    error_log("Falha de conexão com o banco: " . $e->getMessage());
    http_response_code(503);
    die("Serviço temporariamente indisponível. Tente novamente em instantes.");
}
