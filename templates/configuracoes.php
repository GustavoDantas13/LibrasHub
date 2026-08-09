<?php

session_start();

require_once "configs/config.php";

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
        tp_usuario,
        dt_usuario
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

if (
    isset($_GET["acao"]) &&
    $_GET["acao"] === "exportar_dados"
) {
    $stmt = $pdo->prepare("
        SELECT
            h.id_historico,
            h.id_gesto,
            g.nm_gesto,
            h.url_arquivo,
            h.texto_resultado,
            h.criado_em
        FROM historico h
        LEFT JOIN gesto g
            ON g.id_gesto = h.id_gesto
        WHERE h.id_usuario = ?
        ORDER BY h.criado_em DESC
    ");

    $stmt->execute([
        $idUsuarioAtual
    ]);

    $historico = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    $dados = [
        "usuario" => [
            "id_usuario" =>
                (int) $usuarioAtual["id_usuario"],
            "nome" =>
                $usuarioAtual["nm_usuario"],
            "email" =>
                $usuarioAtual["email_usuario"],
            "tipo" =>
                $usuarioAtual["tp_usuario"],
            "membro_desde" =>
                $usuarioAtual["dt_usuario"]
        ],
        "historico" => $historico
    ];

    header(
        "Content-Type: application/json; charset=utf-8"
    );

    header(
        'Content-Disposition: attachment; filename="librashub_dados.json"'
    );

    echo json_encode(
        $dados,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["acao"] ?? "") === "limpar_historico"
) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM historico
            WHERE id_usuario = ?
        ");

        $stmt->execute([
            $idUsuarioAtual
        ]);

        $sucesso =
            "Histórico removido com sucesso.";

    } catch (PDOException $e) {
        $erro =
            "Não foi possível limpar o histórico.";
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
    LibrasHub - Configurações
</title>

<link
    rel="stylesheet"
    href="../static/css/style.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
    integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
>

<script>

(function(){

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

    } catch (erro) {}

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

.seg-row{
    display:flex;
    gap:8px;
    margin:10px 0 16px 0;
}

.seg-btn{
    flex:1;
    padding:9px;
    text-align:center;
    border:1px solid var(--border);
    border-radius:8px;
    font-size:0.8125rem;
    cursor:pointer;
    background:var(--surface);
    color:var(--text);
}

.seg-btn.active{
    background:var(--primary);
    color:var(--primary-text);
    border-color:var(--primary);
    font-weight:600;
}

.row-between{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    padding:12px 0;
    border-bottom:1px solid var(--bg);
}

.row-between:last-child{
    border-bottom:none;
}

.row-title{
    font-size:0.875rem;
    font-weight:600;
}

.row-desc{
    font-size:0.75rem;
    color:var(--text-muted);
}

.link-item{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 0;
    border:0;
    border-bottom:1px solid var(--bg);
    background:transparent;
    color:var(--text);
    font:inherit;
    font-size:0.875rem;
    text-align:left;
    cursor:pointer;
}

.link-item:last-child{
    border-bottom:none;
}

.link-item:hover{
    color:var(--text-muted);
}

.settings-form{
    margin:0;
}

.settings-alert{
    max-width:640px;
    margin-bottom:16px;
}

@media(max-width:900px){

    .content{
        margin-left:0;
        padding:20px;
    }

}

@media(max-width:600px){

    .seg-row{
        flex-direction:column;
    }

    .row-between{
        align-items:flex-start;
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
        Configurações
    </div>


    <?php if ($erro !== ""): ?>

        <div class="alert alert-error settings-alert">

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

        <div class="alert alert-success settings-alert">

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


    <div
        class="panel"
        style="max-width:640px;"
    >

        <div class="section-title">
            ◐ Aparência
        </div>


        <div
            class="row-desc"
            style="margin-bottom:6px;"
        >
            Tema
        </div>


        <div
            class="seg-row"
            id="segTheme"
        >

            <button
                type="button"
                class="seg-btn"
                data-value="claro"
                onclick="setTheme(this)"
            >
                Claro
            </button>

            <button
                type="button"
                class="seg-btn"
                data-value="escuro"
                onclick="setTheme(this)"
            >
                Escuro
            </button>

            <button
                type="button"
                class="seg-btn"
                data-value="automatico"
                onclick="setTheme(this)"
            >
                Automático
            </button>

        </div>


        <div
            class="row-desc"
            style="margin-bottom:6px;"
        >
            Tamanho da Fonte
        </div>


        <div
            class="seg-row"
            id="segFont"
        >

            <button
                type="button"
                class="seg-btn"
                data-value="pequena"
                onclick="setFontSize(this)"
            >
                Pequena
            </button>

            <button
                type="button"
                class="seg-btn"
                data-value="media"
                onclick="setFontSize(this)"
            >
                Média
            </button>

            <button
                type="button"
                class="seg-btn"
                data-value="grande"
                onclick="setFontSize(this)"
            >
                Grande
            </button>

        </div>


        <div class="row-between">

            <div>

                <div class="row-title">
                    Alto Contraste
                </div>

                <div class="row-desc">
                    Melhora a legibilidade
                </div>

            </div>

            <button
                type="button"
                class="toggle"
                id="toggleContrast"
                onclick="setContrast(this)"
                aria-label="Ativar ou desativar alto contraste"
            ></button>

        </div>


        <div class="section-title">
            🌐 Idioma
        </div>


        <div class="field">

            <label for="idiomaInterface">
                Idioma da Interface
            </label>

            <select
                id="idiomaInterface"
                onchange="salvarPreferencia('libras_idioma', this.value)"
            >
                <option value="pt-BR">
                    Português (Brasil)
                </option>
                <option value="en">
                    English
                </option>
                <option value="es">
                    Español
                </option>
            </select>

        </div>

    </div>


    <div
        class="panel"
        style="
            max-width:640px;
            margin-top:20px;
        "
    >

        <div class="section-title">
            🔔 Notificações
        </div>


        <div class="row-between">

            <div>

                <div class="row-title">
                    Notificações de Email
                </div>

                <div class="row-desc">
                    Receber atualizações por email
                </div>

            </div>

            <button
                type="button"
                class="toggle"
                id="toggleEmail"
                onclick="alternarPreferencia(this, 'libras_notificacoes_email')"
                aria-label="Ativar ou desativar notificações de email"
            ></button>

        </div>


        <div class="row-between">

            <div>

                <div class="row-title">
                    Sons
                </div>

                <div class="row-desc">
                    Reproduzir sons nas ações
                </div>

            </div>

            <button
                type="button"
                class="toggle"
                id="toggleSons"
                onclick="alternarPreferencia(this, 'libras_sons')"
                aria-label="Ativar ou desativar sons"
            ></button>

        </div>


        <div class="section-title">
            🔒 Privacidade e Segurança
        </div>


        <div class="row-between">

            <div>

                <div class="row-title">
                    Armazenar Vídeos da Câmera
                </div>

                <div class="row-desc">
                    Salvar capturas para melhorar traduções
                </div>

            </div>

            <button
                type="button"
                class="toggle"
                id="toggleVideos"
                onclick="alternarPreferencia(this, 'libras_armazenar_videos')"
                aria-label="Ativar ou desativar armazenamento de vídeos"
            ></button>

        </div>


        <div class="row-between">

            <div>

                <div class="row-title">
                    Perfil Público
                </div>

                <div class="row-desc">
                    Tornar seu perfil visível para outros
                </div>

            </div>

            <button
                type="button"
                class="toggle"
                id="togglePerfil"
                onclick="alternarPreferencia(this, 'libras_perfil_publico')"
                aria-label="Ativar ou desativar perfil público"
            ></button>

        </div>


        <div class="section-title">
            📁 Dados
        </div>


        <button
            type="button"
            class="link-item"
            onclick="window.location.href='configuracoes.php?acao=exportar_dados'"
        >
            <span>
                Exportar Dados
            </span>

            <i class="fa-solid fa-download"></i>
        </button>


        <form
            method="POST"
            action="configuracoes.php"
            class="settings-form"
            onsubmit="return confirm('Tem certeza que deseja limpar todo o seu histórico?');"
        >

            <input
                type="hidden"
                name="acao"
                value="limpar_historico"
            >

            <button
                type="submit"
                class="link-item"
            >
                <span>
                    Limpar Histórico
                </span>

                <i class="fa-solid fa-trash"></i>
            </button>

        </form>


        <button
            type="button"
            class="link-item"
            onclick="limparCacheLocal()"
        >
            <span>
                Limpar Cache
            </span>

            <i class="fa-solid fa-broom"></i>
        </button>

    </div>

</main>


<script>

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

const memoryStore = {};


function safeGet(
    key,
    fallback
) {

    try {

        const valor =
            localStorage.getItem(
                key
            );

        return valor !== null
            ? valor
            : fallback;

    } catch (erro) {

        return key in memoryStore
            ? memoryStore[key]
            : fallback;
    }
}


function safeSet(
    key,
    value
) {

    memoryStore[key] =
        value;

    try {

        localStorage.setItem(
            key,
            value
        );

    } catch (erro) {}
}


function applyTheme(
    theme
) {

    const html =
        document.documentElement;

    let efetivo =
        theme;

    if (
        theme === "automatico"
    ) {

        efetivo =
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
        efetivo === "escuro"
    ) {

        html.setAttribute(
            "data-theme",
            "dark"
        );

    } else {

        html.removeAttribute(
            "data-theme"
        );
    }
}


function applyFontSize(
    size
) {

    document.documentElement
        .style
        .setProperty(
            "--font-scale",
            FONT_SCALES[size]
            || 1
        );
}


function applyContrast(
    state
) {

    document.documentElement
        .classList
        .toggle(
            "high-contrast",
            state === "on"
        );
}


function setTheme(
    elemento
) {

    document
        .querySelectorAll(
            "#segTheme .seg-btn"
        )
        .forEach(
            botao =>
                botao.classList.remove(
                    "active"
                )
        );

    elemento.classList.add(
        "active"
    );

    safeSet(
        KEYS.theme,
        elemento.dataset.value
    );

    applyTheme(
        elemento.dataset.value
    );
}


function setFontSize(
    elemento
) {

    document
        .querySelectorAll(
            "#segFont .seg-btn"
        )
        .forEach(
            botao =>
                botao.classList.remove(
                    "active"
                )
        );

    elemento.classList.add(
        "active"
    );

    safeSet(
        KEYS.fontSize,
        elemento.dataset.value
    );

    applyFontSize(
        elemento.dataset.value
    );
}


function setContrast(
    elemento
) {

    const ligado =
        !elemento.classList.contains(
            "on"
        );

    elemento.classList.toggle(
        "on",
        ligado
    );

    const estado =
        ligado
            ? "on"
            : "off";

    safeSet(
        KEYS.contrast,
        estado
    );

    applyContrast(
        estado
    );
}


function salvarPreferencia(
    chave,
    valor
) {

    safeSet(
        chave,
        valor
    );
}


function alternarPreferencia(
    elemento,
    chave
) {

    const ligado =
        !elemento.classList.contains(
            "on"
        );

    elemento.classList.toggle(
        "on",
        ligado
    );

    safeSet(
        chave,
        ligado
            ? "on"
            : "off"
    );
}


function carregarToggle(
    id,
    chave,
    padrao
) {

    const elemento =
        document.getElementById(
            id
        );

    if (!elemento) {
        return;
    }

    elemento.classList.toggle(
        "on",
        safeGet(
            chave,
            padrao
        ) === "on"
    );
}


function limparCacheLocal() {

    const confirmar =
        confirm(
            "Deseja limpar as preferências locais deste navegador?"
        );

    if (!confirmar) {
        return;
    }

    const chaves = [
        "libras_theme",
        "libras_fontsize",
        "libras_contrast",
        "libras_idioma",
        "libras_notificacoes_email",
        "libras_sons",
        "libras_armazenar_videos",
        "libras_perfil_publico"
    ];

    chaves.forEach(
        chave => {

            try {
                localStorage.removeItem(
                    chave
                );
            } catch (erro) {}
        }
    );

    window.location.reload();
}


(function iniciar(){

    const tema =
        safeGet(
            KEYS.theme,
            "claro"
        );

    applyTheme(
        tema
    );

    document.querySelector(
        `#segTheme .seg-btn[data-value="${tema}"]`
    )?.classList.add(
        "active"
    );


    const fonte =
        safeGet(
            KEYS.fontSize,
            "media"
        );

    applyFontSize(
        fonte
    );

    document.querySelector(
        `#segFont .seg-btn[data-value="${fonte}"]`
    )?.classList.add(
        "active"
    );


    const contraste =
        safeGet(
            KEYS.contrast,
            "off"
        );

    applyContrast(
        contraste
    );

    document
        .getElementById(
            "toggleContrast"
        )
        ?.classList
        .toggle(
            "on",
            contraste === "on"
        );


    const idioma =
        safeGet(
            "libras_idioma",
            "pt-BR"
        );

    const selectIdioma =
        document.getElementById(
            "idiomaInterface"
        );

    if (selectIdioma) {
        selectIdioma.value =
            idioma;
    }


    carregarToggle(
        "toggleEmail",
        "libras_notificacoes_email",
        "on"
    );

    carregarToggle(
        "toggleSons",
        "libras_sons",
        "off"
    );

    carregarToggle(
        "toggleVideos",
        "libras_armazenar_videos",
        "off"
    );

    carregarToggle(
        "togglePerfil",
        "libras_perfil_publico",
        "on"
    );

})();

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

    if (
        !btn ||
        !sidebar ||
        !overlay
    ) {
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

                if (
                    link.dataset.page ===
                    path
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
