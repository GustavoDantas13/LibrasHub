<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

session_start();

require_once "configs/config.php";
require_once "configs/email.php";
require_once "../vendor/autoload.php";

if (empty($_SESSION["usuario_id"])) {
    header("Location: ../index.php");
    exit;
}

$idUsuarioAtual = (int) $_SESSION["usuario_id"];

$stmt = $pdo->prepare("
    SELECT
        id_usuario,
        nm_usuario,
        email_usuario,
        tp_usuario
    FROM usuario
    WHERE id_usuario = ?
    LIMIT 1
");

$stmt->execute([
    $idUsuarioAtual
]);

$usuarioAtual = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$usuarioAtual) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../index.php");
    exit;
}

$_SESSION["usuario_tipo"] =
    $usuarioAtual["tp_usuario"];

$ehAdmin =
    strcasecmp(
        trim(
            $usuarioAtual["tp_usuario"]
        ),
        "Administrador"
    ) === 0;

$erro = "";
$sucesso = "";

$nomeContato =
    $usuarioAtual["nm_usuario"];

$emailContato =
    $usuarioAtual["email_usuario"];

$mensagemContato = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["acao"] ?? "") === "enviar_contato"
) {

    $nomeContato = trim(
        $_POST["nome"]
        ?? ""
    );

    $emailContato = trim(
        $_POST["email"]
        ?? ""
    );

    $mensagemContato = trim(
        $_POST["mensagem"]
        ?? ""
    );

    if (
        $nomeContato === ""
        ||
        $emailContato === ""
        ||
        $mensagemContato === ""
    ) {

        $erro =
            "Preencha todos os campos.";

    } elseif (
        !filter_var(
            $emailContato,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erro =
            "Informe um email válido.";

    } elseif (
        mb_strlen(
            $mensagemContato
        ) < 10
    ) {

        $erro =
            "A mensagem deve possuir pelo menos 10 caracteres.";

    } elseif (
        mb_strlen(
            $mensagemContato
        ) > 5000
    ) {

        $erro =
            "A mensagem é muito longa.";

    } else {

        $nomeSeguro =
            str_replace(
                [
                    "\r",
                    "\n"
                ],
                "",
                $nomeContato
            );

        $emailSeguro =
            str_replace(
                [
                    "\r",
                    "\n"
                ],
                "",
                $emailContato
            );

        try {

            $mail =
                new PHPMailer(
                    true
                );

            $mail->isSMTP();

            $mail->Host =
                SMTP_HOST;

            $mail->SMTPAuth =
                true;

            $mail->Username =
                SMTP_USUARIO;

            $mail->Password =
                SMTP_SENHA;

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                SMTP_PORT;

            $mail->CharSet =
                "UTF-8";

            $mail->Timeout =
                20;

            $mail->setFrom(
                SMTP_REMETENTE,
                SMTP_NOME
            );

            $mail->addAddress(
                EMAIL_SUPORTE
            );

            $mail->addReplyTo(
                $emailSeguro,
                $nomeSeguro
            );

            $mail->isHTML(
                false
            );

            $mail->Subject =
                "Contato LibrasHub - "
                .
                $nomeSeguro;

            $mail->Body =
                "Nova mensagem enviada pela página de ajuda do LibrasHub.\n\n"
                .
                "Usuário: "
                .
                $nomeSeguro
                .
                "\n"
                .
                "Email: "
                .
                $emailSeguro
                .
                "\n"
                .
                "ID do usuário: "
                .
                $idUsuarioAtual
                .
                "\n\n"
                .
                "Mensagem:\n"
                .
                $mensagemContato
                .
                "\n";

            $mail->send();

            $sucesso =
                "Mensagem enviada com sucesso.";

            $mensagemContato =
                "";

        } catch (
            Exception $e
        ) {

            $erro =
                "Não foi possível enviar a mensagem. "
                .
                $mail->ErrorInfo;
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

<link
    rel="icon"
    type="image/png"
    href="../static/images/librashub-logo.png"
>

<title>
    LibrasHub - Ajuda
</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
>

<link
    rel="stylesheet"
    href="../static/css/style.css"
>

<script>

(function(){

    try{

        var KEYS = {
            theme: "libras_theme",
            fontSize: "libras_fontsize",
            contrast: "libras_contrast"
        };

        var FONT_SCALES = {
            pequena: 0.9,
            media: 1,
            grande: 1.15
        };

        function safeGet(
            key,
            fallback
        ){

            try{

                var valor =
                    localStorage.getItem(
                        key
                    );

                return valor !== null
                    ? valor
                    : fallback;

            }catch(e){

                return fallback;
            }
        }

        var tema =
            safeGet(
                KEYS.theme,
                "claro"
            );

        var efetivo =
            tema;

        if(
            tema === "automatico"
        ){

            efetivo =
                (
                    window.matchMedia
                    &&
                    window.matchMedia(
                        "(prefers-color-scheme: dark)"
                    ).matches
                )
                    ? "escuro"
                    : "claro";
        }

        if(
            efetivo === "escuro"
        ){

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

        if(
            safeGet(
                KEYS.contrast,
                "off"
            ) === "on"
        ){

            document.documentElement
                .classList
                .add(
                    "high-contrast"
                );
        }

    }catch(e){}

})();

</script>

<style>

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    overflow-y:auto;
}

.content{
    margin-left:260px;
    flex:1;
    padding:40px;
}

.ajuda-grid{
    display:grid;
    grid-template-columns:1.3fr 1fr;
    gap:20px;
}

.faq-item{
    border-bottom:1px solid var(--bg);
}

.faq-q{
    padding:14px 0;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    cursor:pointer;
    font-size:0.875rem;
    font-weight:600;
}

.faq-q span{
    transition:transform 0.2s;
}

.faq-a{
    display:none;
    padding-bottom:14px;
    font-size:0.8125rem;
    color:var(--text-muted);
    line-height:1.5;
}

.faq-item.open .faq-a{
    display:block;
}

.faq-item.open .faq-q span{
    transform:rotate(180deg);
}

.tips-box{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:10px;
    padding:18px;
    margin-top:16px;
}

.tips-box ul{
    padding-left:18px;
    font-size:0.8125rem;
    line-height:1.8;
    color:var(--text-muted);
}

.contact-alert{
    margin-bottom:16px;
}

.contact-note{
    margin-top:12px;
    color:var(--text-muted);
    font-size:0.72rem;
    line-height:1.5;
}

@media(max-width:900px){

    .content{
        margin-left:0;
        padding:20px;
    }

    .ajuda-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:600px){

    .content{
        padding:16px;
    }
}

</style>

</head>

<body>


<aside class="sidebar">

    <div class="sidebar-top">

        <div class="logo">

            <img
                src="../static/images/librashub-logo.png"
                alt="LibrasHub"
                class="logo-img"
                style="
                    width:32px;
                    height:32px;
                    object-fit:contain;
                    border-radius:6px;
                "
            >

            LibrasHub

        </div>


        <a
            class="nav-item"
            href="home.php"
            data-page="home"
        >

            <span class="nav-icon">

                <i
                    class="fa-regular fa-house"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Início

        </a>


        <a
            class="nav-item"
            href="leitor.php"
            data-page="leitor"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-video"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Leitor

        </a>


        <a
            class="nav-item"
            href="upload.php"
            data-page="upload"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-upload"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Upload

        </a>


        <a
            class="nav-item"
            href="historico.php"
            data-page="historico"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-arrow-rotate-left"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Histórico

        </a>


        <a
            class="nav-item"
            href="ajuda.php"
            data-page="ajuda"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-question"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Ajuda

        </a>


        <a
            class="nav-item"
            href="comunidade.php"
            data-page="comunidade"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-users"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Comunidade

        </a>


        <?php if ($ehAdmin): ?>

            <a
                class="nav-item"
                href="admin.php"
                data-page="admin"
            >

                <span class="nav-icon">

                    <i
                        class="fa-solid fa-shield-halved"
                        style="color:#fdbe00;"
                    ></i>

                </span>

                Administração

            </a>

        <?php endif; ?>

    </div>


    <div class="sidebar-bottom">

        <a
            class="nav-item"
            href="configuracoes.php"
            data-page="configuracoes"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-gear"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Configurações

        </a>


        <a
            class="nav-item"
            href="usuario.php"
            data-page="usuario"
        >

            <span class="nav-icon">

                <i
                    class="fa-solid fa-user"
                    style="color:#fdbe00;"
                ></i>

            </span>

            Usuário

        </a>

    </div>

</aside>


<main class="content">

    <div class="page-title">
        Ajuda
    </div>

    <div class="page-subtitle">
        Encontre respostas rápidas ou entre em contato com o suporte.
    </div>


    <?php if ($erro !== ""): ?>

        <div class="alert alert-error contact-alert">

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

        <div class="alert alert-success contact-alert">

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


    <div class="ajuda-grid">


        <div>


            <div class="panel">

                <div
                    class="side-title"
                    style="
                        font-size:0.75rem;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    ❓ PERGUNTAS FREQUENTES
                </div>


                <div class="faq-item">

                    <div
                        class="faq-q"
                        onclick="toggleFaq(this)"
                    >
                        Como usar a câmera?
                        <span>⌄</span>
                    </div>

                    <div class="faq-a">
                        Vá até a tela Leitor e clique diretamente na área da câmera.
                        Conceda a permissão solicitada pelo navegador para iniciar a tradução.
                    </div>

                </div>


                <div class="faq-item">

                    <div
                        class="faq-q"
                        onclick="toggleFaq(this)"
                    >
                        Como enviar vídeos?
                        <span>⌄</span>
                    </div>

                    <div class="faq-a">
                        Na tela Upload, arraste os arquivos até a área indicada
                        ou clique em "Selecionar arquivos".
                    </div>

                </div>


                <div class="faq-item">

                    <div
                        class="faq-q"
                        onclick="toggleFaq(this)"
                    >
                        Problemas com a câmera?
                        <span>⌄</span>
                    </div>

                    <div class="faq-a">
                        Verifique as permissões de câmera do navegador
                        e se nenhum outro aplicativo está utilizando o dispositivo.
                    </div>

                </div>


                <div class="faq-item">

                    <div
                        class="faq-q"
                        onclick="toggleFaq(this)"
                    >
                        Quais formatos são suportados?
                        <span>⌄</span>
                    </div>

                    <div class="faq-a">
                        Imagens: JPG, JPEG e PNG.
                        Vídeos: MP4, AVI, MOV e MKV.
                    </div>

                </div>

            </div>


            <div class="tips-box">

                <div
                    class="side-title"
                    style="
                        font-size:0.75rem;
                        font-weight:700;
                        margin-bottom:6px;
                    "
                >
                    💡 DICAS PARA MELHOR PRECISÃO
                </div>

                <ul>
                    <li>
                        Use boa iluminação ou uma fonte de luz
                    </li>

                    <li>
                        Mantenha as mãos bem visíveis
                    </li>

                    <li>
                        Faça gestos de forma clara e pausada
                    </li>

                    <li>
                        Verifique se sua câmera não está muito longe
                    </li>
                </ul>

            </div>

        </div>


        <div class="panel">

            <div
                class="side-title"
                style="
                    font-size:0.75rem;
                    font-weight:700;
                    margin-bottom:12px;
                "
            >
                ✉ ENTRE EM CONTATO
            </div>


            <form
                method="POST"
                action="ajuda.php"
            >

                <input
                    type="hidden"
                    name="acao"
                    value="enviar_contato"
                >


                <div class="field">

                    <label for="nome">
                        Nome
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Seu nome"
                        value="<?= htmlspecialchars(
                            $nomeContato,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="255"
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
                        value="<?= htmlspecialchars(
                            $emailContato,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        maxlength="255"
                        required
                    >

                </div>


                <div class="field">

                    <label for="mensagem">
                        Mensagem
                    </label>

                    <textarea
                        id="mensagem"
                        name="mensagem"
                        rows="5"
                        placeholder="Descreva sua dúvida ou problema..."
                        maxlength="5000"
                        required
                    ><?= htmlspecialchars(
                        $mensagemContato,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></textarea>

                </div>


                <button
                    type="submit"
                    class="btn btn-block"
                >
                    Enviar Mensagem
                </button>

            </form>


            <div class="contact-note">
                Sua mensagem será enviada diretamente para o suporte do LibrasHub.
            </div>

        </div>


    </div>

</main>


<script>

function toggleFaq(
    elemento
){

    const item =
        elemento.closest(
            ".faq-item"
        );

    if(
        !item
    ){
        return;
    }

    item.classList.toggle(
        "open"
    );
}

</script>


<button
    class="menu-toggle"
    id="menuToggle"
    aria-label="Abrir menu"
>
    &#9776;
</button>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<script>

(function(){

    var btn =
        document.getElementById(
            "menuToggle"
        );

    var sidebar =
        document.querySelector(
            ".sidebar"
        );

    var overlay =
        document.getElementById(
            "sidebarOverlay"
        );

    if(
        !btn
        ||
        !sidebar
        ||
        !overlay
    ){
        return;
    }

    function open(){

        sidebar.classList.add(
            "open"
        );

        overlay.classList.add(
            "open"
        );

        btn.innerHTML =
            "&#10005;";
    }

    function close(){

        sidebar.classList.remove(
            "open"
        );

        overlay.classList.remove(
            "open"
        );

        btn.innerHTML =
            "&#9776;";
    }

    btn.addEventListener(
        "click",
        function(){

            sidebar.classList.contains(
                "open"
            )
                ? close()
                : open();
        }
    );

    overlay.addEventListener(
        "click",
        close
    );

    sidebar
        .querySelectorAll(
            "a"
        )
        .forEach(
            function(link){

                link.addEventListener(
                    "click",
                    close
                );
            }
        );

})();

</script>


<script>

(function(){

    var path =
        window.location.pathname
            .split("/")
            .pop()
            .replace(
                /\.php$/,
                ""
            );

    document
        .querySelectorAll(
            ".sidebar .nav-item[data-page]"
        )
        .forEach(
            function(link){

                if(
                    link.dataset.page ===
                    path
                ){

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
