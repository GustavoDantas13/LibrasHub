<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");

error_reporting(E_ALL);

set_time_limit(0);


function responder(
    array $dados,
    int $codigo = 200
): void {

    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (
    !isset($_POST["dataset"]) ||
    trim($_POST["dataset"]) === ""
) {

    responder([
        "success" => false,
        "error" => "Dataset não informado."
    ], 400);
}


if (!function_exists("curl_init")) {

    responder([
        "success" => false,
        "error" => "A extensão cURL do PHP não está habilitada."
    ], 500);
}


$dataset = trim(
    $_POST["dataset"]
);


$curl = curl_init();


curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/finalizar_dataset",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => [
        "dataset" => $dataset
    ],

    CURLOPT_CONNECTTIMEOUT => 30,

    /*
     * O processamento dos vídeos pode demorar.
     */
    CURLOPT_TIMEOUT => 0,

    CURLOPT_HTTPHEADER => [
        "Accept: application/json"
    ]
]);


$resposta = curl_exec(
    $curl
);


$erroCurl = curl_error(
    $curl
);


$codigoHttp = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);


if ($resposta === false) {

    responder([
        "success" => false,
        "error" => "Falha ao comunicar com o Flask.",
        "detalhes" => $erroCurl
    ], 502);
}


$json = json_decode(
    $resposta,
    true
);


if (!is_array($json)) {

    responder([
        "success" => false,
        "error" => "O Flask retornou uma resposta inválida.",
        "http_code_flask" => $codigoHttp,
        "resposta_flask" => $resposta,
        "erro_json" => json_last_error_msg()
    ], 502);
}


if (
    $codigoHttp < 200 ||
    $codigoHttp >= 300
) {

    $json["success"] = false;

    $json["http_code_flask"] =
        $codigoHttp;

    responder(
        $json,
        $codigoHttp
    );
}


responder(
    $json,
    200
);