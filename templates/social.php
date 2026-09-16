<?php
session_start();
require_once __DIR__.'/configs/config.php';
require_once __DIR__.'/configs/social_schema.php';
if (empty($_SESSION['usuario_id'])) { header('Location: Login.php?redirect=social.php'); exit; }
ensureSocialSchema($pdo);
$uid=(int)$_SESSION['usuario_id'];
$s=$pdo->prepare('SELECT nm_usuario,tp_usuario,is_community_user FROM usuario WHERE id_usuario=?');
$s->execute([$uid]); $me=$s->fetch();
if(!$me){session_destroy();header('Location: Login.php');exit;}
$_SESSION['usuario_nome']=$me['nm_usuario']; $_SESSION['usuario_tipo']=$me['tp_usuario'];
if(empty($_SESSION['social_csrf']))$_SESSION['social_csrf']=bin2hex(random_bytes(24));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Para você | LibrasHub</title><link rel="icon" href="../static/images/librashub-logo.png">
<link rel="stylesheet" href="../static/css/style.css"><link rel="stylesheet" href="../static/css/app-shell.css"><link rel="stylesheet" href="../static/css/sidebar.css"><link rel="stylesheet" href="../static/css/social-feed.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"></head>
<body class="app-shell app-dashboard social-page social-feed-page">
<?php $sidebarId='sidebarMenu';include __DIR__.'/partials/sidebar.php';?>
<main class="content" id="conteudo-principal"><div class="feed-shell">
  <section class="feed-column" aria-labelledby="feedTitle">
    <header class="feed-header"><div><span class="ui-eyebrow"><i class="fa-solid fa-earth-americas"></i> Social</span><h1 id="feedTitle">Para você</h1><p>Publicações de toda a comunidade LibrasHub.</p></div></header>
    <form class="feed-composer ui-card" id="postForm" enctype="multipart/form-data">
      <div class="feed-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($me['nm_usuario'],0,1))) ?></div>
      <div class="feed-composer__body"><label class="sr-only" for="postText">Criar publicação</label><textarea id="postText" name="text" maxlength="4000" placeholder="O que você quer compartilhar?"></textarea>
        <div id="mediaPreview" class="feed-media-preview"></div><div class="feed-composer__actions"><label class="feed-media-button" for="postMedia"><i class="fa-regular fa-image"></i><span>Foto ou vídeo</span></label><input id="postMedia" name="media" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" hidden><span id="postStatus" role="status"></span><button class="ui-button" type="submit">Publicar</button></div>
      </div>
    </form>
    <div id="feedList" class="feed-list" aria-live="polite"><div class="feed-loading">Carregando publicações...</div></div>
    <button class="ui-button ui-button--secondary feed-more" id="loadMore" type="button" hidden>Carregar mais</button>
  </section>
  <aside class="feed-shortcuts" aria-label="Atalhos do Social">
    <section class="ui-card shortcut-card"><h2>Conversas</h2><p>Acesse suas comunidades e mensagens privadas.</p><a href="chats.php?tab=private" class="shortcut-link"><i class="fa-solid fa-message"></i><span><strong>Chats privados</strong><small>Conversas individuais</small></span><i class="fa-solid fa-chevron-right"></i></a><a href="chats.php?tab=communities" class="shortcut-link"><i class="fa-solid fa-users"></i><span><strong>Comunidades</strong><small>Públicas e privadas</small></span><i class="fa-solid fa-chevron-right"></i></a></section>
    <section class="ui-card shortcut-card"><h2>Descobrir</h2><a href="locais.php" class="shortcut-link"><i class="fa-solid fa-map-location-dot"></i><span><strong>Locais acessíveis</strong><small>Mapa e avaliações</small></span><i class="fa-solid fa-chevron-right"></i></a></section>
  </aside>
</div></main>
<script>
const API='ajax/social_api.php',CSRF=<?= json_encode($_SESSION['social_csrf']) ?>,ME=<?= $uid ?>;let oldest=0,busy=false;
const esc=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
async function api(action,opt={}){const url=API+'?'+new URLSearchParams({action,...(opt.query||{})}),r=await fetch(url,opt.fetch),j=await r.json().catch(()=>({ok:false,message:'Resposta inválida.'}));if(!r.ok||!j.ok)throw new Error(j.message||'Falha na operação.');return j}
function mediaHtml(p){if(!p.midia_url)return '';return p.midia_tipo==='video'?`<video class="feed-media" controls preload="metadata"><source src="${esc(p.midia_url)}"></video>`:`<img class="feed-media" src="${esc(p.midia_url)}" alt="Mídia publicada por ${esc(p.nm_usuario)}" loading="lazy">`}
function postHtml(p){return `<article class="feed-post ui-card" id="post-${p.id_post}" data-post="${p.id_post}"><header><div class="feed-avatar">${esc(p.nm_usuario?.[0]||'U')}</div><div><a class="feed-user-link" href="perfil.php?id=${Number(p.id_usuario)}"><strong>${esc(p.nm_usuario)}</strong></a>${Number(p.is_community_user)?'<span class="community-badge"><i class="fa-solid fa-certificate"></i>Comunitário</span>':''}<time>${esc(p.criado_em)}${p.editado_em?' · editado':''}</time></div></header>${p.conteudo?`<p class="feed-post__text">${esc(p.conteudo)}</p>`:''}${mediaHtml(p)}<footer><button type="button" data-like class="feed-action ${Number(p.curtiu)?'is-active':''}" aria-label="Curtir"><i class="fa-${Number(p.curtiu)?'solid':'regular'} fa-heart"></i><span>${Number(p.total_curtidas)}</span></button><button type="button" data-comments class="feed-action"><i class="fa-regular fa-comment"></i><span>${Number(p.total_comentarios)}</span></button>${Number(p.id_usuario)===ME?'<button type="button" data-edit class="feed-action"><i class="fa-regular fa-pen-to-square"></i><span>Editar</span></button>':''}<button type="button" data-share class="feed-action"><i class="fa-solid fa-arrow-up-from-bracket"></i><span>Compartilhar</span></button></footer><section class="feed-comments" hidden></section></article>`}
async function load(reset=false){if(busy)return;busy=true;if(reset){oldest=0;feedList.innerHTML=''}try{const j=await api('feed_public',{query:oldest?{before:oldest}:{}});feedList.insertAdjacentHTML('beforeend',j.posts.map(postHtml).join(''));if(!feedList.children.length)feedList.innerHTML='<div class="feed-empty ui-card"><h2>A conversa começa aqui</h2><p>Seja a primeira pessoa a publicar no Social.</p></div>';if(j.posts.length){oldest=Number(j.posts.at(-1).id_post)}loadMore.hidden=j.posts.length<30}catch(e){feedList.innerHTML='<div class="ui-alert ui-alert--error">'+esc(e.message)+'</div>'}finally{busy=false}}
postMedia.onchange=()=>{mediaPreview.innerHTML='';const f=postMedia.files[0];if(!f)return;const url=URL.createObjectURL(f);mediaPreview.innerHTML=f.type.startsWith('video/')?`<video src="${url}" controls></video>`:`<img src="${url}" alt="Prévia da mídia">`};
postForm.onsubmit=async e=>{e.preventDefault();postStatus.textContent='Publicando...';const fd=new FormData(e.target);fd.append('csrf',CSRF);try{await api('create_public_post',{fetch:{method:'POST',body:fd}});e.target.reset();mediaPreview.innerHTML='';postStatus.textContent='Publicado.';await load(true)}catch(x){postStatus.textContent=x.message}};
feedList.onclick=async e=>{const post=e.target.closest('[data-post]');if(!post)return;const id=post.dataset.post,like=e.target.closest('[data-like]'),comments=e.target.closest('[data-comments]'),edit=e.target.closest('[data-edit]'),share=e.target.closest('[data-share]');try{if(like){const j=await api('toggle_like',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:CSRF,post:id})}});like.classList.toggle('is-active',j.liked);like.querySelector('i').className=`fa-${j.liked?'solid':'regular'} fa-heart`;like.querySelector('span').textContent=j.count}if(comments){await openComments(post,id)}if(edit){const current=post.querySelector('.feed-post__text')?.textContent||'',text=prompt('Edite sua publicação:',current);if(text!==null){await api('edit_post',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:CSRF,post:id,text})}});await load(true)}}if(share){const url=location.origin+location.pathname+'#post-'+id;navigator.share?await navigator.share({title:'LibrasHub Social',url}):await navigator.clipboard.writeText(url);share.querySelector('span').textContent='Link copiado' }}catch(x){alert(x.message)}};
async function openComments(post,id){const box=post.querySelector('.feed-comments');if(!box.hidden){box.hidden=true;return}const j=await api('comments',{query:{post:id}});box.innerHTML=`<div class="comment-list">${j.comments.map(c=>`<article><a class="feed-user-link" href="perfil.php?id=${Number(c.id_usuario)}"><strong>${esc(c.nm_usuario)}</strong></a><time>${esc(c.criado_em)}</time><p>${esc(c.conteudo)}</p></article>`).join('')||'<p>Nenhum comentário ainda.</p>'}</div><form data-comment-form><input name="text" maxlength="1200" placeholder="Escreva um comentário" required><button class="ui-button" aria-label="Enviar comentário"><i class="fa-solid fa-paper-plane"></i></button></form>`;box.hidden=false;box.querySelector('form').onsubmit=async e=>{e.preventDefault();await api('add_comment',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:CSRF,post:id,text:e.target.text.value})}});await openComments(post,id);await openComments(post,id);const n=post.querySelector('[data-comments] span');n.textContent=Number(n.textContent)+1}}
loadMore.onclick=()=>load();load();
</script><script src="../static/js/acessibility.js" defer></script></body></html>
