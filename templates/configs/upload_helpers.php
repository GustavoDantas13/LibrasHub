<?php
declare(strict_types=1);

/**
 * Salva uma imagem validada dentro de static/uploads e devolve a URL relativa
 * utilizada pelas páginas que ficam em /templates.
 */
function storeImageUpload(array $file, string $subdirectory, int $maxBytes = 5242880): ?string
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível receber a imagem. Tente novamente.');

    $size = (int)($file['size'] ?? 0);
    if ($size < 1 || $size > $maxBytes) throw new RuntimeException('A imagem deve ter no máximo 5 MB.');

    $temporaryPath = (string)($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) throw new RuntimeException('O arquivo enviado é inválido.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Use uma imagem JPG, PNG ou WebP.');
    if (@getimagesize($temporaryPath) === false) throw new RuntimeException('A imagem selecionada está corrompida.');

    $safeDirectory = trim(preg_replace('/[^a-z0-9\/_-]/i', '', $subdirectory) ?? '', '/');
    if ($safeDirectory === '') throw new RuntimeException('Destino de imagem inválido.');
    $directory = dirname(__DIR__, 2) . '/static/uploads/' . $safeDirectory;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('O armazenamento de imagens está indisponível.');
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    return '../static/uploads/' . $safeDirectory . '/' . $filename;
}

function removeStoredUpload(?string $url): void
{
    if (!$url || !str_starts_with($url, '../static/uploads/')) return;
    $relative = substr($url, strlen('../static/uploads/'));
    if ($relative === '' || str_contains($relative, '..')) return;
    $path = dirname(__DIR__, 2) . '/static/uploads/' . $relative;
    $uploadRoot = realpath(dirname(__DIR__, 2) . '/static/uploads');
    $realPath = realpath($path);
    if ($uploadRoot && $realPath && str_starts_with($realPath, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($realPath)) {
        @unlink($realPath);
    }
}
