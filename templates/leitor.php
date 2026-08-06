<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAMERA</title>
    <link rel="stylesheet" href="../static/css/style.css">
    <style>
        .camera-container {
            position: relative;
        }
        .camera-placeholder {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #555;
            background: #fff;
            border-radius: 32px;
            cursor: pointer;
            z-index: 2;
        }
        .camera-active .camera-placeholder {
            display: none;
        }
        .camera-active video {
            display: block;
        }
        .camera-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 32px;
            display: none;
        }
    </style>
</head>
<body>
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
                        <li>
                            <a href="administrador.php">
                                <img src="../static/images/folder.png">
                                <span>Administrador</span>
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
        <div class="camera-container" id="cameraBox">
            <div class="camera-placeholder">Clique para abrir a câmera</div>
            <video id="cameraFeed" autoplay playsinline></video>
            <canvas id="canvas" style="display: none;"></canvas>
        </div>
        <div class="text-display">
            <div id="resultText"><br><br><br>A interpretação aparecerá aqui...</div>
        </div>
        <button type="button" id="btnLimpar" class="btn btn-limpar" style="margin-top:16px;padding:8px 20px;border-radius:8px;">
            Limpar
        </button>
    </main>
    </div>

        <button class="fab" title="Perfil" aria-label="Perfil">
            <!-- User / perfil -->
            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
        </button>
    </div>

    <script>

        const canvas = document.getElementById("canvas");
        const ctx = canvas.getContext("2d");

        const cameraBox = document.getElementById('cameraBox');
        const video = document.getElementById('cameraFeed');
        let stream = null;
        let captura = null;
        let isActive = false;

        let enviando = false;



        function iniciarCaptura(){

            if(captura)
                clearInterval(captura);

            captura = setInterval(async () => {

                if(!isActive || enviando) return;

                enviando = true;

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                ctx.drawImage(video,0,0);

                canvas.toBlob(async(blob)=>{

                    const form = new FormData();

                    form.append("frame",blob,"frame.jpg");

                    try{

                        console.log("Enviando...");
                        const resposta = await fetch("ajax/traducao_tempo_real.php",{
                            method:"POST",
                            body:form
                        });

                        const dados = await resposta.json();
                        console.log("Resposta:", dados);

                        const result = document.getElementById("resultText");

                        switch(dados.status){

                            case "analisando":
                                result.innerHTML =
                                    `Gesto: <b>${dados.gesto}</b><br>
                                    <small>Confirmando (${dados.confirmacao}/3)...</small>`;
                                break;

                            case "traduzido":
                                result.textContent = dados.texto;
                                break;

                            case "aguardando":
                                if(result.textContent.trim() === ""){
                                    result.textContent = "A interpretação aparecerá aqui...";
                                }
                                break;
                        }
                        

                    }catch(e){
                        console.log(e);
                    }

                    enviando=false;

                },"image/jpeg",0.8);

            },100);

        }


        document.addEventListener('DOMContentLoaded', function() {
            // Alternância da câmera ao clicar
            cameraBox.addEventListener('click', async function() {
                if (!isActive) {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        video.srcObject = stream;

                        isActive = true;

                        video.onloadedmetadata = () => {
                            iniciarCaptura();
                        };

                        cameraBox.classList.add('camera-active');
                    } catch (error) {
                        alert('Não foi possível acessar a câmera: ' + error.message);
                    }
                } else {
                    
                    clearInterval(captura);
                    captura = null;

                    if(stream){
                        stream.getTracks().forEach(track => track.stop());
                    }

                    video.pause();
                    video.srcObject = null;

                    clearInterval(captura);
                    captura = null;

                    isActive = false;

                    cameraBox.classList.remove("camera-active");
                }
            });

            // Menu hamburguer toggle
            var hamburger = document.getElementById('hamburgerMenu');
            var menu = document.getElementById('sidebarMenu');
            hamburger.addEventListener('click', function(e){
                e.stopPropagation();
                if(menu.style.display === 'none' || menu.style.display === ''){
                    menu.style.display = 'block';
                } else {
                    menu.style.display = 'none';
                }
            });
            document.addEventListener('click', function(e){
                if(menu.style.display === 'block' && !menu.contains(e.target) && !hamburger.contains(e.target)){
                    menu.style.display = 'none';
                }
            });
        });

        // Função para atualizar o texto do campo de resultado
        window.setGestureText = function(text) {
            document.getElementById('resultText').textContent = text;
        };
        // F1 shortcut to open help page from this page
        window.addEventListener('keydown', function(e){
            if(e.key === 'F1'){
                e.preventDefault();
                window.location.href = "{{ url_for('ajuda') }}";
            }
        });


        const btnLimpar = document.getElementById("btnLimpar");

        btnLimpar.addEventListener("click", async () => {

            // limpa a caixa de texto
            document.getElementById("resultText").innerHTML =
                "<center><br><br>A interpretação aparecerá aqui...</center>";

            // avisa o Flask para limpar a frase
            await fetch("ajax/limpar_traducao.php", {
                method: "POST"
            });

        });

 
    </script>
</body>
</html>
