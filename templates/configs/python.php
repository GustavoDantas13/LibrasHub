<?php

declare(strict_types=1);

function pythonTranslatorUrl(string $path = ''): string
{
    $baseUrl = trim((string) (getenv('PYTHON_TRANSLATOR_URL') ?: 'http://127.0.0.1:5000'));
    $baseUrl = rtrim($baseUrl, '/');

    if (!preg_match('#^https?://#i', $baseUrl)) {
        $baseUrl = 'http://127.0.0.1:5000';
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function translationSessionId(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['traducao_tempo_real_id'])) {
        $_SESSION['traducao_tempo_real_id'] = 'web-' . bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['traducao_tempo_real_id'];
}
