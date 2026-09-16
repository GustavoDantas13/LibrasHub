<?php
declare(strict_types=1);
require_once __DIR__ . '/configs/config.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.php?redirect=perfil.php');
    exit;
}

require_once __DIR__ . '/configs/social_schema.php';
ensureSocialSchema($pdo);

$viewerId = (int)$_SESSION['usuario_id'];
$profileId = max(1, (int)($_GET['id'] ?? $viewerId));
$stmt = $pdo->prepare('SELECT id_usuario,nm_usuario,tp_usuario,is_community_user,foto_perfil,banner_perfil,status_visibilidade,ultimo_acesso_em,dt_usuario FROM usuario WHERE id_usuario=? LIMIT 1');
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
            c.nome AS comunidade_nome,c.privada,
            (SELECT COUNT(*) FROM social_post_curtidas l WHERE l.id_post=p.id_post) AS total_curtidas,
            (SELECT COUNT(*) FROM social_post_comentarios pc WHERE pc.id_post=p.id_post) AS total_comentarios
       FROM social_posts p
       LEFT JOIN social_comunidades c ON c.id_comunidade=p.id_comunidade
       LEFT JOIN social_comunidade_membros vm ON vm.id_comunidade=c.id_comunidade AND vm.id_usuario=?
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
$isCommunity = !$isAdmin && !empty($profile['is_community_user']);
$accountBadge = $isAdmin ? 'Administrador' : ($isCommunity ? 'Comunitário' : '');
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
      <div class="profile-cover" aria-hidden="true"<?= !empty($profile['banner_perfil']) ? ' style="background-image:url(\'' . htmlspecialchars((string)$profile['banner_perfil'], ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>></div>
      <div class="profile-summary">
        <div class="profile-avatar" aria-hidden="true"><?php if (!empty($profile['foto_perfil'])): ?><img src="<?= htmlspecialchars((string)$profile['foto_perfil']) ?>" alt=""><?php else: ?><?= htmlspecialchars($initial) ?><?php endif; ?></div>
        <div class="profile-actions">
          <?php if ($isOwnProfile): ?>
            <a class="ui-button ui-button--secondary" href="usuario.php"><i class="fa-solid fa-pen"></i>Editar perfil</a>
          <?php else: ?>
            <button class="ui-button" id="startChat" type="button"><i class="fa-solid fa-message"></i>Enviar mensagem</button>
          <?php endif; ?>
        </div>
        <div class="profile-identity">
          <div class="profile-name-row"><h1 id="profileName"><?= htmlspecialchars((string)$profile['nm_usuario']) ?></h1><?php if ($accountBadge !== ''): ?><span class="community-badge"><i class="fa-solid <?= $isAdmin ? 'fa-shield-halved' : 'fa-certificate' ?>"></i><?= htmlspecialchars($accountBadge) ?></span><?php endif; ?></div>
          <p class="profile-role"><?= htmlspecialchars((string)$profile['tp_usuario']) ?></p>
          <p class="profile-role"><i class="fa-solid fa-circle" style="font-size:.55rem;color:<?= $isOnline ? 'var(--ui-accent)' : 'var(--ui-muted)' ?>"></i> <?= $isOnline ? 'Online' : 'Offline' ?></p>
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
          <article class="profile-post ui-card" id="post-<?= (int)$post['id_post'] ?>">
            <header class="profile-post__header">
              <div class="profile-post__avatar" aria-hidden="true"><?php if (!empty($profile['foto_perfil'])): ?><img src="<?= htmlspecialchars((string)$profile['foto_perfil']) ?>" alt=""><?php else: ?><?= htmlspecialchars($initial) ?><?php endif; ?></div>
              <div><strong><?= htmlspecialchars((string)$profile['nm_usuario']) ?></strong><?php if ($accountBadge !== ''): ?><span class="community-badge"><i class="fa-solid <?= $isAdmin ? 'fa-shield-halved' : 'fa-certificate' ?>"></i><?= htmlspecialchars($accountBadge) ?></span><?php endif; ?><time datetime="<?= htmlspecialchars((string)$post['criado_em']) ?>"><?= htmlspecialchars(profileDate((string)$post['criado_em'])) ?><?= $post['editado_em'] ? ' · editado' : '' ?></time></div>
            </header>
            <?php if (!empty($post['comunidade_nome'])): ?><span class="profile-post__context"><i class="fa-solid fa-users"></i><?= htmlspecialchars((string)$post['comunidade_nome']) ?></span><?php endif; ?>
            <?php if (trim((string)$post['conteudo']) !== ''): ?><p class="profile-post__text"><?= nl2br(htmlspecialchars((string)$post['conteudo'])) ?></p><?php endif; ?>
            <?php if (!empty($post['midia_url'])): ?>
              <?php if ($post['midia_tipo'] === 'video'): ?><video class="profile-post__media" controls preload="metadata"><source src="<?= htmlspecialchars((string)$post['midia_url']) ?>"></video><?php else: ?><img class="profile-post__media" src="<?= htmlspecialchars((string)$post['midia_url']) ?>" alt="Mídia publicada por <?= htmlspecialchars((string)$profile['nm_usuario']) ?>" loading="lazy"><?php endif; ?>
            <?php endif; ?>
            <footer class="profile-post__stats"><span><i class="fa-regular fa-heart"></i><?= (int)$post['total_curtidas'] ?> curtida<?= (int)$post['total_curtidas'] === 1 ? '' : 's' ?></span><span><i class="fa-regular fa-comment"></i><?= (int)$post['total_comentarios'] ?> comentário<?= (int)$post['total_comentarios'] === 1 ? '' : 's' ?></span></footer>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php if (!$isOwnProfile): ?>
<script>
const startChat=document.getElementById('startChat');
startChat.addEventListener('click',async()=>{startChat.disabled=true;const original=startChat.innerHTML;startChat.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>Abrindo conversa...';try{const response=await fetch('ajax/social_api.php?action=start_private',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:<?= json_encode($_SESSION['social_csrf']) ?>,user:<?= $profileId ?>})});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Não foi possível iniciar o chat.');location.href='chats.php?tab=private&chat='+encodeURIComponent(result.id)}catch(error){alert(error.message);startChat.disabled=false;startChat.innerHTML=original}});
</script>
<?php endif; ?>
<script src="../static/js/acessibility.js" defer></script>
</body>
</html>
