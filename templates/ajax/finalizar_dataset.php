<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

set_time_limit(300);

if (!isset($_FILES["mediaFile"])) {
    echo json_encode([
        "success" => false,
        "error" => "Nenhum arquivo recebido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    !isset($_POST["dataset"]) ||
    trim($_POST["dataset"]) === ""
) {
    echo json_encode([
        "success" => false,
        "error" => "Nome do dataset não informado."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    $_FILES["mediaFile"]["error"]
    !== UPLOAD_ERR_OK
) {
    echo json_encode([
        "success" => false,
        "error" => "Erro no upload do arquivo.",
        "codigo_upload" =>
            $_FILES["mediaFile"]["error"]
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dataset = trim($_POST["dataset"]);

$tempDir = __DIR__ . DIRECTORY_SEPARATOR . "temp";

if (
    !is_dir($tempDir) &&
    !mkdir($tempDir, 0777, true)
) {
    echo json_encode([
        "success" => false,
        "error" =>
            "Não foi possível criar a pasta temporária."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$tmp = $_FILES["mediaFile"]["tmp_name"];

$nomeOriginal = basename(
    $_FILES["mediaFile"]["name"]
);

$nomeTemporario =
    uniqid("dataset_", true)
    . "_"
    . $nomeOriginal;

$destino =
    $tempDir
    . DIRECTORY_SEPARATOR
    . $nomeTemporario;

if (!move_uploaded_file($tmp, $destino)) {
    echo json_encode([
        "success" => false,
        "error" =>
            "Erro ao salvar o arquivo temporário."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!function_exists("curl_init")) {
    @unlink($destino);

    echo json_encode([
        "success" => false,
        "error" =>
            "A extensão cURL não está habilitada."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$tipoMime =
    mime_content_type($destino)
    ?: "application/octet-stream";

$postFields = [
    "dataset" => $dataset,

    "mediaFile" => new CURLFile(
        $destino,
        $tipoMime,
        $nomeOriginal
    )
];

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/criar_dataset",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => $postFields,

    CURLOPT_TIMEOUT => 300,

    CURLOPT_CONNECTTIMEOUT => 30
]);

$resposta = curl_exec($curl);

$erroCurl = curl_error($curl);

$codigoHttp = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);


@unlink($destino);

if ($resposta === false) {
    echo json_encode([
        "success" => false,
        "error" => "Erro ao comunicar com o Flask.",
        "detalhes" => $erroCurl
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$json = json_decode($resposta, true);

if (!is_array($json)) {
    echo json_encode([
        "success" => false,
        "error" => "Resposta inválida do Flask.",
        "http_code" => $codigoHttp,
        "resposta_flask" => $resposta,
        "json_error" => json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($codigoHttp < 200 || $codigoHttp >= 300) {
    echo json_encode([
        "success" => false,
        "error" =>
            $json["error"]
            ?? "O Flask retornou um erro.",
        "http_code" => $codigoHttp
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode(
    $json,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

?>