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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>LibrasHub - Comunidade</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
      integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer">

<link rel="stylesheet" href="style.css">

<script>
(function(){
  try{
    var KEYS={theme:"libras_theme",fontSize:"libras_fontsize",contrast:"libras_contrast"};
    var FONT_SCALES={pequena:0.9,media:1,grande:1.15};
    function safeGet(k,f){
      try{
        var v=localStorage.getItem(k);
        return v!==null?v:f;
      }catch(e){
        return f;
      }
    }

    var tema=safeGet(KEYS.theme,"claro");
    var efetivo=tema;

    if(tema==="automatico"){
      efetivo=(window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches)
        ?"escuro"
        :"claro";
    }

    if(efetivo==="escuro"){
      document.documentElement.setAttribute("data-theme","dark");
    }

    document.documentElement.style.setProperty(
      "--font-scale",
      FONT_SCALES[safeGet(KEYS.fontSize,"media")]||1
    );

    if(safeGet(KEYS.contrast,"off")==="on"){
      document.documentElement.classList.add("high-contrast");
    }
  }catch(e){}
})();
</script>

<style>
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

/* ===== SIDEBAR DESKTOP ===== */
.sidebar{
  position:fixed;
  top:0;
  left:0;
  width:260px;
  height:100vh;
  height:100dvh;
  overflow-y:auto;
  z-index:1200;
  transition:transform .28s ease;
}

.content{
  margin-left:260px;
  min-width:0;
  padding:40px;
}

/* ===== CONTEÚDO DA COMUNIDADE ===== */
.search-box{
  display:flex;
  align-items:center;
  gap:8px;
  width:100%;
  max-width:400px;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:8px;
  padding:10px 14px;
  margin-bottom:16px;
}

.search-box input{
  width:100%;
  min-width:0;
  border:none;
  outline:none;
  font-size:0.8125rem;
  flex:1;
  background:transparent;
  color:var(--text);
}

.tabs{
  display:flex;
  gap:8px;
  margin-bottom:16px;
  max-width:100%;
}

.tab{
  padding:8px 16px;
  border:1px solid var(--border);
  border-radius:8px;
  font-size:0.8125rem;
  cursor:pointer;
  background:var(--surface);
  white-space:nowrap;
  flex-shrink:0;
}

.tab.active{
  background:var(--primary);
  color:var(--primary-text);
  border-color:var(--primary);
}

.comm-layout{
  display:grid;
  grid-template-columns:minmax(0,1fr) minmax(300px,380px);
  gap:20px;
  align-items:start;
}

.panel{
  min-width:0;
}

.thread-item{
  display:flex;
  gap:12px;
  padding:14px 0;
  border-bottom:1px solid var(--bg);
  cursor:pointer;
  min-width:0;
}

.thread-item:hover{
  background:var(--bg);
}

.thread-item > div:last-child{
  flex:1;
  min-width:0;
}

.avatar{
  width:34px;
  height:34px;
  border-radius:50%;
  background:var(--border);
  flex-shrink:0;
}

.thread-line{
  height:8px;
  background:var(--border);
  border-radius:4px;
  width:80%;
  max-width:100%;
}

.thread-meta{
  font-size:0.6875rem;
  color:var(--text-muted);
  margin-top:6px;
}

.comment{
  display:flex;
  gap:10px;
  padding:12px 0;
  border-bottom:1px solid var(--bg);
  min-width:0;
}

.comment-body{
  flex:1;
  min-width:0;
}

.comment-line{
  height:7px;
  background:var(--border);
  border-radius:4px;
  width:90%;
  max-width:100%;
  margin-bottom:6px;
}

.comment-actions{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  font-size:0.6875rem;
  color:var(--text-muted);
}

.comment-actions span{
  cursor:pointer;
}

.comment-input{
  display:flex;
  gap:8px;
  align-items:center;
  width:100%;
  margin-top:12px;
  border:1px solid var(--border);
  border-radius:24px;
  padding:8px 8px 8px 16px;
}

.comment-input input{
  flex:1;
  min-width:0;
  width:100%;
  border:none;
  outline:none;
  font-size:0.8125rem;
  background:transparent;
  color:var(--text);
}

.send-btn{
  width:32px;
  height:32px;
  border-radius:50%;
  background:var(--primary);
  color:var(--primary-text);
  border:none;
  cursor:pointer;
  flex-shrink:0;
}

/* ===== MENU HAMBÚRGUER ===== */
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

/* Notebook/tablet */
@media(max-width:1100px){
  .content{
    padding:30px;
  }

  .comm-layout{
    grid-template-columns:minmax(0,1fr) minmax(280px,340px);
    gap:16px;
  }
}

/* Mobile e tablet */
@media(max-width:900px){
  .sidebar{
    width:min(82vw,300px);
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

  .comm-layout{
    grid-template-columns:1fr;
  }

  .search-box{
    max-width:none;
  }

  .tabs{
    overflow-x:auto;
    padding-bottom:4px;
    scrollbar-width:thin;
    -webkit-overflow-scrolling:touch;
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
  }

  .page-subtitle{
    line-height:1.5;
  }

  .search-box{
    padding:11px 12px;
  }

  .search-box input,
  .comment-input input{
    font-size:16px;
  }

  .tab{
    padding:9px 14px;
  }

  .panel{
    width:100%;
  }

  .thread-item{
    padding:12px 0;
  }

  .comment-input{
    padding:7px 7px 7px 13px;
  }

  .send-btn{
    width:36px;
    height:36px;
  }
}

@media(max-width:380px){
  .content{
    padding-left:10px;
    padding-right:10px;
  }

  .tab{
    padding:8px 12px;
  }

  .comment-actions{
    gap:8px;
  }
}
</style>
</head>

<body>

<aside class="sidebar">
  <div class="sidebar-top">

    <div class="logo">
      <img src="librashub-logo.png"
           alt="LibrasHub"
           class="logo-img"
           style="width:32px;height:32px;object-fit:contain;border-radius:6px;">
      LibrasHub
    </div>

    <a class="nav-item" href="home.html" data-page="home">
      <span class="nav-icon">
        <i class="fa-regular fa-house" style="color:#fdbe00;"></i>
      </span>
      Início
    </a>

    <a class="nav-item" href="leitor.html" data-page="leitor">
      <span class="nav-icon">
        <i class="fa-solid fa-video" style="color:#fdbe00;"></i>
      </span>
      Leitor
    </a>

    <a class="nav-item" href="upload.html" data-page="upload">
      <span class="nav-icon">
        <i class="fa-solid fa-upload" style="color:#fdbe00;"></i>
      </span>
      Upload
    </a>

    <a class="nav-item" href="historico.html" data-page="historico">
      <span class="nav-icon">
        <i class="fa-solid fa-arrow-rotate-left" style="color:#fdbe00;"></i>
      </span>
      Histórico
    </a>

    <a class="nav-item" href="ajuda.html" data-page="ajuda">
      <span class="nav-icon">
        <i class="fa-solid fa-question" style="color:#fdbe00;"></i>
      </span>
      Ajuda
    </a>

    <a class="nav-item" href="comunidade.php" data-page="comunidade">
      <span class="nav-icon">
        <i class="fa-solid fa-users" style="color:#fdbe00;"></i>
      </span>
      Comunidade
    </a>

  </div>

  <div class="sidebar-bottom">

    <a class="nav-item" href="configuracoes.html" data-page="configuracoes">
      <span class="nav-icon">
        <i class="fa-solid fa-gear" style="color:#fdbe00;"></i>
      </span>
      Configurações
    </a>

    <a class="nav-item" href="usuario.php" data-page="usuario">
      <span class="nav-icon">
        <i class="fa-solid fa-user" style="color:#fdbe00;"></i>
      </span>
      Usuário
    </a>

  </div>
</aside>

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
  aria-hidden="true"
></div>

<main class="content">

  <div class="page-title">Comunidade</div>

  <div class="page-subtitle">
    Tire dúvidas, compartilhe e aprenda com outros.
  </div>

  <div class="search-box">
    🔍
    <input placeholder="Buscar na comunidade">
  </div>

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

      <div style="font-weight:700;font-size:0.875rem;margin-bottom:10px;">
        Comentários
      </div>

      <div id="commentList"></div>

      <div class="comment-input">
        <input
          id="commentInput"
          placeholder="Escreva um comentário..."
        >
        <button
          class="send-btn"
          type="button"
          onclick="addComment()"
          aria-label="Enviar comentário"
        >
          ➤
        </button>
      </div>

    </div>

  </div>

</main>

<script>
function setTab(el){
  document
    .querySelectorAll(".tab")
    .forEach(function(t){
      t.classList.remove("active");
    });

  el.classList.add("active");
}


// gera lista fake de tópicos
const threadList =
  document.getElementById("threadList");

for(let i=0;i<8;i++){

  const div =
    document.createElement("div");

  div.className =
    "thread-item";

  div.innerHTML =
    `<div class="avatar"></div>
     <div>
       <div class="thread-line"></div>
       <div class="thread-meta">há ${i+1}h</div>
     </div>`;

  threadList.appendChild(div);
}


// gera comentários fake
function renderComments(){

  const list =
    document.getElementById("commentList");

  list.innerHTML =
    "";

  const comentarios = [
    {resp:false},
    {resp:true},
    {resp:false},
    {resp:false},
    {resp:true}
  ];

  comentarios.forEach(function(c){

    const div =
      document.createElement("div");

    div.className =
      "comment";

    div.innerHTML =
      `<div class="avatar" style="width:26px;height:26px;"></div>
       <div class="comment-body">
         <div class="comment-line"></div>
         <div class="comment-actions">
           <span>Responder</span>
           <span>♡ 2</span>
         </div>
       </div>`;

    list.appendChild(div);
  });
}

renderComments();


function addComment(){

  const input =
    document.getElementById("commentInput");

  const texto =
    input.value.trim();

  if(!texto){
    return;
  }

  const list =
    document.getElementById("commentList");

  const div =
    document.createElement("div");

  div.className =
    "comment";

  const avatar =
    document.createElement("div");

  avatar.className =
    "avatar";

  avatar.style.width =
    "26px";

  avatar.style.height =
    "26px";


  const body =
    document.createElement("div");

  body.className =
    "comment-body";


  const textoDiv =
    document.createElement("div");

  textoDiv.style.fontSize =
    "0.8125rem";

  textoDiv.style.marginBottom =
    "4px";

  textoDiv.textContent =
    texto;


  const actions =
    document.createElement("div");

  actions.className =
    "comment-actions";

  actions.innerHTML =
    "<span>Responder</span><span>♡ 0</span>";


  body.appendChild(textoDiv);
  body.appendChild(actions);

  div.appendChild(avatar);
  div.appendChild(body);

  list.appendChild(div);

  input.value =
    "";
}


// envia comentário com Enter
document
  .getElementById("commentInput")
  ?.addEventListener(
    "keydown",
    function(event){

      if(
        event.key === "Enter"
        &&
        !event.shiftKey
      ){
        event.preventDefault();
        addComment();
      }
    }
  );
</script>

<script>
(function(){

  var btn =
    document.getElementById("menuToggle");

  var sidebar =
    document.querySelector(".sidebar");

  var overlay =
    document.getElementById("sidebarOverlay");

  if(
    !btn
    ||
    !sidebar
    ||
    !overlay
  ){
    return;
  }


  function openMenu(){

    sidebar.classList.add("open");
    overlay.classList.add("open");

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

    sidebar.classList.remove("open");
    overlay.classList.remove("open");

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

      sidebar.classList.contains("open")
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
        event.key === "Escape"
        &&
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
        window.innerWidth > 900
        &&
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
      .replace(/\.php$/, "")
      .replace(/\.html$/, "");

  document
    .querySelectorAll(
      ".sidebar .nav-item[data-page]"
    )
    .forEach(
      function(link){

        if(
          link.dataset.page === path
        ){
          link.classList.add("active");
        }
      }
    );

})();
</script>

</body>
</html>