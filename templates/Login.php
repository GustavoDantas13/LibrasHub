<?php

session_start();

require_once "configs/config.php";


$erro = "";


/* Páginas permitidas para redirecionamento */

$paginasPermitidas = [
    "home.php",
    "comunidade.php",
    "locais.php",
    "social.php",
    "chats.php",
    "leitor.php",
    "upload.php",
    "historico.php",
    "ajuda.php",
    "usuario.php",
    "perfil.php",
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

    $email = authNormalizeEmail((string) (
        $_POST["email"]
        ?? ""
    ));

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

            WHERE LOWER(TRIM(email_usuario)) = ?

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
            !authVerifyUserPassword(
                $pdo,
                $usuario,
                $senha
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

            try {
                authRevokeRememberToken($pdo);
                authCreateRememberToken($pdo, (int) $usuario["id_usuario"]);
            } catch (Throwable $rememberError) {
                // O login por sessão continua funcionando mesmo se a tabela de
                // "lembrar-me" ainda não tiver sido migrada no ambiente.
                error_log("Falha ao criar token persistente: " . $rememberError->getMessage());
                authForgetCookie();
            }


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

    <link rel="icon" type="image/png" href="../static/images/librashub-logo.png">

    <link
        rel="stylesheet"
        href="../static/css/style.css"
    >
    <link rel="stylesheet" href="../static/css/app-shell.css">
    <link rel="stylesheet" href="../static/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>


<body class="app-shell auth-page">


<div class="auth-wrap">


    <div class="auth-box">

        <a class="auth-brand" href="../index.php"><img src="../static/images/librashub-logo.png" alt=""><span>LibrasHub</span></a>


        <div class="auth-icon">

            <i class="fa-solid fa-user" aria-hidden="true"></i>

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

                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>

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
            action="Login.php"
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

        <a class="auth-back" href="../index.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Voltar para a página inicial</a>


    </div>


</div>


<script src="../static/js/password-toggle.js?v=1" defer></script>
<script src="../static/js/acessibility.js?v=67" defer></script>
<script src="../static/js/i18n.js" defer></script>
</body>

</html>
