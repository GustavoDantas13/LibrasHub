<?php
session_start();

// Só acessa quem estiver logado
if (empty($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LibrasHub - Comunidade</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<link rel="stylesheet" href="style.css">
<script>
(function(){
  try{
    var KEYS={theme:"libras_theme",fontSize:"libras_fontsize",contrast:"libras_contrast"};
    var FONT_SCALES={pequena:0.9,media:1,grande:1.15};
    function safeGet(k,f){try{var v=localStorage.getItem(k);return v!==null?v:f;}catch(e){return f;}}
    var tema=safeGet(KEYS.theme,"claro");
    var efetivo=tema;
    if(tema==="automatico"){efetivo=(window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches)?"escuro":"claro";}
    if(efetivo==="escuro") document.documentElement.setAttribute("data-theme","dark");
    document.documentElement.style.setProperty("--font-scale", FONT_SCALES[safeGet(KEYS.fontSize,"media")]||1);
    if(safeGet(KEYS.contrast,"off")==="on") document.documentElement.classList.add("high-contrast");
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
  .search-box{
    display:flex;align-items:center;gap:8px;background:var(--surface);
    border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:16px;max-width:400px;
  }
  .search-box input{border:none;outline:none;font-size:0.8125rem;flex:1;background:transparent;color:var(--text);}
  .tabs{display:flex;gap:8px;margin-bottom:16px;}
  .tab{padding:8px 16px;border:1px solid var(--border);border-radius:8px;font-size:0.8125rem;cursor:pointer;background:var(--surface);}
  .tab.active{background:var(--primary);color:var(--primary-text);border-color:var(--primary);}
  .comm-layout{display:grid;grid-template-columns:1fr 380px;gap:20px;}
  .thread-item{
    display:flex;gap:12px;padding:14px 0;border-bottom:1px solid var(--bg);cursor:pointer;
  }
  .thread-item:hover{background:var(--bg);}
  .avatar{width:34px;height:34px;border-radius:50%;background:var(--border);flex-shrink:0;}
  .thread-line{height:8px;background:var(--border);border-radius:4px;width:80%;}
  .thread-meta{font-size:0.6875rem;color:var(--text-muted);margin-top:6px;}

  .comment{display:flex;gap:10px;padding:12px 0;border-bottom:1px solid var(--bg);}
  .comment-body{flex:1;}
  .comment-line{height:7px;background:var(--border);border-radius:4px;width:90%;margin-bottom:6px;}
  .comment-actions{display:flex;justify-content:space-between;align-items:center;font-size:0.6875rem;color:var(--text-muted);}
  .comment-actions span{cursor:pointer;}
  .comment-input{
    display:flex;gap:8px;align-items:center;margin-top:12px;
    border:1px solid var(--border);border-radius:24px;padding:8px 8px 8px 16px;
  }
  .comment-input input{flex:1;border:none;outline:none;font-size:0.8125rem;background:transparent;color:var(--text);}
  .send-btn{
    width:32px;height:32px;border-radius:50%;background:var(--primary);color:var(--primary-text);border:none;
    cursor:pointer;flex-shrink:0;
  }
</style>
</head>
<body>
    <aside class="sidebar">
    <div class="sidebar-top">
      <div class="logo">
        <img src="librashub-logo.png" alt="LibrasHub" class="logo-img" style="width:32px;height:32px;object-fit:contain;border-radius:6px;">
        LibrasHub
      </div>
      <a class="nav-item" href="home.html"          data-page="home"><span class="nav-icon"><i class="fa-regular fa-house"            style="color:#fdbe00;"></i></span>Início</a>
      <a class="nav-item" href="leitor.html"        data-page="leitor"><span class="nav-icon"><i class="fa-solid fa-video"             style="color:#fdbe00;"></i></span>Leitor</a>
      <a class="nav-item" href="upload.html"        data-page="upload"><span class="nav-icon"><i class="fa-solid fa-upload"            style="color:#fdbe00;"></i></span>Upload</a>
      <a class="nav-item" href="historico.html"     data-page="historico"><span class="nav-icon"><i class="fa-solid fa-arrow-rotate-left" style="color:#fdbe00;"></i></span>Histórico</a>
      <a class="nav-item" href="ajuda.html"         data-page="ajuda"><span class="nav-icon"><i class="fa-solid fa-question"           style="color:#fdbe00;"></i></span>Ajuda</a>
      <a class="nav-item" href="comunidade.php"     data-page="comunidade"><span class="nav-icon"><i class="fa-solid fa-users"         style="color:#fdbe00;"></i></span>Comunidade</a>
    </div>
    <div class="sidebar-bottom">
      <a class="nav-item" href="configuracoes.html" data-page="configuracoes"><span class="nav-icon"><i class="fa-solid fa-gear"       style="color:#fdbe00;"></i></span>Configurações</a>
      <a class="nav-item" href="usuario.php"        data-page="usuario"><span class="nav-icon"><i class="fa-solid fa-user"             style="color:#fdbe00;"></i></span>Usuário</a>
    </div>
  </aside>

  <main class="content">
    <div class="page-title">Comunidade</div>
    <div class="page-subtitle">Tire dúvidas, compartilhe e aprenda com outros.</div>

    <div class="search-box">🔍 <input placeholder="Buscar na comunidade"></div>

    <div class="tabs">
      <div class="tab active" onclick="setTab(this)">Todos</div>
      <div class="tab" onclick="setTab(this)">Discussões</div>
      <div class="tab" onclick="setTab(this)">Eventos</div>
      <div class="tab" onclick="setTab(this)">Grupos</div>
    </div>

    <div class="comm-layout">
      <div class="panel">
        <div id="threadList"></div>
      </div>

      <div class="panel">
        <div style="font-weight:700;font-size:0.875rem;margin-bottom:10px;">Comentários</div>
        <div id="commentList"></div>
        <div class="comment-input">
          <input id="commentInput" placeholder="Escreva um comentário...">
          <button class="send-btn" onclick="addComment()">➤</button>
        </div>
      </div>
    </div>
  </main>

<script>
function setTab(el){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
}

// gera lista fake de tópicos
const threadList = document.getElementById('threadList');
for(let i=0;i<8;i++){
  const div = document.createElement('div');
  div.className = 'thread-item';
  div.innerHTML = `<div class="avatar"></div><div><div class="thread-line"></div><div class="thread-meta">há ${i+1}h</div></div>`;
  threadList.appendChild(div);
}

// gera comentários fake
function renderComments(){
  const list = document.getElementById('commentList');
  list.innerHTML = '';
  const comentarios = [
    {resp:false},{resp:true},{resp:false},{resp:false},{resp:true}
  ];
  comentarios.forEach(c=>{
    const div = document.createElement('div');
    div.className = 'comment';
    div.innerHTML = `<div class="avatar" style="width:26px;height:26px;"></div>
      <div class="comment-body">
        <div class="comment-line"></div>
        <div class="comment-actions"><span>${c.resp?'Responder':'Responder'}</span><span>♡ 2</span></div>
      </div>`;
    list.appendChild(div);
  });
}
renderComments();

function addComment(){
  const input = document.getElementById('commentInput');
  if(!input.value.trim()) return;
  const list = document.getElementById('commentList');
  const div = document.createElement('div');
  div.className = 'comment';
  div.innerHTML = `<div class="avatar" style="width:26px;height:26px;"></div>
    <div class="comment-body">
      <div style="font-size:0.8125rem;margin-bottom:4px;">${input.value}</div>
      <div class="comment-actions"><span>Responder</span><span>♡ 0</span></div>
    </div>`;
  list.appendChild(div);
  input.value = '';
}
</script>

  <!-- MENU MOBILE -->
  <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">&#9776;</button>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script>
  (function(){
    var btn = document.getElementById('menuToggle');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if(!btn || !sidebar) return;
    function open(){ sidebar.classList.add('open'); overlay.classList.add('open'); btn.innerHTML='&#10005;'; }
    function close(){ sidebar.classList.remove('open'); overlay.classList.remove('open'); btn.innerHTML='&#9776;'; }
    btn.addEventListener('click', function(){ sidebar.classList.contains('open') ? close() : open(); });
    overlay.addEventListener('click', close);
    // fecha ao clicar em um link da sidebar
    sidebar.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', close); });
  })();
  </script>
  <script>
  (function(){
    var path = window.location.pathname.split('/').pop().replace(/\.php$/, '').replace(/\.html$/, '');
    document.querySelectorAll('.sidebar .nav-item[data-page]').forEach(function(a){
      if(a.dataset.page === path){ a.classList.add('active'); }
    });
  })();
  </script>
</body>
</html>