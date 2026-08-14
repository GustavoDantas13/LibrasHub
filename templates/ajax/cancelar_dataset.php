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


function removerDiretorio(
    string $diretorio
): bool {

    if (
        !is_dir(
            $diretorio
        )
    ) {

        return true;
    }


    $itens =
        scandir(
            $diretorio
        );


    if (
        $itens === false
    ) {

        return false;
    }


    foreach (
        $itens
        as $item
    ) {

        if (
            $item === "."
            ||
            $item === ".."
        ) {

            continue;
        }


        $caminho =
            $diretorio
            .
            DIRECTORY_SEPARATOR
            .
            $item;


        if (
            is_dir(
                $caminho
            )
            &&
            !is_link(
                $caminho
            )
        ) {

            if (
                !removerDiretorio(
                    $caminho
                )
            ) {

                return false;
            }

        } else {

            if (
                is_file(
                    $caminho
                )
                ||
                is_link(
                    $caminho
                )
            ) {

                @chmod(
                    $caminho,
                    0777
                );

                if (
                    !@unlink(
                        $caminho
                    )
                    &&
                    file_exists(
                        $caminho
                    )
                ) {

                    return false;
                }
            }
        }
    }


    @chmod(
        $diretorio,
        0777
    );


    return (
        @rmdir(
            $diretorio
        )
        ||
        !is_dir(
            $diretorio
        )
    );
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


$baseProjeto =
    dirname(
        __DIR__,
        2
    );


$pastaLibraas =
    $baseProjeto
    .
    DIRECTORY_SEPARATOR
    .
    "libraas";


$tentativas =
    5;


for (
    $tentativa = 1;
    $tentativa <= $tentativas;
    $tentativa++
) {

    if (
        !is_dir(
            $pastaLibraas
        )
    ) {

        responder([
            "success" => true,
            "message" => "Criação do dataset cancelada.",
            "pasta_libraas_removida" => true
        ]);
    }


    removerDiretorio(
        $pastaLibraas
    );


    if (
        !is_dir(
            $pastaLibraas
        )
    ) {

        responder([
            "success" => true,
            "message" => "Criação do dataset cancelada.",
            "pasta_libraas_removida" => true
        ]);
    }


    usleep(
        250000
    );
}


responder([
    "success" => false,
    "error" => (
        "Não foi possível remover completamente a pasta libraas. "
        .
        "Algum arquivo pode ainda estar em uso pelo processamento."
    ),
    "pasta_libraas_removida" => false
], 500);
