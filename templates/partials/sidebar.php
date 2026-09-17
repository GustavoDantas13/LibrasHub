<?php
$sidebarId = $sidebarId ?? 'sidebarMenu';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'home.php');
$isAdmin = isset($ehAdmin)
    ? (bool) $ehAdmin
    : strcasecmp(trim((string)($_SESSION['usuario_tipo'] ?? '')), 'Administrador') === 0;
$displayName = trim((string)($_SESSION['usuario_nome'] ?? 'Usuário')) ?: 'Usuário';
$navigation = [
    ['home.php', 'house', 'Início', 'home'],
    ['leitor.php', 'video', 'Leitor', 'leitor'],
    ['upload.php', 'upload', 'Upload', 'upload'],
    ['historico.php', 'clock-rotate-left', 'Histórico', 'historico'],
    ['ajuda.php', 'circle-question', 'Ajuda', 'ajuda'],
    ['social.php', 'comments', 'Social', 'social'],
    ['locais.php', 'location-dot', 'Locais acessíveis', 'locais'],
];
if ($currentPage === 'chats.php') $currentPage = 'social.php';
?>
<link rel="stylesheet" href="../static/css/sidebar-viewport.css">
<aside class="sidebar unified-sidebar" id="<?= htmlspecialchars($sidebarId) ?>" aria-label="Navegação principal">
  <div class="sidebar-top">
    <a class="logo" href="home.php" aria-label="LibrasHub — início">
      <img src="../static/images/librashub-logo.png" alt="" class="logo-img">
      <span>LibrasHub</span>
    </a>
    <nav class="sidebar-nav" aria-label="Menu principal">
      <?php foreach ($navigation as [$href,$icon,$label,$key]): ?>
        <a class="nav-item <?= $currentPage === $href ? 'active' : '' ?>" href="<?= $href ?>" data-page="<?= $key ?>" <?= $currentPage === $href ? 'aria-current="page"' : '' ?>>
          <span class="nav-icon"><i class="fa-solid fa-<?= $icon ?>" aria-hidden="true"></i></span>
          <span><?= $label ?></span>
        </a>
      <?php endforeach; ?>
      <?php if ($isAdmin): ?>
        <a class="nav-item <?= $currentPage === 'admin.php' ? 'active' : '' ?>" href="admin.php" data-page="admin" <?= $currentPage === 'admin.php' ? 'aria-current="page"' : '' ?>>
          <span class="nav-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span><span>Administração</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>
  <div class="sidebar-bottom">
    <a class="nav-item <?= $currentPage === 'configuracoes.php' ? 'active' : '' ?>" href="configuracoes.php" data-page="configuracoes"><span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span><span>Configurações</span></a>
    <a class="nav-item <?= $currentPage === 'perfil.php' || $currentPage === 'usuario.php' ? 'active' : '' ?>" href="perfil.php" data-page="perfil" <?= $currentPage === 'perfil.php' || $currentPage === 'usuario.php' ? 'aria-current="page"' : '' ?>><span class="nav-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span><span><?= htmlspecialchars($displayName) ?></span></a>
  </div>
</aside>
<button class="sidebar-toggle" type="button" aria-label="Abrir menu" aria-controls="<?= htmlspecialchars($sidebarId) ?>" aria-expanded="false">
  <i class="fa-solid fa-bars" aria-hidden="true"></i><span>Menu</span>
</button>
<button class="sidebar-backdrop" type="button" aria-label="Fechar menu" tabindex="-1"></button>
<script>
document.addEventListener('DOMContentLoaded',()=>{const sidebar=document.getElementById(<?= json_encode($sidebarId) ?>),toggle=document.querySelector('.sidebar-toggle'),backdrop=document.querySelector('.sidebar-backdrop');if(!sidebar||!toggle||!backdrop)return;const setOpen=open=>{sidebar.classList.toggle('open',open);backdrop.classList.toggle('open',open);document.body.classList.toggle('sidebar-open',open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');toggle.querySelector('i').className=open?'fa-solid fa-xmark':'fa-solid fa-bars'};toggle.addEventListener('click',()=>setOpen(!sidebar.classList.contains('open')));backdrop.addEventListener('click',()=>setOpen(false));sidebar.addEventListener('click',e=>{if(e.target.closest('a')&&matchMedia('(max-width: 900px)').matches)setOpen(false)});document.addEventListener('keydown',e=>{if(e.key==='Escape')setOpen(false)});matchMedia('(min-width: 901px)').addEventListener('change',e=>{if(e.matches)setOpen(false)})});
</script>
