<?php

session_start();

require_once "configs/config.php";


$erro = "";
$sucesso = "";


$TIPOS_PERMITIDOS = [
    "Usuário Simples",
    "Intérprete",
    "Educador"
];


if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $nome = trim(
        $_POST["nome"]
        ?? ""
    );

    $email = trim(
        $_POST["email"]
        ?? ""
    );

    $tipoUsuario = trim(
        $_POST["tipo_usuario"]
        ?? "Usuário Simples"
    );

    $senha =
        $_POST["senha"]
        ?? "";

    $confirmarSenha =
        $_POST["confirmar_senha"]
        ?? "";


    if (
        $nome === "" ||
        $email === "" ||
        $senha === "" ||
        $confirmarSenha === ""
    ) {

        $erro =
            "Preencha todos os campos obrigatórios.";

    } elseif (
        mb_strlen($nome) < 2
    ) {

        $erro =
            "Informe um nome válido.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erro =
            "Informe um email válido.";

    } elseif (
        !in_array(
            $tipoUsuario,
            $TIPOS_PERMITIDOS,
            true
        )
    ) {

        $erro =
            "Tipo de usuário inválido.";

    } elseif (
        strlen($senha) < 6
    ) {

        $erro =
            "A senha deve possuir pelo menos 6 caracteres.";

    } elseif (
        $senha !==
        $confirmarSenha
    ) {

        $erro =
            "As senhas não coincidem.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id_usuario
                FROM usuario
                WHERE email_usuario = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);


            if (
                $stmt->fetchColumn()
            ) {

                $erro =
                    "Já existe uma conta cadastrada com este email.";

            } else {

                $senhaHash =
                    password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );


                if (
                    $senhaHash === false
                ) {

                    $erro =
                        "Não foi possível processar a senha.";

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO usuario (
                            nm_usuario,
                            email_usuario,
                            senha_usuario,
                            tp_usuario
                        )
                        VALUES (
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");


                    $stmt->execute([
                        $nome,
                        $email,
                        $senhaHash,
                        $tipoUsuario
                    ]);


                    $sucesso =
                        "Conta criada com sucesso. "
                        . "Você já pode entrar no sistema.";


                    $_POST = [];
                }
            }


        } catch (
            PDOException $e
        ) {

            if (
                $e->getCode() === "23000"
            ) {

                $erro =
                    "Este email já está cadastrado.";

            } else {

                $erro =
                    "Não foi possível criar a conta. "
                    . "Tente novamente.";
            }
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

    <title>LibrasHub - Cadastro</title>

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

            Criar Conta

        </div>


        <div class="auth-subtitle">

            Cadastre-se no LibrasHub

        </div>


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


        <?php if ($sucesso): ?>

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


        <form
            method="POST"
            action="cadastro.php"
        >


            <div class="field">

                <label for="nome">
                    Nome Completo
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Seu nome"
                    autocomplete="name"
                    value="<?= htmlspecialchars(
                        $_POST["nome"]
                        ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


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

                <label for="tipo_usuario">
                    Tipo de Usuário
                </label>

                <select
                    name="tipo_usuario"
                    id="tipo_usuario"
                    required
                >

                    <?php foreach (
                        $TIPOS_PERMITIDOS
                        as $tipo
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $tipo,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            <?= (
                                ($_POST["tipo_usuario"] ?? "Usuário Simples")
                                ===
                                $tipo
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            <?= htmlspecialchars(
                                $tipo,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

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
                    autocomplete="new-password"
                    required
                    minlength="6"
                >

            </div>


            <div class="field">

                <label for="confirmar_senha">
                    Confirmar Senha
                </label>

                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    required
                    minlength="6"
                >

            </div>


            <button
                type="submit"
                class="btn btn-block"
            >
                Criar Conta
            </button>


        </form>


        <div class="auth-footer">

            Já tem uma conta?

            <a href="login.php">
                Entrar
            </a>

        </div>


    </div>


</div>


</body>

</html>