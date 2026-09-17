<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(bool $ok, array $data = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['usuario_id'])) {
    out(false, ['message' => 'Faça login para acessar os locais.'], 401);
}

require_once __DIR__ . '/../configs/config.php';

$uid = (int) $_SESSION['usuario_id'];
if (empty($_SESSION['locais_csrf'])) {
    $_SESSION['locais_csrf'] = bin2hex(random_bytes(24));
}

try {
    $communityColumn = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'is_community_user'")->fetch();
    if (!$communityColumn) {
        $pdo->exec("ALTER TABLE usuario ADD COLUMN is_community_user TINYINT(1) NOT NULL DEFAULT 0 AFTER tp_usuario");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS locais (
        id_local INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_usuario_criador INT NOT NULL,
        nome VARCHAR(140) NOT NULL,
        descricao TEXT NOT NULL,
        tipo VARCHAR(80) NOT NULL,
        cep CHAR(8) NOT NULL,
        logradouro VARCHAR(160) NOT NULL,
        numero VARCHAR(20) NOT NULL,
        complemento VARCHAR(100),
        bairro VARCHAR(100) NOT NULL,
        cidade VARCHAR(100) NOT NULL,
        uf CHAR(2) NOT NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        coordinates_confirmed TINYINT(1) NOT NULL DEFAULT 0,
        visualizacoes INT UNSIGNED NOT NULL DEFAULT 0,
        horario_funcionamento TEXT NOT NULL,
        datas_funcionamento VARCHAR(255),
        acessibilidades JSON NOT NULL,
        imagem_principal VARCHAR(255) NOT NULL,
        galeria JSON,
        status ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'aprovado',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(status,cidade),
        INDEX(tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'latitude' => 'DECIMAL(10,7) NULL AFTER uf',
        'longitude' => 'DECIMAL(10,7) NULL AFTER latitude',
        'coordinates_confirmed' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER longitude',
        'visualizacoes' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER longitude',
    ] as $column => $definition) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='locais' AND column_name=?");
        $check->execute([$column]);
        if (!(bool) $check->fetchColumn()) {
            $pdo->exec("ALTER TABLE locais ADD COLUMN $column $definition");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS avaliacoes (
        id_avaliacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_local INT UNSIGNED NOT NULL,
        id_usuario INT NOT NULL,
        nota TINYINT UNSIGNED NOT NULL,
        resenha VARCHAR(2000) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY(id_local,id_usuario),
        FOREIGN KEY(id_local) REFERENCES locais(id_local) ON DELETE CASCADE,
        FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
    error_log($e->getMessage());
    out(false, ['message' => 'Não foi possível preparar o módulo de locais.'], 500);
}

$action = (string) ($_REQUEST['action'] ?? 'list');

function csrf(): void
{
    if (!hash_equals((string) ($_SESSION['locais_csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        out(false, ['message' => 'Sessão inválida. Atualize a página.'], 403);
    }
}

function clean(string $value, int $max): string
{
    return mb_substr(trim($value), 0, $max);
}

function isAdmin(): bool
{
    return strcasecmp(trim((string) ($_SESSION['usuario_tipo'] ?? '')), 'Administrador') === 0;
}

function canPublishPlace(PDO $pdo, int $uid): bool
{
    if (isAdmin()) {
        return true;
    }
    $statement = $pdo->prepare('SELECT is_community_user FROM usuario WHERE id_usuario=?');
    $statement->execute([$uid]);
    return (bool) $statement->fetchColumn();
}

function findManagedPlace(PDO $pdo, int $uid, int $id): array
{
    $statement = $pdo->prepare("SELECT * FROM locais WHERE id_local=? AND status='aprovado'");
    $statement->execute([$id]);
    $local = $statement->fetch();
    if (!$local) {
        out(false, ['message' => 'Local não encontrado.'], 404);
    }
    if (!isAdmin() && (int) $local['id_usuario_criador'] !== $uid) {
        out(false, ['message' => 'Você não tem permissão para alterar este local.'], 403);
    }
    return $local;
}

function validCoordinates(mixed $latitude, mixed $longitude): bool
{
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        return false;
    }
    $lat = (float) $latitude;
    $lng = (float) $longitude;
    return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat === 0.0 && $lng === 0.0);
}

function requestJson(string $url, array $headers = [], int $timeout = 9): ?array
{
    $body = false;
    $status = 0;
    $headers = array_merge(['Accept: application/json'], $headers);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
    } elseif ((bool) ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => [
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers) . "\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        $status = $body === false ? 0 : 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int) $match[1];
        }
    }

    if ($status < 200 || $status >= 300 || !is_string($body)) {
        return null;
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function normalizedLocationText(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
    return preg_replace('/[^a-z0-9]+/', ' ', $ascii !== false ? $ascii : $value) ?: '';
}

function geocodeWithNominatim(array $local): ?array
{
    $lock = @fopen(sys_get_temp_dir() . '/librashub-nominatim.lock', 'c+');
    if ($lock) {
        flock($lock, LOCK_EX);
        rewind($lock);
        $last = (float) stream_get_contents($lock);
        $wait = 1.05 - (microtime(true) - $last);
        if ($wait > 0) {
            usleep((int) ($wait * 1000000));
        }
    }

    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'street' => trim((string) ($local['numero'] ?? '') . ' ' . (string) ($local['logradouro'] ?? '')),
        'city' => (string) ($local['cidade'] ?? ''),
        'state' => (string) ($local['uf'] ?? ''),
        'postalcode' => preg_replace('/\D/', '', (string) ($local['cep'] ?? '')),
        'country' => 'Brasil',
        'format' => 'jsonv2',
        'addressdetails' => 1,
        'limit' => 5,
        'countrycodes' => 'br',
        'dedupe' => 1,
    ]);
    $host = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'librashub.local'));
    $json = requestJson($url, ['User-Agent: LibrasHub/1.1 (' . $host . ')']);

    if ($lock) {
        rewind($lock);
        ftruncate($lock, 0);
        fwrite($lock, (string) microtime(true));
        fflush($lock);
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    if (!$json) {
        return null;
    }

    $wantedCity = normalizedLocationText((string) ($local['cidade'] ?? ''));
    $wantedCep = preg_replace('/\D/', '', (string) ($local['cep'] ?? ''));
    $genericTypes = ['administrative', 'city', 'town', 'village', 'municipality', 'state', 'county', 'postcode', 'country'];

    foreach ($json as $candidate) {
        if (!is_array($candidate) || !isset($candidate['lat'], $candidate['lon']) || !validCoordinates($candidate['lat'], $candidate['lon'])) {
            continue;
        }
        $address = is_array($candidate['address'] ?? null) ? $candidate['address'] : [];
        $type = mb_strtolower((string) ($candidate['addresstype'] ?? $candidate['type'] ?? ''), 'UTF-8');
        if (in_array($type, $genericTypes, true)) {
            continue;
        }
        $resultCity = normalizedLocationText((string) ($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? ''));
        if ($wantedCity !== '' && $resultCity !== '' && !str_contains($resultCity, $wantedCity) && !str_contains($wantedCity, $resultCity)) {
            continue;
        }
        $resultCep = preg_replace('/\D/', '', (string) ($address['postcode'] ?? ''));
        if ($wantedCep !== '' && $resultCep !== '' && substr($wantedCep, 0, 5) !== substr($resultCep, 0, 5)) {
            continue;
        }
        $wantedNumber = normalizedLocationText((string) ($local['numero'] ?? ''));
        $resultNumber = normalizedLocationText((string) ($address['house_number'] ?? ''));
        $precision = $wantedNumber !== '' && $resultNumber === $wantedNumber ? 'numero' : 'rua';
        return [
            'latitude' => (float) $candidate['lat'],
            'longitude' => (float) $candidate['lon'],
            'label' => clean((string) ($candidate['display_name'] ?? ''), 500),
            'precision' => $precision,
        ];
    }
    return null;
}

function resolveCoordinates(array $local): ?array
{
    return geocodeWithNominatim($local);
}

function normalizePlaceInput(array $source): array
{
    $required = ['nome', 'descricao', 'tipo', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'horario'];
    foreach ($required as $field) {
        if (trim((string) ($source[$field] ?? '')) === '') {
            out(false, ['message' => 'Preencha todos os campos obrigatórios.'], 422);
        }
    }
    $cep = preg_replace('/\D/', '', (string) $source['cep']);
    if (strlen($cep) !== 8) {
        out(false, ['message' => 'Informe um CEP válido com 8 números.'], 422);
    }
    $allowedAccessibility = ['libras', 'rampa', 'elevador', 'banheiro', 'piso_tatil', 'braille', 'atendimento_prioritario', 'vaga_pcd'];
    return [
        'nome' => clean((string) $source['nome'], 140),
        'descricao' => clean((string) $source['descricao'], 5000),
        'tipo' => clean((string) $source['tipo'], 80),
        'cep' => $cep,
        'logradouro' => clean((string) $source['logradouro'], 160),
        'numero' => clean((string) $source['numero'], 20),
        'complemento' => clean((string) ($source['complemento'] ?? ''), 100),
        'bairro' => clean((string) $source['bairro'], 100),
        'cidade' => clean((string) $source['cidade'], 100),
        'uf' => strtoupper(clean((string) $source['uf'], 2)),
        'horario' => clean((string) $source['horario'], 1000),
        'datas' => clean((string) ($source['datas'] ?? ''), 255),
        'acessibilidades' => array_values(array_intersect((array) ($source['acessibilidades'] ?? []), $allowedAccessibility)),
    ];
}

function coordinatesFromInput(array $source): array
{
    if (validCoordinates($source['latitude'] ?? null, $source['longitude'] ?? null)
        && hash_equals('1', (string) ($source['coordinates_confirmed'] ?? ''))) {
        return [(float) $source['latitude'], (float) $source['longitude']];
    }
    out(false, [
        'code' => 'coordinates_confirmation_required',
        'message' => 'Confirme o ponto exato no mapa antes de salvar o local.',
    ], 422);
}

function uploadImages(array $files): array
{
    $saved = [];
    $validated = [];
    $directory = dirname(__DIR__, 2) . '/static/uploads/locais';
    if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
        out(false, ['message' => 'Diretório de imagens indisponível.'], 500);
    }

    $names = (array) ($files['name'] ?? []);
    $sent = count(array_filter($names, static fn($name): bool => trim((string) $name) !== ''));
    if ($sent > 10) {
        out(false, ['message' => 'Envie no máximo 10 imagens por estabelecimento.'], 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($names as $index => $unused) {
        $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK || (int) ($files['size'][$index] ?? 0) > 5 * 1024 * 1024) {
            out(false, ['message' => 'Cada imagem deve ter no máximo 5 MB.'], 422);
        }
        $temporary = (string) ($files['tmp_name'][$index] ?? '');
        if ($temporary === '' || !is_uploaded_file($temporary)) {
            out(false, ['message' => 'Upload de imagem inválido.'], 422);
        }
        $mime = $finfo->file($temporary);
        if (!isset($allowed[$mime])) {
            out(false, ['message' => 'Use imagens JPG, PNG ou WebP.'], 422);
        }
        $validated[] = [$temporary, $allowed[$mime]];
    }

    foreach ($validated as [$temporary, $extension]) {
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($temporary, $directory . '/' . $name)) {
            out(false, ['message' => 'Falha ao salvar uma imagem.'], 500);
        }
        $saved[] = '../static/uploads/locais/' . $name;
    }
    return $saved;
}

function fetchPlace(PDO $pdo, int $id): array|false
{
    $statement = $pdo->prepare("SELECT l.*, u.nm_usuario autor, u.is_community_user,
        COALESCE(AVG(a.nota),0) nota_media, COUNT(a.id_avaliacao) total_avaliacoes
        FROM locais l
        JOIN usuario u ON u.id_usuario=l.id_usuario_criador
        LEFT JOIN avaliacoes a ON a.id_local=l.id_local
        WHERE l.id_local=? AND l.status='aprovado'
        GROUP BY l.id_local");
    $statement->execute([$id]);
    return $statement->fetch();
}

try {
    if ($action === 'list') {
        $query = clean((string) ($_GET['q'] ?? ''), 80);
        $type = clean((string) ($_GET['type'] ?? ''), 80);
        $accessibility = clean((string) ($_GET['accessibility'] ?? ''), 40);
        $allowedAccessibility = ['libras', 'rampa', 'elevador', 'banheiro', 'piso_tatil', 'braille', 'atendimento_prioritario', 'vaga_pcd'];
        $where = ["l.status='aprovado'"];
        $parameters = [];
        if ($query !== '') {
            $where[] = '(l.nome LIKE ? OR l.tipo LIKE ? OR l.cidade LIKE ? OR l.bairro LIKE ?)';
            $like = "%$query%";
            array_push($parameters, $like, $like, $like, $like);
        }
        if ($type !== '') {
            $where[] = 'l.tipo=?';
            $parameters[] = $type;
        }
        if (in_array($accessibility, $allowedAccessibility, true)) {
            $where[] = 'JSON_CONTAINS(l.acessibilidades,JSON_QUOTE(?))';
            $parameters[] = $accessibility;
        }
        $sql = "SELECT l.*,u.nm_usuario autor,u.is_community_user,COALESCE(AVG(a.nota),0) nota_media,COUNT(a.id_avaliacao) total_avaliacoes
            FROM locais l JOIN usuario u ON u.id_usuario=l.id_usuario_criador
            LEFT JOIN avaliacoes a ON a.id_local=l.id_local
            WHERE " . implode(' AND ', $where) . '
            GROUP BY l.id_local ORDER BY l.criado_em DESC LIMIT 100';
        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        out(true, ['locais' => $statement->fetchAll()]);
    }

    if ($action === 'popular') {
        $statement = $pdo->query("SELECT l.*,u.nm_usuario autor,u.is_community_user,COALESCE(AVG(a.nota),0) nota_media,COUNT(a.id_avaliacao) total_avaliacoes
            FROM locais l JOIN usuario u ON u.id_usuario=l.id_usuario_criador
            LEFT JOIN avaliacoes a ON a.id_local=l.id_local
            WHERE l.status='aprovado' GROUP BY l.id_local
            ORDER BY nota_media DESC,total_avaliacoes DESC,l.visualizacoes DESC LIMIT 4");
        out(true, ['locais' => $statement->fetchAll()]);
    }

    if ($action === 'preview_geocode') {
        csrf();
        $managedId = (int) ($_POST['id_local'] ?? 0);
        if ($managedId > 0) {
            findManagedPlace($pdo, $uid, $managedId);
        } elseif (!canPublishPlace($pdo, $uid)) {
            out(false, ['message' => 'Você não tem permissão para cadastrar locais.'], 403);
        }
        $place = [
            'cep' => clean((string) ($_POST['cep'] ?? ''), 9),
            'logradouro' => clean((string) ($_POST['logradouro'] ?? ''), 160),
            'numero' => clean((string) ($_POST['numero'] ?? ''), 20),
            'bairro' => clean((string) ($_POST['bairro'] ?? ''), 100),
            'cidade' => clean((string) ($_POST['cidade'] ?? ''), 100),
            'uf' => strtoupper(clean((string) ($_POST['uf'] ?? ''), 2)),
        ];
        $coordinates = resolveCoordinates($place);
        if (!$coordinates) {
            out(false, ['code' => 'coordinates_required', 'message' => 'Endereço não encontrado automaticamente. Clique no mapa para marcar o ponto exato.'], 404);
        }
        out(true, $coordinates);
    }

    if ($action === 'geocode') {
        csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare("SELECT id_local,logradouro,numero,bairro,cidade,uf,cep,latitude,longitude,coordinates_confirmed FROM locais WHERE id_local=? AND status='aprovado'");
        $statement->execute([$id]);
        $local = $statement->fetch();
        if (!$local) {
            out(false, ['message' => 'Local não encontrado.'], 404);
        }
        if ((bool) $local['coordinates_confirmed'] && validCoordinates($local['latitude'], $local['longitude'])) {
            out(true, ['latitude' => (float) $local['latitude'], 'longitude' => (float) $local['longitude']]);
        }
        $coordinates = resolveCoordinates($local);
        if (!$coordinates || $coordinates['precision'] !== 'numero') {
            out(false, ['message' => 'Este local precisa ter o ponto confirmado. Edite-o e marque a posição exata no mapa.'], 409);
        }
        $pdo->prepare('UPDATE locais SET latitude=?,longitude=?,coordinates_confirmed=1 WHERE id_local=?')->execute([$coordinates['latitude'], $coordinates['longitude'], $id]);
        out(true, ['latitude' => $coordinates['latitude'], 'longitude' => $coordinates['longitude']]);
    }

    if ($action === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        if (($_GET['track'] ?? '1') !== '0') {
            $pdo->prepare("UPDATE locais SET visualizacoes=visualizacoes+1 WHERE id_local=? AND status='aprovado'")->execute([$id]);
        }
        $local = fetchPlace($pdo, $id);
        if (!$local) {
            out(false, ['message' => 'Local não encontrado.'], 404);
        }
        $statement = $pdo->prepare('SELECT a.*,u.nm_usuario autor,u.is_community_user FROM avaliacoes a JOIN usuario u ON u.id_usuario=a.id_usuario WHERE a.id_local=? ORDER BY a.criado_em DESC');
        $statement->execute([$id]);
        out(true, ['local' => $local, 'avaliacoes' => $statement->fetchAll()]);
    }

    if ($action === 'create') {
        csrf();
        if (!canPublishPlace($pdo, $uid)) {
            out(false, ['message' => 'Apenas usuários comunitários e administradores podem cadastrar locais.'], 403);
        }
        $place = normalizePlaceInput($_POST);
        $coordinates = coordinatesFromInput($_POST);
        $images = uploadImages($_FILES['imagens'] ?? []);
        if (!$images) {
            out(false, ['message' => 'Envie pelo menos uma imagem.'], 422);
        }
        $statement = $pdo->prepare('INSERT INTO locais (
            id_usuario_criador,nome,descricao,tipo,cep,logradouro,numero,complemento,bairro,cidade,uf,latitude,longitude,
            coordinates_confirmed,horario_funcionamento,datas_funcionamento,acessibilidades,imagem_principal,galeria
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $statement->execute([
            $uid, $place['nome'], $place['descricao'], $place['tipo'], $place['cep'], $place['logradouro'], $place['numero'],
            $place['complemento'], $place['bairro'], $place['cidade'], $place['uf'], $coordinates[0], $coordinates[1], 1,
            $place['horario'], $place['datas'], json_encode($place['acessibilidades']), $images[0], json_encode($images),
        ]);
        $id = (int) $pdo->lastInsertId();
        out(true, ['id' => $id, 'local' => fetchPlace($pdo, $id), 'message' => 'Local publicado com sucesso.']);
    }

    if ($action === 'update') {
        csrf();
        $id = (int) ($_POST['id_local'] ?? 0);
        $current = findManagedPlace($pdo, $uid, $id);
        $place = normalizePlaceInput($_POST);
        $coordinates = coordinatesFromInput($_POST);
        $newImages = uploadImages($_FILES['imagens'] ?? []);
        $images = $newImages ?: (json_decode((string) $current['galeria'], true) ?: [(string) $current['imagem_principal']]);

        $statement = $pdo->prepare('UPDATE locais SET
            nome=?,descricao=?,tipo=?,cep=?,logradouro=?,numero=?,complemento=?,bairro=?,cidade=?,uf=?,latitude=?,longitude=?,coordinates_confirmed=1,
            horario_funcionamento=?,datas_funcionamento=?,acessibilidades=?,imagem_principal=?,galeria=?
            WHERE id_local=?');
        $statement->execute([
            $place['nome'], $place['descricao'], $place['tipo'], $place['cep'], $place['logradouro'], $place['numero'],
            $place['complemento'], $place['bairro'], $place['cidade'], $place['uf'], $coordinates[0], $coordinates[1],
            $place['horario'], $place['datas'], json_encode($place['acessibilidades']), $images[0], json_encode($images), $id,
        ]);
        out(true, ['local' => fetchPlace($pdo, $id), 'message' => 'Local atualizado com sucesso.']);
    }

    if ($action === 'delete') {
        csrf();
        $id = (int) ($_POST['id_local'] ?? 0);
        findManagedPlace($pdo, $uid, $id);
        $statement = $pdo->prepare("UPDATE locais SET status='rejeitado' WHERE id_local=? AND status='aprovado'");
        $statement->execute([$id]);
        out(true, ['id' => $id, 'message' => 'Local excluído com sucesso.']);
    }

    if ($action === 'review') {
        csrf();
        $id = (int) ($_POST['id_local'] ?? 0);
        $grade = (int) ($_POST['nota'] ?? 0);
        $text = clean((string) ($_POST['resenha'] ?? ''), 2000);
        if ($id < 1 || $grade < 1 || $grade > 5 || mb_strlen($text) < 3) {
            out(false, ['message' => 'Informe nota de 1 a 5 e uma resenha.'], 422);
        }
        $exists = $pdo->prepare("SELECT 1 FROM locais WHERE id_local=? AND status='aprovado'");
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            out(false, ['message' => 'Local não encontrado.'], 404);
        }
        $statement = $pdo->prepare('INSERT INTO avaliacoes(id_local,id_usuario,nota,resenha) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE nota=VALUES(nota),resenha=VALUES(resenha),atualizado_em=CURRENT_TIMESTAMP');
        $statement->execute([$id, $uid, $grade, $text]);
        out(true, ['message' => 'Avaliação salva.']);
    }

    out(false, ['message' => 'Ação inválida.'], 400);
} catch (Throwable $e) {
    error_log($e->getMessage());
    out(false, ['message' => 'Não foi possível concluir a operação.'], 500);
}
