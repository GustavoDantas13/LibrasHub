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
    content="width=device-width, initial-scale=1.0"
>
<link rel="icon" type="image/png" href="../static/images/librashub-logo.png">
<title>LibrasHub - Leitor</title>

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

        function safeGet(k,f){

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
}


.leitor-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:20px;
}


.cam-box{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:10px;
    padding:16px;
}


.cam-frame{
    background:var(--bg);
    border:1px dashed var(--border);
    border-radius:8px;
    height:500px;

    display:flex;
    align-items:center;
    justify-content:center;

    flex-direction:column;
    gap:10px;

    color:var(--text-muted);

    overflow:hidden;
    position:relative;

    cursor:pointer;

    user-select:none;

    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}


.cam-frame:hover{
    border-color:var(--primary);
}


.cam-frame.camera-active{
    border-style:solid;
    border-color:var(--primary);
}


#camPlaceholder{
    text-align:center;
    pointer-events:none;
}


video{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:8px;
    display:none;

    pointer-events:none;
}


.cam-icon{
    font-size:1.625rem;
}


.status-row{
    display:flex;
    gap:16px;
    margin-top:12px;

    font-size:0.75rem;
    color:var(--text-muted);
}


.dot{
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--border);
    display:inline-block;
    margin-right:5px;
}


.dot.on{
    background:var(--primary);
}


.side-panel{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:10px;

    padding:16px;
    margin-bottom:16px;
}


.side-title{
    font-size:0.75rem;
    font-weight:700;
    margin-bottom:10px;
    color:var(--text-muted);
}


.palavra-box{
    font-size:1.25rem;
    font-weight:700;
    text-align:center;
    padding:20px 0;
    color:var(--text-muted);
}


.frase-box{
    min-height:80px;
    font-size:0.8125rem;
    color:var(--text-muted);
    line-height:1.5;
}


.icon-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    width:100%;
    padding:9px;

    border:1px solid var(--border);
    border-radius:8px;

    background:var(--surface);

    font-size:0.8125rem;
    cursor:pointer;
    margin-top:8px;
}


.icon-btn:hover{
    border-color:var(--primary);
}


.camera-hint{
    margin-top:10px;

    font-size:0.72rem;
    color:var(--text-muted);

    text-align:center;
}


@media(max-width:900px){

    .content{
        margin-left:0;
        padding:20px;
    }

    .leitor-grid{
        grid-template-columns:1fr;
    }

    .cam-frame{
        height:400px;
    }

}


@media(max-width:600px){

    .cam-frame{
        height:300px;
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
        Leitor de LIBRAS
    </div>

    <div class="page-subtitle">
        Clique na câmera para iniciar ou interromper a tradução em tempo real.
    </div>


    <div class="leitor-grid">


        <div class="cam-box">


            <div
                class="cam-frame"
                id="camFrame"
                role="button"
                tabindex="0"
                aria-label="Ativar ou desativar câmera"
            >


                <div id="camPlaceholder">

                    <div class="cam-icon">
                        📷
                    </div>

                    <div>
                        Câmera desativada
                    </div>

                    <small>
                        Clique aqui para ativar
                    </small>

                </div>


                <video
                    id="video"
                    autoplay
                    playsinline
                    muted
                ></video>


                <canvas
                    id="canvas"
                    style="display:none;"
                ></canvas>


            </div>


            <div class="status-row">

                <span>

                    <span
                        class="dot"
                        id="dotCam"
                    ></span>

                    <span id="statusCam">
                        Câmera inativa
                    </span>

                </span>


                <span>

                    <span
                        class="dot"
                        id="dotHand"
                    ></span>

                    <span id="statusHand">
                        Nenhuma mão detectada
                    </span>

                </span>

            </div>


            <div class="camera-hint">
                Clique novamente sobre a câmera para desativá-la.
            </div>

        </div>


        <div>


            <div class="side-panel">

                <div class="side-title">
                    PALAVRA ATUAL
                </div>

                <div
                    class="palavra-box"
                    id="palavraAtual"
                >
                    –
                </div>

            </div>


            <div class="side-panel">

                <div class="side-title">
                    FRASE COMPLETA
                </div>


                <div
                    class="frase-box"
                    id="fraseCompleta"
                >
                    A tradução aparecerá aqui...
                </div>


                <button
                    class="icon-btn"
                    type="button"
                    onclick="lerVoz()"
                >
                    🔊 Ler em voz alta
                </button>


                <button
                    class="icon-btn"
                    type="button"
                    onclick="limparTexto()"
                >
                    🗑 Limpar texto
                </button>

            </div>

        </div>


    </div>

</main>



<script>

const camFrame =
    document.getElementById("camFrame");

const video =
    document.getElementById("video");

const canvas =
    document.getElementById("canvas");

const ctx =
    canvas.getContext("2d");

const camPlaceholder =
    document.getElementById(
        "camPlaceholder"
    );

const statusCam =
    document.getElementById(
        "statusCam"
    );

const dotCam =
    document.getElementById(
        "dotCam"
    );

const statusHand =
    document.getElementById(
        "statusHand"
    );

const dotHand =
    document.getElementById(
        "dotHand"
    );

const palavraAtual =
    document.getElementById(
        "palavraAtual"
    );

const fraseCompleta =
    document.getElementById(
        "fraseCompleta"
    );


let stream = null;

let ativo = false;

let captura = null;

let enviando = false;


/* Captura */

function iniciarCaptura(){

    if(captura){

        clearInterval(
            captura
        );

    }


    captura = setInterval(

        async () => {

            if(
                !ativo ||
                enviando
            ){
                return;
            }


            if(
                video.readyState <
                HTMLMediaElement.HAVE_CURRENT_DATA
            ){
                return;
            }


            if(
                video.videoWidth === 0 ||
                video.videoHeight === 0
            ){
                return;
            }


            enviando = true;


            canvas.width =
                video.videoWidth;

            canvas.height =
                video.videoHeight;


            ctx.drawImage(
                video,
                0,
                0,
                canvas.width,
                canvas.height
            );


            canvas.toBlob(

                async (blob) => {

                    if(!blob){

                        enviando = false;

                        return;

                    }


                    const form =
                        new FormData();


                    form.append(
                        "frame",
                        blob,
                        "frame.jpg"
                    );


                    try{

                        const resposta =
                            await fetch(
                                "ajax/traducao_tempo_real.php",
                                {
                                    method:"POST",
                                    body:form
                                }
                            );


                        const texto =
                            await resposta.text();


                        let dados;


                        try{

                            dados =
                                JSON.parse(
                                    texto
                                );

                        }catch(erro){

                            console.error(
                                "Resposta inválida:",
                                texto
                            );

                            return;

                        }


                        console.log(
                            "Resposta:",
                            dados
                        );


                        tratarRespostaTraducao(
                            dados
                        );


                    }catch(erro){

                        console.error(
                            "Erro na tradução:",
                            erro
                        );

                        statusHand.textContent =
                            "Erro de comunicação";

                        dotHand.classList.remove(
                            "on"
                        );

                    }finally{

                        enviando = false;

                    }

                },

                "image/jpeg",

                0.8

            );

        },

        200

    );

}


/* Resposta do Flask */

function tratarRespostaTraducao(
    dados
){

    switch(
        dados.status
    ){


        case "analisando":

            if(dados.gesto){

                palavraAtual.textContent =
                    dados.gesto;

            }


            statusHand.textContent =
                dados.confirmacao
                    ? `Gesto detectado (${dados.confirmacao})`
                    : "Gesto detectado";


            dotHand.classList.add(
                "on"
            );


            if(
                dados.texto &&
                dados.texto.trim() !== ""
            ){

                fraseCompleta.textContent =
                    dados.texto;

            }

            break;



        case "traduzido":

            if(
                dados.texto &&
                dados.texto.trim() !== ""
            ){

                fraseCompleta.textContent =
                    dados.texto;

            }


            if(dados.gesto){

                palavraAtual.textContent =
                    dados.gesto;

            }


            statusHand.textContent =
                "Gesto reconhecido";


            dotHand.classList.add(
                "on"
            );

            break;



        case "aguardando":

            statusHand.textContent =
                "Nenhuma mão detectada";


            dotHand.classList.remove(
                "on"
            );


            palavraAtual.textContent =
                "–";

            break;



        case "erro":

            statusHand.textContent =
                dados.error ||
                "Erro na tradução";


            dotHand.classList.remove(
                "on"
            );


            console.error(
                "Erro retornado:",
                dados.error
            );

            break;



        default:

            console.log(
                "Status desconhecido:",
                dados
            );

            break;

    }

}


/* Câmera */

async function toggleCamera(){

    if(!ativo){

        try{

            stream =
                await navigator.mediaDevices
                    .getUserMedia({

                        video:{
                            facingMode:"user"
                        },

                        audio:false

                    });


            video.srcObject =
                stream;


            await video.play();


            video.style.display =
                "block";


            camPlaceholder.style.display =
                "none";


            camFrame.classList.add(
                "camera-active"
            );


            statusCam.textContent =
                "Câmera ativa";


            dotCam.classList.add(
                "on"
            );


            ativo = true;


            video.onloadedmetadata =
                () => {

                    iniciarCaptura();

                };


            if(
                video.readyState >=
                HTMLMediaElement.HAVE_METADATA
            ){

                iniciarCaptura();

            }


        }catch(erro){

            console.error(
                "Erro ao abrir câmera:",
                erro
            );


            alert(
                "Não foi possível acessar a câmera: " +
                erro.message +
                "\n\nVerifique se você concedeu permissão ao navegador."
            );

        }


        return;

    }


    desligarCamera();

}


/* Desligar */

function desligarCamera(){

    if(captura){

        clearInterval(
            captura
        );

        captura = null;

    }


    enviando = false;


    if(stream){

        stream
            .getTracks()
            .forEach(
                track => track.stop()
            );

        stream = null;

    }


    video.pause();

    video.srcObject =
        null;

    video.style.display =
        "none";


    camPlaceholder.style.display =
        "block";


    camFrame.classList.remove(
        "camera-active"
    );


    statusCam.textContent =
        "Câmera inativa";


    dotCam.classList.remove(
        "on"
    );


    statusHand.textContent =
        "Nenhuma mão detectada";


    dotHand.classList.remove(
        "on"
    );


    palavraAtual.textContent =
        "–";


    ativo = false;

}


/* Clique na câmera */

camFrame.addEventListener(
    "click",
    () => {

        toggleCamera();

    }
);


/* Acessibilidade pelo teclado */

camFrame.addEventListener(
    "keydown",
    (evento) => {

        if(
            evento.key === "Enter" ||
            evento.key === " "
        ){

            evento.preventDefault();

            toggleCamera();

        }

    }
);


/* Voz */

function lerVoz(){

    const texto =
        fraseCompleta
            .textContent
            .trim();


    if(
        !("speechSynthesis" in window)
    ){

        alert(
            "Seu navegador não suporta leitura em voz alta."
        );

        return;

    }


    if(
        texto === "" ||
        texto ===
            "A tradução aparecerá aqui..."
    ){

        return;

    }


    speechSynthesis.cancel();


    const utter =
        new SpeechSynthesisUtterance(
            texto
        );


    utter.lang =
        "pt-BR";


    speechSynthesis.speak(
        utter
    );

}


/* Limpar tradução */

async function limparTexto(){

    fraseCompleta.textContent =
        "A tradução aparecerá aqui...";


    palavraAtual.textContent =
        "–";


    statusHand.textContent =
        "Nenhuma mão detectada";


    dotHand.classList.remove(
        "on"
    );


    try{

        const resposta =
            await fetch(
                "ajax/limpar_traducao.php",
                {
                    method:"POST"
                }
            );


        const texto =
            await resposta.text();


        try{

            const dados =
                JSON.parse(
                    texto
                );


            console.log(
                "Tradução limpa:",
                dados
            );


        }catch(erro){

            console.warn(
                "Resposta de limpar tradução:",
                texto
            );

        }


    }catch(erro){

        console.error(
            "Erro ao limpar tradução:",
            erro
        );

    }

}


/* Desliga ao sair */

window.addEventListener(
    "beforeunload",
    () => {

        if(stream){

            stream
                .getTracks()
                .forEach(
                    track => track.stop()
                );

        }

    }
);

</script>



<!-- MENU MOBILE -->

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
        !btn ||
        !sidebar
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
        .querySelectorAll("a")
        .forEach(
            function(a){

                a.addEventListener(
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