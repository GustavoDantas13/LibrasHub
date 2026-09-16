<?php
/**
 * Configuração de conexão com o banco de dados MySQL.
 * Ajuste os valores abaixo conforme seu ambiente (XAMPP, servidor, etc).
 */

$DB_HOST = "localhost";
$DB_NAME = "librashub";
$DB_USER = "root";
$DB_PASS = "";

require_once __DIR__ . "/auth.php";
authStartSession();

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    authRestoreSession($pdo);

    // Atualiza a presença sem impedir o acesso caso a migração mais recente
    // ainda não tenha sido executada.
    if (!empty($_SESSION["usuario_id"])) {
        try {
            $presence = $pdo->prepare(
                "UPDATE usuario SET ultimo_acesso_em = NOW() WHERE id_usuario = ?"
            );
            $presence->execute([(int) $_SESSION["usuario_id"]]);
        } catch (PDOException $presenceError) {
            // A página continua funcional até a aplicação da migração SQL.
        }
    }
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
