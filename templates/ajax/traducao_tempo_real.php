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
        "status" => "erro",
        "error" => "Método não permitido."
    ]);

    exit;
}


if (
    !isset(
        $_FILES["frame"]
    )
) {

    http_response_code(
        400
    );

    echo json_encode([
        "status" => "erro",
        "error" => "Nenhum frame recebido pelo PHP."
    ]);

    exit;
}


if (
    $_FILES["frame"]["error"]
    !==
    UPLOAD_ERR_OK
) {

    http_response_code(
        400
    );

    echo json_encode([
        "status" => "erro",
        "error" => "Erro no upload do frame.",
        "codigo" => $_FILES["frame"]["error"]
    ]);

    exit;
}


$tempDir =
    __DIR__
    .
    DIRECTORY_SEPARATOR
    .
    "temp";


if (
    !is_dir(
        $tempDir
    )
) {

    if (
        !mkdir(
            $tempDir,
            0777,
            true
        )
    ) {

        http_response_code(
            500
        );

        echo json_encode([
            "status" => "erro",
            "error" => "Não foi possível criar a pasta temporária."
        ]);

        exit;
    }
}


$nomeArquivo =
    "frame_"
    .
    bin2hex(
        random_bytes(
            8
        )
    )
    .
    ".jpg";


$caminho =
    $tempDir
    .
    DIRECTORY_SEPARATOR
    .
    $nomeArquivo;


if (
    !move_uploaded_file(
        $_FILES["frame"]["tmp_name"],
        $caminho
    )
) {

    http_response_code(
        500
    );

    echo json_encode([
        "status" => "erro",
        "error" => "Não foi possível salvar o frame temporariamente."
    ]);

    exit;
}


if (
    !function_exists(
        "curl_init"
    )
) {

    @unlink(
        $caminho
    );

    http_response_code(
        500
    );

    echo json_encode([
        "status" => "erro",
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
            "http://127.0.0.1:5000/traducao_tempo_real",

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            30,

        CURLOPT_POSTFIELDS => [
            "frame" =>
                new CURLFile(
                    $caminho,
                    "image/jpeg",
                    "frame.jpg"
                )
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
    curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $curl
);


if (
    is_file(
        $caminho
    )
) {

    @unlink(
        $caminho
    );
}


if (
    $resposta === false
    ||
    $erroCurl !== ""
) {

    http_response_code(
        502
    );

    echo json_encode([
        "status" => "erro",
        "error" => (
            "Não foi possível conectar ao servidor Python: "
            .
            $erroCurl
        )
    ]);

    exit;
}


if (
    $codigoHttp <= 0
) {

    http_response_code(
        502
    );

    echo json_encode([
        "status" => "erro",
        "error" => "O Flask não respondeu à requisição."
    ]);

    exit;
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

    http_response_code(
        502
    );

    echo json_encode([
        "status" => "erro",
        "error" => "O Python retornou uma resposta inválida.",
        "resposta_python" => $resposta
    ]);

    exit;
}


http_response_code(
    $codigoHttp
);


echo json_encode(
    $json,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);