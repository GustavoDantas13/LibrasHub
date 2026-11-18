<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.php?redirect=perfil.php');
    exit;
}

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/social_schema.php';
ensureSocialSchema($pdo);

$viewerId = (int)$_SESSION['usuario_id'];
$profileId = max(1, (int)($_GET['id'] ?? $viewerId));
if($profileId!==$viewerId){
 $check=$pdo->prepare('SELECT 1 FROM social_relacoes_usuario WHERE st_bloqueado=1 AND ((id_usuario=? AND id_usuario_alvo=?) OR (id_usuario=? AND id_usuario_alvo=?)) LIMIT 1');
 $check->execute([$profileId,$viewerId,$viewerId,$profileId]);
 if($check->fetchColumn()){http_response_code(404);echo 'Perfil indisponível.';exit;}
}
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

function profileMediaUrl(?string $value): ?string
{
    $url = trim((string)$value);
    if ($url === '') return null;
    if (preg_match('#^https?://#i', $url)) return $url;
    $url = str_replace('\\', '/', $url);
    if (str_starts_with($url, '/static/')) $url = '..' . $url;
    elseif (str_starts_with($url, 'static/')) $url = '../' . $url;
    elseif (str_starts_with($url, '/uploads/')) $url = '../static' . $url;
    elseif (str_starts_with($url, 'uploads/')) $url = '../static/' . $url;
    if (!str_starts_with($url, '../static/')) return null;
    $file = realpath(__DIR__ . '/' . strtok($url, '?'));
    return $file && is_file($file) ? $url . '?v=' . filemtime($file) : $url;
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
// Compatibilidade com as duas versões do esquema de anexos (campos antigos e mnemônicos).
// SHOW COLUMNS é usado em vez de information_schema, que pode estar restrito no XAMPP.
$attachmentColumns = $pdo->query('SHOW COLUMNS FROM `social_post_anexos`')->fetchAll(PDO::FETCH_COLUMN);
$attachmentColumn = static function (array $options) use ($attachmentColumns): ?string {
    foreach ($options as $option) {
        if (in_array($option, $attachmentColumns, true)) return $option;
    }
    return null;
};
$pathColumn = $attachmentColumn(['ds_caminho', 'caminho']);
$nameColumn = $attachmentColumn(['nm_arquivo', 'nome_arquivo']);
$mimeColumn = $attachmentColumn(['cd_tipo_mime', 'mime_type', 'tipo_mime']);
$orderColumn = $attachmentColumn(['nr_ordem', 'ordem', 'id_anexo', 'id']);
if ($pathColumn === null) {
    error_log('[LibrasHub Perfil] social_post_anexos sem coluna de caminho reconhecida.');
    foreach ($posts as &$item) $item['anexos'] = [];
    unset($item);
} else {
    // Os identificadores vêm exclusivamente de uma lista fixa de nomes permitidos.
    $pathField = '`' . $pathColumn . '`';
    $nameField = $nameColumn ? '`' . $nameColumn . '`' : "''";
    $mimeField = $mimeColumn ? '`' . $mimeColumn . '`' : "''";
    $orderSql = $orderColumn ? ' ORDER BY `' . $orderColumn . '`' : '';
    $attachmentsStmt = $pdo->prepare(
        "SELECT {$pathField} AS caminho, {$nameField} AS nome_arquivo, {$mimeField} AS mime_type " .
        "FROM `social_post_anexos` WHERE `id_post` = ?{$orderSql}"
    );
    foreach ($posts as &$item) {
        $attachmentsStmt->execute([(int)$item['id_post']]);
        $item['anexos'] = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($item);
}
$followStatement = $pdo->prepare('SELECT (SELECT COUNT(*) FROM social_seguidores WHERE id_seguido=?) seguidores,(SELECT COUNT(*) FROM social_seguidores WHERE id_seguidor=?) seguindo,EXISTS(SELECT 1 FROM social_seguidores WHERE id_seguidor=? AND id_seguido=?) seguindo_usuario');
$followStatement->execute([$profileId, $profileId, $viewerId, $profileId]);
$followState = $followStatement->fetch(PDO::FETCH_ASSOC) ?: ['seguidores'=>0,'seguindo'=>0,'seguindo_usuario'=>0];
$punishmentStatement = $pdo->prepare("SELECT p.id_punicao,p.tipo,p.motivo,p.inicio_em,p.expira_em,a.nm_usuario administrador FROM usuario_punicoes p JOIN usuario a ON a.id_usuario=p.id_administrador WHERE p.id_usuario=? AND p.revogada_em IS NULL AND p.expira_em>NOW() ORDER BY p.expira_em DESC");
$punishmentStatement->execute([$profileId]);
$activePunishments = $punishmentStatement->fetchAll(PDO::FETCH_ASSOC);

$_SESSION['usuario_nome'] = $_SESSION['usuario_nome'] ?? (string)$profile['nm_usuario'];
if (empty($_SESSION['social_csrf'])) $_SESSION['social_csrf'] = bin2hex(random_bytes(24));
$isOwnProfile = $profileId === $viewerId;
$viewerIsAdmin = strcasecmp(trim((string)($_SESSION['usuario_tipo'] ?? '')), 'Administrador') === 0;
$isAdmin = strcasecmp((string)$profile['tp_usuario'], 'Administrador') === 0;
$isCommunity = !empty($profile['is_community_user']) || $isAdmin;
$isOnline = ($profile['status_visibilidade'] ?? 'visivel') === 'visivel'
    && !empty($profile['ultimo_acesso_em'])
    && strtotime((string)$profile['ultimo_acesso_em']) >= time() - 300;
$initial = mb_strtoupper(mb_substr((string)$profile['nm_usuario'], 0, 1));
$joined = date('d/m/Y', strtotime((string)$profile['dt_usuario']));
$profilePhoto = profileMediaUrl($profile['foto_perfil'] ?? null);
$profileBanner = profileMediaUrl($profile['banner_perfil'] ?? null);

function profilePostMediaUrl(?string $value): string
{
    $original = trim((string)$value);
    if ($original === '') return '';
    // Inclui URL completa, caminho relativo e links antigos de mobile/media.php.
    $decoded = rawurldecode(str_replace('\\', '/', $original));
    if (preg_match('~(?:^|/)static/uploads/(social/(?:feed|communities)/[a-f0-9]{36,40}\.(?:jpg|jpeg|png|webp|gif|mp4|webm))(?=$|[?#])~i', $decoded, $m)) {
        return '../static/uploads/' . $m[1];
    }
    if (preg_match('~[?&]file=(social/(?:feed|communities)/[a-f0-9]{36,40}\.(?:jpg|jpeg|png|webp|gif|mp4|webm))(?=$|[&#])~i', $decoded, $m)) {
        return '../static/uploads/' . $m[1];
    }
    return $original;
}

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
  <link rel="stylesheet" href="../static/css/report-modal.css?v=66">
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
      <div class="profile-cover" aria-hidden="true"><?php if ($profileBanner): ?><img src="<?= htmlspecialchars($profileBanner) ?>" alt="" decoding="async"><?php endif; ?></div>
      <div class="profile-summary">
        <div class="profile-avatar <?= $profilePhoto ? 'has-photo' : '' ?>"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="Foto de perfil de <?= htmlspecialchars((string)$profile['nm_usuario']) ?>" fetchpriority="high" decoding="async" data-profile-image><?php endif; ?><span class="profile-avatar__fallback" aria-hidden="true"><?= htmlspecialchars($initial) ?></span></div>
        <div class="profile-actions">
          <?php if ($isOwnProfile): ?>
            <a class="ui-button ui-button--secondary" href="usuario.php"><i class="fa-solid fa-pen"></i>Editar perfil</a>
          <?php else: ?>
            <button class="ui-button ui-button--secondary" id="followUser" type="button" aria-pressed="<?= !empty($followState['seguindo_usuario']) ? 'true' : 'false' ?>"><i class="fa-solid <?= !empty($followState['seguindo_usuario']) ? 'fa-user-check' : 'fa-user-plus' ?>"></i><span><?= !empty($followState['seguindo_usuario']) ? 'Deixar de seguir' : 'Seguir' ?></span></button>
            <button class="ui-button" id="startChat" type="button"><i class="fa-solid fa-message"></i>Enviar mensagem</button>
            <button class="ui-button ui-button--secondary" type="button" data-report-type="usuario" data-report-id="<?= $profileId ?>" data-report-label="Perfil de <?= htmlspecialchars((string)$profile['nm_usuario'], ENT_QUOTES) ?>"><i class="fa-regular fa-flag"></i>Denunciar</button>
            <?php if ($viewerIsAdmin): ?><button class="ui-button ui-button--danger" type="button" data-open-admin-controls><i class="fa-solid fa-user-shield"></i>Administrar conta</button><?php endif; ?>
          <?php endif; ?>
          <span class="profile-action-status" id="profileActionStatus" role="status"></span>
        </div>
        <div class="profile-identity">
          <div class="profile-name-row"><h1 id="profileName"><?= htmlspecialchars((string)$profile['nm_usuario']) ?></h1><?php if ($isAdmin): ?><span class="community-badge"><i class="fa-solid fa-shield-halved"></i>Administrador</span><?php elseif ($isCommunity): ?><span class="community-badge"><i class="fa-solid fa-certificate"></i>Comunitário</span><?php endif; ?></div>
          <p class="profile-handle">@<?= htmlspecialchars((string)($profile['social_handle'] ?: 'u' . $profileId)) ?></p>
          <p class="profile-role"><?= htmlspecialchars((string)$profile['tp_usuario']) ?></p>
          <div class="profile-follow-stats"><span><strong id="followersCount"><?= (int)$followState['seguidores'] ?></strong> seguidores</span><span><strong><?= (int)$followState['seguindo'] ?></strong> seguindo</span></div>
          <p class="profile-presence <?= $isOnline ? 'is-online' : 'is-offline' ?>"><i class="fa-solid fa-circle"></i><?= $isOnline ? 'Online' : 'Offline' ?></p>
          <p class="profile-joined"><i class="fa-regular fa-calendar"></i>Membro desde <?= htmlspecialchars($joined) ?></p>
        </div>
      </div>
    </section>

    <?php if ($isOwnProfile && $activePunishments): ?>
      <section class="profile-punishment-notice ui-card" role="alert" aria-labelledby="punishmentTitle">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <div><h2 id="punishmentTitle">Restrição temporária na sua conta</h2><p>As punições abaixo duram 30 dias a partir da aplicação.</p>
          <ul><?php foreach ($activePunishments as $punishment): ?><li><strong><?= $punishment['tipo'] === 'publicar' ? 'Publicações e comentários suspensos' : 'Cadastro de locais suspenso' ?></strong> até <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$punishment['expira_em']))) ?>. Motivo: <?= htmlspecialchars((string)$punishment['motivo']) ?>.</li><?php endforeach; ?></ul>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($viewerIsAdmin && !$isOwnProfile): ?>
      <section class="profile-admin-panel ui-card" id="profileAdminPanel" hidden aria-labelledby="adminPanelTitle">
        <header><div><span><i class="fa-solid fa-user-shield"></i>Moderação</span><h2 id="adminPanelTitle">Administrar conta de <?= htmlspecialchars((string)$profile['nm_usuario']) ?></h2></div><button type="button" data-close-admin-controls aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header>
        <p>Uma suspensão dura 30 dias. Informe o motivo que ficará visível somente para este usuário no próprio perfil.</p>
        <label>Motivo da punição<textarea id="adminPunishmentReason" maxlength="500" placeholder="Descreva claramente o motivo (mínimo de 5 caracteres)"></textarea></label>
        <div class="profile-admin-actions">
          <button class="ui-button ui-button--secondary" type="button" data-admin-punish="publicar"><i class="fa-solid fa-ban"></i>Suspender publicações</button>
          <button class="ui-button ui-button--secondary" type="button" data-admin-punish="cadastrar_locais"><i class="fa-solid fa-location-dot"></i>Suspender cadastro de locais</button>
          <?php foreach ($activePunishments as $punishment): ?><button class="ui-button ui-button--secondary" type="button" data-admin-restore="<?= htmlspecialchars((string)$punishment['tipo']) ?>"><i class="fa-solid fa-rotate-left"></i>Restaurar <?= $punishment['tipo'] === 'publicar' ? 'publicações' : 'locais' ?></button><?php endforeach; ?>
          <button class="ui-button ui-button--danger" type="button" data-admin-delete><i class="fa-solid fa-user-xmark"></i>Excluir conta</button>
        </div><p class="profile-admin-status" role="status"></p>
      </section>
    <?php endif; ?>

    <section class="profile-posts" aria-labelledby="postsTitle">
      <header class="profile-section-title"><h2 id="postsTitle">Publicações</h2><span>Últimos posts primeiro</span></header>
      <?php if (!$posts): ?>
        <div class="profile-empty ui-card"><i class="fa-regular fa-message"></i><h3>Nenhuma publicação visível</h3><p>Quando este usuário publicar, os posts aparecerão aqui.</p></div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <article class="profile-post ui-card" id="post-<?= (int)$post['id_post'] ?>" data-profile-post="<?= (int)$post['id_post'] ?>">
            <header class="profile-post__header">
              <div class="profile-post__avatar <?= $profilePhoto ? 'has-photo' : '' ?>"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="" loading="lazy" decoding="async" data-profile-image><?php endif; ?><span class="profile-avatar__fallback" aria-hidden="true"><?= htmlspecialchars($initial) ?></span></div>
              <div><strong><?= htmlspecialchars((string)$profile['nm_usuario']) ?></strong><?php if (($post['papel_comunidade'] ?? '') === 'dono'): ?><span class="profile-comment__role is-owner"><i class="fa-solid fa-crown"></i>Dono</span><?php elseif (($post['papel_comunidade'] ?? '') === 'moderador'): ?><span class="profile-comment__role is-moderator"><i class="fa-solid fa-shield-halved"></i>Moderador</span><?php elseif ($isCommunity): ?><span class="community-badge"><i class="fa-solid fa-certificate"></i>Comunitário</span><?php endif; ?><time datetime="<?= htmlspecialchars((string)$post['criado_em']) ?>"><?= htmlspecialchars(profileDate((string)$post['criado_em'])) ?><?= $post['editado_em'] ? ' · editado' : '' ?></time></div>
            </header>
            <?php if (!empty($post['comunidade_nome'])): ?><span class="profile-post__context"><i class="fa-solid fa-users"></i><?= htmlspecialchars((string)$post['comunidade_nome']) ?></span><?php endif; ?>
            <?php if (trim((string)$post['conteudo']) !== ''): ?><p class="profile-post__text"><?= nl2br(htmlspecialchars((string)$post['conteudo'])) ?></p><?php endif; ?>
            <?php if (!empty($post['midia_url'])): ?>
              <?php if ($post['midia_tipo'] === 'video'): ?><video class="profile-post__media" controls preload="metadata"><source src="<?= htmlspecialchars(profilePostMediaUrl((string)$post['midia_url'])) ?>"></video><?php else: ?><img class="profile-post__media" src="<?= htmlspecialchars(profilePostMediaUrl((string)$post['midia_url'])) ?>" alt="Mídia publicada por <?= htmlspecialchars((string)$profile['nm_usuario']) ?>" loading="lazy"><?php endif; ?>
            <?php endif; ?>
            <?php foreach (($post['anexos'] ?? []) as $anexo): ?>
              <?php if (($anexo['caminho'] ?? '') === ($post['midia_url'] ?? null)) continue; ?>
              <?php $urlExtra = profilePostMediaUrl((string)$anexo['caminho']); $mimeExtra=(string)$anexo['mime_type']; $nameExtra=htmlspecialchars((string)$anexo['nome_arquivo'],ENT_QUOTES); ?>
              <?php if (str_starts_with($mimeExtra,'image/')): ?><img class="profile-post__media" src="<?= htmlspecialchars($urlExtra,ENT_QUOTES) ?>" alt="<?= $nameExtra ?>" loading="lazy">
              <?php elseif (str_starts_with($mimeExtra,'video/')): ?><video class="profile-post__media" controls preload="metadata" src="<?= htmlspecialchars($urlExtra,ENT_QUOTES) ?>"></video>
              <?php else: ?><p><a href="<?= htmlspecialchars($urlExtra,ENT_QUOTES) ?>" download target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-file"></i><?= $nameExtra ?></a></p><?php endif; ?>
            <?php endforeach; ?>
            <footer class="profile-post__stats"><span><i class="fa-regular fa-heart"></i><?= (int)$post['total_curtidas'] ?> curtida<?= (int)$post['total_curtidas'] === 1 ? '' : 's' ?></span><button type="button" data-profile-comments aria-expanded="false"><i class="fa-regular fa-comment"></i><span data-profile-comment-count><?= (int)$post['total_comentarios'] ?></span><span data-profile-comment-label>comentário<?= (int)$post['total_comentarios'] === 1 ? '' : 's' ?></span></button><?php if (!$isOwnProfile): ?><button type="button" class="report-action" data-report-type="post" data-report-id="<?= (int)$post['id_post'] ?>" data-report-label="Publicação de <?= htmlspecialchars((string)$profile['nm_usuario'], ENT_QUOTES) ?>" data-report-source="<?= htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'perfil.php'), ENT_QUOTES) ?>#post-<?= (int)$post['id_post'] ?>"><i class="fa-regular fa-flag"></i>Denunciar</button><?php endif; ?></footer>
            <section class="profile-post__comments" hidden aria-label="Respostas da publicação"></section>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<script>
const PROFILE_API='ajax/social_api.php',PROFILE_CSRF=<?= json_encode($_SESSION['social_csrf']) ?>,PROFILE_VIEWER=<?= $viewerId ?>;
document.querySelectorAll('[data-profile-image]').forEach(image=>{image.addEventListener('error',()=>{image.closest('.has-photo')?.classList.remove('has-photo');image.remove()},{once:true})});
const profileEscape=value=>String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
async function profileApi(action,options={}){const response=await fetch(PROFILE_API+'?'+new URLSearchParams({action,...(options.query||{})}),options.fetch),result=await response.json().catch(()=>({ok:false,message:'Resposta inválida.'}));if(!response.ok||!result.ok)throw new Error(result.message||'Falha na operação.');return result}
function profileRoleBadge(role){if(role==='dono')return '<span class="profile-comment__role is-owner"><i class="fa-solid fa-crown"></i>Dono</span>';if(role==='moderador')return '<span class="profile-comment__role is-moderator"><i class="fa-solid fa-shield-halved"></i>Moderador</span>';return ''}
function profileCommentTree(comments,postId){const grouped=new Map();comments.forEach(comment=>{const parent=Number(comment.id_comentario_pai)||0;if(!grouped.has(parent))grouped.set(parent,[]);grouped.get(parent).push(comment)});const render=comment=>`<article class="profile-comment" data-profile-comment="${Number(comment.id_comentario)}"><header><a href="perfil.php?id=${Number(comment.id_usuario)}">${profileEscape(comment.nm_usuario)}</a>${profileRoleBadge(comment.papel_comunidade)}<time>${profileEscape(comment.criado_em)}</time></header><p>${profileEscape(comment.conteudo)}</p><div class="profile-comment__actions"><button type="button" data-profile-comment-like class="${Number(comment.curtiu)?'is-active':''}"><i class="fa-${Number(comment.curtiu)?'solid':'regular'} fa-heart"></i><span>${Number(comment.total_curtidas)}</span></button><button type="button" data-profile-reply><i class="fa-solid fa-reply"></i>Responder</button>${Number(comment.id_usuario)!==PROFILE_VIEWER?`<button type="button" class="report-action" data-report-type="comentario" data-report-id="${Number(comment.id_comentario)}" data-report-label="Comentário de ${profileEscape(comment.nm_usuario)}" data-report-source="${profileEscape(location.pathname+location.search)}#post-${Number(postId)}"><i class="fa-regular fa-flag"></i>Denunciar</button>`:''}</div><form data-profile-reply-form hidden><input name="text" maxlength="1200" placeholder="Responder a ${profileEscape(comment.nm_usuario)}" required><button class="ui-button" aria-label="Enviar resposta"><i class="fa-solid fa-paper-plane"></i></button></form><div class="profile-comment__children">${(grouped.get(Number(comment.id_comentario))||[]).map(render).join('')}</div></article>`;return (grouped.get(0)||[]).map(render).join('')}
function addProfileCommentAvatars(box,comments){comments.forEach(comment=>{const header=box.querySelector(`[data-profile-comment="${Number(comment.id_comentario)}"] > header`);if(!header||header.querySelector('.comment-avatar'))return;const avatar=document.createElement('span');avatar.className='comment-avatar';if(comment.foto_perfil){const image=document.createElement('img');image.src=comment.foto_perfil;image.alt=`Foto de ${comment.nm_usuario}`;avatar.append(image)}else{avatar.textContent=(comment.nm_usuario||'U').charAt(0).toUpperCase();avatar.setAttribute('aria-hidden','true')}header.prepend(avatar)});box.querySelectorAll('.profile-comment__children .profile-comment .report-action').forEach(button=>{button.innerHTML='<i class="fa-regular fa-flag"></i>Denunciar resposta';button.title='Denunciar esta resposta'})}
async function loadProfileComments(post){const postId=Number(post.dataset.profilePost),box=post.querySelector('.profile-post__comments'),result=await profileApi('comments',{query:{post:postId}}),count=result.comments.length;box.innerHTML=`<div class="profile-comment-list">${profileCommentTree(result.comments,postId)||'<p class="profile-comments-empty">Nenhuma resposta ainda.</p>'}</div><form data-profile-comment-form><input name="text" maxlength="1200" placeholder="Escreva um comentário" required><button class="ui-button" aria-label="Enviar comentário"><i class="fa-solid fa-paper-plane"></i></button></form>`;addProfileCommentAvatars(box,result.comments);box.hidden=false;post.querySelector('[data-profile-comment-count]').textContent=count;post.querySelector('[data-profile-comment-label]').textContent=count===1?'comentário':'comentários';return count}
document.querySelector('.profile-posts')?.addEventListener('click',async event=>{const post=event.target.closest('[data-profile-post]');if(!post)return;const toggle=event.target.closest('[data-profile-comments]'),reply=event.target.closest('[data-profile-reply]'),like=event.target.closest('[data-profile-comment-like]'),postId=Number(post.dataset.profilePost);try{if(toggle){const box=post.querySelector('.profile-post__comments');if(!box.hidden){box.hidden=true;toggle.setAttribute('aria-expanded','false')}else{await loadProfileComments(post);toggle.setAttribute('aria-expanded','true')}return}if(reply){const form=reply.closest('[data-profile-comment]').querySelector(':scope > [data-profile-reply-form]');form.hidden=!form.hidden;if(!form.hidden)form.elements.text.focus();return}if(like){const comment=like.closest('[data-profile-comment]'),result=await profileApi('toggle_comment_like',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,post:postId,comment:comment.dataset.profileComment})}});like.classList.toggle('is-active',result.liked);like.querySelector('i').className=`fa-${result.liked?'solid':'regular'} fa-heart`;like.querySelector('span').textContent=result.count}}catch(error){const box=post.querySelector('.profile-post__comments');box.hidden=false;box.innerHTML=`<div class="ui-alert ui-alert--error">${profileEscape(error.message)}</div>`}});
document.querySelector('.profile-posts')?.addEventListener('submit',async event=>{const form=event.target.closest('[data-profile-comment-form],[data-profile-reply-form]');if(!form)return;event.preventDefault();const post=form.closest('[data-profile-post]'),postId=Number(post.dataset.profilePost),parent=form.matches('[data-profile-reply-form]')?Number(form.closest('[data-profile-comment]').dataset.profileComment):0,button=form.querySelector('button');button.disabled=true;try{await profileApi('add_comment',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,post:postId,parent,text:form.elements.text.value})}});await loadProfileComments(post)}catch(error){form.insertAdjacentHTML('beforebegin',`<div class="ui-alert ui-alert--error">${profileEscape(error.message)}</div>`)}finally{button.disabled=false}});
</script>
<?php if (!$isOwnProfile): ?>
<script>
const startChat=document.getElementById('startChat'),followUser=document.getElementById('followUser'),profileActionStatus=document.getElementById('profileActionStatus');
followUser.addEventListener('click',async()=>{followUser.disabled=true;profileActionStatus.textContent='Atualizando...';try{const result=await profileApi('toggle_follow',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,user:<?= $profileId ?>})}});followUser.setAttribute('aria-pressed',String(result.following));followUser.querySelector('i').className=`fa-solid ${result.following?'fa-user-check':'fa-user-plus'}`;followUser.querySelector('span').textContent=result.following?'Deixar de seguir':'Seguir';followersCount.textContent=result.followers;profileActionStatus.textContent=result.message}catch(error){profileActionStatus.textContent=error.message}finally{followUser.disabled=false}});
startChat.addEventListener('click',async()=>{startChat.disabled=true;const original=startChat.innerHTML;startChat.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Abrindo conversa...';profileActionStatus.textContent='';try{const result=await profileApi('start_private',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,user:<?= $profileId ?>})}});location.href='chats.php?tab=private&chat='+encodeURIComponent(result.id)}catch(error){profileActionStatus.textContent=error.message;startChat.disabled=false;startChat.innerHTML=original}});
</script>
<?php endif; ?>
<?php if ($viewerIsAdmin && !$isOwnProfile): ?>
<script>
const adminPanel=document.getElementById('profileAdminPanel'),adminStatus=adminPanel.querySelector('.profile-admin-status'),adminReason=document.getElementById('adminPunishmentReason');
document.querySelector('[data-open-admin-controls]')?.addEventListener('click',()=>{adminPanel.hidden=false;adminPanel.scrollIntoView({behavior:'smooth',block:'center'})});
adminPanel.querySelector('[data-close-admin-controls]')?.addEventListener('click',()=>adminPanel.hidden=true);
adminPanel.addEventListener('click',async event=>{const punish=event.target.closest('[data-admin-punish]'),restore=event.target.closest('[data-admin-restore]'),remove=event.target.closest('[data-admin-delete]');if(!punish&&!restore&&!remove)return;let action,payload;if(remove){if(!confirm('Excluir permanentemente esta conta e todos os dados vinculados? Esta ação não pode ser desfeita.'))return;action='admin_delete_user';payload={user:<?= $profileId ?>}}else if(punish){const reason=adminReason.value.trim();if(reason.length<5){adminStatus.textContent='Informe um motivo com pelo menos 5 caracteres.';adminReason.focus();return}if(!confirm('Aplicar esta suspensão por 30 dias?'))return;action='admin_punish_user';payload={user:<?= $profileId ?>,type:punish.dataset.adminPunish,reason}}else{if(!confirm('Restaurar esta permissão agora?'))return;action='admin_restore_user_permission';payload={user:<?= $profileId ?>,type:restore.dataset.adminRestore}}const button=punish||restore||remove;button.disabled=true;adminStatus.textContent='Processando...';try{const result=await profileApi(action,{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:PROFILE_CSRF,...payload})}});adminStatus.textContent=result.message;if(remove)setTimeout(()=>location.href='social.php',700);else setTimeout(()=>location.reload(),700)}catch(error){adminStatus.textContent=error.message;button.disabled=false}});
</script>
<?php endif; ?>
<script src="../static/js/report-modal.js?v=66"></script>
<script>window.LibrasHubReports.init({api:'ajax/social_api.php',csrf:PROFILE_CSRF});</script>
<script src="../static/js/acessibility.js?v=67" defer></script>
</body>
</html>
