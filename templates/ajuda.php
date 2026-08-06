<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Ajuda – Programa de LIBRAS</title>
    <link rel="stylesheet" href="../static/css/style.css">
    <style>
        /* Pequeno estilo local para a página de ajuda para combinar com o layout existente */
        .help-wrapper {
            margin-left: 0;
            padding: 12px 24px;
            box-sizing: border-box;
            display: block;
        }
        .help-card {
            background: #f3f3f3;
            border-radius: 20px;
            padding: 28px 36px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin: 12px 16px;
            max-width: none;
            width: calc(100% - 40px);
        }
        /* Headings: larger and distinct font */
        .help-card h1 { font-size: 2rem; margin-bottom: 12px; font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight:700; }
    .help-card h2 { font-size: 1.6rem; margin-top: 18px; font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight:600; }
    .help-card h3 { font-size: 1.15rem; margin-top:14px; font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight:600; }
    .help-card p, .help-card li { line-height: 1.6; color: #222; font-size:1rem }
        .contact { margin-top: 18px; }
        .faq ol { margin-left: 18px; }
        @media (max-width: 900px) {
            .help-wrapper { margin-left: 0; padding: 16px; }
            .help-card { padding: 18px; margin: 8px; width: auto; }
            .help-card h1 { font-size: 1.6rem; }
            .help-card h2 { font-size: 1.2rem; }
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
                                <img src="../static/images/videocam.png'" alt="" srcset="">
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
            <div class="help-wrapper">
                <div class="help-card">
                    <h1><svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:10px"><path d="M12 2a1 1 0 00-1 1v7H4a1 1 0 000 2h7v7a1 1 0 002 0v-7h7a1 1 0 000-2h-7V3a1 1 0 00-1-1z"></path></svg> Machine in Libras</h1>
                    <p><strong><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zM11 6h2v6h-2V6zm0 8h2v2h-2v-2z"></path></svg> Sobre o Programa</strong></p>
                    <p>Bem-vindo(a) ao <strong>Machine in Libras</strong>! Este programa foi desenvolvido para auxiliar estudantes, intérpretes e professores na compreensão, tradução e prática da Língua Brasileira de Sinais (LIBRAS). Aqui você pode aprender sinais, testar seu conhecimento e usar ferramentas de tradução entre Português e Libras.</p>

                    <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px"><path d="M12 2L2 7l10 5 10-5-10-5zm0 7.5L4.5 7 12 3.5 19.5 7 12 9.5zM2 17l10 5 10-5v-2l-10 5-10-5v2z"></path></svg> Navegação Principal</h2>

                    <h3><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px"><path d="M3 4v16h18V4H3zm2 2h14v2H5V6zm0 4h9v2H5v-2z"/></svg> Tradutor de Texto</h3>
                    <p><strong>O que faz:</strong> Converte videos e/ou imagens de sinais ou movimentos em libras para textos em português interpretados</p>
                    <p><strong>Como usar:</strong></p>
                    <ol>
                        <li>selecione sua foto ou video ou utilize de sua própria camera. Estando em um lugar onde a camera possa te ver o programa faz o resto. .</li>
                    
                    </ol>
                    <p><em>Dica:</em> Faça movimentos abertos e sempre em um ambiente bem iluminado</p>

                   

                    <h3><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2v2zm1.07-7.75l-.9.92A2.99 2.99 0 0011 12v1h-2v-1c0-1.1.45-2.1 1.17-2.83l1.24-1.26A2 2 0 1010 5.5V6h2v-.5A4 4 0 1114.07 9.25z"/></svg> Perguntas Frequentes (FAQ)</h3>
                    <div class="faq">
                        <ol>
                            <li><strong>O programa funciona offline?</strong> Não, por ser um site o programa precisa de internet para ser utilizado.</li>
                            <li><strong>O programa reconhece sinais pela câmera?</strong> Sim. Essa é a nossa maior virtude, facilitar a vida de quem interpreta ou quem está aprendendo a linguagem.</li>
                            <li><strong>Há suporte técnico?</strong> Sim. Envie um e-mail para machinelibras@gmail.com.br; Contato.</li>
                        </ol>
                    </div>

                    <h3><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px"><path d="M21 16.5a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 16.5v-9A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9zM5.5 7.5l6 4 6-4"/></svg> Contato e Suporte</h3>
                    <p class="contact">E-mail: <a href="mailto:suporte@interpretaLibras.com.br"> machinelibras@gmail.com.br</a><br>
                    Site: <a href="https://www.interpretalibras.com.br" target="_blank" rel="noopener">www.interpretalibras.com.br</a><br>
                    WhatsApp: (11) 99999-9999</p>

                    <div style="margin-top:24px;">
                        <h3>Enviar mensagem ao suporte</h3>
                        <form id="contactForm">
                            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                                <input type="text" id="cfName" name="name" placeholder="Seu nome" style="flex:1;min-width:220px;padding:10px;border-radius:6px;border:1px solid #ddd;" required>
                                <input type="email" id="cfEmail" name="email" placeholder="Seu e-mail" style="flex:1;min-width:220px;padding:10px;border-radius:6px;border:1px solid #ddd;" required>
                            </div>
                            <div style="margin-top:12px;">
                                <textarea id="cfMessage" name="message" rows="5" placeholder="Sua mensagem" style="width:100%;padding:12px;border-radius:6px;border:1px solid #ddd;" required></textarea>
                            </div>
                            <div style="margin-top:12px;display:flex;gap:12px;align-items:center;">
                                <button type="submit" class="submit-button" style="width:auto;padding:10px 18px;">Enviar</button>
                                <div id="contactStatus" style="color:#333;font-size:0.95rem;"></div>
                            </div>
                        </form>
                        <p style="margin-top:8px;font-size:0.9rem;color:#555;">Observação: este formulário usa um endpoint de exemplo (<code>/contact</code>). Sem backend configurado, será usada a alternativa por e-mail (mailto).</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Reusar lógica do menu hambúrguer já presente em outras páginas
        document.addEventListener('DOMContentLoaded', function() {
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

        // Contact form handling: try POST to /contact, fallback to mailto
        document.addEventListener('DOMContentLoaded', function(){
            var form = document.getElementById('contactForm');
            var status = document.getElementById('contactStatus');
            if(!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                status.textContent = 'Enviando...';
                var data = {
                    name: document.getElementById('cfName').value,
                    email: document.getElementById('cfEmail').value,
                    message: document.getElementById('cfMessage').value
                };

                // Tenta enviar para /contact (placeholder). Se falhar, abre mailto como fallback.
                fetch('/contact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).then(function(res){
                    if(res.ok){
                        status.textContent = 'Mensagem enviada com sucesso.';
                        form.reset();
                    } else {
                        throw new Error('Erro no envio');
                    }
                }).catch(function(){
                    // fallback mailto
                    var mailto = 'mailto:suporte@interpretaLibras.com.br'
                        + '?subject=' + encodeURIComponent('Contato: ' + data.name)
                        + '&body=' + encodeURIComponent('Email: ' + data.email + '\n\n' + data.message);
                    window.location.href = mailto;
                    status.textContent = 'Se o envio automático falhar, sua aplicação de e-mail abriu para envio manual.';
                });
            });
        });

        // Global F1 handler: direciona para help.html e previne a ajuda do navegador
        window.addEventListener('keydown', function(e){
            if(e.key === 'F1'){
                e.preventDefault();
                window.location.href = "{{ url_for('ajuda') }}";
            }
        });
    </script>
</body>
</html>