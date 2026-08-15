<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

ini_set(
    "display_errors",
    "0"
);

ini_set(
    "log_errors",
    "1"
);

error_reporting(
    E_ALL
);

set_time_limit(
    30
);


function responder(
    array $dados,
    int $codigo = 200
): void {

    http_response_code(
        $codigo
    );

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "GET"
) {

    responder([
        "success" => false,
        "error" => "Método não permitido."
    ], 405);
}


if (
    !function_exists(
        "curl_init"
    )
) {

    responder([
        "success" => false,
        "error" => "A extensão cURL do PHP não está habilitada."
    ], 500);
}


$jobId =
    isset(
        $_GET["job_id"]
    )
        ? trim(
            (string) $_GET["job_id"]
        )
        : "";


$desdeLog =
    isset(
        $_GET["desde_log"]
    )
        ? filter_var(
            $_GET["desde_log"],
            FILTER_VALIDATE_INT
        )
        : 0;


if (
    $desdeLog === false
    ||
    $desdeLog < 0
) {

    $desdeLog =
        0;
}


$query = [
    "desde_log" =>
        $desdeLog
];


if (
    $jobId !== ""
) {

    $query["job_id"] =
        $jobId;
}


$url =
    "http://127.0.0.1:5000/status_treinamento?"
    .
    http_build_query(
        $query
    );


$curl =
    curl_init();


curl_setopt_array(
    $curl,
    [

        CURLOPT_URL =>
            $url,

        CURLOPT_HTTPGET =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            15,

        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Cache-Control: no-cache"
        ]

    ]
);


$resposta =
    curl_exec(
        $curl
    );

$erroCurl =
    curl_error(
        $curl
    );

$codigoHttp =
    (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );



if (
    $resposta === false
) {

    responder([
        "success" => false,
        "error" => "Não foi possível consultar o status do treinamento.",
        "detalhes" => $erroCurl
    ], 502);
}


$json =
    json_decode(
        $resposta,
        true
    );


if (
    !is_array(
        $json
    )
) {

    responder([
        "success" => false,
        "error" => "O Flask retornou um status inválido.",
        "http_code_flask" => $codigoHttp,
        "resposta_flask" => $resposta,
        "erro_json" => json_last_error_msg()
    ], 502);
}


if (
    !array_key_exists(
        "success",
        $json
    )
) {

    $json["success"] =
        $codigoHttp >= 200
        &&
        $codigoHttp < 300;
}


if (
    !isset(
        $json["logs"]
    )
    ||
    !is_array(
        $json["logs"]
    )
) {

    $json["logs"] =
        [];
}


if (
    !isset(
        $json["proximo_log"]
    )
) {

    $json["proximo_log"] =
        $desdeLog
        +
        count(
            $json["logs"]
        );
}


responder(
    $json,
    $codigoHttp >= 400
        ? $codigoHttp
        : 200
);
