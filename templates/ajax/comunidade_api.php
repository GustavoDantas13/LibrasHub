<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode([
    'ok' => false,
    'message' => 'Este endpoint foi substituído pelo Social. Use ajax/social_api.php.',
    'redirect' => '../social.php',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
