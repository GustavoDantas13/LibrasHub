<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

ini_set(
    "display_errors",
    "0"
);

error_reporting(
    E_ALL
);


if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "POST"
) {

    http_response_code(
        405
    );

    echo json_encode([
        "success" => false,
        "error" => "Método não permitido."
    ]);

    exit;
}


$pasta =
    __DIR__
    .
    DIRECTORY_SEPARATOR
    .
    "temp";


if (
    is_dir(
        $pasta
    )
) {

    $arquivos =
        glob(
            $pasta
            .
            DIRECTORY_SEPARATOR
            .
            "*"
        );


    if (
        is_array(
            $arquivos
        )
    ) {

        foreach (
            $arquivos
            as $arquivo
        ) {

            if (
                is_file(
                    $arquivo
                )
            ) {

                @unlink(
                    $arquivo
                );
            }
        }
    }
}


if (
    !function_exists(
        "curl_init"
    )
) {

    http_response_code(
        500
    );

    echo json_encode([
        "success" => false,
        "error" => "A extensão cURL do PHP não está habilitada."
    ]);

    exit;
}


$curl =
    curl_init();


curl_setopt_array(
    $curl,
    [

        CURLOPT_URL =>
            "http://127.0.0.1:5000/limpar_traducao",

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            15

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
    curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $curl
);


if (
    $resposta === false
    ||
    $erroCurl !== ""
) {

    http_response_code(
        502
    );

    echo json_encode([
        "success" => false,
        "error" => (
            "Não foi possível limpar a tradução no Python: "
            .
            $erroCurl
        )
    ]);

    exit;
}


$dadosPython =
    json_decode(
        $resposta,
        true
    );


if (
    !is_array(
        $dadosPython
    )
) {

    http_response_code(
        502
    );

    echo json_encode([
        "success" => false,
        "error" => "O Python retornou uma resposta inválida.",
        "resposta_python" => $resposta
    ]);

    exit;
}


if (
    $codigoHttp < 200
    ||
    $codigoHttp >= 300
) {

    http_response_code(
        $codigoHttp
    );

    echo json_encode([
        "success" => false,
        "error" => (
            $dadosPython["error"]
            ??
            "O Python não conseguiu limpar a tradução."
        )
    ]);

    exit;
}


echo json_encode(
    [
        "success" => true,
        "python" => $dadosPython,
        "temporarios_limpos" => true
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

?>