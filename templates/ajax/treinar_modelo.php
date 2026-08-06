<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

set_time_limit(60);


if (!function_exists("curl_init")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => "A extensão cURL do PHP não está habilitada."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$epochs = 120;
$batchSize = 16;

if (
    isset($_POST["epochs"]) &&
    $_POST["epochs"] !== ""
) {
    $epochs = filter_var(
        $_POST["epochs"],
        FILTER_VALIDATE_INT
    );
}

if (
    isset($_POST["batch_size"]) &&
    $_POST["batch_size"] !== ""
) {
    $batchSize = filter_var(
        $_POST["batch_size"],
        FILTER_VALIDATE_INT
    );
}


if (
    $epochs === false ||
    $epochs <= 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "error" => "O número de épocas deve ser um inteiro maior que zero."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    $batchSize === false ||
    $batchSize <= 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "error" => "O tamanho do lote deve ser um inteiro maior que zero."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/treinar_modelo",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => [
        "epochs" => $epochs,
        "batch_size" => $batchSize
    ],

    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_HTTPHEADER => [
        "Accept: application/json"
    ]

]);

$resposta = curl_exec($curl);

$erroCurl = curl_error($curl);

$codigoHttp = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);


if ($resposta === false) {

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "error" => "Não foi possível comunicar com o Flask.",
        "detalhes" => $erroCurl
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$json = json_decode(
    $resposta,
    true
);

if (!is_array($json)) {

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "error" => "O Flask retornou uma resposta inválida.",
        "http_code_flask" => $codigoHttp,
        "resposta_flask" => $resposta,
        "erro_json" => json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if ($codigoHttp >= 400) {

    http_response_code($codigoHttp);

} else {

    http_response_code(
        $codigoHttp ?: 202
    );
}

echo json_encode(
    $json,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

?>