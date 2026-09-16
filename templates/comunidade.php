<?php
declare(strict_types=1);
require_once __DIR__ . '/configs/config.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.php?redirect=comunidade.php');
    exit;
}

// A Comunidade agora é o guia colaborativo de locais acessíveis.
header('Location: social.php', true, 302);
exit;
