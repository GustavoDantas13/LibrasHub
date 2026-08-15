<?php

session_start();

require_once "configs/config.php";

if (empty($_SESSION["usuario_id"])) {
    header("Location: ../index.php");
    exit;
}

$idUsuarioAtual = (int) $_SESSION["usuario_id"];

$stmt = $pdo->prepare("
    SELECT
        id_usuario,
        tp_usuario
    FROM usuario
    WHERE id_usuario = ?
    LIMIT 1
");

$stmt->execute([
    $idUsuarioAtual
]);

$usuarioAtual = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$usuarioAtual) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../index.php");
    exit;
}

$_SESSION["usuario_tipo"] =
    $usuarioAtual["tp_usuario"];

$ehAdmin =
    strcasecmp(
        trim(
            $usuarioAtual["tp_usuario"]
        ),
        "Administrador"
    ) === 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../static/images/librashub-logo.png">
<title>LibrasHub – Início</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="../static/css/style.css">

<!-- aplica tema antes do paint para evitar flash -->
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
/* ── BASE ────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
html, body {
  width: 100%;
  min-height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
}
body { display: block; margin: 0; }

/* Todas as imagens respeitam o espaço disponível */
img {
  max-width: 100%;
  height: auto;
  display: block;
}
.logo-img {
  object-fit: contain;
  flex-shrink: 0;
}

/* Evita que grids/flex criem rolagem horizontal por conteúdo grande */
.hero > *,
.tool-block > *,
.comm-grid > *,
.content { min-width: 0; }

/* ── SIDEBAR ─────────────────────────────────────────── */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 260px;
  height: 100dvh;
  max-height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 1100;
  transition: transform .25s ease;
}
.content {
  margin-left: 260px;
  width: calc(100% - 260px);
  min-height: 100vh;
}

/* Botão/overlay do menu mobile ficam escondidos no desktop */
.menu-toggle,
.sidebar-overlay { display: none; }

/* Links da navegação nunca estouram a largura */
.sidebar .nav-item {
  width: 100%;
  min-width: 0;
  overflow-wrap: anywhere;
}

/* ── HERO ────────────────────────────────────────────── */
.hero {
  padding: 100px 72px 80px;
  max-width: 1080px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 64px;
  align-items: center;
}
.hero-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--primary);
  color: var(--primary-text);
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .8px;
  text-transform: uppercase;
  padding: 6px 14px;
  border-radius: 20px;
  margin-bottom: 24px;
}
.hero-label i { font-size: .65rem; }
.hero h1 {
  font-size: 2.8rem;
  font-weight: 900;
  line-height: 1.12;
  letter-spacing: -.5px;
  margin-bottom: 20px;
}
.hero h1 em {
  font-style: normal;
  color: var(--primary);
  position: relative;
}
.hero-desc {
  font-size: .95rem;
  color: var(--text-muted);
  line-height: 1.75;
  margin-bottom: 36px;
  max-width: 440px;
}
.hero-cta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 24px;
  border-radius: 12px;
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
}
.btn-cta:hover { transform: translateY(-2px); }
.btn-cta-primary {
  background: var(--primary);
  color: var(--primary-text);
  box-shadow: 0 4px 16px color-mix(in srgb, var(--primary) 35%, transparent);
}
.btn-cta-primary:hover {
  box-shadow: 0 8px 24px color-mix(in srgb, var(--primary) 45%, transparent);
}
.btn-cta-ghost {
  background: transparent;
  color: var(--text);
  border: 2px solid var(--border);
}
.btn-cta-ghost:hover { border-color: var(--primary); color: var(--primary); }

/* hero visual — ilustração animada */
.hero-visual {
  position: relative;
  height: 340px;
  border-radius: 24px;
  background: var(--sidebar-bg);
  border: 1px solid var(--border);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 10px;
}
.hero-visual-glow {
  position: absolute;
  width: 260px;
  height: 260px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--primary) 18%, transparent);
  filter: blur(60px);
  animation: pulse-glow 3.5s ease-in-out infinite;
}
@keyframes pulse-glow {
  0%, 100% { opacity: .5; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.12); }
}
.hero-visual-icon {
  font-size: 4.5rem;
  position: relative;
  z-index: 1;
  animation: float 4s ease-in-out infinite;
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.hero-visual-caption {
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  color: var(--text-muted);
  position: relative;
  z-index: 1;
}
.hero-visual-badge {
  position: absolute;
  top: 16px; right: 16px;
  background: var(--success);
  color: var(--success-text);
  font-size: .65rem;
  font-weight: 700;
  padding: 5px 11px;
  border-radius: 20px;
  letter-spacing: .3px;
}

/* ── DIVISOR ─────────────────────────────────────────── */
.divider {
  height: 1px;
  background: var(--border);
  opacity: .5;
  max-width: 1080px;
  margin: 0 auto;
}

/* ── SEÇÃO GENÉRICA ──────────────────────────────────── */
.section {
  max-width: 1080px;
  margin: 0 auto;
  padding: 80px 72px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--primary);
  margin-bottom: 10px;
}
.eyebrow::before {
  content: '';
  display: block;
  width: 16px;
  height: 2px;
  background: var(--primary);
  border-radius: 2px;
}
.section-h2 {
  font-size: 1.9rem;
  font-weight: 900;
  line-height: 1.2;
  margin-bottom: 12px;
  letter-spacing: -.3px;
}
.section-desc {
  font-size: .9rem;
  color: var(--text-muted);
  line-height: 1.75;
  max-width: 520px;
  margin-bottom: 52px;
}

/* ── QUICK STATS ─────────────────────────────────────── */
.stats-strip {
  background: var(--sidebar-bg);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.stats-inner {
  max-width: 1080px;
  margin: 0 auto;
  padding: 32px 72px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  text-align: center;
}
.stat-item { padding: 16px; }
.stat-num {
  font-size: 2rem;
  font-weight: 900;
  color: var(--primary);
  line-height: 1;
  margin-bottom: 6px;
}
.stat-label {
  font-size: .75rem;
  color: var(--text-muted);
  font-weight: 600;
  letter-spacing: .3px;
}

/* ── TOOL CARDS ──────────────────────────────────────── */
.tool-block {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 56px;
  align-items: center;
  margin-bottom: 80px;
}
.tool-block:last-child { margin-bottom: 0; }
.tool-block.flip { direction: rtl; }
.tool-block.flip > * { direction: ltr; }

.tool-visual {
  background: var(--sidebar-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  height: 280px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 12px;
  color: var(--text-muted);
  position: relative;
  overflow: hidden;
  transition: border-color .25s ease, box-shadow .25s ease;
}
.tool-visual:hover {
  border-color: var(--primary);
  box-shadow: 0 8px 32px color-mix(in srgb, var(--primary) 18%, transparent);
}
.tool-visual-ico { font-size: 3.2rem; }
.tool-visual-lbl { font-size: .75rem; font-weight: 600; letter-spacing: .3px; }
.tool-badge {
  position: absolute;
  top: 14px; right: 14px;
  font-size: .65rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  background: var(--success);
  color: var(--success-text);
}

.tool-chip {
  display: inline-block;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .4px;
  text-transform: uppercase;
  background: color-mix(in srgb, var(--success) 15%, transparent);
  color: var(--success);
  border: 1px solid var(--success);
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 14px;
}
.tool-h3 {
  font-size: 1.45rem;
  font-weight: 900;
  margin-bottom: 12px;
  letter-spacing: -.2px;
}
.tool-p {
  font-size: .875rem;
  color: var(--text-muted);
  line-height: 1.75;
  margin-bottom: 24px;
}
.tool-steps { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
.tool-step { display: flex; align-items: flex-start; gap: 12px; }
.step-num {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: var(--primary);
  color: var(--primary-text);
  display: flex; align-items: center; justify-content: center;
  font-size: .68rem; font-weight: 800;
  flex-shrink: 0;
  margin-top: 1px;
}
.step-num.green { background: var(--success); color: var(--success-text); }
.step-txt {
  font-size: .85rem;
  color: var(--text-muted);
  line-height: 1.6;
  padding-top: 3px;
}
.step-txt strong { color: var(--text); }

.btn-tool {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 22px;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  transition: transform .18s ease, opacity .18s ease;
  border: none;
  cursor: pointer;
}
.btn-tool:hover { transform: translateY(-2px); opacity: .9; }
.btn-tool-green { background: var(--success); color: var(--success-text); }
.btn-tool-primary { background: var(--primary); color: var(--primary-text); }

/* ── COMUNIDADE ──────────────────────────────────────── */
.comm-wrap {
  background: var(--sidebar-bg);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.comm-inner {
  max-width: 1080px;
  margin: 0 auto;
  padding: 80px 72px;
}
.comm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 56px;
  align-items: start;
}
.comm-features { display: flex; flex-direction: column; gap: 28px; }
.comm-feat { display: flex; align-items: flex-start; gap: 16px; }
.comm-ico {
  width: 44px; height: 44px;
  border-radius: 14px;
  background: color-mix(in srgb, var(--primary) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--primary) 30%, transparent);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
  transition: transform .2s ease;
}
.comm-feat:hover .comm-ico { transform: scale(1.08); }
.comm-feat-title { font-size: .9rem; font-weight: 700; margin-bottom: 4px; }
.comm-feat-desc { font-size: .8rem; color: var(--text-muted); line-height: 1.6; }

.lock-card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 36px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.lock-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--primary) 6%, transparent),
    transparent 60%);
  pointer-events: none;
}
.lock-icon { font-size: 2.8rem; margin-bottom: 16px; display: block; }
.lock-title { font-size: 1.05rem; font-weight: 800; margin-bottom: 8px; }
.lock-desc {
  font-size: .82rem;
  color: var(--text-muted);
  line-height: 1.65;
  margin-bottom: 24px;
}
.lock-btns { display: flex; flex-direction: column; gap: 10px; }
.btn-lock-primary {
  display: block;
  text-align: center;
  padding: 12px;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  background: var(--primary);
  color: var(--primary-text);
  transition: opacity .18s;
}
.btn-lock-primary:hover { opacity: .88; }
.btn-lock-outline {
  display: block;
  text-align: center;
  padding: 11px;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 700;
  text-decoration: none;
  background: transparent;
  color: var(--primary);
  border: 2px solid var(--primary);
  transition: background .18s, color .18s;
}
.btn-lock-outline:hover { background: var(--primary); color: var(--primary-text); }

/* ── DICAS ───────────────────────────────────────────── */
.tips-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.tip-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 24px 20px;
  transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
  cursor: default;
}
.tip-card:hover {
  transform: translateY(-4px);
  border-color: var(--primary);
  box-shadow: 0 6px 20px color-mix(in srgb, var(--primary) 12%, transparent);
}
.tip-ico { font-size: 1.6rem; margin-bottom: 12px; display: block; }
.tip-title { font-size: .875rem; font-weight: 700; margin-bottom: 6px; }
.tip-desc { font-size: .78rem; color: var(--text-muted); line-height: 1.6; }

/* ── FOOTER ──────────────────────────────────────────── */
.hw-footer {
  background: var(--sidebar-bg);
  border-top: 1px solid var(--border);
  padding: 36px 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  max-width: 100%;
}
.footer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: .875rem;
  color: var(--sidebar-text);
}
.footer-links { display: flex; gap: 24px; flex-wrap: wrap; }
.footer-links a {
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: .78rem;
  opacity: .65;
  transition: opacity .15s;
}
.footer-links a:hover { opacity: 1; }
.footer-copy { font-size: .72rem; opacity: .4; color: var(--sidebar-text); }

/* reveal — sempre visível (sem scroll trigger) */
.reveal { opacity: 1; transform: none; }


/* ── RESPONSIVIDADE ──────────────────────────────────── */

/* Desktop/tablet grande: paddings fluidos */
.hero,
.section,
.comm-inner,
.stats-inner {
  width: 100%;
}

.hero {
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  padding-left: clamp(28px, 5vw, 72px);
  padding-right: clamp(28px, 5vw, 72px);
}
.section,
.comm-inner {
  padding-left: clamp(28px, 5vw, 72px);
  padding-right: clamp(28px, 5vw, 72px);
}
.stats-inner {
  padding-left: clamp(24px, 5vw, 72px);
  padding-right: clamp(24px, 5vw, 72px);
}
.hero h1 { font-size: clamp(2rem, 4vw, 2.8rem); }

/* Tablet e celular: sidebar vira menu lateral recolhível */
@media (max-width: 900px) {
  .content {
    margin-left: 0 !important;
    width: 100% !important;
  }

  .sidebar {
    width: min(82vw, 300px);
    transform: translateX(-105%);
    box-shadow: 8px 0 30px rgba(0, 0, 0, .22);
  }
  .sidebar.open { transform: translateX(0); }

  .menu-toggle {
    display: flex;
    position: fixed;
    top: max(14px, env(safe-area-inset-top));
    left: max(14px, env(safe-area-inset-left));
    z-index: 1200;
    width: 46px;
    height: 46px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    font-size: 1.35rem;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(0, 0, 0, .14);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 1090;
    background: rgba(0, 0, 0, .48);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .25s ease, visibility .25s ease;
  }
  .sidebar-overlay.open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  .hero {
    grid-template-columns: 1fr !important;
    padding: 88px 28px 54px !important;
    gap: 38px;
  }
  .hero-desc { max-width: 650px; }
  .hero-visual {
    width: 100%;
    height: auto;
    min-height: 260px;
    aspect-ratio: 16 / 9;
  }

  .tool-block,
  .comm-grid {
    grid-template-columns: 1fr !important;
  }
  .tool-block,
  .tool-block.flip {
    direction: ltr !important;
    gap: 30px;
  }
  .tool-visual {
    width: 100%;
    height: auto;
    min-height: 240px;
  }

  .tips-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

/* Celulares */
@media (max-width: 600px) {
  .hero {
    padding: 82px 18px 42px !important;
    gap: 28px;
  }
  .hero h1 {
    font-size: clamp(1.65rem, 8vw, 2rem);
    line-height: 1.16;
  }
  .hero h1 br { display: none; }
  .hero-desc {
    font-size: .9rem;
    margin-bottom: 28px;
  }
  .hero-visual {
    min-height: 210px;
    border-radius: 18px;
  }
  .hero-visual-icon { font-size: 3.6rem; }

  .section,
  .comm-inner {
    padding: 48px 18px;
  }
  .section-desc { margin-bottom: 36px; }

  .stats-inner {
    grid-template-columns: 1fr;
    padding: 24px 18px;
    gap: 0;
  }
  .stat-item {
    border-bottom: 1px solid var(--border);
    padding: 20px 0;
  }
  .stat-item:last-child { border-bottom: none; }

  .tool-block { margin-bottom: 48px; }
  .tool-visual { min-height: 200px; }

  .comm-grid { gap: 32px; }
  .lock-card { padding: 28px 20px; }
  .comm-feat { gap: 12px; }

  .tips-grid { grid-template-columns: 1fr !important; }

  .hero-cta {
    flex-direction: column;
    width: 100%;
  }
  .btn-cta,
  .btn-tool {
    width: 100%;
    justify-content: center;
    text-align: center;
  }

  .hw-footer {
    padding: 28px 18px;
    flex-direction: column;
    text-align: center;
  }
  .footer-links {
    justify-content: center;
    gap: 14px 20px;
  }
}

/* Celulares bem estreitos */
@media (max-width: 380px) {
  .sidebar { width: 88vw; }
  .hero,
  .section,
  .comm-inner,
  .stats-inner { padding-left: 14px !important; padding-right: 14px !important; }
  .hero-visual-badge { top: 10px; right: 10px; }
  .comm-ico { width: 40px; height: 40px; }
}

/* Acessibilidade: reduz animações quando o sistema solicitar */
@media (prefers-reduced-motion: reduce) {
  .sidebar,
  .sidebar-overlay,
  .hero-visual-glow,
  .hero-visual-icon { transition: none !important; animation: none !important; }
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════ -->
<aside class="sidebar" id="sidebarNav" aria-label="Navegação principal">
  <div class="sidebar-top">
    <div class="logo">
      <img src="../static/images/librashub-logo.png" alt="LibrasHub" class="logo-img">
      LibrasHub
    </div>
    <a class="nav-item" href="home.php"          data-page="home"><span class="nav-icon"><i class="fa-regular fa-house" style="color:#fdbe00;"></i></span>Início</a>
    <a class="nav-item" href="leitor.php"        data-page="leitor"><span class="nav-icon"><i class="fa-solid fa-video" style="color:#fdbe00;"></i></span>Leitor</a>
    <a class="nav-item" href="upload.php"        data-page="upload"><span class="nav-icon"><i class="fa-solid fa-upload" style="color:#fdbe00;"></i></span>Upload</a>
    <a class="nav-item" href="historico.php"     data-page="historico"><span class="nav-icon"><i class="fa-solid fa-arrow-rotate-left" style="color:#fdbe00;"></i></span>Histórico</a>
    <a class="nav-item" href="ajuda.php"         data-page="ajuda"><span class="nav-icon"><i class="fa-solid fa-question" style="color:#fdbe00;"></i></span>Ajuda</a>
    <a class="nav-item" href="comunidade.php"     data-page="comunidade"><span class="nav-icon"><i class="fa-solid fa-users" style="color:#fdbe00;"></i></span>Comunidade</a>

    <?php if ($ehAdmin): ?>

      <a class="nav-item" href="admin.php" data-page="admin">
        <span class="nav-icon">
          <i class="fa-solid fa-shield-halved" style="color:#fdbe00;"></i>
        </span>
        Administração
      </a>

    <?php endif; ?>

  </div>
  <div class="sidebar-bottom">
    <a class="nav-item" href="configuracoes.php" data-page="configuracoes"><span class="nav-icon"><i class="fa-solid fa-gear" style="color:#fdbe00;"></i></span>Configurações</a>
    <a class="nav-item" href="usuario.php"        data-page="usuario"><span class="nav-icon"><i class="fa-solid fa-user" style="color:#fdbe00;"></i></span>Usuário</a>
  </div>
</aside>

<!-- ══ CONTEÚDO PRINCIPAL ══════════════════════════════ -->
<div class="content" id="conteudo-principal" role="main">

  <!-- ── HERO ────────────────────────────────────────── -->
  <div class="hero reveal">
    <div class="hero-text">
      <div class="hero-label">
        <i class="fa-solid fa-hand-dots"></i>
        Tecnologia assistiva com IA
      </div>
      <h1>Tradução de Libras<br>e comunidade<br><em>em um só lugar</em></h1>
      <p class="hero-desc">
        O LibrasHub usa tecnologia assistiva para aproximar pessoas surdas e ouvintes. Traduza sinais, envie conteúdos para análise e participe de uma comunidade criada para tornar a comunicação mais acessível.
      </p>
      <div class="hero-cta">
        <a class="btn-cta btn-cta-primary" href="leitor.php">
          <i class="fa-solid fa-video"></i> Iniciar Tradução
        </a>
        <a class="btn-cta btn-cta-ghost" href="comunidade.php">
          <i class="fa-solid fa-users"></i> Acessar Comunidade
        </a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-visual-glow"></div>
      <div class="hero-visual-badge">● IA + Libras</div>
      <div class="hero-visual-icon"><i class="fa-solid fa-hands" style="color: #fdbe00;"></i></div>
      <div class="hero-visual-caption">Tecnologia para uma comunicação mais acessível</div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- ── NÚMEROS ──────────────────────────────────────── -->
  <div class="stats-strip">
    <div class="stats-inner">
      <div class="stat-item reveal reveal-delay-1">
        <div class="stat-num">3</div>
        <div class="stat-label">Recursos integrados em uma plataforma</div>
      </div>
      <div class="stat-item reveal reveal-delay-2">
        <div class="stat-num">IA</div>
        <div class="stat-label">Processamento inteligente de sinais</div>
      </div>
      <div class="stat-item reveal reveal-delay-3">
        <div class="stat-num">Web</div>
        <div class="stat-label">Acesso direto pelo navegador</div>
      </div>
    </div>
  </div>

  <!-- ── SEÇÃO: LEITOR ────────────────────────────────── -->
  <div class="section">
    <div class="reveal">
      <div class="eyebrow">Tradução com IA</div>
      <div class="section-h2">Leitor de Libras em Tempo Real</div>
      <div class="section-desc">Use a câmera para transformar sinais reconhecidos pelo sistema em texto, formando uma frase conforme a tradução acontece.</div>
    </div>

    <div class="tool-block reveal">
      <div class="tool-visual">
        <div class="tool-badge">Tempo real</div>
        <div class="tool-visual-ico"><i class="fa-solid fa-video" style="color: #fdbe00;"></i>
</div>
        <div class="tool-visual-lbl">Captura e interpretação de sinais</div>
      </div>
      <div class="tool-text">
        <div class="tool-chip">Tecnologia assistiva</div>
        <div class="tool-h3">Da câmera para a comunicação</div>
        <p class="tool-p">
          O Leitor utiliza a câmera do dispositivo para capturar os sinais e enviar os quadros ao motor de tradução do LibrasHub. A inteligência artificial analisa os padrões reconhecidos e devolve o resultado em texto.
        </p>
        <div class="tool-steps">
          <div class="tool-step">
            <div class="step-num green">1</div>
            <div class="step-txt"><strong>Capture:</strong> abra o Leitor e permita o acesso à câmera do dispositivo.</div>
          </div>
          <div class="tool-step">
            <div class="step-num green">2</div>
            <div class="step-txt"><strong>Sinalize:</strong> mantenha as mãos visíveis e faça o sinal de forma clara para facilitar a leitura.</div>
          </div>
          <div class="tool-step">
            <div class="step-num green">3</div>
            <div class="step-txt"><strong>Processe:</strong> o motor de IA analisa a sequência capturada e identifica o sinal reconhecido.</div>
          </div>
          <div class="tool-step">
            <div class="step-num green">4</div>
            <div class="step-txt"><strong>Comunique:</strong> acompanhe a palavra e a frase formada na tela e use a leitura em voz alta quando necessário.</div>
          </div>
        </div>
        <a class="btn-tool btn-tool-green" href="leitor.php">
          <i class="fa-solid fa-video"></i> Começar tradução agora
        </a>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- ── SEÇÃO: UPLOAD ────────────────────────────────── -->
  <div class="section">
    <div class="reveal">
      <div class="eyebrow">Análise de mídia</div>
      <div class="section-h2">Tradução por Upload</div>
      <div class="section-desc">Já possui uma imagem ou vídeo com sinais de Libras? Envie o arquivo para o sistema analisar sem precisar usar a câmera ao vivo.</div>
    </div>

    <div class="tool-block flip reveal">
      <div class="tool-visual">
        <div class="tool-badge">Sem login</div>
        <div class="tool-visual-ico">⬆</div>
        <div class="tool-visual-lbl">Imagem ou vídeo para análise</div>
      </div>
      <div class="tool-text">
        <div class="tool-chip">Processamento inteligente</div>
        <div class="tool-h3">Analise conteúdo já gravado</div>
        <p class="tool-p">
          O módulo de Upload permite enviar mídias que já estão no dispositivo. O arquivo segue para o mesmo ecossistema de reconhecimento do LibrasHub, mantendo a tradução centralizada na plataforma.
        </p>
        <div class="tool-steps">
          <div class="tool-step">
            <div class="step-num green">1</div>
            <div class="step-txt"><strong>Escolha:</strong> selecione ou arraste a imagem ou vídeo que deseja analisar.</div>
          </div>
          <div class="tool-step">
            <div class="step-num green">2</div>
            <div class="step-txt"><strong>Envie:</strong> confira o arquivo selecionado e inicie a tradução pela própria página de Upload.</div>
          </div>
          <div class="tool-step">
            <div class="step-num green">3</div>
            <div class="step-txt"><strong>Receba:</strong> acompanhe o processamento e visualize os sinais reconhecidos pelo sistema.</div>
          </div>
        </div>
        <a class="btn-tool btn-tool-green" href="upload.php">
          <i class="fa-solid fa-upload"></i> Traduzir um arquivo
        </a>
      </div>
    </div>
  </div>

  <!-- ── SEÇÃO: DICAS ─────────────────────────────────── -->
  <div class="stats-strip">
    <div class="comm-inner">
      <div class="reveal">
        <div class="eyebrow">Por trás do LibrasHub</div>
        <div class="section-h2">Tecnologia pensada para acessibilidade</div>
        <div class="section-desc">O projeto combina reconhecimento visual, inteligência artificial e recursos comunitários para reduzir barreiras de comunicação.</div>
      </div>
      <div class="tips-grid">
        <div class="tip-card reveal reveal-delay-1">
          <span class="tip-ico"><i class="fa-regular fa-lightbulb" style="color: #fdbe00;"></i></span>
          <div class="tip-title">Visão Computacional</div>
          <div class="tip-desc">A câmera fornece os quadros usados pelo sistema para localizar e interpretar características dos sinais.</div>
        </div>
        <div class="tip-card reveal reveal-delay-2">
          <span class="tip-ico"><i class="fa-solid fa-hand" style="color: #fdbe00;"></i></span>
          <div class="tip-title">Inteligência Artificial</div>
          <div class="tip-desc">O modelo treinado identifica padrões das sequências capturadas e retorna os gestos reconhecidos.</div>
        </div>
        <div class="tip-card reveal reveal-delay-3">
          <span class="tip-ico"><i class="fa-solid fa-bullseye" style="color: #fdbe00;"></i></span>
          <div class="tip-title">Acessibilidade</div>
          <div class="tip-desc">Tema escuro, alto contraste, ajuste de fonte e leitura em voz alta ajudam a adaptar a experiência ao usuário.</div>
        </div>
        <div class="tip-card reveal reveal-delay-4">
          <span class="tip-ico"><i class="fa-solid fa-ruler" style="color: #fdbe00;"></i></span>
          <div class="tip-title">Comunidade</div>
          <div class="tip-desc">Tecnologia e participação caminham juntas: a plataforma também cria espaço para troca de experiências e conhecimento.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── SEÇÃO: COMUNIDADE ────────────────────────────── -->
  <div class="comm-wrap">
    <div class="comm-inner">
      <div class="reveal">
        <div class="eyebrow">Conexão e inclusão</div>
        <div class="section-h2">Comunidade LibrasHub</div>
        <div class="section-desc">
          Mais do que traduzir sinais, o LibrasHub cria um espaço para pessoas surdas, estudantes, intérpretes e apoiadores compartilharem conhecimento e experiências.
        </div>
      </div>
      <div class="comm-grid">
        <div class="comm-features reveal">
          <div class="comm-feat">
            <div class="comm-ico"><i class="fa-solid fa-bullhorn" style="color: #fdbe00;"></i></div>
            <div>
              <div class="comm-feat-title">Conversas e experiências</div>
              <div class="comm-feat-desc">Compartilhe dúvidas, vivências, aprendizados e informações relevantes para a comunidade.</div>
            </div>
          </div>
          <div class="comm-feat">
            <div class="comm-ico"><i class="fa-solid fa-calendar" style="color: #fdbe00;"></i></div>
            <div>
              <div class="comm-feat-title">Aprendizado coletivo</div>
              <div class="comm-feat-desc">Aprenda com outras pessoas e ajude quem está começando a conhecer Libras e acessibilidade.</div>
            </div>
          </div>
          <div class="comm-feat">
            <div class="comm-ico"><i class="fa-solid fa-magnifying-glass" style="color: #fdbe00;"></i></div>
            <div>
              <div class="comm-feat-title">Conteúdo acessível</div>
              <div class="comm-feat-desc">Encontre discussões e publicações reunidas em um ambiente conectado ao restante da plataforma.</div>
            </div>
          </div>
          <div class="comm-feat">
            <div class="comm-ico"><i class="fa-solid fa-heart" style="color: #fdbe00;"></i></div>
            <div>
              <div class="comm-feat-title">Participação ativa</div>
              <div class="comm-feat-desc">Responda, interaja e contribua para fortalecer uma rede mais inclusiva e colaborativa.</div>
            </div>
          </div>
        </div>

        <div class="lock-card reveal reveal-delay-2">
          <span class="lock-icon"><i class="fa-solid fa-users" style="color: #fdbe00;"></i></span>
          <div class="lock-title">Você já está conectado ao LibrasHub</div>
          <div class="lock-desc">
            Use sua conta atual para entrar na comunidade, participar das conversas e acompanhar os recursos do seu perfil.
          </div>
          <div class="lock-btns">
            <a class="btn-lock-primary" href="comunidade.php">Abrir Comunidade</a>
            <a class="btn-lock-outline" href="usuario.php">Ver meu perfil</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── FOOTER ────────────────────────────────────────── -->
  <footer class="hw-footer" role="contentinfo">
    <div class="footer-logo">
      <img src="../static/images/librashub-logo.png" alt="" class="logo-img" style="width:22px;height:22px;">
      LibrasHub
    </div>
    <div class="footer-links">
      <a href="ajuda.php">Ajuda</a>
      <a href="configuracoes.php">Configurações</a>
      <a href="comunidade.php">Comunidade</a>
    </div>
    <div class="footer-copy">© 2026 LibrasHub</div>
  </footer>

</div><!-- /.content -->

<!-- ── MENU MOBILE ───────────────────────────────────── -->
<button class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebarNav">&#9776;</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
/* ── hambúrguer ── */
(function(){
  var btn = document.getElementById('menuToggle');
  var sidebar = document.querySelector('.sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  if(!btn || !sidebar) return;
  function openMenu(){
    sidebar.classList.add('open');
    overlay.classList.add('open');
    btn.innerHTML='&#10005;';
    btn.setAttribute('aria-expanded', 'true');
    btn.setAttribute('aria-label', 'Fechar menu');
  }
  function closeMenu(){
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    btn.innerHTML='&#9776;';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-label', 'Abrir menu');
  }
  btn.addEventListener('click', function(){ sidebar.classList.contains('open') ? closeMenu() : openMenu(); });
  overlay.addEventListener('click', closeMenu);
  sidebar.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', closeMenu); });
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
  window.addEventListener('resize', function(){ if(window.innerWidth > 900) closeMenu(); });
})();

/* ── nav ativo ── */
(function(){
  var path = window.location.pathname.split('/').pop().replace(/\.php$/,'').replace(/\.html$/,'');
  if(path===''||path==='index') path='home';
  document.querySelectorAll('.sidebar .nav-item[data-page]').forEach(function(a){
    if(a.dataset.page===path) a.classList.add('active');
  });
})();


</script>

</body>
</html>