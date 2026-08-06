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

$jobId = isset($_POST["job_id"])
    ? trim($_POST["job_id"])
    : "";



$postFields = [];

if ($jobId !== "") {
    $postFields["job_id"] = $jobId;
}


$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/cancelar_treinamento",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => $postFields,

    CURLOPT_CONNECTTIMEOUT => 5,

    CURLOPT_TIMEOUT => 20,

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
        "error" => "Não foi possível solicitar o cancelamento.",
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

    http_response_code(200);
}



echo json_encode(
    $json,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);
?>