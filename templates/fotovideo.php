<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>UPLOAD - Tradução Foto/Video</title>
        <link rel="stylesheet" href="../static/css/style.css">
        <style>
            .upload-container { max-width: 600px; margin:auto; padding:24px; }
            .upload-area { border:2px dashed #aaa; padding:32px; border-radius:16px; text-align:center; cursor:pointer; }
            .upload-area.highlight { border-color: #333; }
            .preview-area { margin-top:16px; }
            .preview-area img, .preview-area video { max-width:100%; border-radius:12px; }
            .text-display { margin-top:24px; background:#f3f3f3; padding:16px; border-radius:12px; min-height:60px; font-size:1.1rem; }
        </style>
    </head>
<body>
<div class="container">
    <div class="container">
        <aside class="sidebar" aria-label="Sidebar principal">
            <div>
                <nav>
                    <ul>
                        <li>
                            <a href="../index.php">
                                <img src="../static/images/house.png" alt="" srcset="">
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="leitor.php">
                                <img src="../static/images/videocam.png" alt="" srcset="">
                                <span>Leitura e tradução</span>
                            </a>
                        </li>
                        <li>
                            <a href="fotovideo.php">
                                <img src="../static/images/image (1).png" alt="" srcset="">
                                <span>Tradução foto/video</span>
                            </a>
                        </li>
                        <!-- Ajuda removido do menu principal conforme solicitado; permanece no menu hambúrguer -->
                    </ul>
                </nav>
            </div>
            <div>
                <div class="sidebar-card" role="button" aria-label="Menu rápido" id="hamburgerMenu">
                    <div style="display:flex;flex-direction:column;justify-content:center">
                      <img src="../static/images/information.png" alt="" srcset="">
                    </div>
                </div>
                <div class="sidebar-menu" id="sidebarMenu" style="display:none;">
                    <ul>
                        <li><a href="ajuda.php">Ajuda</a></li>
                    </ul>
                </div>
            </div>
        </aside>

    <main class="content">
        <div class="upload-container">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="upload-area" id="dropZone">
                    <input type="file" id="fileInput" name="mediaFile[]" accept="image/*,video/*" multiple style="display:none;">
                    <div class="upload-info">
                        <p>Arraste uma foto ou vídeo aqui ou</p>
                        <button type="button" onclick="fileInput.click()">Selecionar arquivo</button>
                    </div>
                    <div id="preview" class="preview-area" style="display:none;">
                        
                    </div>
                </div>

                <div class="text-display" id="resultText"><center><br><br>A interpretação aparecerá aqui...</center></div>

                <button type="submit" style="margin-top:16px;padding:8px 20px;border-radius:8px;">Enviar</button>
                <button type="button" id="btnLimpar" class="btn btn-limpar" style="margin-top:16px;padding:8px 20px;border-radius:8px;">
                    Limpar
                </button>
            </form>
        </div>
    </main>
</div>

<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('preview');
    const uploadInfo = document.querySelector('.upload-info');

    let arquivosSelecionados = [];

    ['dragenter','dragover','dragleave','drop'].forEach(e => {
        dropZone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); }, false);
    });
    ['dragenter','dragover'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.add('highlight'), false));
    ['dragleave','drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('highlight'), false));

    dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);
    fileInput.addEventListener('change', e => handleFiles(e.target.files));

    function handleFiles(files){

        for(const file of files){

            // evita duplicados
            if(arquivosSelecionados.some(f =>
                f.name === file.name &&
                f.size === file.size &&
                f.lastModified === file.lastModified
            )){
                continue;
            }

            arquivosSelecionados.push(file);

            if(preview.style.display === "none"){
                preview.style.display = "block";
                uploadInfo.style.display = "none";
            }

            if(file.type.startsWith("image/")){

                const img = document.createElement("img");

                img.style.maxWidth = "250px";
                img.style.margin = "10px";

                const reader = new FileReader();

                reader.onload = e => img.src = e.target.result;

                reader.readAsDataURL(file);

                preview.appendChild(img);

            }else if(file.type.startsWith("video/")){

                const video = document.createElement("video");

                video.controls = true;

                video.src = URL.createObjectURL(file);

                video.style.maxWidth = "250px";
                video.style.margin = "10px";

                preview.appendChild(video);
            }
        }

        // permite selecionar novamente
        fileInput.value = "";
    }
    // Envio do arquivo via fetch
    document.getElementById('uploadForm').addEventListener('submit', async e => {
        e.preventDefault();



        const files = arquivosSelecionados;

        if(files.length === 0){
            alert("Selecione um arquivo primeiro"); 
            return; 
            }

        const formData = new FormData();

        for(const file of files){
            
        formData.append("mediaFile[]", file);

        }

        const res = await fetch('ajax/analisar.php', { method:'POST', body:formData });
        const data = await res.json();

        const resultDiv = document.getElementById('resultText');
        if(data.success){

            const frase = data.resultados
            .map(r => r.gesto)
            .join(" ");

        resultDiv.textContent = frase;

        } else {
            resultDiv.textContent = "Erro: " + data.error;
        }
    });

    // limpar tradução
    arquivosSelecionados = [];
    preview.innerHTML = "";

    const btnLimpar = document.getElementById("btnLimpar");

    btnLimpar.addEventListener("click", async () => {

        arquivosSelecionados = [];
        preview.innerHTML = "";
        preview.style.display = "none";

        fileInput.value = "";

        uploadInfo.style.display = "block";

        document.getElementById("resultText").innerHTML =
            "<center><br><br>A interpretação aparecerá aqui...</center>";

        await fetch("ajax/limpar_traducao.php", {
            method: "POST"
        });

    });
</script>
</body>
</html>
