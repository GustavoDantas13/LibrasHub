<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

session_start();

require_once "../configs/config.php";


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
    "POST"
) {

    responder([
        "success" => false,
        "error" => "Método não permitido."
    ], 405);
}


if (
    empty(
        $_SESSION["usuario_id"]
    )
) {

    responder([
        "success" => false,
        "error" => "Usuário não autenticado."
    ], 401);
}


$idUsuario =
    (int) $_SESSION["usuario_id"];


$stmt =
    $pdo->prepare("
        SELECT tp_usuario
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");


$stmt->execute([
    $idUsuario
]);


$tipoUsuario =
    $stmt->fetchColumn();


if (
    !$tipoUsuario
    ||
    mb_strtolower(
        trim(
            (string) $tipoUsuario
        )
    )
    !==
    "administrador"
) {

    responder([
        "success" => false,
        "error" => "Acesso negado."
    ], 403);
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


$curl =
    curl_init();


curl_setopt_array(
    $curl,
    [

        CURLOPT_URL =>
            "http://127.0.0.1:5000/cancelar_dataset",

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            45,

        CURLOPT_HTTPHEADER => [
            "Accept: application/json"
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


curl_close(
    $curl
);


if (
    $resposta === false
) {

    responder([
        "success" => false,
        "error" => (
            "Não foi possível solicitar o cancelamento ao Python."
        ),
        "detalhes" =>
            $erroCurl
    ], 502);
}


$dados =
    json_decode(
        $resposta,
        true
    );


if (
    !is_array(
        $dados
    )
) {

    responder([
        "success" => false,
        "error" => (
            "O Python retornou uma resposta inválida."
        ),
        "resposta_python" =>
            $resposta
    ], 502);
}


responder(
    $dados,
    $codigoHttp > 0
        ? $codigoHttp
        : 500
);
