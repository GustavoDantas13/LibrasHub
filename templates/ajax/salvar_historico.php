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


$itensJson =
    $_POST["itens"]
    ?? "";


$itens =
    json_decode(
        $itensJson,
        true
    );


if (
    !is_array(
        $itens
    )
    ||
    count(
        $itens
    ) === 0
) {

    responder([
        "success" => false,
        "error" => "Nenhuma tradução foi recebida.",
        "post_max_size" =>
            ini_get(
                "post_max_size"
            ),
        "upload_max_filesize" =>
            ini_get(
                "upload_max_filesize"
            ),
        "content_length" =>
            $_SERVER[
                "CONTENT_LENGTH"
            ]
            ?? null,
        "post_recebido" =>
            array_keys(
                $_POST
            ),
        "arquivos_recebidos" =>
            array_keys(
                $_FILES
            )
    ], 400);
}


$stmtUsuario =
    $pdo->prepare("
        SELECT id_usuario
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");


$stmtUsuario->execute([
    $idUsuario
]);


if (
    !$stmtUsuario->fetchColumn()
) {

    responder([
        "success" => false,
        "error" => "Usuário não encontrado."
    ], 401);
}


$pastaBase =
    dirname(
        __DIR__
    )
    .
    DIRECTORY_SEPARATOR
    .
    "uploads_historico";


$pastaUsuario =
    $pastaBase
    .
    DIRECTORY_SEPARATOR
    .
    $idUsuario;


if (
    !is_dir(
        $pastaUsuario
    )
    &&
    !mkdir(
        $pastaUsuario,
        0777,
        true
    )
) {

    responder([
        "success" => false,
        "error" => "Não foi possível criar a pasta do histórico.",
        "pasta" => $pastaUsuario
    ], 500);
}


if (
    !is_writable(
        $pastaUsuario
    )
) {

    responder([
        "success" => false,
        "error" => "A pasta do histórico não possui permissão de escrita.",
        "pasta" => $pastaUsuario
    ], 500);
}


$extensoesPermitidas = [
    "jpg",
    "jpeg",
    "png",
    "webp",
    "mp4",
    "avi",
    "mov",
    "mkv"
];


try {

    $stmtGesto =
        $pdo->prepare("
            SELECT id_gesto
            FROM gesto
            WHERE LOWER(TRIM(nm_gesto)) = LOWER(TRIM(?))
            LIMIT 1
        ");


    $stmtHistorico =
        $pdo->prepare("
            INSERT INTO historico (
                id_usuario,
                id_gesto,
                url_arquivo,
                texto_resultado
            )
            VALUES (
                ?,
                ?,
                ?,
                ?
            )
        ");


    $salvos = [];

    $ignorados = [];


    foreach (
        $itens
        as $indice => $item
    ) {

        if (
            !is_array(
                $item
            )
        ) {
            continue;
        }


        $gesto =
            trim(
                (string) (
                    $item["gesto"]
                    ??
                    ""
                )
            );


        $textoResultado =
            trim(
                (string) (
                    $item["texto_resultado"]
                    ??
                    $gesto
                )
            );


        $chaveArquivo =
            trim(
                (string) (
                    $item["chave_arquivo"]
                    ??
                    "midia"
                )
            );


        if (
            $textoResultado === ""
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "motivo" => "Tradução vazia."
            ];

            continue;
        }


        if (
            $chaveArquivo === ""
            ||
            !isset(
                $_FILES[
                    $chaveArquivo
                ]
            )
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "gesto" => $gesto,
                "chave_arquivo" => $chaveArquivo,
                "arquivos_recebidos" =>
                    array_keys(
                        $_FILES
                    ),
                "motivo" => "A mídia não chegou ao PHP."
            ];

            continue;
        }


        $arquivo =
            $_FILES[
                $chaveArquivo
            ];


        if (
            $arquivo["error"]
            !==
            UPLOAD_ERR_OK
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "arquivo" =>
                    $arquivo["name"]
                    ?? "",
                "codigo_upload" =>
                    $arquivo["error"],
                "upload_max_filesize" =>
                    ini_get(
                        "upload_max_filesize"
                    ),
                "post_max_size" =>
                    ini_get(
                        "post_max_size"
                    ),
                "motivo" => "Erro no upload da mídia."
            ];

            continue;
        }


        if (
            !is_uploaded_file(
                $arquivo[
                    "tmp_name"
                ]
            )
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "arquivo" =>
                    $arquivo["name"]
                    ?? "",
                "motivo" => "Arquivo temporário inválido."
            ];

            continue;
        }


        $nomeOriginal =
            basename(
                $arquivo[
                    "name"
                ]
            );


        $extensao =
            strtolower(
                pathinfo(
                    $nomeOriginal,
                    PATHINFO_EXTENSION
                )
            );


        if (
            !in_array(
                $extensao,
                $extensoesPermitidas,
                true
            )
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "arquivo" => $nomeOriginal,
                "motivo" => "Formato de mídia não permitido."
            ];

            continue;
        }


        $idGesto =
            null;


        if (
            $gesto !== ""
        ) {

            $stmtGesto->execute([
                $gesto
            ]);


            $idEncontrado =
                $stmtGesto->fetchColumn();


            if (
                $idEncontrado
            ) {

                $idGesto =
                    (int) $idEncontrado;
            }
        }


        $nomeLimpo =
            preg_replace(
                '/[^A-Za-z0-9._-]/',
                '_',
                $nomeOriginal
            );


        $nomeSalvo =
            date(
                "Ymd_His"
            )
            .
            "_"
            .
            bin2hex(
                random_bytes(
                    6
                )
            )
            .
            "_"
            .
            $nomeLimpo;


        $caminhoSalvo =
            $pastaUsuario
            .
            DIRECTORY_SEPARATOR
            .
            $nomeSalvo;


        if (
            !move_uploaded_file(
                $arquivo[
                    "tmp_name"
                ],
                $caminhoSalvo
            )
        ) {

            $ignorados[] = [
                "indice" => $indice,
                "arquivo" => $nomeOriginal,
                "destino" => $caminhoSalvo,
                "motivo" => "Não foi possível mover a mídia para a pasta do histórico."
            ];

            continue;
        }


        $urlArquivo =
            "uploads_historico/"
            .
            $idUsuario
            .
            "/"
            .
            $nomeSalvo;


        try {

            $stmtHistorico->execute([
                $idUsuario,
                $idGesto,
                $urlArquivo,
                $textoResultado
            ]);

        } catch (
            PDOException $erroInsert
        ) {

            if (
                is_file(
                    $caminhoSalvo
                )
            ) {

                @unlink(
                    $caminhoSalvo
                );
            }

            throw $erroInsert;
        }


        $salvos[] = [
            "id_historico" =>
                (int) $pdo->lastInsertId(),
            "id_gesto" =>
                $idGesto,
            "arquivo" =>
                $urlArquivo,
            "gesto" =>
                $gesto,
            "texto_resultado" =>
                $textoResultado
        ];
    }


    if (
        count(
            $salvos
        ) === 0
    ) {

        responder([
            "success" => false,
            "error" => "Nenhuma mídia pôde ser salva no histórico.",
            "pasta_historico" => $pastaUsuario,
            "arquivos_recebidos" =>
                array_keys(
                    $_FILES
                ),
            "ignorados" => $ignorados
        ], 400);
    }


    responder([
        "success" => true,
        "total_salvos" =>
            count(
                $salvos
            ),
        "pasta_historico" =>
            $pastaUsuario,
        "salvos" =>
            $salvos,
        "ignorados" =>
            $ignorados
    ]);


} catch (
    PDOException $erro
) {

    responder([
        "success" => false,
        "error" => "Erro ao salvar o histórico.",
        "detalhes" => $erro->getMessage()
    ], 500);
}
