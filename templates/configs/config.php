<?php
/**
 * Configuração de conexão com o banco de dados MySQL.
 * Ajuste os valores abaixo conforme seu ambiente (XAMPP, servidor, etc).
 */

$DB_HOST = "localhost";
$DB_NAME = "librashub";
$DB_USER = "root";
$DB_PASS = "";

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
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}