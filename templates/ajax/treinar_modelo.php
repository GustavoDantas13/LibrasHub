<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

session_start();

require_once "../configs/config.php";

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
    60
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


$stmtUsuario =
    $pdo->prepare("
        SELECT tp_usuario
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");


$stmtUsuario->execute([
    $idUsuario
]);


$tipoUsuario =
    $stmtUsuario->fetchColumn();


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


$baseProjeto =
    dirname(
        __DIR__,
        2
    );


$pastaDataset =
    $baseProjeto
    .
    DIRECTORY_SEPARATOR
    .
    "dataset_processado";


if (
    !is_dir(
        $pastaDataset
    )
) {

    responder([
        "success" => false,
        "error" => "A pasta dataset_processado não foi encontrada."
    ], 400);
}


$stmtDatasets =
    $pdo->query("
        SELECT dataset
        FROM gesto
        WHERE
            dataset IS NOT NULL
            AND TRIM(dataset) <> ''
    ");


$datasetsBanco =
    $stmtDatasets->fetchAll(
        PDO::FETCH_COLUMN
    );


$permitidos =
    [];


foreach (
    $datasetsBanco
    as $datasetBanco
) {

    $relativo =
        str_replace(
            "\\",
            "/",
            trim(
                (string) $datasetBanco
            )
        );

    $relativo =
        ltrim(
            $relativo,
            "/"
        );

    if (
        !str_starts_with(
            $relativo,
            "dataset_processado/"
        )
    ) {

        continue;
    }

    $permitidos[
        mb_strtolower(
            basename(
                $relativo
            )
        )
    ] =
        true;
}


$arquivosDataset =
    glob(
        $pastaDataset
        .
        DIRECTORY_SEPARATOR
        .
        "*.npy"
    );


if (
    is_array(
        $arquivosDataset
    )
) {

    foreach (
        $arquivosDataset
        as $arquivoDataset
    ) {

        $nome =
            mb_strtolower(
                basename(
                    $arquivoDataset
                )
            );


        if (
            !isset(
                $permitidos[
                    $nome
                ]
            )
        ) {

            if (
                !unlink(
                    $arquivoDataset
                )
            ) {

                responder([
                    "success" => false,
                    "error" => (
                        "Não foi possível remover o dataset órfão "
                        .
                        basename(
                            $arquivoDataset
                        )
                        .
                        "."
                    )
                ], 500);
            }
        }
    }
}


$arquivosRestantes =
    glob(
        $pastaDataset
        .
        DIRECTORY_SEPARATOR
        .
        "*.npy"
    );


if (
    !is_array(
        $arquivosRestantes
    )
    ||
    count(
        $arquivosRestantes
    ) === 0
) {

    responder([
        "success" => false,
        "error" => "Nenhum dataset cadastrado está disponível para treinamento."
    ], 400);
}


$epochs =
    120;

$batchSize =
    16;


if (
    isset(
        $_POST["epochs"]
    )
    &&
    $_POST["epochs"] !== ""
) {

    $epochs =
        filter_var(
            $_POST["epochs"],
            FILTER_VALIDATE_INT
        );
}


if (
    isset(
        $_POST["batch_size"]
    )
    &&
    $_POST["batch_size"] !== ""
) {

    $batchSize =
        filter_var(
            $_POST["batch_size"],
            FILTER_VALIDATE_INT
        );
}


if (
    $epochs === false
    ||
    $epochs <= 0
) {

    responder([
        "success" => false,
        "error" => "O número de épocas deve ser um inteiro maior que zero."
    ], 400);
}


if (
    $batchSize === false
    ||
    $batchSize <= 0
) {

    responder([
        "success" => false,
        "error" => "O tamanho do lote deve ser um inteiro maior que zero."
    ], 400);
}


$curl =
    curl_init();


curl_setopt_array(
    $curl,
    [

        CURLOPT_URL =>
            "http://127.0.0.1:5000/treinar_modelo",

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_POSTFIELDS => [
            "epochs" =>
                $epochs,

            "batch_size" =>
                $batchSize
        ],

        CURLOPT_CONNECTTIMEOUT =>
            10,

        CURLOPT_TIMEOUT =>
            30,

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
        "error" => "Não foi possível comunicar com o Flask.",
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
        "error" => "O Flask retornou uma resposta inválida.",
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


responder(
    $json,
    $codigoHttp > 0
        ? $codigoHttp
        : 202
);
?>

