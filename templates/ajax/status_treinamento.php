<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

set_time_limit(30);


if (!function_exists("curl_init")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => "A extensão cURL do PHP não está habilitada."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}



$jobId = isset($_GET["job_id"])
    ? trim($_GET["job_id"])
    : "";

$desdeLog = isset($_GET["desde_log"])
    ? filter_var(
        $_GET["desde_log"],
        FILTER_VALIDATE_INT
    )
    : 0;

if (
    $desdeLog === false ||
    $desdeLog < 0
) {
    $desdeLog = 0;
}


$query = [
    "desde_log" => $desdeLog
];

if ($jobId !== "") {
    $query["job_id"] = $jobId;
}

$url = "http://127.0.0.1:5000/status_treinamento?"
    . http_build_query($query);


$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL => $url,

    CURLOPT_HTTPGET => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_CONNECTTIMEOUT => 5,

    CURLOPT_TIMEOUT => 15,

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
        "error" => "Não foi possível consultar o status do treinamento.",
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
        "error" => "O Flask retornou um status inválido.",
        "http_code_flask" => $codigoHttp,
        "resposta_flask" => $resposta,
        "erro_json" => json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if (
    !isset($json["logs"]) ||
    !is_array($json["logs"])
) {
    $json["logs"] = [];
}


if ($codigoHttp >= 400) {

    http_response_code($codigoHttp);

} else {

    http_response_code(200);
}

echo json_encode(
    $json,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);
?>