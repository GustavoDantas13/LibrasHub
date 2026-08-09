<?php

session_start();

require_once "configs/config.php";


$erro = "";


/* Páginas permitidas para redirecionamento */

$paginasPermitidas = [
    "home.php",
    "comunidade.php",
    "leitor.php",
    "upload.php",
    "historico.php",
    "usuario.php",
    "configuracoes.php",
    "admin.php"
];


/* Destino após o login */

$redirect =
    $_GET["redirect"]
    ??
    $_POST["redirect"]
    ??
    "home.php";


if (
    !in_array(
        $redirect,
        $paginasPermitidas,
        true
    )
) {

    $redirect = "home.php";
}


/* Se já estiver logado */

if (!empty($_SESSION["usuario_id"])) {

    header(
        "Location: " . $redirect
    );

    exit;
}


/* Login */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $email = trim(
        $_POST["email"]
        ?? ""
    );

    $senha =
        $_POST["senha"]
        ?? "";


    if (
        $email === "" ||
        $senha === ""
    ) {

        $erro =
            "Preencha o email e a senha.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erro =
            "Informe um email válido.";

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id_usuario,
                nm_usuario,
                email_usuario,
                senha_usuario,
                tp_usuario,
                dt_usuario

            FROM usuario

            WHERE email_usuario = ?

            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);


        $usuario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$usuario ||
            !password_verify(
                $senha,
                $usuario["senha_usuario"]
            )
        ) {

            $erro =
                "Email ou senha incorretos.";

        } else {

            session_regenerate_id(
                true
            );


            $_SESSION["usuario_id"] =
                (int) $usuario["id_usuario"];


            $_SESSION["usuario_nome"] =
                $usuario["nm_usuario"];


            $_SESSION["usuario_email"] =
                $usuario["email_usuario"];


            $_SESSION["usuario_tipo"] =
                $usuario["tp_usuario"];


            header(
                "Location: " . $redirect
            );

            exit;
        }
    }
}

?>
<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        LibrasHub - Login
    </title>

    <link
        rel="stylesheet"
        href="../static/css/style.css"
    >

</head>


<body>


<div class="auth-wrap">


    <div class="auth-box">


        <div class="auth-icon">

            👤

        </div>


        <div class="auth-title">

            Entrar

        </div>


        <?php if (
            $redirect === "comunidade.php"
        ): ?>

            <div class="auth-subtitle">

                Entre para acessar a Comunidade

            </div>

        <?php else: ?>

            <div class="auth-subtitle">

                Acesse sua conta

            </div>

        <?php endif; ?>


        <?php if ($erro): ?>

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


        <form
            method="POST"
            action="login.php"
        >


            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars(
                    $redirect,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


            <div class="field">


                <label for="email">

                    Email

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seuemail@exemplo.com"
                    autocomplete="email"
                    value="<?= htmlspecialchars(
                        $_POST["email"]
                        ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >


            </div>


            <div class="field">


                <label for="senha">

                    Senha

                </label>


                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >


            </div>


            <button
                type="submit"
                class="btn btn-block"
            >

                Entrar

            </button>


        </form>


        <div class="auth-footer">

            Não tem uma conta?

            <a href="cadastro.php">

                Cadastre-se

            </a>

        </div>


    </div>


</div>


</body>

</html>