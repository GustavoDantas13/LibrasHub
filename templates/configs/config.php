<?php
/**
 * Conexão central do LibrasHub com MySQL/MariaDB.
 * Local (XAMPP): localhost / root / sem senha / banco librashub.
 * Produção: use DB_* ou as variáveis MYSQL* fornecidas pelo provedor.
 */

$DB_HOST = getenv("DB_HOST") ?: getenv("MYSQLHOST") ?: "127.0.0.1";
$DB_PORT = getenv("DB_PORT") ?: getenv("MYSQLPORT") ?: "3306";
$DB_NAME = getenv("DB_NAME") ?: getenv("MYSQLDATABASE") ?: "librashub";
$DB_USER = getenv("DB_USER") ?: getenv("MYSQLUSER") ?: "root";
$DB_PASS = getenv("DB_PASS");
if ($DB_PASS === false) {
    $DB_PASS = getenv("MYSQLPASSWORD");
}
if ($DB_PASS === false) {
    $DB_PASS = "";
}

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
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    authRestoreSession($pdo);

    if (!empty($_SESSION["usuario_id"])) {
        try {
            $presence = $pdo->prepare("UPDATE usuario SET ultimo_acesso_em = NOW() WHERE id_usuario = ?");
            $presence->execute([(int) $_SESSION["usuario_id"]]);
        } catch (PDOException $presenceError) {
            // Compatibilidade durante migrações graduais do schema.
        }
    }
} catch (PDOException $e) {
    error_log("Falha de conexão com o banco LibrasHub: " . $e->getMessage());
    http_response_code(503);
    die("Serviço temporariamente indisponível. Verifique a conexão com o banco de dados.");
}
