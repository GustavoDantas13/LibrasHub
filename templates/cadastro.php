<?php 

session_start(); 

require_once "configs/config.php"; 


$erro = ""; 
$sucesso = ""; 


$TIPOS_PERMITIDOS = [ 
    "Usuário Comum", 
    "Usuário Comunitário" 
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
        ?? "Usuário Comum" 
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
        content="width=device-width, initial-scale=1.0, viewport-fit=cover" 
    > 

    <title>LibrasHub - Cadastro</title> 

    <link 
        rel="stylesheet" 
        href="../static/css/style.css" 
    > 

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
        }

        .auth-wrap {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(24px, 5vw, 56px) 20px;
        }

        .auth-box {
            width: min(100%, 480px);
            max-width: 480px;
            margin: 0 auto;
        }

        .auth-icon {
            font-size: clamp(2.2rem, 6vw, 3rem);
            line-height: 1;
        }

        .auth-title {
            font-size: clamp(1.55rem, 4vw, 2rem);
            line-height: 1.2;
        }

        .auth-subtitle {
            font-size: clamp(.88rem, 2vw, 1rem);
            line-height: 1.5;
        }

        .field {
            width: 100%;
        }

        .field input,
        .field select {
            width: 100%;
            max-width: 100%;
            min-height: 46px;
        }

        .btn.btn-block {
            width: 100%;
            min-height: 46px;
            white-space: normal;
        }

        .alert {
            width: 100%;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .auth-footer {
            text-align: center;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .auth-wrap {
                align-items: flex-start;
                padding: 34px 18px;
            }

            .auth-box {
                width: 100%;
                max-width: 520px;
            }
        }

        @media (max-width: 600px) {
            .auth-wrap {
                padding: 22px 14px;
            }

            .auth-box {
                padding: 22px 18px;
                border-radius: 14px;
            }

            .auth-title,
            .auth-subtitle,
            .auth-icon {
                text-align: center;
            }

            .field {
                margin-bottom: 14px;
            }

            .field label {
                display: block;
                margin-bottom: 6px;
            }

            .field input,
            .field select {
                min-height: 48px;
                font-size: 16px;
            }

            .btn.btn-block {
                min-height: 48px;
                font-size: 16px;
            }

            .alert {
                font-size: .9rem;
            }
        }

        @media (max-width: 380px) {
            .auth-wrap {
                padding: 14px 10px;
            }

            .auth-box {
                padding: 18px 14px;
                border-radius: 12px;
            }

            .auth-title {
                font-size: 1.45rem;
            }

            .auth-subtitle {
                font-size: .86rem;
            }
        }

        @media (min-width: 1200px) {
            .auth-box {
                max-width: 500px;
            }
        }
    </style>

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
                                ($_POST["tipo_usuario"] ?? "Usuário Comum") 
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