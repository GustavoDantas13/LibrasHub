<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

set_time_limit(0);

if (
    !isset($_POST["dataset"]) ||
    trim($_POST["dataset"]) === ""
) {
    echo json_encode([
        "success" => false,
        "error" => "Dataset não informado."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!function_exists("curl_init")) {
    echo json_encode([
        "success" => false,
        "error" => "A extensão cURL não está habilitada no PHP."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dataset = trim($_POST["dataset"]);

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/finalizar_dataset",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => [
        "dataset" => $dataset
    ],

    CURLOPT_TIMEOUT => 0,

    CURLOPT_CONNECTTIMEOUT => 30
]);

$resposta = curl_exec($curl);

$erroCurl = curl_error($curl);

$codigoHttp = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

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