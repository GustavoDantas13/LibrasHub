<?php

session_start();

require_once "configs/config.php";

$usuarioLogado = false;
$ehAdmin = false;

if (!empty($_SESSION["usuario_id"])) {

    $idUsuarioAtual = (int) $_SESSION["usuario_id"];

    $stmt = $pdo->prepare("
        SELECT
            id_usuario,
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

    if ($usuarioAtual) {

        $usuarioLogado = true;

        $_SESSION["usuario_tipo"] =
            $usuarioAtual["tp_usuario"];

        $ehAdmin =
            strcasecmp(
                trim(
                    $usuarioAtual["tp_usuario"]
                ),
                "Administrador"
            ) === 0;

    } else {

        $_SESSION = [];
        session_destroy();
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

<title>LibrasHub - Uploads</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
    integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
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

        function safeGet(k, f){

            try{

                var v = localStorage.getItem(k);

                return v !== null
                    ? v
                    : f;

            }catch(e){

                return f;

            }

        }

        var tema = safeGet(
            KEYS.theme,
            "claro"
        );

        var efetivo = tema;

        if(tema === "automatico"){

            efetivo = (
                window.matchMedia &&
                window.matchMedia(
                    "(prefers-color-scheme: dark)"
                ).matches
            )
                ? "escuro"
                : "claro";

        }

        if(efetivo === "escuro"){

            document.documentElement.setAttribute(
                "data-theme",
                "dark"
            );

        }

        document.documentElement.style.setProperty(
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

            document.documentElement.classList.add(
                "high-contrast"
            );

        }

    }catch(e){}

})();

</script>


<style>

.layout{
    display:flex;
}


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


.upload-layout{

    display:grid;

    grid-template-columns:
        minmax(0, 2fr)
        minmax(300px, 1fr);

    gap:20px;

    align-items:start;
}


.upload-panel,
.result-panel{

    background:var(--surface);

    border:1px solid var(--border);

    border-radius:10px;

    padding:16px;
}


.upload-area{

    width:100%;

    min-height:340px;

    border:1px dashed var(--border);

    border-radius:8px;

    background:var(--bg);

    display:flex;

    align-items:center;
    justify-content:center;

    padding:24px;

    box-sizing:border-box;

    cursor:pointer;

    transition:
        border-color 0.2s,
        background 0.2s;
}


.upload-area:hover,
.upload-area.highlight{

    border-color:var(--primary);
}


.upload-info{

    display:flex;

    flex-direction:column;

    align-items:center;
    justify-content:center;

    gap:12px;

    text-align:center;

    color:var(--text-muted);
}


.upload-icon{

    font-size:2rem;

    color:var(--primary);
}


.upload-info-title{

    font-weight:700;

    color:var(--text);
}


.upload-info-text{

    font-size:0.8125rem;

    color:var(--text-muted);
}


.upload-select-btn{

    border:1px solid var(--border);

    border-radius:8px;

    background:var(--surface);

    color:var(--text);

    padding:9px 16px;

    font-size:0.8125rem;

    cursor:pointer;
}


.upload-select-btn:hover{

    border-color:var(--primary);
}


.preview-area{

    width:100%;

    max-height:410px;

    overflow-y:auto;
}


.file-list{

    display:flex;

    flex-direction:column;

    gap:8px;
}


.file-item{

    display:flex;

    align-items:center;

    gap:12px;

    border:1px solid var(--border);

    background:var(--surface);

    border-radius:8px;

    padding:10px;

    cursor:default;
}


.file-thumb{

    width:52px;
    height:52px;

    flex-shrink:0;

    border-radius:7px;

    border:1px solid var(--border);

    background:var(--bg);

    display:flex;

    align-items:center;
    justify-content:center;

    overflow:hidden;

    font-size:1.2rem;
}


.file-thumb img{

    width:100%;
    height:100%;

    object-fit:cover;
}


.file-info{

    min-width:0;

    flex:1;
}


.file-name{

    color:var(--text);

    font-size:0.8125rem;

    font-weight:600;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;
}


.file-size{

    color:var(--text-muted);

    font-size:0.72rem;

    margin-top:3px;
}


.file-remove{

    width:32px;
    height:32px;

    border:0;

    border-radius:6px;

    background:transparent;

    color:var(--text-muted);

    cursor:pointer;

    font-size:0.9rem;
}


.file-remove:hover{

    background:var(--bg);

    color:var(--text);
}


.upload-status{

    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:12px;

    margin-top:12px;

    font-size:0.75rem;

    color:var(--text-muted);
}


.actions{

    display:flex;

    gap:10px;

    margin-top:14px;
}


.actions button{

    flex:1;

    padding:10px 14px;

    border-radius:8px;

    cursor:pointer;

    font-size:0.8125rem;
}


.btn-send{

    border:1px solid var(--primary);

    background:var(--primary);

    color:#fff;
}


.btn-clear{

    border:1px solid var(--border);

    background:var(--surface);

    color:var(--text);
}


.btn-clear:hover{

    border-color:var(--primary);
}


.actions button:disabled{

    opacity:0.55;

    cursor:not-allowed;
}


.side-title{

    font-size:0.75rem;

    font-weight:700;

    margin-bottom:10px;

    color:var(--text-muted);
}


.result-section + .result-section{

    margin-top:18px;
}


.result-display{

    min-height:120px;

    padding:16px;

    border:1px solid var(--border);

    border-radius:8px;

    background:var(--bg);

    color:var(--text-muted);

    font-size:0.86rem;

    line-height:1.6;

    box-sizing:border-box;
}


.result-display.placeholder{

    display:flex;

    align-items:center;
    justify-content:center;

    text-align:center;
}


.result-file-list{

    display:flex;

    flex-direction:column;

    gap:0;
}


.translation-item{

    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    gap:14px;

    padding:10px 0;
}


.translation-item + .translation-item{

    border-top:1px solid var(--border);
}


.translation-file{

    min-width:0;

    flex:1;

    color:var(--text-muted);

    font-size:0.78rem;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;
}


.translation-result{

    flex-shrink:0;

    max-width:50%;

    text-align:right;

    color:var(--text);

    font-weight:600;

    font-size:0.8rem;

    word-break:break-word;
}


.translation-error{

    color:var(--text-muted);

    font-weight:500;
}


.complete-phrase{

    min-height:100px;

    padding:16px;

    border:1px solid var(--border);

    border-radius:8px;

    background:var(--bg);

    color:var(--text);

    font-size:1rem;

    line-height:1.7;

    word-break:break-word;

    box-sizing:border-box;
}


.complete-phrase.placeholder{

    color:var(--text-muted);

    display:flex;

    align-items:center;
    justify-content:center;

    text-align:center;
}


.processing{

    min-height:90px;

    display:flex;

    flex-direction:column;

    align-items:center;
    justify-content:center;

    gap:10px;
}


.spinner{

    width:28px;
    height:28px;

    border:3px solid var(--border);

    border-top-color:var(--primary);

    border-radius:50%;

    animation:spin 0.8s linear infinite;
}


@keyframes spin{

    to{
        transform:rotate(360deg);
    }

}


.upload-help{

    margin-top:12px;

    font-size:0.72rem;

    line-height:1.5;

    color:var(--text-muted);
}


@media(max-width:900px){

    .content{

        margin-left:0;

        padding:20px;
    }


    .upload-layout{

        grid-template-columns:1fr;
    }

}


@media(max-width:600px){

    .content{

        padding:16px;
    }


    .upload-area{

        min-height:280px;

        padding:16px;
    }


    .actions{

        flex-direction:column;
    }


    .translation-item{

        flex-direction:column;

        gap:4px;
    }


    .translation-result{

        max-width:100%;

        text-align:left;
    }

}


/* ===== RESPONSIVIDADE COMPLETA DO UPLOAD ===== */
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

.upload-layout > *{
    min-width:0;
}

.upload-panel,
.result-panel,
.upload-area,
.result-display,
.complete-phrase{
    width:100%;
    max-width:100%;
}

.upload-status{
    flex-wrap:wrap;
}

.file-item{
    min-width:0;
}

.file-info{
    min-width:0;
}

.actions button,
.upload-select-btn{
    min-height:44px;
}

.translation-file,
.translation-result,
.complete-phrase,
.result-display{
    overflow-wrap:anywhere;
    word-break:break-word;
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

/* overlay do menu mobile */
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

    .upload-layout{
        grid-template-columns:minmax(0,1.5fr) minmax(280px,1fr);
        gap:16px;
    }

    .upload-area{
        min-height:320px;
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

    .upload-layout{
        grid-template-columns:1fr;
    }

    .upload-area{
        min-height:300px;
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

    .upload-panel,
    .result-panel{
        padding:14px;
    }

    .upload-area{
        min-height:250px;
        padding:14px;
    }

    .upload-icon{
        font-size:1.7rem;
    }

    .upload-info-title{
        font-size:.95rem;
    }

    .upload-info-text,
    .upload-help{
        font-size:.78rem;
    }

    .upload-select-btn{
        width:100%;
        min-height:48px;
        font-size:.9rem;
    }

    .upload-status{
        align-items:flex-start;
        gap:6px;
    }

    .actions{
        flex-direction:column;
    }

    .actions button{
        width:100%;
        min-height:48px;
        font-size:.9rem;
    }

    .file-item{
        gap:10px;
        padding:9px;
    }

    .file-thumb{
        width:46px;
        height:46px;
    }

    .file-remove{
        width:36px;
        height:36px;
        flex-shrink:0;
    }

    .translation-item{
        flex-direction:column;
        gap:5px;
    }

    .translation-file{
        width:100%;
        white-space:normal;
    }

    .translation-result{
        width:100%;
        max-width:100%;
        text-align:left;
    }

    .result-display,
    .complete-phrase{
        padding:14px;
        font-size:.9rem;
    }
}

@media(max-width:380px){
    .content{
        padding-left:10px;
        padding-right:10px;
    }

    .upload-panel,
    .result-panel{
        padding:12px;
        border-radius:8px;
    }

    .upload-area{
        min-height:220px;
        padding:12px;
    }

    .file-thumb{
        width:42px;
        height:42px;
    }

    .file-name{
        white-space:normal;
        overflow-wrap:anywhere;
    }

    .upload-status{
        flex-direction:column;
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


        <?php if ($usuarioLogado): ?>

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

        <?php endif; ?>


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


        <?php if (!$usuarioLogado): ?>

            <a
                class="nav-item"
                href="../index.php"
                data-page="index"
            >

                <span class="nav-icon">

                    <i
                        class="fa-solid fa-arrow-left"
                        style="color:#fdbe00;"
                    ></i>

                </span>

                Voltar ao início

            </a>

        <?php endif; ?>


        <?php if ($usuarioLogado): ?>

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

        <?php endif; ?>

    </div>


    <?php if ($usuarioLogado): ?>

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

    <?php endif; ?>

</aside>



<main class="content">

    <div class="page-title">
        Tradução por Upload
    </div>

    <div class="page-subtitle">
        Envie fotos ou vídeos contendo sinais em LIBRAS para realizar a tradução.
    </div>


    <form
        id="uploadForm"
        enctype="multipart/form-data"
    >

        <div class="upload-layout">


            <div class="upload-panel">


                <div
                    class="upload-area"
                    id="dropZone"
                >


                    <input
                        type="file"
                        id="fileInput"
                        name="mediaFile[]"
                        accept="image/*,video/*"
                        multiple
                        hidden
                    >


                    <div
                        class="upload-info"
                        id="uploadInfo"
                    >

                        <div class="upload-icon">

                            <i class="fa-solid fa-cloud-arrow-up"></i>

                        </div>


                        <div class="upload-info-title">

                            Arraste suas mídias aqui

                        </div>


                        <div class="upload-info-text">

                            Fotos e vídeos podem ser enviados para análise.

                        </div>


                        <button
                            type="button"
                            class="upload-select-btn"
                            id="btnSelecionar"
                        >

                            <i class="fa-solid fa-folder-open"></i>

                            Selecionar arquivos

                        </button>

                    </div>


                    <div
                        id="preview"
                        class="preview-area"
                        hidden
                    >

                        <div
                            class="file-list"
                            id="fileList"
                        ></div>

                    </div>


                </div>


                <div class="upload-status">

                    <span id="uploadStatus">

                        Nenhum arquivo selecionado.

                    </span>


                    <span id="fileCount">

                        0 arquivos

                    </span>

                </div>


                <div class="actions">

                    <button
                        type="submit"
                        class="btn-send"
                        id="btnEnviar"
                    >

                        <i class="fa-solid fa-language"></i>

                        Traduzir

                    </button>


                    <button
                        type="button"
                        class="btn-clear"
                        id="btnLimpar"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Limpar

                    </button>

                </div>


            </div>


            <div class="result-panel">


                <div class="result-section">

                    <div class="side-title">
                        TRADUÇÃO
                    </div>


                    <div
                        class="result-display placeholder"
                        id="translationResults"
                    >

                        Os resultados individuais aparecerão aqui...

                    </div>

                </div>


                <div class="result-section">

                    <div class="side-title">
                        FRASE COMPLETA
                    </div>


                    <div
                        class="complete-phrase placeholder"
                        id="completePhrase"
                    >

                        A interpretação aparecerá aqui...

                    </div>

                </div>


                <div class="upload-help">

                    A tradução é gerada a partir das mídias
                    enviadas e do modelo treinado no sistema.

                </div>


            </div>


        </div>

    </form>

</main>



<script>

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

const fileList =
    document.getElementById(
        "fileList"
    );

const uploadInfo =
    document.getElementById(
        "uploadInfo"
    );

const uploadStatus =
    document.getElementById(
        "uploadStatus"
    );

const fileCount =
    document.getElementById(
        "fileCount"
    );

const translationResults =
    document.getElementById(
        "translationResults"
    );

const completePhrase =
    document.getElementById(
        "completePhrase"
    );

const btnSelecionar =
    document.getElementById(
        "btnSelecionar"
    );

const btnEnviar =
    document.getElementById(
        "btnEnviar"
    );

const btnLimpar =
    document.getElementById(
        "btnLimpar"
    );

const uploadForm =
    document.getElementById(
        "uploadForm"
    );


let arquivosSelecionados = [];

let objectUrls = [];

const usuarioLogado =
    <?= $usuarioLogado
        ? "true"
        : "false"
    ?>;


async function salvarHistorico(
    resultados
){

    if(
        !usuarioLogado
        ||
        !Array.isArray(
            resultados
        )
    ){
        return;
    }


    const salvos = [];

    const falhas = [];


    for(
        let indice = 0;
        indice < resultados.length;
        indice++
    ){

        const item =
            resultados[indice];


        if(
            !item
            ||
            !item.gesto
            ||
            item.valido === false
            ||
            String(
                item.gesto
            ).trim().toLowerCase()
                ===
                "gesto inválido"
        ){
            continue;
        }


        const nomeArquivo =
            obterNomeArquivo(
                item.arquivo
                ??
                ""
            );


        let arquivoOriginal =
            arquivosSelecionados.find(
                arquivo =>
                    arquivo.name ===
                    nomeArquivo
            );


        if(
            !arquivoOriginal
            &&
            arquivosSelecionados[indice]
        ){

            arquivoOriginal =
                arquivosSelecionados[indice];
        }


        if(
            !arquivoOriginal
        ){

            falhas.push({
                indice:indice,
                gesto:item.gesto,
                arquivo:nomeArquivo,
                erro:"Arquivo original não encontrado no navegador."
            });

            continue;
        }


        const formData =
            new FormData();


        const itemHistorico = {

            gesto:
                String(
                    item.gesto
                ).trim(),

            arquivo:
                arquivoOriginal.name,

            texto_resultado:
                String(
                    item.gesto
                ).trim(),

            chave_arquivo:
                "midia"

        };


        formData.append(
            "itens",
            JSON.stringify([
                itemHistorico
            ])
        );


        formData.append(
            "midia",
            arquivoOriginal,
            arquivoOriginal.name
        );


        try{

            const resposta =
                await fetch(
                    "ajax/salvar_historico.php",
                    {
                        method:"POST",
                        body:formData
                    }
                );


            const data =
                await lerJsonSeguro(
                    resposta
                );


            console.log(
                "Histórico arquivo "
                +
                (indice + 1)
                +
                ":",
                data
            );


            if(
                !resposta.ok
                ||
                !data.success
            ){

                falhas.push({
                    indice:indice,
                    gesto:item.gesto,
                    arquivo:arquivoOriginal.name,
                    erro:
                        data.error
                        ??
                        "Falha ao salvar.",
                    detalhes:data
                });

                continue;
            }


            salvos.push(
                ...(
                    Array.isArray(
                        data.salvos
                    )
                        ? data.salvos
                        : []
                )
            );


        }catch(erro){

            falhas.push({
                indice:indice,
                gesto:item.gesto,
                arquivo:arquivoOriginal.name,
                erro:erro.message
            });
        }
    }


    console.log(
        "Resumo do histórico:",
        {
            total_salvos:
                salvos.length,
            falhas:
                falhas
        }
    );


    if(
        salvos.length === 0
        &&
        falhas.length > 0
    ){

        throw new Error(
            falhas[0].erro
            ??
            "Nenhuma mídia foi salva no histórico."
        );
    }


    return {
        success:true,
        total_salvos:
            salvos.length,
        salvos:
            salvos,
        falhas:
            falhas
    };
}


/* Drag and drop */

[
    "dragenter",
    "dragover",
    "dragleave",
    "drop"
].forEach(evento => {

    dropZone.addEventListener(
        evento,
        e => {

            e.preventDefault();
            e.stopPropagation();

        }
    );

});


[
    "dragenter",
    "dragover"
].forEach(evento => {

    dropZone.addEventListener(
        evento,
        () => {

            dropZone.classList.add(
                "highlight"
            );

        }
    );

});


[
    "dragleave",
    "drop"
].forEach(evento => {

    dropZone.addEventListener(
        evento,
        () => {

            dropZone.classList.remove(
                "highlight"
            );

        }
    );

});


dropZone.addEventListener(
    "drop",
    e => {

        adicionarArquivos(
            e.dataTransfer.files
        );

    }
);


fileInput.addEventListener(
    "change",
    e => {

        adicionarArquivos(
            e.target.files
        );

    }
);


btnSelecionar.addEventListener(
    "click",
    e => {

        e.stopPropagation();

        fileInput.click();

    }
);


dropZone.addEventListener(
    "click",
    e => {

        if(
            e.target.closest(
                ".file-remove"
            )
        ){
            return;
        }

        if(
            e.target.closest(
                "#btnSelecionar"
            )
        ){
            return;
        }

        fileInput.click();

    }
);


/* Arquivos */

function adicionarArquivos(files){

    for(
        const file
        of files
    ){

        const duplicado =
            arquivosSelecionados.some(
                atual =>
                    atual.name === file.name &&
                    atual.size === file.size &&
                    atual.lastModified ===
                        file.lastModified
            );


        if(duplicado){
            continue;
        }


        if(
            !file.type.startsWith(
                "image/"
            ) &&
            !file.type.startsWith(
                "video/"
            )
        ){
            continue;
        }


        arquivosSelecionados.push(
            file
        );

    }


    fileInput.value = "";

    renderizarArquivos();

}


/* Lista */

function renderizarArquivos(){

    fileList.innerHTML = "";

    liberarObjectUrls();


    if(
        arquivosSelecionados.length === 0
    ){

        preview.hidden = true;

        uploadInfo.style.display =
            "flex";

        uploadStatus.textContent =
            "Nenhum arquivo selecionado.";

        fileCount.textContent =
            "0 arquivos";

        return;

    }


    preview.hidden = false;

    uploadInfo.style.display =
        "none";


    arquivosSelecionados.forEach(
        (file, indice) => {


            const item =
                document.createElement(
                    "div"
                );


            item.className =
                "file-item";


            const thumb =
                document.createElement(
                    "div"
                );


            thumb.className =
                "file-thumb";


            if(
                file.type.startsWith(
                    "image/"
                )
            ){

                const img =
                    document.createElement(
                        "img"
                    );


                const url =
                    URL.createObjectURL(
                        file
                    );


                objectUrls.push(
                    url
                );


                img.src = url;

                img.alt =
                    file.name;


                thumb.appendChild(
                    img
                );


            }else{


                thumb.innerHTML =
                    '<i class="fa-solid fa-video"></i>';

            }


            const info =
                document.createElement(
                    "div"
                );


            info.className =
                "file-info";


            const nome =
                document.createElement(
                    "div"
                );


            nome.className =
                "file-name";

            nome.textContent =
                file.name;


            const tamanho =
                document.createElement(
                    "div"
                );


            tamanho.className =
                "file-size";

            tamanho.textContent =
                formatarTamanho(
                    file.size
                );


            info.appendChild(
                nome
            );

            info.appendChild(
                tamanho
            );


            const remover =
                document.createElement(
                    "button"
                );


            remover.type =
                "button";

            remover.className =
                "file-remove";

            remover.title =
                "Remover arquivo";

            remover.innerHTML =
                '<i class="fa-solid fa-xmark"></i>';


            remover.addEventListener(
                "click",
                e => {

                    e.stopPropagation();

                    removerArquivo(
                        indice
                    );

                }
            );


            item.appendChild(
                thumb
            );

            item.appendChild(
                info
            );

            item.appendChild(
                remover
            );


            fileList.appendChild(
                item
            );

        }
    );


    const quantidade =
        arquivosSelecionados.length;


    uploadStatus.textContent =
        `${quantidade} arquivo(s) selecionado(s).`;


    fileCount.textContent =
        `${quantidade} arquivo(s)`;

}


/* Remover */

function removerArquivo(indice){

    arquivosSelecionados.splice(
        indice,
        1
    );

    renderizarArquivos();

}


/* Tamanho */

function formatarTamanho(bytes){

    const unidades = [
        "B",
        "KB",
        "MB",
        "GB"
    ];


    let tamanho =
        Number(bytes);

    let indice = 0;


    while(
        tamanho >= 1024 &&
        indice < unidades.length - 1
    ){

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


/* URLs */

function liberarObjectUrls(){

    for(
        const url
        of objectUrls
    ){

        URL.revokeObjectURL(
            url
        );

    }


    objectUrls = [];

}


/* JSON */

async function lerJsonSeguro(
    resposta
){

    const texto =
        await resposta.text();


    try{

        return JSON.parse(
            texto
        );

    }catch(erro){

        console.error(
            "Resposta inválida:",
            texto
        );


        throw new Error(
            "O servidor não retornou uma resposta válida."
        );

    }

}


/* Traduzir */

uploadForm.addEventListener(
    "submit",
    async e => {

        e.preventDefault();


        if(
            arquivosSelecionados.length === 0
        ){

            alert(
                "Selecione pelo menos uma foto ou vídeo."
            );

            return;

        }


        btnEnviar.disabled =
            true;

        btnLimpar.disabled =
            true;

        fileInput.disabled =
            true;


        translationResults.classList.remove(
            "placeholder"
        );


        translationResults.innerHTML = `
            <div class="processing">

                <div class="spinner"></div>

                <div>
                    Analisando ${arquivosSelecionados.length}
                    arquivo(s)...
                </div>

            </div>
        `;


        completePhrase.classList.add(
            "placeholder"
        );


        completePhrase.textContent =
            "Processando tradução...";


        try{


            const formData =
                new FormData();


            for(
                const file
                of arquivosSelecionados
            ){

                formData.append(
                    "mediaFile[]",
                    file
                );

            }


            const resposta =
                await fetch(
                    "ajax/analisar.php",
                    {
                        method:"POST",
                        body:formData
                    }
                );


            const data =
                await lerJsonSeguro(
                    resposta
                );


            console.log(
                "Resultado:",
                data
            );


            if(!data.success){

                throw new Error(
                    data.error ??
                    "Não foi possível realizar a tradução."
                );

            }


            const resultados =
                Array.isArray(
                    data.resultados
                )
                    ? data.resultados
                    : [];


            if(
                resultados.length === 0
            ){

                throw new Error(
                    "Nenhum resultado foi retornado."
                );

            }


            /* Traduções válidas */

            const gestos =
                resultados

                    .filter(
                        item =>
                            item.gesto
                            &&
                            item.valido !== false
                            &&
                            String(
                                item.gesto
                            ).trim().toLowerCase()
                                !==
                                "gesto inválido"
                    )

                    .map(
                        item =>
                            String(
                                item.gesto
                            )
                    );


            /* Campo Tradução */

            translationResults.classList.remove(
                "placeholder"
            );


            let html = `
                <div class="result-file-list">
            `;


            resultados.forEach(
                item => {


                    const nomeArquivo =
                        obterNomeArquivo(
                            item.arquivo ??
                            "Arquivo"
                        );


                    html += `
                        <div class="translation-item">

                            <div class="translation-file">
                                ${escaparHtml(
                                    nomeArquivo
                                )}
                            </div>
                    `;


                    if(item.gesto){


                        const gesto =
                            escaparHtml(
                                item.gesto
                            );


                        const confianca =
                            Number(
                                item.confianca
                            );


                        html += `
                            <div class="translation-result">

                                ${gesto}

                                ${
                                    Number.isFinite(
                                        confianca
                                    )
                                        ? `
                                            <small>
                                                (${confianca.toFixed(2)}%)
                                            </small>
                                        `
                                        : ""
                                }

                            </div>
                        `;


                    }else{


                        html += `
                            <div
                                class="
                                    translation-result
                                    translation-error
                                "
                            >

                                ${escaparHtml(
                                    item.erro ??
                                    "Não reconhecido"
                                )}

                            </div>
                        `;

                    }


                    html += `
                        </div>
                    `;

                }
            );


            html += `
                </div>
            `;


            translationResults.innerHTML =
                html;


            /* Campo Frase completa */

            completePhrase.classList.remove(
                "placeholder"
            );


            if(
                gestos.length > 0
            ){

                completePhrase.textContent =
                    formatarFrase(
                        gestos
                    );


                if(
                    usuarioLogado
                ){

                    try{

                        await salvarHistorico(
                            resultados
                        );

                    }catch(
                        erroHistorico
                    ){

                        console.error(
                            "Erro ao salvar histórico:",
                            erroHistorico
                        );
                    }
                }

            }else{

                completePhrase.classList.add(
                    "placeholder"
                );


                completePhrase.textContent =
                    "Não foi possível formar uma frase.";

            }


        }catch(erro){


            console.error(
                erro
            );


            translationResults.classList.remove(
                "placeholder"
            );


            translationResults.innerHTML = `
                <strong>
                    Erro ao realizar a tradução.
                </strong>

                <br><br>

                ${escaparHtml(
                    erro.message
                )}
            `;


            completePhrase.classList.add(
                "placeholder"
            );


            completePhrase.textContent =
                "A interpretação aparecerá aqui...";


        }finally{


            btnEnviar.disabled =
                false;

            btnLimpar.disabled =
                false;

            fileInput.disabled =
                false;

        }

    }
);


/* Limpar */

btnLimpar.addEventListener(
    "click",
    () => {


        arquivosSelecionados = [];


        fileInput.value = "";


        liberarObjectUrls();


        renderizarArquivos();


        translationResults.classList.add(
            "placeholder"
        );


        translationResults.textContent =
            "Os resultados individuais aparecerão aqui...";


        completePhrase.classList.add(
            "placeholder"
        );


        completePhrase.textContent =
            "A interpretação aparecerá aqui...";

    }
);


/* Remove caminhos */

function obterNomeArquivo(valor){

    const texto =
        String(valor);


    const partes =
        texto.split(
            /[\\/]/g
        );


    return partes[
        partes.length - 1
    ] || texto;

}


/* Formata a frase */

function formatarFrase(gestos){

    if(
        !Array.isArray(gestos) ||
        gestos.length === 0
    ){
        return "";
    }


    let frase =
        gestos
            .map(
                gesto =>
                    String(gesto)
                        .trim()
            )
            .filter(Boolean)
            .join(" ");


    if(frase === ""){
        return "";
    }


    frase =
        frase.charAt(0).toUpperCase()
        +
        frase.slice(1);


    return frase;

}


/* HTML seguro */

function escaparHtml(valor){

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


/* Limpeza ao sair */

window.addEventListener(
    "beforeunload",
    liberarObjectUrls
);


renderizarArquivos();

</script>



<!-- MENU MOBILE -->

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
            function(a){

                a.addEventListener(
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
            function(a){

                if(
                    a.dataset.page ===
                    path
                ){

                    a.classList.add(
                        "active"
                    );

                }

            }
        );

})();

</script>


</body>

</html>