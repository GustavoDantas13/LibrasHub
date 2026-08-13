<?php

session_start();

require_once "configs/config.php";

if (empty($_SESSION["usuario_id"])) {

    header("Location: ../index.php");

    exit;
}


$idUsuarioAtual =
    (int) $_SESSION["usuario_id"];


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


$usuarioAtual =
    $stmt->fetch(
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
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["acao"] ?? "") === "excluir_historico"
) {

    $idHistorico =
        (int) (
            $_POST["id_historico"]
            ?? 0
        );


    if ($idHistorico <= 0) {

        $erro =
            "Registro de histórico inválido.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT url_arquivo
                FROM historico
                WHERE
                    id_historico = ?
                    AND id_usuario = ?
                LIMIT 1
            ");


            $stmt->execute([
                $idHistorico,
                $idUsuarioAtual
            ]);


            $registroExcluir =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                !$registroExcluir
            ) {

                $erro =
                    "Registro não encontrado.";

            } else {

                $stmt = $pdo->prepare("
                    DELETE FROM historico
                    WHERE
                        id_historico = ?
                        AND id_usuario = ?
                ");


                $stmt->execute([
                    $idHistorico,
                    $idUsuarioAtual
                ]);


                if (
                    $stmt->rowCount() > 0
                ) {

                    $urlArquivoExcluir =
                        trim(
                            (string) (
                                $registroExcluir[
                                    "url_arquivo"
                                ]
                                ??
                                ""
                            )
                        );


                    if (
                        $urlArquivoExcluir !== ""
                        &&
                        str_starts_with(
                            $urlArquivoExcluir,
                            "uploads_historico/"
                        )
                    ) {

                        $caminhoArquivoExcluir =
                            __DIR__
                            .
                            DIRECTORY_SEPARATOR
                            .
                            str_replace(
                                "/",
                                DIRECTORY_SEPARATOR,
                                $urlArquivoExcluir
                            );


                        if (
                            is_file(
                                $caminhoArquivoExcluir
                            )
                        ) {

                            @unlink(
                                $caminhoArquivoExcluir
                            );
                        }
                    }


                    $sucesso =
                        "Registro removido do histórico.";

                } else {

                    $erro =
                        "Registro não encontrado.";
                }
            }


        } catch (PDOException $e) {

            $erro =
                "Não foi possível excluir o registro.";
        }
    }
}


try {

    $stmt = $pdo->prepare("
        SELECT
            h.id_historico,
            h.id_usuario,
            h.id_gesto,
            h.url_arquivo,
            h.texto_resultado,
            h.criado_em,
            g.nm_gesto

        FROM historico h

        LEFT JOIN gesto g
            ON g.id_gesto = h.id_gesto

        WHERE
            h.id_usuario = ?

        ORDER BY
            h.criado_em DESC,
            h.id_historico DESC
    ");


    $stmt->execute([
        $idUsuarioAtual
    ]);


    $historicos =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $historicos = [];

    $erro =
        "Não foi possível carregar o histórico.";
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

<link
    rel="icon"
    type="image/png"
    href="../static/images/librashub-logo.png"
>

<title>
    LibrasHub - Histórico
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

            theme:
                "libras_theme",

            fontSize:
                "libras_fontsize",

            contrast:
                "libras_contrast"

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
                ]
                || 1
            );


        if(
            safeGet(
                KEYS.contrast,
                "off"
            )
            ===
            "on"
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
    min-width:0;
}

.history-container{
    width:100%;
    max-width:1200px;
}

.history-layout{
    display:grid;
    grid-template-columns:minmax(0, 1fr) minmax(320px, 420px);
    gap:20px;
    align-items:start;
}

.history-column{
    min-width:0;
}

.history-detail{
    position:sticky;
    top:24px;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:12px;
    padding:18px;
    min-height:360px;
}

.history-detail.empty{
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    color:var(--text-muted);
}

.history-detail-title{
    font-size:0.75rem;
    font-weight:700;
    color:var(--text-muted);
    margin-bottom:12px;
    text-transform:uppercase;
    letter-spacing:0.04em;
}

.history-media{
    width:100%;
    border-radius:10px;
    overflow:hidden;
    background:#000;
    margin-bottom:16px;
}

.history-media video,
.history-media img{
    display:block;
    width:100%;
    max-height:280px;
    object-fit:contain;
    background:#000;
}

.history-detail-text{
    font-size:1.05rem;
    font-weight:700;
    color:var(--text);
    line-height:1.5;
    margin-bottom:14px;
}

.history-detail-grid{
    display:grid;
    gap:10px;
}

.history-detail-row{
    padding:10px 0;
    border-top:1px solid var(--border);
}

.history-detail-label{
    font-size:0.68rem;
    font-weight:700;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:0.04em;
    margin-bottom:4px;
}

.history-detail-value{
    font-size:0.82rem;
    color:var(--text);
    word-break:break-word;
}

.history-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}

.history-count{
    font-size:0.75rem;
    color:var(--text-muted);
}

.history-list{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.hist-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:16px;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:10px;
    cursor:pointer;
    transition:
        border-color 0.15s,
        background 0.15s;
}

.hist-item:hover,
.hist-item.selected{
    border-color:var(--primary);
}

.hist-item.selected{
    background:
        color-mix(
            in srgb,
            var(--primary) 6%,
            var(--surface)
        );
}

.hist-left{
    min-width:0;
    display:flex;
    align-items:center;
    gap:14px;
    flex:1;
}

.hist-icon{
    width:44px;
    height:44px;
    flex-shrink:0;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--bg);
    color:var(--primary);
    font-size:1.05rem;
}

.hist-content{
    min-width:0;
    flex:1;
}

.hist-text{
    color:var(--text);
    font-size:0.9rem;
    font-weight:600;
    line-height:1.5;
    word-break:break-word;
}

.hist-meta{
    margin-top:4px;
    color:var(--text-muted);
    font-size:0.72rem;
}

.hist-gesture{
    margin-top:3px;
    color:var(--text-muted);
    font-size:0.7rem;
}

.hist-actions{
    display:flex;
    align-items:center;
    gap:6px;
    flex-shrink:0;
}

.hist-action{
    width:34px;
    height:34px;
    border:1px solid var(--border);
    border-radius:7px;
    background:var(--surface);
    color:var(--text-muted);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:
        background 0.15s,
        color 0.15s,
        border-color 0.15s;
}

.hist-action:hover{
    background:var(--bg);
    color:var(--text);
}

.hist-delete:hover{
    border-color:var(--danger);
    color:var(--danger);
}

.hist-delete-form{
    margin:0;
}

.empty-history{
    padding:40px 20px;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:10px;
    text-align:center;
    color:var(--text-muted);
}

.empty-history i{
    display:block;
    margin-bottom:12px;
    font-size:2rem;
    color:var(--primary);
}

@media(max-width:900px){

    .content{
        margin-left:0;
        padding:20px;
    }

    .history-layout{
        grid-template-columns:1fr;
    }

    .history-detail{
        position:static;
    }

}

@media(max-width:600px){

    .content{
        padding:16px;
    }

    .hist-item{
        align-items:flex-start;
    }

    .hist-actions{
        flex-direction:column;
    }

}


/* ===== RESPONSIVIDADE COMPLETA DO HISTÓRICO ===== */
*{
    box-sizing:border-box;
}

html,
body{
    width:100%;
    max-width:100%;
    overflow-x:hidden;
}

body{
    min-height:100vh;
    min-height:100dvh;
}

.sidebar{
    z-index:1200;
    transition:transform .28s ease;
}

.content{
    min-width:0;
}

.history-container,
.history-column,
.history-detail,
.hist-item,
.hist-left,
.hist-content{
    min-width:0;
}

.history-detail-text,
.history-detail-value,
.hist-text,
.hist-meta,
.hist-gesture{
    overflow-wrap:anywhere;
    word-break:break-word;
}

.history-media img,
.history-media video{
    max-width:100%;
    height:auto;
}

.hist-action{
    flex-shrink:0;
}

/* botão hambúrguer */
.menu-toggle{
    display:none;
    position:fixed;
    top:16px;
    left:16px;
    z-index:1400;
    width:46px;
    height:46px;
    align-items:center;
    justify-content:center;
    border:1px solid var(--border);
    border-radius:12px;
    background:var(--surface);
    color:var(--text);
    font-size:1.35rem;
    cursor:pointer;
    box-shadow:0 8px 22px rgba(0,0,0,.14);
}

/* overlay mobile */
.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    z-index:1100;
    background:rgba(0,0,0,.45);
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease;
}

.sidebar-overlay.open{
    opacity:1;
    pointer-events:auto;
}

@media(max-width:1100px){
    .content{
        padding:30px;
    }

    .history-layout{
        grid-template-columns:minmax(0,1fr) minmax(280px,360px);
        gap:16px;
    }
}

@media(max-width:900px){
    .sidebar{
        width:min(82vw,300px);
        height:100vh;
        height:100dvh;
        transform:translateX(-105%);
        box-shadow:10px 0 30px rgba(0,0,0,.18);
    }

    .sidebar.open{
        transform:translateX(0);
    }

    .content{
        margin-left:0;
        padding:82px 20px 28px;
    }

    .menu-toggle{
        display:flex;
    }

    .sidebar-overlay{
        display:block;
    }

    .history-layout{
        grid-template-columns:1fr;
    }

    .history-detail{
        position:static;
        min-height:unset;
    }
}

@media(max-width:600px){
    .content{
        padding:78px 14px 22px;
    }

    .menu-toggle{
        top:12px;
        left:12px;
        width:44px;
        height:44px;
    }

    .page-title{
        font-size:clamp(1.45rem,7vw,1.9rem);
        line-height:1.2;
    }

    .page-subtitle{
        line-height:1.5;
    }

    .history-header{
        align-items:flex-start;
        gap:10px;
        flex-direction:column;
    }

    .hist-item{
        align-items:flex-start;
        gap:12px;
        padding:14px;
    }

    .hist-left{
        align-items:flex-start;
        gap:10px;
    }

    .hist-icon{
        width:40px;
        height:40px;
    }

    .hist-actions{
        flex-direction:column;
        gap:6px;
    }

    .hist-action{
        width:38px;
        height:38px;
    }

    .history-detail{
        padding:14px;
    }

    .history-media video,
    .history-media img{
        max-height:220px;
    }

    .history-detail-text{
        font-size:.98rem;
    }
}

@media(max-width:420px){
    .content{
        padding-left:10px;
        padding-right:10px;
    }

    .hist-item{
        display:grid;
        grid-template-columns:1fr;
    }

    .hist-actions{
        flex-direction:row;
        justify-content:flex-end;
        width:100%;
        padding-top:4px;
    }

    .hist-left{
        width:100%;
    }

    .hist-meta{
        line-height:1.45;
    }

    .history-detail{
        padding:12px;
        border-radius:10px;
    }
}

</style>

</head>


<body>


<aside class="sidebar" id="sidebarMenu">


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


    <div class="history-container">


        <div class="page-title">

            Histórico

        </div>


        <div class="page-subtitle">

            Consulte suas traduções realizadas anteriormente.

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


        <div class="history-layout">


            <div class="history-column">


                <div class="history-header">

                    <div class="section-title">
                        Suas traduções
                    </div>

                    <div class="history-count">

                        <?= count(
                            $historicos
                        ) ?>

                        registro(s)

                    </div>

                </div>


                <?php if (
                    empty(
                        $historicos
                    )
                ): ?>


                    <div class="empty-history">

                        <i
                            class="fa-solid fa-clock-rotate-left"
                        ></i>

                        Nenhuma tradução foi registrada
                        no seu histórico ainda.

                    </div>


                <?php else: ?>


                    <div class="history-list">


                        <?php foreach (
                            $historicos
                            as $historico
                        ): ?>


                            <?php

                            $urlArquivo =
                                trim(
                                    (string) (
                                        $historico[
                                            "url_arquivo"
                                        ]
                                        ??
                                        ""
                                    )
                                );


                            $tipo =
                                $urlArquivo !== ""
                                    ? "Upload"
                                    : "Câmera";


                            $icone =
                                $urlArquivo !== ""
                                    ? "fa-upload"
                                    : "fa-camera";


                            $textoResultado =
                                trim(
                                    (string) (
                                        $historico[
                                            "texto_resultado"
                                        ]
                                        ??
                                        ""
                                    )
                                );


                            if (
                                $textoResultado === ""
                            ) {

                                $textoResultado =
                                    $historico["nm_gesto"]
                                    ??
                                    "Tradução sem texto";
                            }


                            $dataHistorico =
                                date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $historico[
                                            "criado_em"
                                        ]
                                    )
                                );


                            $extensaoArquivo =
                                strtolower(
                                    pathinfo(
                                        $urlArquivo,
                                        PATHINFO_EXTENSION
                                    )
                                );


                            $tipoMidia =
                                in_array(
                                    $extensaoArquivo,
                                    [
                                        "mp4",
                                        "avi",
                                        "mov",
                                        "mkv"
                                    ],
                                    true
                                )
                                    ? "video"
                                    : (
                                        in_array(
                                            $extensaoArquivo,
                                            [
                                                "jpg",
                                                "jpeg",
                                                "png",
                                                "webp"
                                            ],
                                            true
                                        )
                                            ? "imagem"
                                            : ""
                                    );

                            ?>


                            <div
                                class="hist-item"
                                tabindex="0"
                                data-texto="<?= htmlspecialchars(
                                    $textoResultado,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                data-data="<?= htmlspecialchars(
                                    $dataHistorico,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                data-tipo="<?= htmlspecialchars(
                                    $tipo,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                data-gesto="<?= htmlspecialchars(
                                    $historico["nm_gesto"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                data-arquivo="<?= htmlspecialchars(
                                    $urlArquivo,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                data-midia="<?= htmlspecialchars(
                                    $tipoMidia,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                onclick="selecionarHistorico(this)"
                            >


                                <div class="hist-left">


                                    <div class="hist-icon">

                                        <i
                                            class="fa-solid <?= $icone ?>"
                                        ></i>

                                    </div>


                                    <div class="hist-content">


                                        <div class="hist-text">

                                            <?= htmlspecialchars(
                                                $textoResultado,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                        <div class="hist-meta">

                                            <?= htmlspecialchars(
                                                $dataHistorico,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                            ·

                                            <?= htmlspecialchars(
                                                $tipo,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </div>


                                    </div>


                                </div>


                                <div class="hist-actions">


                                    <button
                                        type="button"
                                        class="hist-action"
                                        title="Ouvir tradução"
                                        data-texto="<?= htmlspecialchars(
                                            $textoResultado,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        onclick="
                                            event.stopPropagation();
                                            lerHist(this);
                                        "
                                    >

                                        <i
                                            class="fa-solid fa-volume-high"
                                        ></i>

                                    </button>


                                    <form
                                        method="POST"
                                        action="historico.php"
                                        class="hist-delete-form"
                                        onsubmit="
                                            event.stopPropagation();
                                            return confirm(
                                                'Deseja excluir este item do histórico?'
                                            );
                                        "
                                        onclick="event.stopPropagation();"
                                    >


                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir_historico"
                                        >


                                        <input
                                            type="hidden"
                                            name="id_historico"
                                            value="<?= (int) $historico["id_historico"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="hist-action hist-delete"
                                            title="Excluir registro"
                                        >

                                            <i
                                                class="fa-solid fa-trash"
                                            ></i>

                                        </button>


                                    </form>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </div>


            <aside
                class="history-detail empty"
                id="historyDetail"
            >

                <div id="historyDetailContent">

                    <i
                        class="fa-regular fa-hand-pointer"
                        style="
                            display:block;
                            font-size:2rem;
                            margin-bottom:12px;
                            color:var(--primary);
                        "
                    ></i>

                    Selecione uma tradução para visualizar
                    os detalhes e a mídia utilizada.

                </div>

            </aside>


        </div>
    </div>


</main>



<script>

function selecionarHistorico(
    elemento
){

    document
        .querySelectorAll(
            ".hist-item"
        )
        .forEach(
            item =>
                item.classList.remove(
                    "selected"
                )
        );


    elemento.classList.add(
        "selected"
    );


    const detalhe =
        document.getElementById(
            "historyDetail"
        );


    const conteudo =
        document.getElementById(
            "historyDetailContent"
        );


    const texto =
        elemento.dataset.texto
        ??
        "";


    const data =
        elemento.dataset.data
        ??
        "";


    const tipo =
        elemento.dataset.tipo
        ??
        "";


    const gesto =
        elemento.dataset.gesto
        ??
        "";


    const arquivo =
        elemento.dataset.arquivo
        ??
        "";


    const midia =
        elemento.dataset.midia
        ??
        "";


    detalhe.classList.remove(
        "empty"
    );


    conteudo.innerHTML =
        "";


    const titulo =
        document.createElement(
            "div"
        );

    titulo.className =
        "history-detail-title";

    titulo.textContent =
        "Detalhes da tradução";

    conteudo.appendChild(
        titulo
    );


    if(
        arquivo !== ""
        &&
        (
            midia === "video"
            ||
            midia === "imagem"
        )
    ){

        const areaMidia =
            document.createElement(
                "div"
            );

        areaMidia.className =
            "history-media";


        if(
            midia === "video"
        ){

            const video =
                document.createElement(
                    "video"
                );

            video.src =
                arquivo;

            video.controls =
                true;

            video.preload =
                "metadata";

            areaMidia.appendChild(
                video
            );

        }else{

            const imagem =
                document.createElement(
                    "img"
                );

            imagem.src =
                arquivo;

            imagem.alt =
                "Mídia utilizada na tradução";

            areaMidia.appendChild(
                imagem
            );
        }


        conteudo.appendChild(
            areaMidia
        );
    }


    const textoDiv =
        document.createElement(
            "div"
        );

    textoDiv.className =
        "history-detail-text";

    textoDiv.textContent =
        texto;

    conteudo.appendChild(
        textoDiv
    );


    const grade =
        document.createElement(
            "div"
        );

    grade.className =
        "history-detail-grid";


    [
        [
            "Data",
            data
        ],
        [
            "Origem",
            tipo
        ],
        [
            "Gesto",
            gesto !== ""
                ? gesto
                : "Não associado"
        ],
        [
            "Arquivo",
            arquivo !== ""
                ? arquivo
                    .split("/")
                    .pop()
                    .replace(
                        /^[a-f0-9]{16}_/,
                        ""
                    )
                : "Não disponível"
        ]
    ].forEach(
        ([rotulo, valor]) => {

            const linha =
                document.createElement(
                    "div"
                );

            linha.className =
                "history-detail-row";


            const label =
                document.createElement(
                    "div"
                );

            label.className =
                "history-detail-label";

            label.textContent =
                rotulo;


            const value =
                document.createElement(
                    "div"
                );

            value.className =
                "history-detail-value";

            value.textContent =
                valor;


            linha.appendChild(
                label
            );

            linha.appendChild(
                value
            );

            grade.appendChild(
                linha
            );
        }
    );


    conteudo.appendChild(
        grade
    );
}


function lerHist(
    elemento
){

    const texto =
        elemento.dataset.texto
        ??
        "";


    if(
        texto.trim() === ""
    ){

        return;
    }


    if(
        !(
            "speechSynthesis"
            in window
        )
    ){

        alert(
            "Seu navegador não oferece suporte à leitura por voz."
        );

        return;
    }


    window.speechSynthesis.cancel();


    const fala =
        new SpeechSynthesisUtterance(
            texto
        );


    fala.lang =
        "pt-BR";


    fala.rate =
        1;


    window.speechSynthesis.speak(
        fala
    );

}

</script>



<button
    class="menu-toggle"
    id="menuToggle"
    type="button"
    aria-label="Abrir menu"
    aria-expanded="false"
    aria-controls="sidebarMenu"
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
        !btn ||
        !sidebar ||
        !overlay
    ){
        return;
    }

    function openMenu(){

        sidebar.classList.add(
            "open"
        );

        overlay.classList.add(
            "open"
        );

        btn.innerHTML =
            "&#10005;";

        btn.setAttribute(
            "aria-label",
            "Fechar menu"
        );

        btn.setAttribute(
            "aria-expanded",
            "true"
        );

        document.body.style.overflow =
            "hidden";
    }

    function closeMenu(){

        sidebar.classList.remove(
            "open"
        );

        overlay.classList.remove(
            "open"
        );

        btn.innerHTML =
            "&#9776;";

        btn.setAttribute(
            "aria-label",
            "Abrir menu"
        );

        btn.setAttribute(
            "aria-expanded",
            "false"
        );

        document.body.style.overflow =
            "";
    }

    btn.addEventListener(
        "click",
        function(){

            sidebar.classList.contains(
                "open"
            )
                ? closeMenu()
                : openMenu();
        }
    );

    overlay.addEventListener(
        "click",
        closeMenu
    );

    sidebar
        .querySelectorAll("a")
        .forEach(
            function(link){

                link.addEventListener(
                    "click",
                    closeMenu
                );
            }
        );

    document.addEventListener(
        "keydown",
        function(event){

            if(
                event.key === "Escape" &&
                sidebar.classList.contains("open")
            ){
                closeMenu();
            }
        }
    );

    window.addEventListener(
        "resize",
        function(){

            if(
                window.innerWidth > 900 &&
                sidebar.classList.contains("open")
            ){
                closeMenu();
            }
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