<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Administração do sistema</title>

    <link
        rel="stylesheet"
        href="../static/css/style.css"
    >

</head>

<body>

<div class="container">

    <aside
        class="sidebar"
        aria-label="Menu principal"
    >

        <div>

            <nav>

                <ul>

                    <li>

                        <a href="../index.php">

                            <img
                                src="../static/images/house.png"
                                alt=""
                            >

                            <span>Home</span>

                        </a>

                    </li>

                    <li>

                        <a href="leitor.php">

                            <img
                                src="../static/images/videocam.png"
                                alt=""
                            >

                            <span>Leitura e tradução</span>

                        </a>

                    </li>

                    <li>

                        <a href="fotovideo.php">

                            <img
                                src="../static/images/image (1).png"
                                alt=""
                            >

                            <span>Tradução foto/vídeo</span>

                        </a>

                    </li>

                    <li>

                        <a href="administrador.php">

                            <img
                                src="../static/images/folder.png"
                                alt=""
                            >

                            <span>Administrador</span>

                        </a>

                    </li>

                </ul>

            </nav>

        </div>

        <div>

            <div
                class="sidebar-card"
                id="hamburgerMenu"
                role="button"
                tabindex="0"
                aria-label="Abrir menu de ajuda"
            >

                <div class="sidebar-card-content">

                    <img
                        src="../static/images/information.png"
                        alt=""
                    >

                </div>

            </div>

            <div
                class="sidebar-menu"
                id="sidebarMenu"
                hidden
            >

                <ul>

                    <li>

                        <a href="ajuda.php">
                            Ajuda
                        </a>

                    </li>

                </ul>

            </div>

        </div>

    </aside>

    <main class="content admin-content">

        <header class="admin-header">

            <div>

                <span class="admin-eyebrow">
                    Painel administrativo
                </span>

                <h1>
                    Administração do tradutor
                </h1>

                <p>
                    Cadastre novas mídias, gere datasets processados
                    e acompanhe o treinamento do modelo.
                </p>

            </div>

        </header>


        <!-- ======================================
             SEÇÃO DE CADASTRO DO DATASET
        ======================================= -->

        <section
            class="admin-section"
            aria-labelledby="datasetSectionTitle"
        >

            <div class="section-heading">

                <div>

                    <span class="section-step">
                        Etapa 1
                    </span>

                    <h2 id="datasetSectionTitle">
                        Cadastro de dataset
                    </h2>

                    <p>
                        Informe o nome do gesto e selecione as imagens
                        ou vídeos que serão utilizados.
                    </p>

                </div>

            </div>

            <form
                id="uploadForm"
                class="admin-form"
                enctype="multipart/form-data"
            >

                <div class="form-group">

                    <label for="datasetNome">
                        Nome do gesto
                    </label>

                    <input
                        type="text"
                        id="datasetNome"
                        name="dataset"
                        class="dataset-input"
                        placeholder="Ex.: obrigado, casa, bom_dia"
                        autocomplete="off"
                        required
                    >

                    <small>
                        Utilize um nome claro para identificar a classe
                        do gesto.
                    </small>

                </div>

                <div
                    class="upload-area"
                    id="dropZone"
                >

                    <input
                        type="file"
                        id="fileInput"
                        accept="image/*,video/*"
                        multiple
                        hidden
                    >

                    <div class="upload-info">

                        <div class="upload-icon">
                            ＋
                        </div>

                        <p>
                            Arraste fotos e vídeos para esta área
                        </p>

                        <span>
                            ou
                        </span>

                        <button
                            type="button"
                            id="btnSelecionarArquivos"
                            class="btn-secondary"
                        >
                            Selecionar arquivos
                        </button>

                    </div>

                    <div
                        id="preview"
                        class="preview-area"
                        hidden
                    >

                        <div class="preview-header">

                            <strong>
                                Arquivos selecionados
                            </strong>

                            <span id="contadorArquivos">
                                0 arquivos
                            </span>

                        </div>

                        <div
                            id="listaArquivos"
                            class="lista-arquivos"
                        ></div>

                    </div>

                </div>

                <div class="status-grid">

                    <div
                        class="status-display"
                        id="status"
                        aria-live="polite"
                    >
                        Nenhum arquivo selecionado.
                    </div>

                    <div
                        class="status-display"
                        id="resultText"
                        aria-live="polite"
                    >
                        Aguardando envio...
                    </div>

                </div>

                <div class="acoes-upload">

                    <button
                        type="submit"
                        id="btnEnviarDataset"
                        class="btn-primary"
                    >
                        Enviar e processar dataset
                    </button>

                    <button
                        type="button"
                        id="btnLimpar"
                        class="btn-danger-outline"
                    >
                        Limpar seleção
                    </button>

                </div>

            </form>

        </section>


        <!-- ======================================
             SEÇÃO DE TREINAMENTO DO MODELO
        ======================================= -->

        <section
            class="admin-section training-section"
            aria-labelledby="trainingSectionTitle"
        >

            <div class="section-heading training-heading">

                <div>

                    <span class="section-step">
                        Etapa 2
                    </span>

                    <h2 id="trainingSectionTitle">
                        Treinamento do modelo
                    </h2>

                    <p>
                        Inicie o treinamento e acompanhe as métricas
                        de acurácia, perda e progresso por época.
                    </p>

                </div>

                <div class="training-actions">

                    <button
                        type="button"
                        id="btnTreinarModelo"
                        class="btn-training"
                    >
                        Iniciar treinamento
                    </button>

                    <button
                        type="button"
                        id="btnCancelarTreino"
                        class="btn-danger-outline"
                        disabled
                    >
                        Cancelar
                    </button>

                </div>

            </div>


            <!-- Progresso geral -->

            <div class="training-progress-card">

                <div class="training-progress-header">

                    <div>

                        <span class="training-progress-label">
                            Progresso do treinamento
                        </span>

                        <strong id="trainingStatus">
                            Aguardando início
                        </strong>

                    </div>

                    <span id="trainingPercent">
                        0%
                    </span>

                </div>

                <div
                    class="progress-track"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                    id="trainingProgressBar"
                >

                    <div
                        class="progress-fill"
                        id="trainingProgressFill"
                    ></div>

                </div>

            </div>


            <!-- Métricas atuais -->

            <div class="metrics-grid">

                <article class="metric-card">

                    <span class="metric-label">
                        Época
                    </span>

                    <strong
                        class="metric-value"
                        id="metricEpoch"
                    >
                        0 / 0
                    </strong>

                    <small>
                        Época atual
                    </small>

                </article>

                <article class="metric-card">

                    <span class="metric-label">
                        Acurácia
                    </span>

                    <strong
                        class="metric-value"
                        id="metricAccuracy"
                    >
                        0,00%
                    </strong>

                    <small>
                        Dados de treinamento
                    </small>

                </article>

                <article class="metric-card">

                    <span class="metric-label">
                        Acurácia de validação
                    </span>

                    <strong
                        class="metric-value"
                        id="metricValAccuracy"
                    >
                        0,00%
                    </strong>

                    <small>
                        Dados de validação
                    </small>

                </article>

                <article class="metric-card">

                    <span class="metric-label">
                        Perda
                    </span>

                    <strong
                        class="metric-value"
                        id="metricLoss"
                    >
                        0,0000
                    </strong>

                    <small>
                        Erro atual
                    </small>

                </article>

            </div>


            <!-- Gráficos -->

            <div class="charts-grid">

                <article class="chart-card">

                    <div class="chart-card-header">

                        <div>

                            <h3>
                                Acurácia por época
                            </h3>

                            <p>
                                Treinamento e validação
                            </p>

                        </div>

                        <div class="chart-legend">

                            <span>
                                <i class="legend-line legend-training"></i>
                                Treinamento
                            </span>

                            <span>
                                <i class="legend-line legend-validation"></i>
                                Validação
                            </span>

                        </div>

                    </div>

                    <div class="chart-wrapper">

                        <canvas
                            id="accuracyChart"
                            aria-label="Gráfico de acurácia"
                        ></canvas>

                        <div
                            class="chart-empty"
                            id="accuracyChartEmpty"
                        >
                            O gráfico será exibido durante o treinamento.
                        </div>

                    </div>

                </article>

                <article class="chart-card">

                    <div class="chart-card-header">

                        <div>

                            <h3>
                                Perda por época
                            </h3>

                            <p>
                                Erro de treinamento e validação
                            </p>

                        </div>

                        <div class="chart-legend">

                            <span>
                                <i class="legend-line legend-training"></i>
                                Treinamento
                            </span>

                            <span>
                                <i class="legend-line legend-validation"></i>
                                Validação
                            </span>

                        </div>

                    </div>

                    <div class="chart-wrapper">

                        <canvas
                            id="lossChart"
                            aria-label="Gráfico de perda"
                        ></canvas>

                        <div
                            class="chart-empty"
                            id="lossChartEmpty"
                        >
                            O gráfico será exibido durante o treinamento.
                        </div>

                    </div>

                </article>

            </div>


            <!-- Log textual -->

            <article class="training-log-card">

                <div class="training-log-header">

                    <div>

                        <h3>
                            Relatório de execução
                        </h3>

                        <p>
                            Mensagens enviadas pelo processo de treinamento.
                        </p>

                    </div>

                    <button
                        type="button"
                        id="btnLimparLog"
                        class="btn-small"
                    >
                        Limpar relatório
                    </button>

                </div>

                <textarea
                    id="trainingLog"
                    class="training-log"
                    readonly
                    spellcheck="false"
                    aria-label="Relatório do treinamento"
                >Aguardando o início do treinamento...</textarea>

            </article>

        </section>

    </main>

</div>

<script>

const dropZone = document.getElementById("dropZone");
const fileInput = document.getElementById("fileInput");
const btnSelecionarArquivos = document.getElementById(
    "btnSelecionarArquivos"
);

const preview = document.getElementById("preview");
const listaArquivos = document.getElementById("listaArquivos");
const contadorArquivos = document.getElementById("contadorArquivos");
const uploadInfo = document.querySelector(".upload-info");

const statusDataset = document.getElementById("status");
const resultadoDataset = document.getElementById("resultText");

const uploadForm = document.getElementById("uploadForm");
const btnEnviarDataset = document.getElementById("btnEnviarDataset");
const btnLimpar = document.getElementById("btnLimpar");

const btnTreinarModelo = document.getElementById("btnTreinarModelo");
const btnCancelarTreino = document.getElementById("btnCancelarTreino");
const btnLimparLog = document.getElementById("btnLimparLog");

const trainingStatus = document.getElementById("trainingStatus");
const trainingPercent = document.getElementById("trainingPercent");

const trainingProgressBar = document.getElementById(
    "trainingProgressBar"
);

const trainingProgressFill = document.getElementById(
    "trainingProgressFill"
);

const metricEpoch = document.getElementById("metricEpoch");
const metricAccuracy = document.getElementById("metricAccuracy");

const metricValAccuracy = document.getElementById(
    "metricValAccuracy"
);

const metricLoss = document.getElementById("metricLoss");

const trainingLog = document.getElementById("trainingLog");

const accuracyCanvas = document.getElementById("accuracyChart");
const lossCanvas = document.getElementById("lossChart");

const accuracyChartEmpty = document.getElementById(
    "accuracyChartEmpty"
);

const lossChartEmpty = document.getElementById(
    "lossChartEmpty"
);

const hamburgerMenu = document.getElementById("hamburgerMenu");
const sidebarMenu = document.getElementById("sidebarMenu");


let arquivosSelecionados = [];

let treinamentoAtivo = false;
let treinamentoJobId = null;
let pollingTreinamento = null;
let consultaStatusEmAndamento = false;
let proximoLogTreinamento = 0;

const dadosGraficos = {
    epochs: [],
    accuracy: [],
    valAccuracy: [],
    loss: [],
    valLoss: []
};


/* Funções gerais */

function escaparHtml(valor) {

    return String(valor)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


function formatarTamanho(bytes) {

    const unidades = [
        "B",
        "KB",
        "MB",
        "GB"
    ];

    let tamanho = Number(bytes);
    let indice = 0;

    while (
        tamanho >= 1024 &&
        indice < unidades.length - 1
    ) {
        tamanho /= 1024;
        indice++;
    }

    return (
        tamanho.toFixed(
            indice === 0 ? 0 : 2
        ) +
        " " +
        unidades[indice]
    );
}


function formatarPercentual(valor) {

    const numero = Number(valor);

    if (!Number.isFinite(numero)) {
        return "0,00%";
    }

    const percentual =
        numero <= 1
            ? numero * 100
            : numero;

    return (
        percentual
            .toFixed(2)
            .replace(".", ",") +
        "%"
    );
}


function formatarDecimal(valor) {

    const numero = Number(valor);

    if (!Number.isFinite(numero)) {
        return "0,0000";
    }

    return numero
        .toFixed(4)
        .replace(".", ",");
}


async function lerJsonSeguro(resposta) {

    const texto = await resposta.text();

    let dados;

    try {

        dados = JSON.parse(texto);

    } catch (erro) {

        console.error(
            "Resposta bruta:",
            texto
        );

        throw new Error(
            "O servidor não retornou um JSON válido. " +
            texto.substring(0, 500)
        );
    }

    if (!resposta.ok) {

        dados.success = false;

        dados.error =
            dados.error ??
            `Erro HTTP ${resposta.status}.`;
    }

    return dados;
}


/* Menu de ajuda */

function alternarMenuLateral() {

    if (!sidebarMenu) {
        return;
    }

    sidebarMenu.hidden =
        !sidebarMenu.hidden;
}


hamburgerMenu?.addEventListener(
    "click",
    alternarMenuLateral
);


hamburgerMenu?.addEventListener(
    "keydown",
    (evento) => {

        if (
            evento.key === "Enter" ||
            evento.key === " "
        ) {
            evento.preventDefault();
            alternarMenuLateral();
        }
    }
);


document.addEventListener(
    "click",
    (evento) => {

        if (
            sidebarMenu &&
            hamburgerMenu &&
            !sidebarMenu.hidden &&
            !sidebarMenu.contains(evento.target) &&
            !hamburgerMenu.contains(evento.target)
        ) {
            sidebarMenu.hidden = true;
        }
    }
);


/* Upload */

function definirBotoesDataset(bloqueado) {

    btnEnviarDataset.disabled = bloqueado;
    btnLimpar.disabled = bloqueado;
    btnSelecionarArquivos.disabled = bloqueado;
    fileInput.disabled = bloqueado;
}


[
    "dragenter",
    "dragover",
    "dragleave",
    "drop"
].forEach((nomeEvento) => {

    dropZone.addEventListener(
        nomeEvento,
        (evento) => {

            evento.preventDefault();
            evento.stopPropagation();
        }
    );
});


[
    "dragenter",
    "dragover"
].forEach((nomeEvento) => {

    dropZone.addEventListener(
        nomeEvento,
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
].forEach((nomeEvento) => {

    dropZone.addEventListener(
        nomeEvento,
        () => {

            dropZone.classList.remove(
                "highlight"
            );
        }
    );
});


dropZone.addEventListener(
    "drop",
    (evento) => {

        handleFiles(
            evento.dataTransfer.files
        );
    }
);


fileInput.addEventListener(
    "change",
    (evento) => {

        handleFiles(
            evento.target.files
        );
    }
);


btnSelecionarArquivos.addEventListener(
    "click",
    () => {

        fileInput.click();
    }
);


function handleFiles(files) {

    for (const file of files) {

        const duplicado =
            arquivosSelecionados.some(
                (arquivoAtual) => (
                    arquivoAtual.name === file.name &&
                    arquivoAtual.size === file.size &&
                    arquivoAtual.lastModified ===
                        file.lastModified
                )
            );

        if (duplicado) {
            continue;
        }

        arquivosSelecionados.push(file);

        const linha =
            document.createElement("div");

        linha.className = "arquivo";

        if (file.type.startsWith("image/")) {

            const imagem =
                document.createElement("img");

            imagem.alt =
                `Pré-visualização de ${file.name}`;

            const reader =
                new FileReader();

            reader.onload = (evento) => {

                imagem.src =
                    evento.target.result;
            };

            reader.readAsDataURL(file);

            linha.appendChild(imagem);

        } else {

            const icone =
                document.createElement("div");

            icone.className = "icone";
            icone.textContent = "🎥";

            linha.appendChild(icone);
        }

        const info =
            document.createElement("div");

        info.className = "info";

        info.innerHTML = `
            <div class="nome">
                ${escaparHtml(file.name)}
            </div>

            <div class="tamanho">
                ${formatarTamanho(file.size)}
            </div>
        `;

        linha.appendChild(info);
        listaArquivos.appendChild(linha);
    }

    fileInput.value = "";

    atualizarResumoArquivos();
}


function atualizarResumoArquivos() {

    const quantidade =
        arquivosSelecionados.length;

    if (quantidade === 0) {

        preview.hidden = true;
        uploadInfo.hidden = false;

        statusDataset.textContent =
            "Nenhum arquivo selecionado.";

        contadorArquivos.textContent =
            "0 arquivos";

        return;
    }

    preview.hidden = false;
    uploadInfo.hidden = true;

    statusDataset.textContent =
        `${quantidade} arquivo(s) selecionado(s).`;

    contadorArquivos.textContent =
        `${quantidade} arquivo(s)`;
}


uploadForm.addEventListener(
    "submit",
    async (evento) => {

        evento.preventDefault();

        if (arquivosSelecionados.length === 0) {

            alert("Selecione as mídias.");
            return;
        }

        const nomeDataset = document
            .getElementById("datasetNome")
            .value
            .trim();

        if (!nomeDataset) {

            alert("Informe o nome do dataset.");
            return;
        }

        definirBotoesDataset(true);

        resultadoDataset.textContent =
            "Iniciando o envio dos arquivos...";

        try {

            for (
                let indice = 0;
                indice < arquivosSelecionados.length;
                indice++
            ) {

                const arquivo =
                    arquivosSelecionados[indice];

                resultadoDataset.innerHTML = `
                    <strong>
                        Enviando ${indice + 1}
                        de ${arquivosSelecionados.length}
                    </strong>

                    <br><br>

                    ${escaparHtml(arquivo.name)}
                `;

                const formData =
                    new FormData();

                formData.append(
                    "dataset",
                    nomeDataset
                );

                formData.append(
                    "mediaFile",
                    arquivo
                );

                const resposta = await fetch(
                    "ajax/criar_dataset.php",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const dados =
                    await lerJsonSeguro(
                        resposta
                    );

                console.log(
                    "Upload:",
                    dados
                );

                if (
                    !dados.success ||
                    dados.rota !== "criar_dataset" ||
                    dados.existe !== true
                ) {
                    throw new Error(
                        dados.error ??
                        "O servidor não confirmou o arquivo."
                    );
                }
            }

            resultadoDataset.innerHTML = `
                <strong>
                    Upload concluído.
                </strong>

                <br><br>

                Processando o dataset...
            `;

            const respostaFinal =
                await fetch(
                    "ajax/finalizar_dataset.php",
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/x-www-form-urlencoded; charset=UTF-8"
                        },

                        body: new URLSearchParams({
                            dataset: nomeDataset
                        }).toString()
                    }
                );

            const dadosFinais =
                await lerJsonSeguro(
                    respostaFinal
                );

            console.log(
                "Finalização:",
                dadosFinais
            );

            if (!dadosFinais.success) {

                throw new Error(
                    dadosFinais.error ??
                    "Erro ao processar o dataset."
                );
            }

            resultadoDataset.innerHTML = `
                <strong>
                    Dataset criado com sucesso!
                </strong>

                <br><br>

                Total de amostras:
                ${dadosFinais.total_amostras ?? 0}

                <br>

                Datasets processados:
                ${dadosFinais.datasets_processados ?? 0}
            `;

        } catch (erro) {

            resultadoDataset.innerHTML = `
                <strong>
                    Erro durante a criação do dataset.
                </strong>

                <br><br>

                ${escaparHtml(erro.message)}
            `;

        } finally {

            definirBotoesDataset(false);
        }
    }
);


btnLimpar.addEventListener(
    "click",
    () => {

        arquivosSelecionados = [];

        fileInput.value = "";
        listaArquivos.innerHTML = "";

        document.getElementById(
            "datasetNome"
        ).value = "";

        resultadoDataset.textContent =
            "Aguardando envio...";

        atualizarResumoArquivos();
    }
);


/* Treinamento */

function adicionarLog(mensagem) {

    if (
        mensagem === undefined ||
        mensagem === null ||
        mensagem === ""
    ) {
        return;
    }

    if (
        trainingLog.value.trim() ===
        "Aguardando o início do treinamento..."
    ) {
        trainingLog.value = "";
    }

    const horario =
        new Date().toLocaleTimeString(
            "pt-BR",
            {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit"
            }
        );

    trainingLog.value +=
        `${trainingLog.value ? "\n" : ""}` +
        `[${horario}] ${String(mensagem)}`;

    trainingLog.scrollTop =
        trainingLog.scrollHeight;
}


function definirProgresso(
    percentual,
    mensagem
) {

    const valor = Math.max(
        0,
        Math.min(
            100,
            Number(percentual) || 0
        )
    );

    trainingProgressFill.style.width =
        `${valor}%`;

    trainingProgressBar.setAttribute(
        "aria-valuenow",
        String(valor)
    );

    trainingPercent.textContent =
        `${valor.toFixed(0)}%`;

    if (mensagem) {

        trainingStatus.textContent =
            mensagem;
    }
}


function limparDadosTreinamento() {

    dadosGraficos.epochs = [];
    dadosGraficos.accuracy = [];
    dadosGraficos.valAccuracy = [];
    dadosGraficos.loss = [];
    dadosGraficos.valLoss = [];

    metricEpoch.textContent = "0 / 0";
    metricAccuracy.textContent = "0,00%";
    metricValAccuracy.textContent = "0,00%";
    metricLoss.textContent = "0,0000";

    definirProgresso(
        0,
        "Aguardando início"
    );

    atualizarGraficos();
}


function prepararCanvas(canvas) {

    const proporcao =
        window.devicePixelRatio || 1;

    const largura =
        canvas.clientWidth || 600;

    const altura =
        canvas.clientHeight || 290;

    canvas.width =
        largura * proporcao;

    canvas.height =
        altura * proporcao;

    const contexto =
        canvas.getContext("2d");

    contexto.setTransform(
        proporcao,
        0,
        0,
        proporcao,
        0,
        0
    );

    return {
        contexto,
        largura,
        altura
    };
}


function desenharSerie(
    contexto,
    valores,
    converterX,
    converterY,
    cor,
    tracejada
) {

    const pontos = valores
        .map((valor, indice) => ({
            indice,
            valor: Number(valor)
        }))
        .filter((item) =>
            Number.isFinite(item.valor)
        );

    if (pontos.length === 0) {
        return;
    }

    contexto.save();

    contexto.strokeStyle = cor;
    contexto.fillStyle = cor;
    contexto.lineWidth = 2;

    contexto.setLineDash(
        tracejada
            ? [6, 4]
            : []
    );

    contexto.beginPath();

    pontos.forEach(
        (ponto, indice) => {

            const x =
                converterX(ponto.indice);

            const y =
                converterY(ponto.valor);

            if (indice === 0) {
                contexto.moveTo(x, y);
            } else {
                contexto.lineTo(x, y);
            }
        }
    );

    contexto.stroke();
    contexto.setLineDash([]);

    pontos.forEach((ponto) => {

        contexto.beginPath();

        contexto.arc(
            converterX(ponto.indice),
            converterY(ponto.valor),
            3,
            0,
            Math.PI * 2
        );

        contexto.fill();
    });

    contexto.restore();
}


function desenharGrafico(
    canvas,
    epochs,
    treino,
    validacao,
    percentual
) {

    const {
        contexto,
        largura,
        altura
    } = prepararCanvas(canvas);

    contexto.clearRect(
        0,
        0,
        largura,
        altura
    );

    if (epochs.length === 0) {
        return;
    }

    const margem = {
        topo: 20,
        direita: 18,
        baixo: 40,
        esquerda: 52
    };

    const larguraUtil =
        largura -
        margem.esquerda -
        margem.direita;

    const alturaUtil =
        altura -
        margem.topo -
        margem.baixo;

    const valores = [
        ...treino,
        ...validacao
    ]
        .map(Number)
        .filter(Number.isFinite);

    let minimo =
        percentual
            ? 0
            : Math.min(...valores);

    let maximo =
        percentual
            ? 1
            : Math.max(...valores);

    if (!Number.isFinite(minimo)) {
        minimo = 0;
    }

    if (!Number.isFinite(maximo)) {
        maximo = 1;
    }

    if (minimo === maximo) {
        maximo = minimo + 1;
    }

    const converterX = (indice) => {

        if (epochs.length === 1) {

            return (
                margem.esquerda +
                larguraUtil / 2
            );
        }

        return (
            margem.esquerda +
            indice /
            (epochs.length - 1) *
            larguraUtil
        );
    };

    const converterY = (valor) => {

        return (
            margem.topo +
            (
                1 -
                (
                    (valor - minimo) /
                    (maximo - minimo)
                )
            ) *
            alturaUtil
        );
    };

    contexto.font = "11px Arial";
    contexto.fillStyle = "#777";
    contexto.strokeStyle = "#e3e3e3";
    contexto.lineWidth = 1;

    for (
        let indice = 0;
        indice <= 5;
        indice++
    ) {

        const proporcao = indice / 5;

        const y =
            margem.topo +
            proporcao * alturaUtil;

        contexto.beginPath();

        contexto.moveTo(
            margem.esquerda,
            y
        );

        contexto.lineTo(
            margem.esquerda +
            larguraUtil,
            y
        );

        contexto.stroke();

        const valor =
            maximo -
            proporcao *
            (maximo - minimo);

        contexto.fillText(
            percentual
                ? `${(valor * 100).toFixed(0)}%`
                : valor.toFixed(3),
            4,
            y + 4
        );
    }

    desenharSerie(
        contexto,
        treino,
        converterX,
        converterY,
        "#252525",
        false
    );

    desenharSerie(
        contexto,
        validacao,
        converterX,
        converterY,
        "#999999",
        true
    );

    contexto.fillStyle = "#777";
    contexto.textAlign = "center";

    const quantidadeRotulos =
        Math.min(
            epochs.length,
            8
        );

    for (
        let indice = 0;
        indice < quantidadeRotulos;
        indice++
    ) {

        const posicao =
            quantidadeRotulos === 1
                ? 0
                : Math.round(
                    indice *
                    (epochs.length - 1) /
                    (quantidadeRotulos - 1)
                );

        contexto.fillText(
            String(epochs[posicao]),
            converterX(posicao),
            altura - 12
        );
    }

    contexto.textAlign = "start";
}


function atualizarGraficos() {

    const possuiDados =
        dadosGraficos.epochs.length > 0;

    accuracyChartEmpty.hidden =
        possuiDados;

    lossChartEmpty.hidden =
        possuiDados;

    desenharGrafico(
        accuracyCanvas,
        dadosGraficos.epochs,
        dadosGraficos.accuracy,
        dadosGraficos.valAccuracy,
        true
    );

    desenharGrafico(
        lossCanvas,
        dadosGraficos.epochs,
        dadosGraficos.loss,
        dadosGraficos.valLoss,
        false
    );
}


function atualizarDadosEpoca(dados) {

    const epoca =
        Number(dados.epoch);

    if (
        !Number.isInteger(epoca) ||
        epoca <= 0
    ) {
        return;
    }

    const indice =
        dadosGraficos.epochs.indexOf(
            epoca
        );

    const accuracy =
        Number(dados.accuracy);

    const valAccuracy =
        Number(dados.val_accuracy);

    const loss =
        Number(dados.loss);

    const valLoss =
        Number(dados.val_loss);

    if (indice === -1) {

        dadosGraficos.epochs.push(epoca);
        dadosGraficos.accuracy.push(accuracy);
        dadosGraficos.valAccuracy.push(valAccuracy);
        dadosGraficos.loss.push(loss);
        dadosGraficos.valLoss.push(valLoss);

    } else {

        dadosGraficos.accuracy[indice] =
            accuracy;

        dadosGraficos.valAccuracy[indice] =
            valAccuracy;

        dadosGraficos.loss[indice] =
            loss;

        dadosGraficos.valLoss[indice] =
            valLoss;
    }

    atualizarGraficos();
}


function processarLogs(logs) {

    if (!Array.isArray(logs)) {
        return;
    }

    for (const item of logs) {

        if (
            typeof item === "object" &&
            item !== null
        ) {

            adicionarLog(
                item.mensagem ??
                item.message ??
                JSON.stringify(item)
            );

        } else {

            adicionarLog(
                String(item)
            );
        }
    }
}


function aplicarStatusTreinamento(dados) {

    const epoca =
        Number(dados.epoch ?? 0);

    const total =
        Number(dados.total_epochs ?? 0);

    let progresso =
        Number(dados.progress);

    if (!Number.isFinite(progresso)) {

        progresso =
            total > 0
                ? epoca / total * 100
                : 0;
    }

    metricEpoch.textContent =
        `${epoca} / ${total}`;

    metricAccuracy.textContent =
        formatarPercentual(
            dados.accuracy
        );

    metricValAccuracy.textContent =
        formatarPercentual(
            dados.val_accuracy
        );

    metricLoss.textContent =
        formatarDecimal(
            dados.loss
        );

    definirProgresso(
        progresso,
        dados.message ??
        dados.status ??
        "Treinamento em andamento"
    );

    atualizarDadosEpoca(dados);

    processarLogs(
        dados.logs
    );

    if (dados.log) {

        adicionarLog(
            dados.log
        );
    }

    const proximoLog =
        Number(dados.proximo_log);

    if (
        Number.isInteger(proximoLog) &&
        proximoLog >= 0
    ) {

        proximoLogTreinamento =
            proximoLog;
    }
}


function pararMonitoramentoTreinamento() {

    if (pollingTreinamento !== null) {

        clearInterval(
            pollingTreinamento
        );

        pollingTreinamento = null;
    }
}


function finalizarInterfaceTreinamento() {

    treinamentoAtivo = false;
    consultaStatusEmAndamento = false;

    pararMonitoramentoTreinamento();

    btnTreinarModelo.disabled = false;
    btnCancelarTreino.disabled = true;
}


async function consultarStatusTreinamento() {

    if (
        !treinamentoAtivo ||
        consultaStatusEmAndamento
    ) {
        return;
    }

    consultaStatusEmAndamento = true;

    try {

        const parametros =
            new URLSearchParams();

        parametros.set(
            "desde_log",
            String(proximoLogTreinamento)
        );

        if (treinamentoJobId) {

            parametros.set(
                "job_id",
                treinamentoJobId
            );
        }

        const resposta = await fetch(
            "ajax/status_treinamento.php?" +
            parametros.toString(),
            {
                method: "GET",
                cache: "no-store"
            }
        );

        const dados =
            await lerJsonSeguro(
                resposta
            );

        console.log(
            "Status do treinamento:",
            dados
        );

        if (!dados.success) {

            throw new Error(
                dados.error ??
                "Erro ao consultar o treinamento."
            );
        }

        aplicarStatusTreinamento(
            dados
        );

        if (
            dados.status === "erro" ||
            dados.failed === true
        ) {

            adicionarLog(
                "Erro: " +
                (
                    dados.error ??
                    "O treinamento falhou."
                )
            );

            definirProgresso(
                dados.progress ?? 0,
                "Erro no treinamento"
            );

            finalizarInterfaceTreinamento();
            return;
        }

        if (
            dados.status === "cancelado" ||
            dados.cancelled === true
        ) {

            adicionarLog(
                dados.message ??
                "Treinamento cancelado."
            );

            definirProgresso(
                dados.progress ?? 0,
                "Treinamento cancelado"
            );

            finalizarInterfaceTreinamento();
            return;
        }

        if (
            dados.status === "concluido" ||
            (
                dados.finished === true &&
                dados.failed !== true &&
                dados.cancelled !== true
            )
        ) {

            definirProgresso(
                100,
                "Treinamento concluído"
            );

            adicionarLog(
                dados.message ??
                "Modelo treinado com sucesso."
            );

            if (dados.resultado) {

                adicionarLog(
                    "Acurácia final: " +
                    formatarPercentual(
                        dados.resultado
                            .accuracy_final
                    )
                );

                adicionarLog(
                    "Épocas executadas: " +
                    (
                        dados.resultado
                            .epochs_executadas ??
                        dados.epoch ??
                        0
                    )
                );
            }

            finalizarInterfaceTreinamento();
        }

    } catch (erro) {

        console.error(
            "Erro de monitoramento:",
            erro
        );

        adicionarLog(
            `Erro de monitoramento: ${erro.message}`
        );

        definirProgresso(
            trainingProgressBar.getAttribute(
                "aria-valuenow"
            ),
            "Erro de comunicação"
        );

        finalizarInterfaceTreinamento();

    } finally {

        consultaStatusEmAndamento = false;
    }
}


function iniciarMonitoramentoTreinamento() {

    pararMonitoramentoTreinamento();

    consultarStatusTreinamento();

    pollingTreinamento =
        setInterval(
            consultarStatusTreinamento,
            1500
        );
}


btnTreinarModelo.addEventListener(
    "click",
    async () => {

        console.log(
            "Botão de treinamento acionado"
        );

        if (treinamentoAtivo) {
            return;
        }

        treinamentoAtivo = true;
        treinamentoJobId = null;
        proximoLogTreinamento = 0;

        btnTreinarModelo.disabled = true;
        btnCancelarTreino.disabled = false;

        limparDadosTreinamento();

        trainingLog.value = "";

        adicionarLog(
            "Solicitando início do treinamento..."
        );

        definirProgresso(
            0,
            "Iniciando treinamento"
        );

        try {

            const resposta = await fetch(
                "ajax/treinar_modelo.php",
                {
                    method: "POST"
                }
            );

            const dados =
                await lerJsonSeguro(
                    resposta
                );

            console.log(
                "Início do treinamento:",
                dados
            );

            if (!dados.success) {

                throw new Error(
                    dados.error ??
                    "Não foi possível iniciar o treinamento."
                );
            }

            treinamentoJobId =
                dados.job_id ?? null;

            adicionarLog(
                dados.message ??
                "Treinamento iniciado."
            );

            definirProgresso(
                0,
                dados.message ??
                "Preparando treinamento"
            );

            iniciarMonitoramentoTreinamento();

        } catch (erro) {

            console.error(
                "Erro ao iniciar treinamento:",
                erro
            );

            adicionarLog(
                `Erro: ${erro.message}`
            );

            definirProgresso(
                0,
                "Falha ao iniciar"
            );

            finalizarInterfaceTreinamento();
        }
    }
);


btnCancelarTreino.addEventListener(
    "click",
    async () => {

        if (!treinamentoAtivo) {
            return;
        }

        const confirmou = confirm(
            "Deseja cancelar o treinamento atual?"
        );

        if (!confirmou) {
            return;
        }

        btnCancelarTreino.disabled = true;

        adicionarLog(
            "Solicitando cancelamento..."
        );

        try {

            const corpo =
                new URLSearchParams();

            if (treinamentoJobId) {

                corpo.set(
                    "job_id",
                    treinamentoJobId
                );
            }

            const resposta = await fetch(
                "ajax/cancelar_treinamento.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded; charset=UTF-8"
                    },

                    body: corpo.toString()
                }
            );

            const dados =
                await lerJsonSeguro(
                    resposta
                );

            if (!dados.success) {

                throw new Error(
                    dados.error ??
                    "Não foi possível cancelar."
                );
            }

            adicionarLog(
                dados.message ??
                "Cancelamento solicitado."
            );

            definirProgresso(
                trainingProgressBar.getAttribute(
                    "aria-valuenow"
                ),
                "Cancelamento solicitado"
            );

        } catch (erro) {

            adicionarLog(
                `Erro ao cancelar: ${erro.message}`
            );

            btnCancelarTreino.disabled = false;
        }
    }
);


btnLimparLog.addEventListener(
    "click",
    () => {

        trainingLog.value = "";
    }
);


/* Inicialização */

let temporizadorResize = null;

window.addEventListener(
    "resize",
    () => {

        clearTimeout(
            temporizadorResize
        );

        temporizadorResize =
            setTimeout(
                atualizarGraficos,
                150
            );
    }
);


atualizarResumoArquivos();
limparDadosTreinamento();

console.log(
    "Script da administração carregado."
);

</script>

</body>

</html>