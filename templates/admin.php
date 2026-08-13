<?php

session_start();

require_once "configs/config.php";


if (empty($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}


$idUsuarioAtual = (int) $_SESSION["usuario_id"];


/* Usuário atual */

$stmt = $pdo->prepare("
    SELECT
        id_usuario,
        nm_usuario,
        email_usuario,
        tp_usuario,
        dt_usuario
    FROM usuario
    WHERE id_usuario = ?
    LIMIT 1
");

$stmt->execute([
    $idUsuarioAtual
]);

$usuario = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$usuario) {

    session_destroy();

    header("Location: login.php");

    exit;
}


$ehAdmin =
    mb_strtolower(
        trim($usuario["tp_usuario"])
    ) === "administrador";


$TIPOS_VALIDOS = [
    "Usuário Simples",
    "Usuário Comunitário",
    "Administrador"
];


/*
 * AJAX interno para registrar o gesto
 * e o caminho do dataset gerado.
 */

if (
    isset($_GET["ajax"]) &&
    $_GET["ajax"] === "registrar_gesto"
) {

    header(
        "Content-Type: application/json; charset=utf-8"
    );

    if (!$ehAdmin) {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "error" => "Acesso negado."
        ]);

        exit;
    }

    if (
        $_SERVER["REQUEST_METHOD"] !== "POST"
    ) {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "error" => "Método inválido."
        ]);

        exit;
    }

    $nomeGesto = trim(
        $_POST["nome_gesto"]
        ?? ""
    );

    $datasetGesto = trim(
        $_POST["dataset"]
        ?? ""
    );

    if ($nomeGesto === "") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "error" => "Nome do gesto não informado."
        ]);

        exit;
    }

    if ($datasetGesto === "") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "error" => "Caminho do dataset não informado."
        ]);

        exit;
    }

    $datasetGesto = str_replace(
        "\\",
        "/",
        $datasetGesto
    );

    try {

        $stmt = $pdo->prepare("
            SELECT id_gesto
            FROM gesto
            WHERE LOWER(nm_gesto) = LOWER(?)
            LIMIT 1
        ");

        $stmt->execute([
            $nomeGesto
        ]);

        $idExistente =
            $stmt->fetchColumn();

        if ($idExistente) {

            $stmt = $pdo->prepare("
                UPDATE gesto
                SET
                    dataset = ?,
                    id_administrador = ?
                WHERE id_gesto = ?
            ");

            $stmt->execute([
                $datasetGesto,
                $idUsuarioAtual,
                (int) $idExistente
            ]);

            echo json_encode([
                "success" => true,
                "existing" => true,
                "id_gesto" => (int) $idExistente,
                "dataset" => $datasetGesto
            ]);

            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO gesto (
                nm_gesto,
                dataset,
                id_administrador
            )
            VALUES (
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $nomeGesto,
            $datasetGesto,
            $idUsuarioAtual
        ]);

        echo json_encode([
            "success" => true,
            "existing" => false,
            "id_gesto" => (int) $pdo->lastInsertId(),
            "dataset" => $datasetGesto
        ]);

    } catch (PDOException $erroBanco) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "error" => "Não foi possível registrar o gesto e o dataset."
        ]);
    }

    exit;
}


/*
 * AJAX interno para registrar os arquivos
 * gerados pelo treinamento do modelo.
 */

if (
    isset($_GET["ajax"]) &&
    $_GET["ajax"] === "registrar_modelo"
) {

    header(
        "Content-Type: application/json; charset=utf-8"
    );

    if (!$ehAdmin) {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "error" => "Acesso negado."
        ]);

        exit;
    }

    if (
        $_SERVER["REQUEST_METHOD"] !== "POST"
    ) {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "error" => "Método inválido."
        ]);

        exit;
    }

    $baseProjeto = dirname(__DIR__);

    $arquivosOrigem = [
        "labels" => [
            "absoluto" =>
                $baseProjeto
                .
                DIRECTORY_SEPARATOR
                .
                "labels.npy",
            "nome" =>
                "labels.npy"
        ],

        "modelo_gesto" => [
            "absoluto" =>
                $baseProjeto
                .
                DIRECTORY_SEPARATOR
                .
                "modelo_gestos.keras",
            "nome" =>
                "modelo_gestos.keras"
        ],

        "scaler_mean" => [
            "absoluto" =>
                $baseProjeto
                .
                DIRECTORY_SEPARATOR
                .
                "scaler_mean.npy",
            "nome" =>
                "scaler_mean.npy"
        ],

        "scaler_scale" => [
            "absoluto" =>
                $baseProjeto
                .
                DIRECTORY_SEPARATOR
                .
                "scaler_scale.npy",
            "nome" =>
                "scaler_scale.npy"
        ]
    ];

    $faltando = [];

    foreach (
        $arquivosOrigem
        as $dadosArquivo
    ) {

        if (
            !is_file(
                $dadosArquivo["absoluto"]
            )
        ) {

            $faltando[] =
                $dadosArquivo["nome"];
        }
    }

    if (!empty($faltando)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "error" => (
                "O treinamento foi concluído, mas alguns arquivos "
                . "do modelo não foram encontrados."
            ),
            "arquivos_faltando" => $faltando
        ]);

        exit;
    }

    $identificador =
        date("Ymd_His")
        .
        "_"
        .
        bin2hex(
            random_bytes(4)
        );

    $pastaRelativa =
        "modelos_traducao/"
        .
        $identificador;

    $pastaAbsoluta =
        $baseProjeto
        .
        DIRECTORY_SEPARATOR
        .
        str_replace(
            "/",
            DIRECTORY_SEPARATOR,
            $pastaRelativa
        );

    if (
        !is_dir(
            $pastaAbsoluta
        )
        &&
        !mkdir(
            $pastaAbsoluta,
            0777,
            true
        )
    ) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "error" => "Não foi possível criar a pasta da versão do modelo."
        ]);

        exit;
    }

    $arquivosSalvos = [];

    try {

        foreach (
            $arquivosOrigem
            as $campo => $dadosArquivo
        ) {

            $destino =
                $pastaAbsoluta
                .
                DIRECTORY_SEPARATOR
                .
                $dadosArquivo["nome"];

            if (
                !copy(
                    $dadosArquivo["absoluto"],
                    $destino
                )
            ) {

                throw new RuntimeException(
                    "Não foi possível salvar "
                    .
                    $dadosArquivo["nome"]
                    .
                    "."
                );
            }

            $arquivosSalvos[$campo] =
                $pastaRelativa
                .
                "/"
                .
                $dadosArquivo["nome"];
        }

        $pdo->beginTransaction();

        $temAtivo =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM modelo_traducao
                    WHERE ativo = 1
                ")
                ->fetchColumn();

        $novoAtivo =
            $temAtivo === 0
                ? 1
                : 0;

        $stmt = $pdo->prepare("
            INSERT INTO modelo_traducao (
                id_administrador,
                labels,
                modelo_gesto,
                scaler_mean,
                scaler_scale,
                ativo
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $idUsuarioAtual,
            $arquivosSalvos["labels"],
            $arquivosSalvos["modelo_gesto"],
            $arquivosSalvos["scaler_mean"],
            $arquivosSalvos["scaler_scale"],
            $novoAtivo
        ]);

        $idModelo =
            (int) $pdo->lastInsertId();

        if (
            $novoAtivo === 0
        ) {

            $stmtAtivo =
                $pdo->query("
                    SELECT
                        labels,
                        modelo_gesto,
                        scaler_mean,
                        scaler_scale
                    FROM modelo_traducao
                    WHERE ativo = 1
                    LIMIT 1
                ");

            $modeloAtivoAtual =
                $stmtAtivo->fetch(
                    PDO::FETCH_ASSOC
                );

            if (
                $modeloAtivoAtual
            ) {

                $mapaRestauracao = [
                    "labels" =>
                        "labels.npy",

                    "modelo_gesto" =>
                        "modelo_gestos.keras",

                    "scaler_mean" =>
                        "scaler_mean.npy",

                    "scaler_scale" =>
                        "scaler_scale.npy"
                ];

                foreach (
                    $mapaRestauracao
                    as $campo => $nomeDestino
                ) {

                    $origemAtiva =
                        $baseProjeto
                        .
                        DIRECTORY_SEPARATOR
                        .
                        str_replace(
                            "/",
                            DIRECTORY_SEPARATOR,
                            $modeloAtivoAtual[$campo]
                        );

                    $destinoAtivo =
                        $baseProjeto
                        .
                        DIRECTORY_SEPARATOR
                        .
                        $nomeDestino;

                    if (
                        !is_file(
                            $origemAtiva
                        )
                        ||
                        !copy(
                            $origemAtiva,
                            $destinoAtivo
                        )
                    ) {

                        throw new RuntimeException(
                            "Não foi possível restaurar o modelo atualmente ativo."
                        );
                    }
                }
            }

        } else {

            file_put_contents(
                $baseProjeto
                .
                DIRECTORY_SEPARATOR
                .
                "modelo_ativo.version",
                $idModelo
                .
                "|"
                .
                microtime(true)
            );
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "id_modelo" => $idModelo,
            "ativo" => $novoAtivo === 1,
            "arquivos" => $arquivosSalvos
        ]);

    } catch (Throwable $erroRegistro) {

        if (
            $pdo->inTransaction()
        ) {

            $pdo->rollBack();
        }

        if (
            is_dir(
                $pastaAbsoluta
            )
        ) {

            foreach (
                glob(
                    $pastaAbsoluta
                    .
                    DIRECTORY_SEPARATOR
                    .
                    "*"
                )
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

            @rmdir(
                $pastaAbsoluta
            );
        }

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "error" => $erroRegistro->getMessage()
        ]);
    }

    exit;
}


$erro = "";
$sucesso = "";


$abaAtiva =
    $_POST["aba_atual"]
    ??
    $_GET["aba"]
    ??
    "geral";


$ABAS_VALIDAS = [
    "geral",
    "gestos",
    "treinamento",
    "usuarios",
    "comunidade"
];


if (
    !in_array(
        $abaAtiva,
        $ABAS_VALIDAS,
        true
    )
) {

    $abaAtiva = "geral";
}


/* Ações administrativas */

if (
    $ehAdmin &&
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["acao"])
) {

    $acao = $_POST["acao"];


    /* Alterar tipo */

    if (
        $acao === "atualizar_tipo_usuario"
    ) {

        $idAlvo = (int) (
            $_POST["usuario_id"]
            ?? 0
        );

        $novoTipo = trim(
            $_POST["novo_tipo"]
            ?? ""
        );


        if ($idAlvo <= 0) {

            $erro =
                "Usuário inválido.";

        } elseif (
            !in_array(
                $novoTipo,
                $TIPOS_VALIDOS,
                true
            )
        ) {

            $erro =
                "Tipo de usuário inválido.";

        } else {

            $stmt = $pdo->prepare("
                SELECT
                    id_usuario,
                    nm_usuario,
                    tp_usuario
                FROM usuario
                WHERE id_usuario = ?
                LIMIT 1
            ");

            $stmt->execute([
                $idAlvo
            ]);

            $usuarioAlvo =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$usuarioAlvo) {

                $erro =
                    "Usuário não encontrado.";

            } elseif (
                $idAlvo === $idUsuarioAtual &&
                $novoTipo !== "Administrador"
            ) {

                $erro =
                    "Você não pode remover seu próprio acesso de administrador.";

            } elseif (
                $usuarioAlvo["tp_usuario"] ===
                $novoTipo
            ) {

                $sucesso =
                    "Nenhuma alteração foi necessária.";

            } else {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE usuario
                        SET tp_usuario = ?
                        WHERE id_usuario = ?
                    ");

                    $stmt->execute([
                        $novoTipo,
                        $idAlvo
                    ]);


                    if (
                        $stmt->rowCount() > 0
                    ) {

                        $sucesso =
                            "O cargo de "
                            . $usuarioAlvo["nm_usuario"]
                            . " foi atualizado para "
                            . $novoTipo
                            . ".";

                    } else {

                        $erro =
                            "Não foi possível atualizar o cargo.";
                    }

                } catch (PDOException $erroBanco) {

                    $erro =
                        "Ocorreu um erro ao atualizar o cargo do usuário.";
                }
            }
        }


        $abaAtiva =
            "usuarios";
    }


    /* Excluir usuário */ elseif (
        $acao === "excluir_usuario"
    ) {

        $idAlvo = (int) (
            $_POST["usuario_id"]
            ?? 0
        );


        if ($idAlvo <= 0) {

            $erro =
                "Usuário inválido.";

        } elseif (
            $idAlvo === $idUsuarioAtual
        ) {

            $erro =
                "Você não pode excluir a própria conta.";

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM usuario
                    WHERE id_usuario = ?
                ");

                $stmt->execute([
                    $idAlvo
                ]);


                if (
                    $stmt->rowCount() > 0
                ) {

                    $sucesso =
                        "Usuário removido.";

                } else {

                    $erro =
                        "Usuário não encontrado.";
                }


            } catch (PDOException $erroBanco) {

                $erro =
                    "Não foi possível excluir o usuário.";
            }
        }


        $abaAtiva =
            "usuarios";
    }


    elseif (
        $acao === "ativar_modelo"
    ) {

        $idModelo =
            (int) (
                $_POST["modelo_id"]
                ?? 0
            );


        if (
            $idModelo <= 0
        ) {

            $erro =
                "Modelo inválido.";

        } else {

            try {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            id_modelo,
                            labels,
                            modelo_gesto,
                            scaler_mean,
                            scaler_scale,
                            ativo
                        FROM modelo_traducao
                        WHERE id_modelo = ?
                        LIMIT 1
                    ");


                $stmt->execute([
                    $idModelo
                ]);


                $modeloSelecionado =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (
                    !$modeloSelecionado
                ) {

                    $erro =
                        "Modelo não encontrado.";

                } elseif (
                    (int) $modeloSelecionado[
                        "ativo"
                    ] === 1
                ) {

                    $sucesso =
                        "Este modelo já está em uso.";

                } else {

                    $baseProjeto =
                        dirname(
                            __DIR__
                        );


                    $mapaArquivos = [

                        "labels" =>
                            "labels.npy",

                        "modelo_gesto" =>
                            "modelo_gestos.keras",

                        "scaler_mean" =>
                            "scaler_mean.npy",

                        "scaler_scale" =>
                            "scaler_scale.npy"

                    ];


                    foreach (
                        $mapaArquivos
                        as $campo => $nomeDestino
                    ) {

                        $origem =
                            $baseProjeto
                            .
                            DIRECTORY_SEPARATOR
                            .
                            str_replace(
                                "/",
                                DIRECTORY_SEPARATOR,
                                $modeloSelecionado[
                                    $campo
                                ]
                            );


                        if (
                            !is_file(
                                $origem
                            )
                        ) {

                            throw new RuntimeException(
                                "O arquivo "
                                .
                                basename(
                                    $modeloSelecionado[
                                        $campo
                                    ]
                                )
                                .
                                " não foi encontrado para este modelo."
                            );
                        }
                    }


                    foreach (
                        $mapaArquivos
                        as $campo => $nomeDestino
                    ) {

                        $origem =
                            $baseProjeto
                            .
                            DIRECTORY_SEPARATOR
                            .
                            str_replace(
                                "/",
                                DIRECTORY_SEPARATOR,
                                $modeloSelecionado[
                                    $campo
                                ]
                            );


                        $destino =
                            $baseProjeto
                            .
                            DIRECTORY_SEPARATOR
                            .
                            $nomeDestino;


                        if (
                            !copy(
                                $origem,
                                $destino
                            )
                        ) {

                            throw new RuntimeException(
                                "Não foi possível ativar os arquivos do modelo."
                            );
                        }
                    }


                    $pdo->beginTransaction();


                    $pdo->exec("
                        UPDATE modelo_traducao
                        SET ativo = 0
                    ");


                    $stmt =
                        $pdo->prepare("
                            UPDATE modelo_traducao
                            SET ativo = 1
                            WHERE id_modelo = ?
                        ");


                    $stmt->execute([
                        $idModelo
                    ]);


                    file_put_contents(
                        $baseProjeto
                        .
                        DIRECTORY_SEPARATOR
                        .
                        "modelo_ativo.version",
                        $idModelo
                        .
                        "|"
                        .
                        microtime(
                            true
                        )
                    );


                    $pdo->commit();


                    $sucesso =
                        "Modelo #"
                        .
                        $idModelo
                        .
                        " definido como modelo de tradução em uso.";
                }


            } catch (
                Throwable $erroModelo
            ) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }


                $erro =
                    $erroModelo->getMessage();
            }
        }


        $abaAtiva =
            "treinamento";
    }


    elseif (
        $acao === "excluir_modelo"
    ) {

        $idModelo =
            (int) (
                $_POST["modelo_id"]
                ?? 0
            );


        if (
            $idModelo <= 0
        ) {

            $erro =
                "Modelo inválido.";

        } else {

            try {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            id_modelo,
                            labels,
                            modelo_gesto,
                            scaler_mean,
                            scaler_scale,
                            ativo
                        FROM modelo_traducao
                        WHERE id_modelo = ?
                        LIMIT 1
                    ");


                $stmt->execute([
                    $idModelo
                ]);


                $modeloExcluir =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (
                    !$modeloExcluir
                ) {

                    $erro =
                        "Modelo não encontrado.";

                } elseif (
                    (int) $modeloExcluir[
                        "ativo"
                    ] === 1
                ) {

                    $erro =
                        "O modelo em uso não pode ser excluído. Ative outro modelo primeiro.";

                } else {

                    $stmt =
                        $pdo->prepare("
                            DELETE FROM modelo_traducao
                            WHERE id_modelo = ?
                        ");


                    $stmt->execute([
                        $idModelo
                    ]);


                    if (
                        $stmt->rowCount() === 0
                    ) {

                        throw new RuntimeException(
                            "Não foi possível excluir o modelo."
                        );
                    }


                    $baseProjeto =
                        dirname(
                            __DIR__
                        );


                    $pastasRemover = [];


                    foreach (
                        [
                            "labels",
                            "modelo_gesto",
                            "scaler_mean",
                            "scaler_scale"
                        ]
                        as $campo
                    ) {

                        $relativo =
                            str_replace(
                                "\\",
                                "/",
                                trim(
                                    (string) $modeloExcluir[
                                        $campo
                                    ]
                                )
                            );


                        if (
                            !str_starts_with(
                                $relativo,
                                "modelos_traducao/"
                            )
                        ) {

                            continue;
                        }


                        $arquivo =
                            $baseProjeto
                            .
                            DIRECTORY_SEPARATOR
                            .
                            str_replace(
                                "/",
                                DIRECTORY_SEPARATOR,
                                $relativo
                            );


                        if (
                            is_file(
                                $arquivo
                            )
                        ) {

                            @unlink(
                                $arquivo
                            );
                        }


                        $pastasRemover[] =
                            dirname(
                                $arquivo
                            );
                    }


                    foreach (
                        array_unique(
                            $pastasRemover
                        )
                        as $pastaRemover
                    ) {

                        if (
                            is_dir(
                                $pastaRemover
                            )
                        ) {

                            @rmdir(
                                $pastaRemover
                            );
                        }
                    }


                    $sucesso =
                        "Modelo #"
                        .
                        $idModelo
                        .
                        " excluído.";
                }


            } catch (
                Throwable $erroModelo
            ) {

                $erro =
                    $erroModelo->getMessage();
            }
        }


        $abaAtiva =
            "treinamento";
    }


    /* Excluir gesto */ elseif (
        $acao === "excluir_gesto"
    ) {

        $idGesto = (int) (
            $_POST["gesto_id"]
            ?? 0
        );


        if ($idGesto <= 0) {

            $erro =
                "Gesto inválido.";

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM gesto
                    WHERE id_gesto = ?
                ");

                $stmt->execute([
                    $idGesto
                ]);


                if (
                    $stmt->rowCount() > 0
                ) {

                    $sucesso =
                        "Gesto removido.";

                } else {

                    $erro =
                        "Gesto não encontrado.";
                }


            } catch (PDOException $erroBanco) {

                $erro =
                    "Não foi possível remover o gesto. "
                    . "Ele pode estar relacionado ao histórico.";
            }
        }


        $abaAtiva =
            "gestos";
    }
}


/* Totais */

$totalUsuarios = 0;
$totalUsuariosComunitarios = 0;
$totalGestos = 0;
$totalComunidades = 0;
$totalModelos = 0;
$totalParticipacoes = 0;


/* Listas */

$usuariosLista = [];
$gestosLista = [];
$comunidadesLista = [];
$modelosLista = [];


$totaisPorTipo = [
    "Usuário Simples" => 0,
    "Usuário Comunitário" => 0,
    "Administrador" => 0
];


if ($ehAdmin) {


    /* Usuários */

    $totalUsuarios =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM usuario
            ")
            ->fetchColumn();


    $usuariosLista =
        $pdo
            ->query("
                SELECT
                    id_usuario,
                    nm_usuario,
                    email_usuario,
                    tp_usuario,
                    dt_usuario
                FROM usuario
                ORDER BY
                    dt_usuario DESC
            ")
            ->fetchAll(
                PDO::FETCH_ASSOC
            );


    foreach (
        $usuariosLista
        as $itemUsuario
    ) {

        $tipo =
            $itemUsuario["tp_usuario"];


        if (
            isset(
            $totaisPorTipo[$tipo]
        )
        ) {

            $totaisPorTipo[
                $tipo
            ]++;
        }
    }


    $totalUsuariosComunitarios =
        $totaisPorTipo[
            "Usuário Comunitário"
        ];


    /* Gestos */

    $totalGestos =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM gesto
            ")
            ->fetchColumn();


    $gestosLista =
        $pdo
            ->query("
                SELECT
                    g.id_gesto,
                    g.nm_gesto,
                    g.dataset,
                    g.id_administrador,

                    u.nm_usuario
                        AS administrador

                FROM gesto g

                INNER JOIN usuario u
                    ON
                    u.id_usuario =
                    g.id_administrador

                ORDER BY
                    g.nm_gesto ASC
            ")
            ->fetchAll(
                PDO::FETCH_ASSOC
            );


    /* Comunidades */

    $totalComunidades =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM comunidade
            ")
            ->fetchColumn();


    $comunidadesLista =
        $pdo
            ->query("
                SELECT
                    c.id_comunidade,
                    c.id_usuario,
                    c.nm_comunidade,
                    c.ds_comunidade,
                    c.dt_criacao,

                    u.nm_usuario
                        AS criador,

                    (
                        SELECT COUNT(*)

                        FROM usuario_comunidade uc

                        WHERE
                            uc.id_comunidade =
                            c.id_comunidade
                    )
                        AS total_membros

                FROM comunidade c

                INNER JOIN usuario u
                    ON
                    u.id_usuario =
                    c.id_usuario

                ORDER BY
                    c.dt_criacao DESC
            ")
            ->fetchAll(
                PDO::FETCH_ASSOC
            );


    foreach (
        $comunidadesLista
        as $comunidade
    ) {

        $totalParticipacoes +=
            (int) $comunidade[
                "total_membros"
            ];
    }


    /* Modelos */

    $totalModelos =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM modelo_traducao
            ")
            ->fetchColumn();


    $modelosLista =
        $pdo
            ->query("
                SELECT
                    m.id_modelo,
                    m.id_administrador,
                    m.dt_modelo,
                    m.labels,
                    m.modelo_gesto,
                    m.scaler_mean,
                    m.scaler_scale,
                    m.ativo,

                    u.nm_usuario
                        AS administrador

                FROM modelo_traducao m

                INNER JOIN usuario u
                    ON
                    u.id_usuario =
                    m.id_administrador

                ORDER BY
                    m.dt_modelo DESC
            ")
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
}


$nomeUsuario =
    $usuario["nm_usuario"];


$inicial =
    strtoupper(
        function_exists("mb_substr")
        ? mb_substr(
            $nomeUsuario,
            0,
            1
        )
        : substr(
            $nomeUsuario,
            0,
            1
        )
    );

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        LibrasHub - Administração
    </title>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">


    <link rel="stylesheet" href="../static/css/style.css">


    <script>

        (function () {

            try {

                const KEYS = {
                    theme: "libras_theme",
                    fontSize: "libras_fontsize",
                    contrast: "libras_contrast"
                };

                const FONT_SCALES = {
                    pequena: 0.9,
                    media: 1,
                    grande: 1.15
                };


                function safeGet(
                    chave,
                    padrao
                ) {

                    try {

                        const valor =
                            localStorage.getItem(
                                chave
                            );

                        return valor !== null
                            ? valor
                            : padrao;

                    } catch (erro) {

                        return padrao;
                    }
                }


                const tema =
                    safeGet(
                        KEYS.theme,
                        "claro"
                    );


                let temaEfetivo =
                    tema;


                if (
                    tema === "automatico"
                ) {

                    temaEfetivo =
                        (
                            window.matchMedia &&
                            window.matchMedia(
                                "(prefers-color-scheme: dark)"
                            ).matches
                        )
                            ? "escuro"
                            : "claro";
                }


                if (
                    temaEfetivo === "escuro"
                ) {

                    document.documentElement
                        .setAttribute(
                            "data-theme",
                            "dark"
                        );
                }


                document.documentElement
                    .style
                    .setProperty(
                        "--font-scale",

                        FONT_SCALES[
                        safeGet(
                            KEYS.fontSize,
                            "media"
                        )
                        ] || 1
                    );


                if (
                    safeGet(
                        KEYS.contrast,
                        "off"
                    ) === "on"
                ) {

                    document.documentElement
                        .classList
                        .add(
                            "high-contrast"
                        );
                }


            } catch (erro) {

                console.error(
                    erro
                );
            }

        })();

    </script>

</head>


<body>


    <aside class="sidebar">


        <div class="sidebar-top">


            <div class="logo">

                <img src="../static/images/librashub-logo.png" alt="LibrasHub" class="logo-img">

                LibrasHub

            </div>


            <a class="nav-item" href="home.php" data-page="home">

                <span class="nav-icon">

                    <i class="fa-regular fa-house"></i>

                </span>

                Início

            </a>


            <a class="nav-item" href="leitor.php" data-page="leitor">

                <span class="nav-icon">

                    <i class="fa-solid fa-video"></i>

                </span>

                Leitor

            </a>


            <a class="nav-item" href="upload.php" data-page="upload">

                <span class="nav-icon">

                    <i class="fa-solid fa-upload"></i>

                </span>

                Upload

            </a>


            <a class="nav-item" href="historico.php" data-page="historico">

                <span class="nav-icon">

                    <i class="fa-solid fa-arrow-rotate-left"></i>

                </span>

                Histórico

            </a>


            <a class="nav-item" href="ajuda.php" data-page="ajuda">

                <span class="nav-icon">

                    <i class="fa-solid fa-question"></i>

                </span>

                Ajuda

            </a>


            <a class="nav-item" href="comunidade.php" data-page="comunidade">

                <span class="nav-icon">

                    <i class="fa-solid fa-users"></i>

                </span>

                Comunidade

            </a>


            <?php if ($ehAdmin): ?>

                <a class="nav-item" href="admin.php" data-page="admin">

                    <span class="nav-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </span>

                    Administração

                </a>

            <?php endif; ?>


        </div>


        <div class="sidebar-bottom">


            <a class="nav-item" href="configuracoes.php" data-page="configuracoes">

                <span class="nav-icon">

                    <i class="fa-solid fa-gear"></i>

                </span>

                Configurações

            </a>


            <a class="nav-item" href="usuario.php" data-page="usuario">

                <span class="nav-icon">

                    <i class="fa-solid fa-user"></i>

                </span>

                Usuário

            </a>


        </div>


    </aside>



    <main class="content">


        <?php if (!$ehAdmin): ?>


            <div class="page-title">

                Acesso restrito

            </div>


            <div class="page-subtitle">

                Esta área é exclusiva para administradores.

            </div>


            <div class="alert alert-error">

                <span aria-hidden="true">

                    ⚠

                </span>

                <span>

                    Sua conta (

                    <?= htmlspecialchars(
                        $usuario["tp_usuario"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                    ) não possui permissão para acessar
                    o painel administrativo.

                </span>

            </div>


        <?php else: ?>


            <div class="admin-header">


                <div class="avatar-md">

                    <?= htmlspecialchars(
                        $inicial,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>


                <div>

                    <div class="page-title">

                        Painel de Administração

                    </div>


                    <div class="page-subtitle">

                        Logado como

                        <?= htmlspecialchars(
                            $usuario["nm_usuario"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                </div>


            </div>



            <?php if ($erro !== ""): ?>

                <div class="alert alert-error">

                    <span aria-hidden="true">
                        ⚠
                    </span>

                    <span>

                        <?= htmlspecialchars(
                            $erro,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <?php if ($sucesso !== ""): ?>

                <div class="alert alert-success">

                    <span aria-hidden="true">
                        ✓
                    </span>

                    <span>

                        <?= htmlspecialchars(
                            $sucesso,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>



            <!-- Abas -->

            <div class="admin-tabs">

                <button type="button" class="admin-tab-btn" data-tab="geral">
                    Visão Geral
                </button>

                <button type="button" class="admin-tab-btn" data-tab="gestos">
                    Gerenciamento de Gestos
                </button>

                <button type="button" class="admin-tab-btn" data-tab="treinamento">
                    Treinamento e Modelos
                </button>

                <button type="button" class="admin-tab-btn" data-tab="usuarios">
                    Usuários
                </button>

                <button type="button" class="admin-tab-btn" data-tab="comunidade">
                    Comunidade
                </button>

            </div>



            <!-- Visão geral -->

            <section class="admin-tab" id="tab-geral">


                <div class="stat-grid">


                    <article class="stat-card">

                        <div class="num">

                            <?= $totalUsuarios ?>

                        </div>

                        <div class="label">

                            Usuários cadastrados

                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="num">

                            <?= $totalUsuariosComunitarios ?>

                        </div>

                        <div class="label">

                            Usuários comunitários

                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="num">

                            <?= $totalGestos ?>

                        </div>

                        <div class="label">

                            Gestos cadastrados

                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="num">

                            <?= $totalModelos ?>

                        </div>

                        <div class="label">

                            Modelos treinados

                        </div>

                    </article>


                </div>



                <div class="admin-grid">


                    <article class="admin-card">


                        <div class="admin-card-title">

                            Distribuição de usuários

                        </div>


                        <?php foreach (
                            $totaisPorTipo
                            as $tipo => $quantidade
                        ): ?>


                            <div class="list-item">

                                <span>

                                    <?= htmlspecialchars(
                                        $tipo,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>

                                <strong>

                                    <?= (int) $quantidade ?>

                                </strong>

                            </div>


                        <?php endforeach; ?>


                    </article>



                    <article class="admin-card">


                        <div class="admin-card-title">

                            Sistema

                        </div>


                        <div class="list-item">

                            <span>
                                Gestos
                            </span>

                            <strong>
                                <?= $totalGestos ?>
                            </strong>

                        </div>


                        <div class="list-item">

                            <span>
                                Modelos
                            </span>

                            <strong>
                                <?= $totalModelos ?>
                            </strong>

                        </div>


                        <div class="list-item">

                            <span>
                                Comunidades
                            </span>

                            <strong>
                                <?= $totalComunidades ?>
                            </strong>

                        </div>


                        <div class="list-item">

                            <span>
                                Participações em comunidades
                            </span>

                            <strong>
                                <?= $totalParticipacoes ?>
                            </strong>

                        </div>


                    </article>


                </div>


            </section>



            <!-- Gerenciamento de Gestos -->

            <section class="admin-tab" id="tab-gestos">

                <div class="admin-unified-stack">

                    <div class="admin-section">


                        <div class="section-heading">


                            <div>

                                <span class="section-step">

                                    Dataset

                                </span>


                                <h2>

                                    Cadastro de Dataset

                                </h2>


                                <p>

                                    Envie fotos ou vídeos referentes
                                    a um gesto para gerar o dataset
                                    processado utilizado no treinamento.

                                </p>

                            </div>


                        </div>



                        <form id="uploadForm" enctype="multipart/form-data">


                            <div class="form-group">


                                <label for="datasetNome">

                                    Nome do gesto

                                </label>


                                <input type="text" id="datasetNome" name="dataset" class="dataset-input"
                                    placeholder="Ex.: obrigado, casa, bom_dia..." autocomplete="off" required>


                                <small>

                                    O gesto será cadastrado no banco
                                    somente após o processamento
                                    do dataset terminar com sucesso.

                                </small>


                            </div>



                            <div class="upload-area" id="dropZone">


                                <input type="file" id="fileInput" accept="image/*,video/*" multiple hidden>


                                <div class="upload-info" id="datasetUploadInfo">


                                    <div class="upload-icon">

                                        <i class="fa-solid fa-cloud-arrow-up"></i>

                                    </div>


                                    <p>

                                        Arraste fotos e vídeos aqui

                                    </p>


                                    <span>

                                        ou

                                    </span>


                                    <button type="button" id="btnSelecionarArquivos" class="btn-secondary">

                                        Selecionar arquivos

                                    </button>


                                </div>



                                <div id="preview" class="preview-area" hidden>


                                    <div class="preview-header">


                                        <strong>

                                            Arquivos selecionados

                                        </strong>


                                        <span id="contadorArquivos">

                                            0 arquivos

                                        </span>


                                    </div>


                                    <div id="listaArquivos" class="lista-arquivos"></div>


                                </div>


                            </div>



                            <div class="status-grid">


                                <div class="status-display" id="statusDataset">

                                    Nenhum arquivo selecionado.

                                </div>


                                <div class="status-display" id="resultTextDataset">

                                    Aguardando envio...

                                </div>


                            </div>



                            <div class="acoes-upload">


                                <button type="submit" id="btnEnviarDataset" class="btn-primary">

                                    Enviar e processar dataset

                                </button>


                                <button type="button" id="btnLimparDataset" class="btn-danger-outline">

                                    Limpar seleção

                                </button>


                            </div>


                        </form>


                    </div>

                    <div class="admin-card">


                        <div class="section-header">


                            <div class="section-title">

                                Gestos cadastrados

                            </div>


                            <div class="count-badge">

                                <?= count(
                                    $gestosLista
                                ) ?>

                            </div>


                        </div>



                        <div class="gesture-list">


                            <?php if (
                                empty(
                                $gestosLista
                            )
                            ): ?>


                                <div class="empty-state">

                                    Nenhum gesto cadastrado.

                                </div>


                            <?php endif; ?>



                            <?php foreach (
                                $gestosLista
                                as $gesto
                            ): ?>


                                <article class="gesture-card">


                                    <div class="gesture-icon">

                                        <i class="fa-solid fa-hands"></i>

                                    </div>


                                    <div class="gesture-info">


                                        <div class="gesture-name">

                                            <?= htmlspecialchars(
                                                $gesto["nm_gesto"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                        <div class="gesture-meta">

                                            Cadastrado por

                                            <?= htmlspecialchars(
                                                $gesto["administrador"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                        <div class="gesture-meta">

                                            Dataset:

                                            <?php if (
                                                !empty(
                                                $gesto["dataset"]
                                            )
                                            ): ?>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $gesto["dataset"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </strong>

                                            <?php else: ?>

                                                <span class="muted">
                                                    Não informado
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                    </div>



                                    <form method="POST" action="admin.php" onsubmit="
                                return confirm(
                                    'Deseja remover este gesto?'
                                );
                            ">


                                        <input type="hidden" name="acao" value="excluir_gesto">


                                        <input type="hidden" name="aba_atual" value="gestos">


                                        <input type="hidden" name="gesto_id" value="<?= (int) $gesto["id_gesto"] ?>">


                                        <button type="submit" class="icon-btn-sm" title="Excluir gesto">

                                            <i class="fa-solid fa-trash"></i>

                                        </button>


                                    </form>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    </div>

                </div>

            </section>



            <!-- Treinamento e Modelos -->

            <section class="admin-tab" id="tab-treinamento">

                <div class="admin-unified-stack">

                    <div class="admin-section">


                        <div class="section-heading">


                            <div>


                                <span class="section-step">

                                    Modelo

                                </span>


                                <h2>

                                    Treinamento do Modelo

                                </h2>


                                <p>

                                    Inicie o treinamento e acompanhe
                                    o progresso, acurácia, perda e
                                    mensagens produzidas pelo processo.

                                </p>


                            </div>



                            <div class="training-actions">


                                <button type="button" id="btnTreinarModelo" class="btn-training">

                                    Iniciar treinamento

                                </button>


                                <button type="button" id="btnCancelarTreino" class="btn-danger-outline" disabled>

                                    Cancelar

                                </button>


                            </div>


                        </div>



                        <div class="training-progress-card">


                            <div class="training-progress-header">


                                <div>


                                    <span class="training-progress-label">

                                        Progresso

                                    </span>


                                    <strong id="trainingStatus">

                                        Aguardando início

                                    </strong>


                                </div>


                                <span id="trainingPercent">

                                    0%

                                </span>


                            </div>



                            <div class="progress-track" id="trainingProgressBar" role="progressbar" aria-valuemin="0"
                                aria-valuemax="100" aria-valuenow="0">

                                <div class="progress-fill" id="trainingProgressFill"></div>

                            </div>


                        </div>



                        <div class="metrics-grid">


                            <article class="metric-card">


                                <span class="metric-label">

                                    Época

                                </span>


                                <strong class="metric-value" id="metricEpoch">

                                    0 / 0

                                </strong>


                                <small>

                                    Época atual

                                </small>


                            </article>



                            <article class="metric-card">


                                <span class="metric-label">

                                    Acurácia

                                </span>


                                <strong class="metric-value" id="metricAccuracy">

                                    0,00%

                                </strong>


                                <small>

                                    Treinamento

                                </small>


                            </article>



                            <article class="metric-card">


                                <span class="metric-label">

                                    Acurácia de validação

                                </span>


                                <strong class="metric-value" id="metricValAccuracy">

                                    0,00%

                                </strong>


                                <small>

                                    Validação

                                </small>


                            </article>



                            <article class="metric-card">


                                <span class="metric-label">

                                    Perda

                                </span>


                                <strong class="metric-value" id="metricLoss">

                                    0,0000

                                </strong>


                                <small>

                                    Erro atual

                                </small>


                            </article>


                        </div>



                        <div class="charts-grid">


                            <article class="chart-card">


                                <div class="chart-card-header">


                                    <div>


                                        <h3>

                                            Acurácia por época

                                        </h3>


                                        <p>

                                            Treinamento e validação

                                        </p>


                                    </div>


                                    <div class="chart-legend">


                                        <span>

                                            <i class="legend-line"></i>

                                            Treinamento

                                        </span>


                                        <span>

                                            <i class="legend-line legend-validation"></i>

                                            Validação

                                        </span>


                                    </div>


                                </div>



                                <div class="chart-wrapper">


                                    <canvas id="accuracyChart"></canvas>


                                    <div class="chart-empty" id="accuracyChartEmpty">

                                        O gráfico será exibido
                                        durante o treinamento.

                                    </div>


                                </div>


                            </article>



                            <article class="chart-card">


                                <div class="chart-card-header">


                                    <div>


                                        <h3>

                                            Perda por época

                                        </h3>


                                        <p>

                                            Treinamento e validação

                                        </p>


                                    </div>


                                </div>



                                <div class="chart-wrapper">


                                    <canvas id="lossChart"></canvas>


                                    <div class="chart-empty" id="lossChartEmpty">

                                        O gráfico será exibido
                                        durante o treinamento.

                                    </div>


                                </div>


                            </article>


                        </div>



                        <article class="training-log-card">


                            <div class="training-log-header">


                                <div>


                                    <h3>

                                        Relatório de execução

                                    </h3>


                                    <p>

                                        Informações retornadas
                                        durante o treinamento.

                                    </p>


                                </div>


                                <button type="button" id="btnLimparLog" class="btn-small">

                                    Limpar relatório

                                </button>


                            </div>


                            <textarea id="trainingLog" class="training-log" readonly
                                spellcheck="false">Aguardando o início do treinamento...</textarea>


                        </article>


                    </div>

                    <div class="section-header">


                        <div class="section-title">

                            Histórico de modelos

                        </div>


                        <div class="count-badge">

                            <?= count(
                                $modelosLista
                            ) ?>

                        </div>


                    </div>



                    <?php if (
                        empty(
                        $modelosLista
                    )
                    ): ?>


                        <div class="admin-card">


                            <div class="empty-state">

                                Nenhum modelo registrado.

                            </div>


                        </div>


                    <?php endif; ?>



                    <?php foreach (
                        $modelosLista
                        as $modelo
                    ): ?>


                        <article class="model-card">


                            <div class="model-head">


                                <div>


                                    <div class="model-id">

                                        Modelo #

                                        <?= (int) $modelo[
                                            "id_modelo"
                                        ] ?>

                                    </div>


                                    <?php if (
                                        (int) $modelo["ativo"] === 1
                                    ): ?>

                                        <div
                                            class="count-badge"
                                            style="
                                                margin-top:8px;
                                                display:inline-flex;
                                                align-items:center;
                                                gap:6px;
                                            "
                                        >
                                            <i class="fa-solid fa-circle-check"></i>
                                            Em uso
                                        </div>

                                    <?php endif; ?>


                                    <div class="model-date">

                                        Treinado por

                                        <?= htmlspecialchars(
                                            $modelo["administrador"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </div>


                                </div>


                                <div class="model-date">

                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $modelo["dt_modelo"]
                                        )
                                    ) ?>

                                </div>


                            </div>



                            <div class="model-files">


                                <?php

                                $arquivosModelo = [

                                    "Labels" =>
                                        $modelo["labels"],

                                    "Modelo" =>
                                        $modelo["modelo_gesto"],

                                    "Scaler Mean" =>
                                        $modelo["scaler_mean"],

                                    "Scaler Scale" =>
                                        $modelo["scaler_scale"]
                                ];

                                ?>


                                <?php foreach (
                                    $arquivosModelo
                                    as $titulo => $arquivo
                                ): ?>


                                    <div class="model-file">


                                        <div class="model-file-label">

                                            <?= htmlspecialchars(
                                                $titulo,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                        <div class="model-file-value" title="<?= htmlspecialchars(
                                            $arquivo,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>">

                                            <?= htmlspecialchars(
                                                basename(
                                                    str_replace(
                                                        "\\",
                                                        "/",
                                                        $arquivo
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                            <div
                                class="training-actions"
                                style="
                                    margin-top:16px;
                                    justify-content:flex-end;
                                    flex-wrap:wrap;
                                "
                            >


                                <?php if (
                                    (int) $modelo["ativo"] !== 1
                                ): ?>

                                    <form
                                        method="POST"
                                        action="admin.php"
                                        onsubmit="
                                            return confirm(
                                                'Deseja usar este modelo nas traduções?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="ativar_modelo"
                                        >

                                        <input
                                            type="hidden"
                                            name="aba_atual"
                                            value="treinamento"
                                        >

                                        <input
                                            type="hidden"
                                            name="modelo_id"
                                            value="<?= (int) $modelo["id_modelo"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-primary"
                                        >
                                            <i class="fa-solid fa-rotate"></i>
                                            Usar este modelo
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        action="admin.php"
                                        onsubmit="
                                            return confirm(
                                                'Deseja excluir permanentemente este modelo e seus arquivos?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir_modelo"
                                        >

                                        <input
                                            type="hidden"
                                            name="aba_atual"
                                            value="treinamento"
                                        >

                                        <input
                                            type="hidden"
                                            name="modelo_id"
                                            value="<?= (int) $modelo["id_modelo"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-danger-outline"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            Excluir modelo
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="btn-secondary"
                                        disabled
                                    >
                                        <i class="fa-solid fa-circle-check"></i>
                                        Modelo em uso
                                    </button>

                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endforeach; ?>

                </div>

            </section>



            <!-- Usuários -->

            <section class="admin-tab" id="tab-usuarios">


                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input type="text" id="buscaUsuarios" placeholder="Buscar por nome ou email" autocomplete="off">

                </div>


                <div class="admin-card">


                    <div class="section-header">

                        <div class="section-title">

                            Usuários cadastrados

                        </div>

                        <div class="count-badge">

                            <?= count(
                                $usuariosLista
                            ) ?>

                        </div>

                    </div>


                    <div class="table-wrapper">


                        <table class="admin-table" id="tabelaUsuarios">


                            <thead>

                                <tr>

                                    <th>
                                        Nome
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Cargo
                                    </th>

                                    <th>
                                        Desde
                                    </th>

                                    <th>
                                        Ações
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $usuariosLista
                                    as $itemUsuario
                                ): ?>


                                    <tr>


                                        <td>

                                            <?= htmlspecialchars(
                                                $itemUsuario["nm_usuario"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                            <?php if (
                                                (int) $itemUsuario["id_usuario"]
                                                ===
                                                $idUsuarioAtual
                                            ): ?>

                                                <span class="muted">
                                                    (Você)
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $itemUsuario["email_usuario"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </td>


                                        <td>


                                            <form method="POST" action="admin.php" class="user-type-form" data-cargo-atual="<?= htmlspecialchars(
                                                $itemUsuario["tp_usuario"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>">


                                                <input type="hidden" name="acao" value="atualizar_tipo_usuario">


                                                <input type="hidden" name="aba_atual" value="usuarios">


                                                <input type="hidden" name="usuario_id"
                                                    value="<?= (int) $itemUsuario["id_usuario"] ?>">


                                                <select name="novo_tipo" class="select-cargo" <?= (
                                                    (int) $itemUsuario["id_usuario"]
                                                    ===
                                                    $idUsuarioAtual
                                                )
                                                    ? "disabled"
                                                    : ""
                                                    ?>>


                                                    <?php foreach (
                                                        $TIPOS_VALIDOS
                                                        as $tipo
                                                    ): ?>


                                                        <option value="<?= htmlspecialchars(
                                                            $tipo,
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>" <?= (
                                                             $itemUsuario["tp_usuario"]
                                                             ===
                                                             $tipo
                                                         )
                                                             ? "selected"
                                                             : ""
                                                             ?>>

                                                            <?= htmlspecialchars(
                                                                $tipo,
                                                                ENT_QUOTES,
                                                                "UTF-8"
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                                <?php if (
                                                    (int) $itemUsuario["id_usuario"]
                                                    !==
                                                    $idUsuarioAtual
                                                ): ?>


                                                    <button type="submit" class="btn-primary btn-salvar-cargo" disabled>

                                                        <i class="fa-solid fa-floppy-disk"></i>

                                                        Salvar alteração

                                                    </button>


                                                <?php endif; ?>


                                            </form>


                                        </td>


                                        <td>

                                            <?= date(
                                                "d/m/Y",
                                                strtotime(
                                                    $itemUsuario["dt_usuario"]
                                                )
                                            ) ?>

                                        </td>


                                        <td>


                                            <?php if (
                                                (int) $itemUsuario["id_usuario"]
                                                !==
                                                $idUsuarioAtual
                                            ): ?>


                                                <form method="POST" action="admin.php" onsubmit="
                                            return confirm(
                                                'Excluir esta conta permanentemente?'
                                            );
                                        ">


                                                    <input type="hidden" name="acao" value="excluir_usuario">


                                                    <input type="hidden" name="aba_atual" value="usuarios">


                                                    <input type="hidden" name="usuario_id"
                                                        value="<?= (int) $itemUsuario["id_usuario"] ?>">


                                                    <button type="submit" class="icon-btn-sm" title="Excluir usuário">

                                                        <i class="fa-solid fa-trash"></i>

                                                    </button>


                                                </form>


                                            <?php else: ?>


                                                <span class="muted">

                                                    Conta atual

                                                </span>


                                            <?php endif; ?>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            </section>



            <!-- Comunidade -->

            <section class="admin-tab" id="tab-comunidade">


                <div class="stat-grid">


                    <article class="stat-card">


                        <div class="num">

                            <?= $totalComunidades ?>

                        </div>


                        <div class="label">

                            Comunidades

                        </div>


                    </article>



                    <article class="stat-card">


                        <div class="num">

                            <?= $totalUsuariosComunitarios ?>

                        </div>


                        <div class="label">

                            Usuários comunitários

                        </div>


                    </article>



                    <article class="stat-card">


                        <div class="num">

                            <?= $totalParticipacoes ?>

                        </div>


                        <div class="label">

                            Participações

                        </div>


                    </article>



                    <article class="stat-card">


                        <div class="num">

                            <?= count(
                                $comunidadesLista
                            ) ?>

                        </div>


                        <div class="label">

                            Comunidades cadastradas

                        </div>


                    </article>


                </div>



                <div class="admin-card">


                    <div class="section-header">


                        <div class="section-title">

                            Comunidades cadastradas

                        </div>


                        <div class="count-badge">

                            <?= count(
                                $comunidadesLista
                            ) ?>

                        </div>


                    </div>



                    <?php if (
                        empty(
                        $comunidadesLista
                    )
                    ): ?>


                        <div class="empty-state">

                            Nenhuma comunidade cadastrada.

                        </div>


                    <?php endif; ?>



                    <div class="community-list">


                        <?php foreach (
                            $comunidadesLista
                            as $comunidade
                        ): ?>


                            <article class="community-card">


                                <div class="community-card-header">


                                    <div>


                                        <h3>

                                            <?= htmlspecialchars(
                                                $comunidade["nm_comunidade"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </h3>


                                        <div class="community-creator">

                                            Criada por

                                            <?= htmlspecialchars(
                                                $comunidade["criador"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                    </div>


                                    <span class="count-badge">

                                        <?= (int) $comunidade[
                                            "total_membros"
                                        ] ?>

                                        membro(s)

                                    </span>


                                </div>



                                <?php if (
                                    !empty(
                                    $comunidade[
                                        "ds_comunidade"
                                    ]
                                )
                                ): ?>


                                    <p>

                                        <?= htmlspecialchars(
                                            $comunidade["ds_comunidade"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </p>


                                <?php endif; ?>



                                <div class="community-meta">

                                    Criada em

                                    <?= date(
                                        "d/m/Y",
                                        strtotime(
                                            $comunidade["dt_criacao"]
                                        )
                                    ) ?>

                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                </div>


            </section>


        <?php endif; ?>


    </main>



    <?php if ($ehAdmin): ?>

        <script>

            /* Abas */

            const adminTabButtons =
                document.querySelectorAll(
                    ".admin-tab-btn"
                );

            const adminTabs =
                document.querySelectorAll(
                    ".admin-tab"
                );


            function mudarAba(
                nome
            ) {

                adminTabs.forEach(
                    aba => {

                        aba.classList.remove(
                            "active"
                        );
                    }
                );


                adminTabButtons.forEach(
                    botao => {

                        botao.classList.remove(
                            "active"
                        );
                    }
                );


                const aba =
                    document.getElementById(
                        "tab-" + nome
                    );


                const botao =
                    document.querySelector(
                        '.admin-tab-btn[data-tab="' +
                        nome +
                        '"]'
                    );


                if (
                    !aba ||
                    !botao
                ) {

                    console.error(
                        "Aba administrativa não encontrada:",
                        nome
                    );

                    return;
                }


                aba.classList.add(
                    "active"
                );


                botao.classList.add(
                    "active"
                );


                try {

                    localStorage.setItem(
                        "admin_aba_ativa",
                        nome
                    );

                } catch (erro) {

                    console.error(
                        erro
                    );
                }


                if (
                    nome === "treinamento"
                ) {

                    setTimeout(
                        atualizarGraficos,
                        50
                    );
                }
            }


            adminTabButtons.forEach(
                botao => {

                    botao.addEventListener(
                        "click",
                        () => {

                            mudarAba(
                                botao.dataset.tab
                            );
                        }
                    );
                }
            );


            (function () {

                const abaServidor =
                    <?= json_encode(
                        $abaAtiva,
                        JSON_UNESCAPED_UNICODE
                    ) ?>;


                let abaInicial =
                    abaServidor;


                if (
                    abaInicial === "geral"
                ) {

                    try {

                        const salva =
                            localStorage.getItem(
                                "admin_aba_ativa"
                            );


                        if (
                            salva &&
                            document.getElementById(
                                "tab-" + salva
                            )
                        ) {

                            abaInicial =
                                salva;
                        }

                    } catch (erro) {

                        console.error(
                            erro
                        );
                    }
                }


                if (
                    !document.getElementById(
                        "tab-" + abaInicial
                    )
                ) {

                    abaInicial =
                        "geral";
                }


                mudarAba(
                    abaInicial
                );

            })();


            /* Busca de usuários */

            const buscaUsuarios =
                document.getElementById(
                    "buscaUsuarios"
                );


            buscaUsuarios?.addEventListener(
                "input",
                function () {

                    const termo =
                        this.value
                            .toLowerCase()
                            .trim();


                    document
                        .querySelectorAll(
                            "#tabelaUsuarios tbody tr"
                        )
                        .forEach(
                            linha => {

                                linha.style.display =
                                    linha
                                        .textContent
                                        .toLowerCase()
                                        .includes(
                                            termo
                                        )
                                        ? ""
                                        : "none";
                            }
                        );
                }
            );


            const formulariosCargo =
                document.querySelectorAll(
                    ".user-type-form"
                );


            formulariosCargo.forEach(
                formulario => {

                    const select =
                        formulario.querySelector(
                            ".select-cargo"
                        );

                    const botao =
                        formulario.querySelector(
                            ".btn-salvar-cargo"
                        );


                    if (
                        !select ||
                        !botao
                    ) {

                        return;
                    }


                    const cargoAtual =
                        formulario.dataset.cargoAtual;


                    function atualizarBotaoCargo() {

                        const alterado =
                            select.value !==
                            cargoAtual;


                        botao.disabled =
                            !alterado;
                    }


                    select.addEventListener(
                        "change",
                        atualizarBotaoCargo
                    );


                    formulario.addEventListener(
                        "submit",
                        evento => {

                            if (
                                select.value ===
                                cargoAtual
                            ) {

                                evento.preventDefault();

                                return;
                            }


                            const confirmar =
                                confirm(
                                    "Deseja alterar o cargo deste usuário de "
                                    +
                                    cargoAtual
                                    +
                                    " para "
                                    +
                                    select.value
                                    +
                                    "?"
                                );


                            if (
                                !confirmar
                            ) {

                                evento.preventDefault();

                                return;
                            }


                            botao.disabled =
                                true;

                            botao.innerHTML =
                                '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';
                        }
                    );


                    atualizarBotaoCargo();
                }
            );


            /* Funções gerais */

            function escaparHtml(
                valor
            ) {

                return String(valor)

                    .replaceAll(
                        "&",
                        "&amp;"
                    )

                    .replaceAll(
                        "<",
                        "&lt;"
                    )

                    .replaceAll(
                        ">",
                        "&gt;"
                    )

                    .replaceAll(
                        '"',
                        "&quot;"
                    )

                    .replaceAll(
                        "'",
                        "&#039;"
                    );
            }


            async function lerJsonSeguro(
                resposta
            ) {

                const texto =
                    await resposta.text();


                let dados;


                try {

                    dados =
                        JSON.parse(
                            texto
                        );

                } catch (erro) {

                    console.error(
                        "Resposta inválida:",
                        texto
                    );

                    throw new Error(
                        "O servidor não retornou um JSON válido."
                    );
                }


                if (
                    !resposta.ok
                ) {

                    throw new Error(
                        dados.error
                        ??
                        `Erro HTTP ${resposta.status}.`
                    );
                }


                return dados;
            }


            /* Dataset */

            const dropZone =
                document.getElementById(
                    "dropZone"
                );

            const fileInput =
                document.getElementById(
                    "fileInput"
                );

            const preview =
                document.getElementById(
                    "preview"
                );

            const listaArquivos =
                document.getElementById(
                    "listaArquivos"
                );

            const contadorArquivos =
                document.getElementById(
                    "contadorArquivos"
                );

            const datasetUploadInfo =
                document.getElementById(
                    "datasetUploadInfo"
                );

            const statusDataset =
                document.getElementById(
                    "statusDataset"
                );

            const resultTextDataset =
                document.getElementById(
                    "resultTextDataset"
                );

            const uploadForm =
                document.getElementById(
                    "uploadForm"
                );

            const btnSelecionarArquivos =
                document.getElementById(
                    "btnSelecionarArquivos"
                );

            const btnEnviarDataset =
                document.getElementById(
                    "btnEnviarDataset"
                );

            const btnLimparDataset =
                document.getElementById(
                    "btnLimparDataset"
                );


            let arquivosSelecionados =
                [];


            function formatarTamanho(
                bytes
            ) {

                const unidades = [
                    "B",
                    "KB",
                    "MB",
                    "GB"
                ];


                let tamanho =
                    Number(bytes);

                let indice =
                    0;


                while (
                    tamanho >= 1024 &&
                    indice <
                    unidades.length - 1
                ) {

                    tamanho /= 1024;

                    indice++;
                }


                return (
                    tamanho.toFixed(
                        indice === 0
                            ? 0
                            : 2
                    )
                    +
                    " "
                    +
                    unidades[indice]
                );
            }


            function atualizarResumoDataset() {

                const total =
                    arquivosSelecionados.length;


                contadorArquivos.textContent =
                    `${total} arquivo(s)`;


                if (
                    total === 0
                ) {

                    preview.hidden =
                        true;

                    datasetUploadInfo.hidden =
                        false;

                    statusDataset.textContent =
                        "Nenhum arquivo selecionado.";

                    return;
                }


                preview.hidden =
                    false;

                datasetUploadInfo.hidden =
                    true;

                statusDataset.textContent =
                    `${total} arquivo(s) selecionado(s).`;
            }


            function renderizarArquivosDataset() {

                listaArquivos.innerHTML =
                    "";


                arquivosSelecionados.forEach(
                    arquivo => {

                        const linha =
                            document.createElement(
                                "div"
                            );


                        linha.className =
                            "arquivo";


                        if (
                            arquivo.type.startsWith(
                                "image/"
                            )
                        ) {

                            const imagem =
                                document.createElement(
                                    "img"
                                );


                            const reader =
                                new FileReader();


                            reader.onload =
                                evento => {

                                    imagem.src =
                                        evento.target.result;
                                };


                            reader.readAsDataURL(
                                arquivo
                            );


                            linha.appendChild(
                                imagem
                            );


                        } else {


                            const icone =
                                document.createElement(
                                    "div"
                                );


                            icone.className =
                                "icone";


                            icone.innerHTML =
                                '<i class="fa-solid fa-video"></i>';


                            linha.appendChild(
                                icone
                            );
                        }


                        const info =
                            document.createElement(
                                "div"
                            );


                        info.className =
                            "info";


                        info.innerHTML = `
                <div class="nome">
                    ${escaparHtml(
                        arquivo.name
                    )}
                </div>

                <div class="tamanho">
                    ${formatarTamanho(
                        arquivo.size
                    )}
                </div>
            `;


                        linha.appendChild(
                            info
                        );


                        listaArquivos.appendChild(
                            linha
                        );
                    }
                );


                atualizarResumoDataset();
            }


            function handleFiles(
                arquivos
            ) {

                for (
                    const arquivo
                    of arquivos
                ) {

                    if (
                        !arquivo.type.startsWith(
                            "image/"
                        )
                        &&
                        !arquivo.type.startsWith(
                            "video/"
                        )
                    ) {

                        continue;
                    }


                    const duplicado =
                        arquivosSelecionados.some(
                            atual =>
                                atual.name === arquivo.name
                                &&
                                atual.size === arquivo.size
                                &&
                                atual.lastModified ===
                                arquivo.lastModified
                        );


                    if (
                        duplicado
                    ) {

                        continue;
                    }


                    arquivosSelecionados.push(
                        arquivo
                    );
                }


                fileInput.value =
                    "";


                renderizarArquivosDataset();
            }


            [
                "dragenter",
                "dragover",
                "dragleave",
                "drop"
            ].forEach(
                evento => {

                    dropZone.addEventListener(
                        evento,
                        e => {

                            e.preventDefault();

                            e.stopPropagation();
                        }
                    );
                }
            );


            [
                "dragenter",
                "dragover"
            ].forEach(
                evento => {

                    dropZone.addEventListener(
                        evento,
                        () => {

                            dropZone.classList.add(
                                "highlight"
                            );
                        }
                    );
                }
            );


            [
                "dragleave",
                "drop"
            ].forEach(
                evento => {

                    dropZone.addEventListener(
                        evento,
                        () => {

                            dropZone.classList.remove(
                                "highlight"
                            );
                        }
                    );
                }
            );


            dropZone.addEventListener(
                "drop",
                evento => {

                    handleFiles(
                        evento.dataTransfer.files
                    );
                }
            );


            fileInput.addEventListener(
                "change",
                evento => {

                    handleFiles(
                        evento.target.files
                    );
                }
            );


            btnSelecionarArquivos.addEventListener(
                "click",
                () => {

                    fileInput.click();
                }
            );


            function bloquearDataset(
                bloqueado
            ) {

                btnEnviarDataset.disabled =
                    bloqueado;

                btnLimparDataset.disabled =
                    bloqueado;

                btnSelecionarArquivos.disabled =
                    bloqueado;

                fileInput.disabled =
                    bloqueado;
            }


            async function registrarGesto(
                nomeGesto,
                caminhoDataset
            ) {

                const corpo =
                    new URLSearchParams();

                corpo.set(
                    "nome_gesto",
                    nomeGesto
                );

                corpo.set(
                    "dataset",
                    caminhoDataset
                );

                const resposta =
                    await fetch(
                        "admin.php?ajax=registrar_gesto",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded; charset=UTF-8"
                            },

                            body:
                                corpo.toString()
                        }
                    );

                return await lerJsonSeguro(
                    resposta
                );
            }


            uploadForm.addEventListener(
                "submit",
                async evento => {

                    evento.preventDefault();


                    if (
                        arquivosSelecionados.length === 0
                    ) {

                        alert(
                            "Selecione as mídias."
                        );

                        return;
                    }


                    const nomeDataset =
                        document
                            .getElementById(
                                "datasetNome"
                            )
                            .value
                            .trim();


                    if (
                        nomeDataset === ""
                    ) {

                        alert(
                            "Informe o nome do gesto."
                        );

                        return;
                    }


                    bloquearDataset(
                        true
                    );


                    resultTextDataset.textContent =
                        "Iniciando upload...";


                    try {


                        for (
                            let indice = 0;
                            indice <
                            arquivosSelecionados.length;
                            indice++
                        ) {

                            const arquivo =
                                arquivosSelecionados[
                                indice
                                ];


                            resultTextDataset.innerHTML = `
                    <strong>
                        Enviando ${indice + 1}
                        de
                        ${arquivosSelecionados.length}
                    </strong>

                    <br><br>

                    ${escaparHtml(
                            arquivo.name
                        )}
                `;


                            const formData =
                                new FormData();


                            formData.append(
                                "dataset",
                                nomeDataset
                            );


                            formData.append(
                                "mediaFile",
                                arquivo
                            );


                            const resposta =
                                await fetch(
                                    "ajax/criar_dataset.php",
                                    {
                                        method: "POST",
                                        body: formData
                                    }
                                );


                            const dados =
                                await lerJsonSeguro(
                                    resposta
                                );


                            if (
                                !dados.success
                            ) {

                                throw new Error(
                                    dados.error
                                    ??
                                    "Erro ao enviar arquivo."
                                );
                            }
                        }


                        resultTextDataset.innerHTML = `
                <strong>
                    Upload concluído.
                </strong>

                <br><br>

                Processando dataset...
            `;


                        const corpoFinal =
                            new URLSearchParams();


                        corpoFinal.set(
                            "dataset",
                            nomeDataset
                        );


                        const respostaFinal =
                            await fetch(
                                "ajax/finalizar_dataset.php",
                                {
                                    method: "POST",

                                    headers: {
                                        "Content-Type":
                                            "application/x-www-form-urlencoded; charset=UTF-8"
                                    },

                                    body:
                                        corpoFinal.toString()
                                }
                            );


                        const dadosFinais =
                            await lerJsonSeguro(
                                respostaFinal
                            );


                        if (
                            !dadosFinais.success
                        ) {

                            throw new Error(
                                dadosFinais.error
                                ??
                                "Erro ao processar o dataset."
                            );
                        }


                        if (
                            !dadosFinais.dataset
                        ) {

                            throw new Error(
                                "O servidor processou o dataset, "
                                +
                                "mas não retornou o caminho do arquivo .npy."
                            );
                        }


                        const registro =
                            await registrarGesto(
                                nomeDataset,
                                dadosFinais.dataset
                            );


                        if (
                            !registro.success
                        ) {

                            throw new Error(
                                registro.error
                                ??
                                "O dataset foi criado, mas "
                                +
                                "o gesto não pôde ser cadastrado."
                            );
                        }


                        resultTextDataset.innerHTML = `
                <strong>
                    Dataset criado com sucesso!
                </strong>

                <br><br>

                Total de amostras:
                ${dadosFinais.total_amostras ?? 0}

                <br>

                Dataset:
                ${escaparHtml(
                        dadosFinais.dataset
                    )}

                <br>

                ${registro.existing
                            ? "Gesto atualizado no banco."
                            : "Gesto registrado no banco."
                        }
            `;


                    } catch (erro) {


                        resultTextDataset.innerHTML = `
                <strong>
                    Erro durante a criação do dataset.
                </strong>

                <br><br>

                ${escaparHtml(
                        erro.message
                    )}
            `;


                    } finally {


                        bloquearDataset(
                            false
                        );
                    }
                }
            );


            btnLimparDataset.addEventListener(
                "click",
                () => {

                    arquivosSelecionados =
                        [];


                    fileInput.value =
                        "";


                    listaArquivos.innerHTML =
                        "";


                    document
                        .getElementById(
                            "datasetNome"
                        )
                        .value =
                        "";


                    resultTextDataset.textContent =
                        "Aguardando envio...";


                    atualizarResumoDataset();
                }
            );


            atualizarResumoDataset();


            /* Treinamento */

            const btnTreinarModelo =
                document.getElementById(
                    "btnTreinarModelo"
                );

            const btnCancelarTreino =
                document.getElementById(
                    "btnCancelarTreino"
                );

            const btnLimparLog =
                document.getElementById(
                    "btnLimparLog"
                );

            const trainingStatus =
                document.getElementById(
                    "trainingStatus"
                );

            const trainingPercent =
                document.getElementById(
                    "trainingPercent"
                );

            const trainingProgressBar =
                document.getElementById(
                    "trainingProgressBar"
                );

            const trainingProgressFill =
                document.getElementById(
                    "trainingProgressFill"
                );

            const metricEpoch =
                document.getElementById(
                    "metricEpoch"
                );

            const metricAccuracy =
                document.getElementById(
                    "metricAccuracy"
                );

            const metricValAccuracy =
                document.getElementById(
                    "metricValAccuracy"
                );

            const metricLoss =
                document.getElementById(
                    "metricLoss"
                );

            const trainingLog =
                document.getElementById(
                    "trainingLog"
                );

            const accuracyCanvas =
                document.getElementById(
                    "accuracyChart"
                );

            const lossCanvas =
                document.getElementById(
                    "lossChart"
                );

            const accuracyChartEmpty =
                document.getElementById(
                    "accuracyChartEmpty"
                );

            const lossChartEmpty =
                document.getElementById(
                    "lossChartEmpty"
                );


            let treinamentoAtivo =
                false;

            let treinamentoJobId =
                null;

            let pollingTreinamento =
                null;

            let consultaStatusEmAndamento =
                false;

            let proximoLogTreinamento =
                0;


            const dadosGraficos = {

                epochs: [],

                accuracy: [],

                valAccuracy: [],

                loss: [],

                valLoss: []
            };


            function formatarPercentual(
                valor
            ) {

                let numero =
                    Number(valor);


                if (
                    !Number.isFinite(
                        numero
                    )
                ) {

                    return "0,00%";
                }


                if (
                    numero <= 1
                ) {

                    numero *= 100;
                }


                return (
                    numero
                        .toFixed(2)
                        .replace(
                            ".",
                            ","
                        )
                    +
                    "%"
                );
            }


            function formatarDecimal(
                valor
            ) {

                const numero =
                    Number(valor);


                if (
                    !Number.isFinite(
                        numero
                    )
                ) {

                    return "0,0000";
                }


                return numero
                    .toFixed(4)
                    .replace(
                        ".",
                        ","
                    );
            }


            function adicionarLog(
                mensagem
            ) {

                if (
                    mensagem === undefined ||
                    mensagem === null ||
                    mensagem === ""
                ) {

                    return;
                }


                if (
                    trainingLog.value.trim()
                    ===
                    "Aguardando o início do treinamento..."
                ) {

                    trainingLog.value =
                        "";
                }


                const hora =
                    new Date()
                        .toLocaleTimeString(
                            "pt-BR"
                        );


                trainingLog.value +=
                    (
                        trainingLog.value
                            ? "\n"
                            : ""
                    )
                    +
                    `[${hora}] ${mensagem}`;


                trainingLog.scrollTop =
                    trainingLog.scrollHeight;
            }


            function definirProgresso(
                percentual,
                mensagem
            ) {

                const valor =
                    Math.max(
                        0,
                        Math.min(
                            100,
                            Number(percentual)
                            || 0
                        )
                    );


                trainingProgressFill
                    .style
                    .width =
                    `${valor}%`;


                trainingProgressBar
                    .setAttribute(
                        "aria-valuenow",
                        String(valor)
                    );


                trainingPercent.textContent =
                    `${valor.toFixed(0)}%`;


                if (
                    mensagem
                ) {

                    trainingStatus.textContent =
                        mensagem;
                }
            }


            function limparDadosTreinamento() {

                dadosGraficos.epochs =
                    [];

                dadosGraficos.accuracy =
                    [];

                dadosGraficos.valAccuracy =
                    [];

                dadosGraficos.loss =
                    [];

                dadosGraficos.valLoss =
                    [];


                metricEpoch.textContent =
                    "0 / 0";

                metricAccuracy.textContent =
                    "0,00%";

                metricValAccuracy.textContent =
                    "0,00%";

                metricLoss.textContent =
                    "0,0000";


                definirProgresso(
                    0,
                    "Aguardando início"
                );


                atualizarGraficos();
            }


            function prepararCanvas(
                canvas
            ) {

                const proporcao =
                    window.devicePixelRatio
                    || 1;


                const largura =
                    canvas.clientWidth
                    || 600;


                const altura =
                    canvas.clientHeight
                    || 280;


                canvas.width =
                    largura * proporcao;


                canvas.height =
                    altura * proporcao;


                const contexto =
                    canvas.getContext(
                        "2d"
                    );


                contexto.setTransform(
                    proporcao,
                    0,
                    0,
                    proporcao,
                    0,
                    0
                );


                return {
                    contexto,
                    largura,
                    altura
                };
            }


            function desenharGrafico(
                canvas,
                seriePrincipal,
                serieValidacao,
                percentual
            ) {

                const {
                    contexto,
                    largura,
                    altura
                } =
                    prepararCanvas(
                        canvas
                    );


                contexto.clearRect(
                    0,
                    0,
                    largura,
                    altura
                );


                const quantidade =
                    Math.max(
                        seriePrincipal.length,
                        serieValidacao.length
                    );


                if (
                    quantidade === 0
                ) {

                    return;
                }


                const margem = {
                    topo: 20,
                    direita: 20,
                    baixo: 30,
                    esquerda: 48
                };


                const larguraGrafico =
                    largura
                    -
                    margem.esquerda
                    -
                    margem.direita;


                const alturaGrafico =
                    altura
                    -
                    margem.topo
                    -
                    margem.baixo;


                const valores = [
                    ...seriePrincipal,
                    ...serieValidacao
                ]
                    .map(Number)
                    .filter(
                        Number.isFinite
                    );


                let minimo =
                    percentual
                        ? 0
                        : Math.min(
                            ...valores
                        );


                let maximo =
                    percentual
                        ? 1
                        : Math.max(
                            ...valores
                        );


                if (
                    !Number.isFinite(
                        minimo
                    )
                ) {

                    minimo = 0;
                }


                if (
                    !Number.isFinite(
                        maximo
                    )
                ) {

                    maximo = 1;
                }


                if (
                    minimo === maximo
                ) {

                    maximo =
                        minimo + 1;
                }


                function calcularX(
                    indice
                ) {

                    if (
                        quantidade <= 1
                    ) {

                        return (
                            margem.esquerda
                            +
                            larguraGrafico / 2
                        );
                    }


                    return (
                        margem.esquerda
                        +
                        (
                            indice
                            /
                            (quantidade - 1)
                        )
                        *
                        larguraGrafico
                    );
                }


                function calcularY(
                    valor
                ) {

                    return (
                        margem.topo
                        +
                        (
                            1
                            -
                            (
                                (valor - minimo)
                                /
                                (maximo - minimo)
                            )
                        )
                        *
                        alturaGrafico
                    );
                }


                contexto.strokeStyle =
                    "#d5d5d5";


                contexto.fillStyle =
                    "#777";


                contexto.font =
                    "11px Arial";


                for (
                    let indice = 0;
                    indice <= 5;
                    indice++
                ) {

                    const y =
                        margem.topo
                        +
                        (
                            indice / 5
                        )
                        *
                        alturaGrafico;


                    contexto.beginPath();


                    contexto.moveTo(
                        margem.esquerda,
                        y
                    );


                    contexto.lineTo(
                        margem.esquerda
                        +
                        larguraGrafico,
                        y
                    );


                    contexto.stroke();


                    const valor =
                        maximo
                        -
                        (
                            indice / 5
                        )
                        *
                        (
                            maximo - minimo
                        );


                    contexto.fillText(
                        percentual
                            ? `${(valor * 100).toFixed(0)}%`
                            : valor.toFixed(3),

                        4,

                        y + 4
                    );
                }


                function desenharLinha(
                    valoresLinha,
                    tracejada
                ) {

                    const pontos =
                        valoresLinha

                            .map(
                                (
                                    valor,
                                    indice
                                ) => ({
                                    indice,
                                    valor:
                                        Number(valor)
                                })
                            )

                            .filter(
                                ponto =>
                                    Number.isFinite(
                                        ponto.valor
                                    )
                            );


                    if (
                        pontos.length === 0
                    ) {

                        return;
                    }


                    contexto.save();


                    contexto.strokeStyle =
                        tracejada
                            ? "#999"
                            : "#222";


                    contexto.lineWidth =
                        2;


                    contexto.setLineDash(
                        tracejada
                            ? [6, 4]
                            : []
                    );


                    contexto.beginPath();


                    pontos.forEach(
                        (
                            ponto,
                            indice
                        ) => {

                            const x =
                                calcularX(
                                    ponto.indice
                                );


                            const y =
                                calcularY(
                                    ponto.valor
                                );


                            if (
                                indice === 0
                            ) {

                                contexto.moveTo(
                                    x,
                                    y
                                );

                            } else {

                                contexto.lineTo(
                                    x,
                                    y
                                );
                            }
                        }
                    );


                    contexto.stroke();


                    contexto.restore();
                }


                desenharLinha(
                    seriePrincipal,
                    false
                );


                desenharLinha(
                    serieValidacao,
                    true
                );
            }


            function atualizarGraficos() {

                const possuiDados =
                    dadosGraficos
                        .epochs
                        .length > 0;


                accuracyChartEmpty.hidden =
                    possuiDados;


                lossChartEmpty.hidden =
                    possuiDados;


                if (
                    !possuiDados
                ) {

                    return;
                }


                desenharGrafico(
                    accuracyCanvas,
                    dadosGraficos.accuracy,
                    dadosGraficos.valAccuracy,
                    true
                );


                desenharGrafico(
                    lossCanvas,
                    dadosGraficos.loss,
                    dadosGraficos.valLoss,
                    false
                );
            }


            function atualizarEpoca(
                dados
            ) {

                const epoca =
                    Number(
                        dados.epoch
                    );


                if (
                    !Number.isInteger(
                        epoca
                    )
                    ||
                    epoca <= 0
                ) {

                    return;
                }


                let indice =
                    dadosGraficos
                        .epochs
                        .indexOf(
                            epoca
                        );


                if (
                    indice === -1
                ) {

                    dadosGraficos
                        .epochs
                        .push(
                            epoca
                        );


                    dadosGraficos
                        .accuracy
                        .push(
                            Number(
                                dados.accuracy
                            )
                        );


                    dadosGraficos
                        .valAccuracy
                        .push(
                            Number(
                                dados.val_accuracy
                            )
                        );


                    dadosGraficos
                        .loss
                        .push(
                            Number(
                                dados.loss
                            )
                        );


                    dadosGraficos
                        .valLoss
                        .push(
                            Number(
                                dados.val_loss
                            )
                        );


                } else {


                    dadosGraficos
                        .accuracy[indice] =
                        Number(
                            dados.accuracy
                        );


                    dadosGraficos
                        .valAccuracy[indice] =
                        Number(
                            dados.val_accuracy
                        );


                    dadosGraficos
                        .loss[indice] =
                        Number(
                            dados.loss
                        );


                    dadosGraficos
                        .valLoss[indice] =
                        Number(
                            dados.val_loss
                        );
                }


                atualizarGraficos();
            }


            function aplicarStatusTreinamento(
                dados
            ) {

                const epoca =
                    Number(
                        dados.epoch
                        ?? 0
                    );


                const totalEpocas =
                    Number(
                        dados.total_epochs
                        ?? 0
                    );


                let progresso =
                    Number(
                        dados.progress
                    );


                if (
                    !Number.isFinite(
                        progresso
                    )
                ) {

                    progresso =
                        totalEpocas > 0
                            ? (
                                epoca
                                /
                                totalEpocas
                                *
                                100
                            )
                            : 0;
                }


                metricEpoch.textContent =
                    `${epoca} / ${totalEpocas}`;


                metricAccuracy.textContent =
                    formatarPercentual(
                        dados.accuracy
                    );


                metricValAccuracy.textContent =
                    formatarPercentual(
                        dados.val_accuracy
                    );


                metricLoss.textContent =
                    formatarDecimal(
                        dados.loss
                    );


                definirProgresso(
                    progresso,

                    dados.message
                    ??
                    dados.status
                    ??
                    "Treinamento em andamento"
                );


                atualizarEpoca(
                    dados
                );


                if (
                    Array.isArray(
                        dados.logs
                    )
                ) {

                    dados.logs.forEach(
                        log => {

                            adicionarLog(
                                typeof log === "object"
                                    ? (
                                        log.mensagem
                                        ??
                                        log.message
                                        ??
                                        JSON.stringify(
                                            log
                                        )
                                    )
                                    : log
                            );
                        }
                    );
                }


                if (
                    dados.log
                ) {

                    adicionarLog(
                        dados.log
                    );
                }


                const proximo =
                    Number(
                        dados.proximo_log
                    );


                if (
                    Number.isInteger(
                        proximo
                    )
                    &&
                    proximo >= 0
                ) {

                    proximoLogTreinamento =
                        proximo;
                }
            }


            async function registrarModeloTreinado() {

                const resposta =
                    await fetch(
                        "admin.php?ajax=registrar_modelo",
                        {
                            method: "POST"
                        }
                    );

                const dados =
                    await lerJsonSeguro(
                        resposta
                    );

                if (
                    !resposta.ok
                    ||
                    !dados.success
                ) {

                    throw new Error(
                        dados.error
                        ??
                        "Não foi possível registrar o modelo treinado."
                    );
                }

                return dados;
            }


            function pararMonitoramento() {

                if (
                    pollingTreinamento !== null
                ) {

                    clearInterval(
                        pollingTreinamento
                    );


                    pollingTreinamento =
                        null;
                }
            }


            function finalizarTreinamentoUI() {

                treinamentoAtivo =
                    false;


                consultaStatusEmAndamento =
                    false;


                pararMonitoramento();


                btnTreinarModelo.disabled =
                    false;


                btnCancelarTreino.disabled =
                    true;
            }


            async function consultarTreinamento() {

                if (
                    !treinamentoAtivo ||
                    consultaStatusEmAndamento
                ) {

                    return;
                }


                consultaStatusEmAndamento =
                    true;


                try {


                    const parametros =
                        new URLSearchParams();


                    parametros.set(
                        "desde_log",
                        String(
                            proximoLogTreinamento
                        )
                    );


                    if (
                        treinamentoJobId
                    ) {

                        parametros.set(
                            "job_id",
                            treinamentoJobId
                        );
                    }


                    const resposta =
                        await fetch(
                            "ajax/status_treinamento.php?"
                            +
                            parametros.toString(),

                            {
                                cache: "no-store"
                            }
                        );


                    const dados =
                        await lerJsonSeguro(
                            resposta
                        );


                    if (
                        !dados.success
                    ) {

                        throw new Error(
                            dados.error
                            ??
                            "Erro ao consultar o treinamento."
                        );
                    }


                    aplicarStatusTreinamento(
                        dados
                    );


                    if (
                        dados.status === "erro"
                        ||
                        dados.failed === true
                    ) {

                        adicionarLog(
                            dados.error
                            ??
                            "Treinamento encerrado com erro."
                        );


                        finalizarTreinamentoUI();


                        return;
                    }


                    if (
                        dados.status === "cancelado"
                        ||
                        dados.cancelled === true
                    ) {

                        adicionarLog(
                            dados.message
                            ??
                            "Treinamento cancelado."
                        );


                        finalizarTreinamentoUI();


                        return;
                    }


                    if (
                        dados.status === "concluido"
                        ||
                        (
                            dados.finished === true
                            &&
                            dados.failed !== true
                            &&
                            dados.cancelled !== true
                        )
                    ) {

                        definirProgresso(
                            100,
                            "Treinamento concluído"
                        );


                        adicionarLog(
                            dados.message
                            ??
                            "Modelo treinado com sucesso."
                        );


                        if (
                            dados.resultado
                        ) {

                            if (
                                dados.resultado
                                    .accuracy_final
                                !== undefined
                            ) {

                                adicionarLog(
                                    "Acurácia final: "
                                    +
                                    formatarPercentual(
                                        dados.resultado
                                            .accuracy_final
                                    )
                                );
                            }


                            if (
                                dados.resultado
                                    .epochs_executadas
                                !== undefined
                            ) {

                                adicionarLog(
                                    "Épocas executadas: "
                                    +
                                    dados.resultado
                                        .epochs_executadas
                                );
                            }
                        }


                        try {

                            const registroModelo =
                                await registrarModeloTreinado();

                            adicionarLog(
                                "Modelo registrado no banco com ID #"
                                +
                                registroModelo.id_modelo
                                +
                                (
                                    registroModelo.ativo
                                        ? " e definido como modelo em uso."
                                        : "."
                                )
                            );

                            setTimeout(
                                () => {
                                    window.location.href =
                                        "admin.php?aba=treinamento";
                                },
                                900
                            );

                        } catch (erroRegistro) {

                            adicionarLog(
                                "Treinamento concluído, mas houve erro "
                                +
                                "ao registrar os arquivos do modelo no banco: "
                                +
                                erroRegistro.message
                            );
                        }


                        finalizarTreinamentoUI();
                    }


                } catch (erro) {


                    adicionarLog(
                        "Erro de monitoramento: "
                        +
                        erro.message
                    );


                    finalizarTreinamentoUI();


                } finally {


                    consultaStatusEmAndamento =
                        false;
                }
            }


            function iniciarMonitoramento() {

                pararMonitoramento();


                consultarTreinamento();


                pollingTreinamento =
                    setInterval(
                        consultarTreinamento,
                        1500
                    );
            }


            btnTreinarModelo.addEventListener(
                "click",
                async () => {

                    if (
                        treinamentoAtivo
                    ) {

                        return;
                    }


                    treinamentoAtivo =
                        true;


                    treinamentoJobId =
                        null;


                    proximoLogTreinamento =
                        0;


                    btnTreinarModelo.disabled =
                        true;


                    btnCancelarTreino.disabled =
                        false;


                    limparDadosTreinamento();


                    trainingLog.value =
                        "";


                    adicionarLog(
                        "Solicitando início do treinamento..."
                    );


                    definirProgresso(
                        0,
                        "Iniciando treinamento"
                    );


                    try {


                        const resposta =
                            await fetch(
                                "ajax/treinar_modelo.php",
                                {
                                    method: "POST"
                                }
                            );


                        const dados =
                            await lerJsonSeguro(
                                resposta
                            );


                        if (
                            !dados.success
                        ) {

                            throw new Error(
                                dados.error
                                ??
                                "Não foi possível iniciar o treinamento."
                            );
                        }


                        treinamentoJobId =
                            dados.job_id
                            ??
                            null;


                        adicionarLog(
                            dados.message
                            ??
                            "Treinamento iniciado."
                        );


                        iniciarMonitoramento();


                    } catch (erro) {


                        adicionarLog(
                            "Erro: "
                            +
                            erro.message
                        );


                        finalizarTreinamentoUI();
                    }
                }
            );


            btnCancelarTreino.addEventListener(
                "click",
                async () => {

                    if (
                        !treinamentoAtivo
                    ) {

                        return;
                    }


                    if (
                        !confirm(
                            "Deseja cancelar o treinamento atual?"
                        )
                    ) {

                        return;
                    }


                    btnCancelarTreino.disabled =
                        true;


                    try {


                        const corpo =
                            new URLSearchParams();


                        if (
                            treinamentoJobId
                        ) {

                            corpo.set(
                                "job_id",
                                treinamentoJobId
                            );
                        }


                        const resposta =
                            await fetch(
                                "ajax/cancelar_treinamento.php",

                                {
                                    method: "POST",

                                    headers: {
                                        "Content-Type":
                                            "application/x-www-form-urlencoded; charset=UTF-8"
                                    },

                                    body:
                                        corpo.toString()
                                }
                            );


                        const dados =
                            await lerJsonSeguro(
                                resposta
                            );


                        if (
                            !dados.success
                        ) {

                            throw new Error(
                                dados.error
                                ??
                                "Não foi possível cancelar o treinamento."
                            );
                        }


                        adicionarLog(
                            dados.message
                            ??
                            "Cancelamento solicitado."
                        );


                    } catch (erro) {


                        adicionarLog(
                            "Erro ao cancelar: "
                            +
                            erro.message
                        );


                        btnCancelarTreino.disabled =
                            false;
                    }
                }
            );


            btnLimparLog.addEventListener(
                "click",
                () => {

                    trainingLog.value =
                        "";
                }
            );


            let resizeGraficoTimer =
                null;


            window.addEventListener(
                "resize",
                () => {

                    clearTimeout(
                        resizeGraficoTimer
                    );


                    resizeGraficoTimer =
                        setTimeout(
                            atualizarGraficos,
                            150
                        );
                }
            );


            limparDadosTreinamento();

        </script>

    <?php endif; ?>



    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">

        &#9776;

    </button>


    <div class="sidebar-overlay" id="sidebarOverlay"></div>



    <script>

        /* Menu mobile */

        (function () {

            const botao =
                document.getElementById(
                    "menuToggle"
                );


            const sidebar =
                document.querySelector(
                    ".sidebar"
                );


            const overlay =
                document.getElementById(
                    "sidebarOverlay"
                );


            if (
                !botao ||
                !sidebar ||
                !overlay
            ) {

                return;
            }


            function abrir() {

                sidebar.classList.add(
                    "open"
                );


                overlay.classList.add(
                    "open"
                );


                botao.innerHTML =
                    "&#10005;";
            }


            function fechar() {

                sidebar.classList.remove(
                    "open"
                );


                overlay.classList.remove(
                    "open"
                );


                botao.innerHTML =
                    "&#9776;";
            }


            botao.addEventListener(
                "click",
                () => {

                    sidebar.classList.contains(
                        "open"
                    )
                        ? fechar()
                        : abrir();
                }
            );


            overlay.addEventListener(
                "click",
                fechar
            );


            sidebar
                .querySelectorAll(
                    "a"
                )
                .forEach(
                    link => {

                        link.addEventListener(
                            "click",
                            fechar
                        );
                    }
                );

        })();

    </script>



    <script>

        /* Item ativo da sidebar */

        (function () {

            const pagina =
                window.location.pathname
                    .split("/")
                    .pop()
                    .replace(
                        /\.php$/,
                        ""
                    )
                    .replace(
                        /\.html$/,
                        ""
                    );


            document
                .querySelectorAll(
                    ".sidebar .nav-item[data-page]"
                )
                .forEach(
                    link => {

                        if (
                            link.dataset.page
                            === pagina
                        ) {

                            link.classList.add(
                                "active"
                            );
                        }
                    }
                );

        })();

    </script>


</body>

</html>