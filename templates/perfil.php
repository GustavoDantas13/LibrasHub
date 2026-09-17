<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/social_schema.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.php?redirect=perfil.php');
    exit;
}

ensureSocialSchema($pdo);

$viewerId = (int)$_SESSION['usuario_id'];
$profileId = max(1, (int)($_GET['id'] ?? $viewerId));
$stmt = $pdo->prepare('SELECT id_usuario,nm_usuario,social_handle,foto_perfil,banner_perfil,status_visibilidade,ultimo_acesso_em,tp_usuario,is_community_user,dt_usuario FROM usuario WHERE id_usuario=? LIMIT 1');
$stmt->execute([$profileId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    $profileId = $viewerId;
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    $profileMissing = true;
}

if (!$profile) {
    session_destroy();
    header('Location: Login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.id_post,p.id_comunidade,p.conteudo,p.midia_url,p.midia_tipo,p.criado_em,p.editado_em,
            c.nome AS comunidade_nome,c.privada,pm.papel AS papel_comunidade,
            (SELECT COUNT(*) FROM social_post_curtidas l WHERE l.id_post=p.id_post) AS total_curtidas,
            (SELECT COUNT(*) FROM social_post_comentarios pc WHERE pc.id_post=p.id_post) AS total_comentarios
       FROM social_posts p
       LEFT JOIN social_comunidades c ON c.id_comunidade=p.id_comunidade
       LEFT JOIN social_comunidade_membros vm ON vm.id_comunidade=c.id_comunidade AND vm.id_usuario=?
       LEFT JOIN social_comunidade_membros pm ON pm.id_comunidade=c.id_comunidade AND pm.id_usuario=p.id_usuario AND pm.status='ativo'
      WHERE p.id_usuario=?
        AND (p.id_comunidade IS NULL OR c.privada=0 OR vm.status='ativo')
      ORDER BY p.criado_em DESC,p.id_post DESC"
);
$stmt->execute([$viewerId, $profileId]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$_SESSION['usuario_nome'] = $_SESSION['usuario_nome'] ?? (string)$profile['nm_usuario'];
if (empty($_SESSION['social_csrf'])) $_SESSION['social_csrf'] = bin2hex(random_bytes(24));
$isOwnProfile = $profileId === $viewerId;
$isAdmin = strcasecmp((string)$profile['tp_usuario'], 'Administrador') === 0;
$isCommunity = !empty($profile['is_community_user']) || $isAdmin;
$isOnline = ($profile['status_visibilidade'] ?? 'visivel') === 'visivel'
    && !empty($profile['ultimo_acesso_em'])
    && strtotime((string)$profile['ultimo_acesso_em']) >= time() - 300;
$initial = mb_strtoupper(mb_substr((string)$profile['nm_usuario'], 0, 1));
$joined = date('d/m/Y', strtotime((string)$profile['dt_usuario']));

function profileDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $date;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= htmlspecialchars((string)$profile['nm_usuario']) ?> | LibrasHub</title>
  <link rel="icon" href="../static/images/librashub-logo.png">
  <link rel="stylesheet" href="../static/css/style.css">
  <link rel="stylesheet" href="../static/css/app-shell.css">
  <link rel="stylesheet" href="../static/css/sidebar.css">
  <link rel="stylesheet" href="../static/css/profile-social.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="app-shell app-dashboard profile-page">
<?php $sidebarId = 'sidebarMenu'; include __DIR__ . '/partials/sidebar.php'; ?>
<main class="content" id="conteudo-principal">
  <div class="profile-shell">
    <header class="profile-topbar">
      <a class="profile-back" href="social.php" aria-label="Voltar ao Social"><i class="fa-solid fa-arrow-left"></i></a>
      <div><strong><?= htmlspecialchars((string)$profile['nm_usuario']) ?></strong><small><?= count($posts) ?> publicaç<?= count($posts) === 1 ? 'ão' : 'ões' ?></small></div>
    </header>

    <?php if (!empty($profileMissing)): ?>
      <div class="ui-alert ui-alert--error" role="alert">O perfil solicitado não foi encontrado. Exibindo seu perfil.</div>
    <?php endif; ?>

    <section class="profile-hero ui-card" aria-labelledby="profileName">
      <div class="profile-cover" aria-hidden="true"><?php if (!empty($profile['banner_perfil'])): ?><img src="<?= htmlspecialchars((string)$profile['banner_perfil']) ?>" alt=""><?php endif; ?></div>
      <div class="profile-summary">
        <div class="profile-avatar"><?php if (!empty($profile['foto_perfil'])): ?><img src="<?= htmlspecialchars((string)$profile['foto_perfil']) ?>" alt="Foto de perfil de <?= htmlspecialchars((string)$profile['nm_usuario']) ?>"><?php else: ?><span aria-hidden="true"><?= htmlspecialchars($initial) ?></span><?php endif; ?></div>
        <div class="profile-actions">
          <?php if ($isOwnProfile): ?>
            <a class="ui-button ui-button--secondary" href="usuario.php"><i class="fa-solid fa-pen"></i>Editar perfil</a>
          <?php else: ?>
            <button class="ui-button" id="startChat" type="button"><i class="fa-solid fa-message"></i>Enviar mensagem</button>
          <?php endif; ?>
        </div>
        <div class="profile-identity">
          <div class="profile-name-row"><h1 id="profileName"><?= htmlspecialchars((string)$profile['nm_usuario']) ?></h1><?php if ($isAdmin): ?><span class="community-badge"><i class="fa-solid fa-shield-halved"></i>Administrador</span><?php elseif ($isCommunity): ?><span class="community-badge"><i class="fa-solid fa-certificate"></i>Comunitário</span><?php endif; ?></div>
          <p class="profile-role"><?= htmlspecialchars((string)$profile['tp_usuario']) ?></p>
          <p class="profile-presence <?= $isOnline ? 'is-online' : 'is-offline' ?>"><i class="fa-solid fa-circle"></i><?= $isOnline ? 'Online' : 'Offline' ?></p>
          <p class="profile-joined"><i class="fa-regular fa-calendar"></i>Membro desde <?= htmlspecialchars($joined) ?></p>
        </div>
      </div>
    </section>

    <section class="profile-posts" aria-labelledby="postsTitle">
      <header class="profile-section-title"><h2 id="postsTitle">Publicações</h2><span>Últimos posts primeiro</span></header>
      <?php if (!$posts): ?>
        <div class="profile-empty ui-card"><i class="fa-regular fa-message"></i><h3>Nenhuma publicação visível</h3><p>Quando este usuário publicar, os posts aparecerão aqui.</p></div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <article class="profile-post ui-card" id="post-<?= (int)$post['id_post'] ?>" data-profile-post="<?= (int)$post['id_post'] ?>">
            <header class="profile-post__header">
              <div class="profile-post__avatar"><?php if (!empty($profile['foto_perfil'])): ?><img src="<?= htmlspecialchars((string)$profile['foto_perfil']) ?>" alt=""><?php else: ?><span aria-hidden="true"><?= htmlspecialchars($initial) ?></span><?php endif; ?></div>
              <div><strong><?= htmlspecialchars((string)$profile['nm_usuario']) ?></strong><?php if (($post['papel_comunidade'] ?? '') === 'dono'): ?><span class="profile-comment__role is-owner"><i class="fa-solid fa-crown"></i>Dono</span><?php elseif (($post['papel_comunidade'] ?? '') === 'moderador'): ?><span class="profile-comment__role is-moderator"><i class="fa-solid fa-shield-halved"></i>Moderador</span><?php elseif ($isCommunity): ?><span class="community-badge"><i class="fa-solid fa-certificate"></i>Comunitário</span><?php endif; ?><time datetime="<?= htmlspecialchars((string)$post['criado_em']) ?>"><?= htmlspecialchars(profileDate((string)$post['criado_em'])) ?><?= $post['editado_em'] ? ' · editado' : '' ?></time></div>
            </header>
            <?php if (!empty($post['comunidade_nome'])): ?><span class="profile-post__context"><i class="fa-solid fa-users"></i><?= htmlspecialchars((string)$post['comunidade_nome']) ?></span><?php endif; ?>
            <?php if (trim((string)$post['conteudo']) !== ''): ?><p class="profile-post__text"><?= nl2br(htmlspecialchars((string)$post['conteudo'])) ?></p><?php endif; ?>
            <?php if (!empty($post['midia_url'])): ?>
              <?php if ($post['midia_tipo'] === 'video'): ?><video class="profile-post__media" controls preload="metadata"><source src="<?= htmlspecialchars((string)$post['midia_url']) ?>"></video><?php else: ?><img class="profile-post__media" src="<?= htmlspecialchars((string)$post['midia_url']) ?>" alt="Mídia publicada por <?= htmlspecialchars((string)$profile['nm_usuario']) ?>" loading="lazy"><?php endif; ?>
            <?php endif; ?>
            <footer class="profile-post__stats"><span><i class="fa-regular fa-heart"></i><?= (int)$post['total_curtidas'] ?> curtida<?= (int)$post['total_curtidas'] === 1 ? '' : 's' ?></span><button type="button" data-profile-comments aria-expanded="false"><i class="fa-regular fa-comment"></i><span data-profile-comment-count><?= (int)$post['total_comentarios'] ?></span><span data-profile-comment-label>comentário<?= (int)$post['total_comentarios'] === 1 ? '' : 's' ?></span></button></footer>
            <section class="profile-post__comments" hidden aria-label="Respostas da publicação"></section>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<script>
const PROFILE_API='ajax/social_api.php',PROFILE_CSRF=<?= json_encode($_SESSION['social_csrf']) ?>;
const profileEscape=value=>String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
async function profileApi(action,options={}){const response=await fetch(PROFILE_API+'?'+new URLSearchParams({action,...(options.query||{})}),options.fetch),result=await response.json().catch(()=>({ok:false,message:'Resposta inválida.'}));if(!response.ok||!result.ok)throw new Error(result.message||'Falha na operação.');return result}
function profileRoleBadge(role){if(role==='dono')return '<span class="profile-comment__role is-owner"><i class="fa-solid fa-crown"></i>Dono</span>';if(role==='moderador')return '<span class="profile-comment__role is-moderator"><i class="fa-solid fa-shield-halved"></i>Moderador</span>';return ''}
function profileCommentTree(comments){const grouped=new Map();comments.forEach(comment=>{const parent=Number(comment.id_comentario_pai)||0;if(!grouped.has(parent))grouped.set(parent,[]);grouped.get(parent).push(comment)});const render=comment=>`<article class="profile-comment" data-profile-comment="${Number(comment.id_comentario)}"><header><a href="perfil.php?id=${Number(comment.id_usuario)}">${profileEscape(comment.nm_usuario)}</a>${profileRoleBadge(comment.papel_comunidade)}<time>${profileEscape(comment.criado_em)}</time></header><p>${profileEscape(comment.conteudo)}</p><div class="profile-comment__actions"><button type="button" data-profile-comment-like class="${Number(comment.curtiu)?'is-active':''}"><i class="fa-${Number(comment.curtiu)?'solid':'regular'} fa-heart"></i><span>${Number(comment.total_curtidas)}</span></button><button type="button" data-profile-reply><i class="fa-solid fa-reply"></i>Responder</button></div><form data-profile-reply-form hidden><input name="text" maxlength="1200" placeholder="Responder a ${profileEscape(comment.nm_usuario)}" required><button class="ui-button" aria-label="Enviar resposta"><i class="fa-solid fa-paper-plane"></i></button></form><div class="profile-comment__children">${(grouped.get(Number(comment.id_comentario))||[]).map(render).join('')}</div></article>`;return (grouped.get(0)||[]).map(render).join('')}
async function loadProfileComments(post){const postId=Number(post.dataset.profilePost),box=post.querySelector('.profile-post__comments'),result=await profileApi('comments',{query:{post:postId}}),count=result.comments.length;box.innerHTML=`<div class="profile-comment-list">${profileCommentTree(result.comments)||'<p class="profile-comments-empty">Nenhuma resposta ainda.</p>'}</div><form data-profile-comment-form><input name="text" maxlength="1200" placeholder="Escreva um comentário" required><button class="ui-button" aria-label="Enviar comentário"><i class="fa-solid fa-paper-plane"></i></button></form>`;box.hidden=false;post.querySelector('[data-profile-comment-count]').textContent=count;post.querySelector('[data-profile-comment-label]').textContent=count===1?'comentário':'comentários';return count}
document.querySelector('.profile-posts')?.addEventListener('click',async event=>{const post=event.target.closest('[data-profile-post]');if(!post)return;const toggle=event.target.closest('[data-profile-comments]'),reply=event.target.closest('[data-profile-reply]'),like=event.target.closest('[data-profile-comment-like]'),postId=Number(post.dataset.profilePost);try{if(toggle){const box=post.querySelector('.profile-post__comments');if(!box.hidden){box.hidden=true;toggle.setAttribute('aria-expanded','false')}else{await loadProfileComments(post);toggle.setAttribute('aria-expanded','true')}return}if(reply){const form=reply.closest('[data-profile-comment]').querySelector(':scope > [data-profile-reply-form]');form.hidden=!form.hidden;if(!form.hidden)form.elements.text.focus();return}if(like){const comment=like.closest('[data-profile-comment]'),result=await profileApi('toggle_comment_like',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,post:postId,comment:comment.dataset.profileComment})}});like.classList.toggle('is-active',result.liked);like.querySelector('i').className=`fa-${result.liked?'solid':'regular'} fa-heart`;like.querySelector('span').textContent=result.count}}catch(error){const box=post.querySelector('.profile-post__comments');box.hidden=false;box.innerHTML=`<div class="ui-alert ui-alert--error">${profileEscape(error.message)}</div>`}});
document.querySelector('.profile-posts')?.addEventListener('submit',async event=>{const form=event.target.closest('[data-profile-comment-form],[data-profile-reply-form]');if(!form)return;event.preventDefault();const post=form.closest('[data-profile-post]'),postId=Number(post.dataset.profilePost),parent=form.matches('[data-profile-reply-form]')?Number(form.closest('[data-profile-comment]').dataset.profileComment):0,button=form.querySelector('button');button.disabled=true;try{await profileApi('add_comment',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,post:postId,parent,text:form.elements.text.value})}});await loadProfileComments(post)}catch(error){form.insertAdjacentHTML('beforebegin',`<div class="ui-alert ui-alert--error">${profileEscape(error.message)}</div>`)}finally{button.disabled=false}});
</script>
<?php if (!$isOwnProfile): ?>
<script>
const startChat=document.getElementById('startChat');
startChat.addEventListener('click',async()=>{startChat.disabled=true;const original=startChat.innerHTML;startChat.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Abrindo conversa...';try{const response=await fetch('ajax/social_api.php?action=start_private',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:<?= json_encode($_SESSION['social_csrf']) ?>,user:<?= $profileId ?>})});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Não foi possível iniciar o chat.');location.href='chats.php?tab=private&chat='+encodeURIComponent(result.id)}catch(error){alert(error.message);startChat.disabled=false;startChat.innerHTML=original}});
</script>
<?php endif; ?>
<script src="../static/js/acessibility.js" defer></script>
</body>
</html>
