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
    300
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
    !isset(
        $_FILES["mediaFile"]
    )
) {

    responder([
        "success" => false,
        "error" => "Nenhum arquivo recebido."
    ], 400);
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
    &&
    !mkdir(
        $tempDir,
        0777,
        true
    )
) {

    responder([
        "success" => false,
        "error" => "Não foi possível criar a pasta temporária."
    ], 500);
}


$postFields = [];

$temporarios = [];


$nomes =
    $_FILES[
        "mediaFile"
    ]["name"];


if (
    !is_array(
        $nomes
    )
) {

    $nomes = [
        $nomes
    ];
}


$total =
    count(
        $nomes
    );


for (
    $i = 0;
    $i < $total;
    $i++
) {

    $erroUpload =
        is_array(
            $_FILES[
                "mediaFile"
            ]["error"]
        )
            ?
            $_FILES[
                "mediaFile"
            ]["error"][$i]
            :
            $_FILES[
                "mediaFile"
            ]["error"];


    if (
        $erroUpload
        !==
        UPLOAD_ERR_OK
    ) {

        responder([
            "success" => false,
            "error" => "Erro ao receber um dos arquivos.",
            "codigo_upload" => $erroUpload
        ], 400);
    }


    $tmp =
        is_array(
            $_FILES[
                "mediaFile"
            ]["tmp_name"]
        )
            ?
            $_FILES[
                "mediaFile"
            ]["tmp_name"][$i]
            :
            $_FILES[
                "mediaFile"
            ]["tmp_name"];


    $nome =
        basename(
            $nomes[$i]
        );


    $destino =
        $tempDir
        .
        DIRECTORY_SEPARATOR
        .
        uniqid(
            "analise_",
            true
        )
        .
        "_"
        .
        $nome;


    if (
        !move_uploaded_file(
            $tmp,
            $destino
        )
    ) {

        foreach (
            $temporarios
            as $arquivoTemp
        ) {

            @unlink(
                $arquivoTemp
            );
        }


        responder([
            "success" => false,
            "error" => "Erro ao salvar: " . $nome
        ], 500);
    }


    $temporarios[] =
        $destino;


    $postFields[
        "mediaFile[$i]"
    ] = new CURLFile(
        $destino,
        mime_content_type(
            $destino
        )
        ?: "application/octet-stream",
        $nome
    );
}


if (
    !function_exists(
        "curl_init"
    )
) {

    foreach (
        $temporarios
        as $arquivoTemp
    ) {

        @unlink(
            $arquivoTemp
        );
    }


    responder([
        "success" => false,
        "error" => "A extensão cURL não está habilitada no PHP."
    ], 500);
}


$curl =
    curl_init();


curl_setopt_array(
    $curl,
    [
        CURLOPT_URL =>
            "http://127.0.0.1:5000/analisar",

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_TIMEOUT =>
            300,

        CURLOPT_CONNECTTIMEOUT =>
            30,

        CURLOPT_POSTFIELDS =>
            $postFields,

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
    curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $curl
);


foreach (
    $temporarios
    as $arquivoTemp
) {

    @unlink(
        $arquivoTemp
    );
}


if (
    $resposta
    ===
    false
) {

    responder([
        "success" => false,
        "error" => (
            "Falha ao comunicar com o servidor de tradução."
        ),
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
        "error" => "Resposta inválida do Flask.",
        "resposta" => $resposta
    ], 502);
}


if (
    $codigoHttp < 200
    ||
    $codigoHttp >= 300
) {

    responder(
        $json,
        $codigoHttp > 0
            ? $codigoHttp
            : 502
    );
}


responder(
    $json,
    200
);
