<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/configs/config.php';
require_once __DIR__.'/configs/social_schema.php';

if (empty($_SESSION['usuario_id'])) { header('Location: Login.php?redirect=denuncias.php'); exit; }
$uid = (int)$_SESSION['usuario_id'];
$stmt = $pdo->prepare('SELECT nm_usuario,tp_usuario FROM usuario WHERE id_usuario=? LIMIT 1');
$stmt->execute([$uid]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me) { session_destroy(); header('Location: Login.php'); exit; }
$_SESSION['usuario_nome'] = $me['nm_usuario'];
$_SESSION['usuario_tipo'] = $me['tp_usuario'];
$ehAdmin = mb_strtolower(trim((string)$me['tp_usuario'])) === 'administrador';
if (!$ehAdmin) { http_response_code(403); ?><!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Acesso negado</title><body><p>Apenas administradores podem acessar a moderação de denúncias.</p><p><a href="home.php">Voltar ao início</a></p></body></html><?php exit; }
ensureSocialSchema($pdo);
if (empty($_SESSION['social_csrf'])) $_SESSION['social_csrf'] = bin2hex(random_bytes(24));
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Denúncias | LibrasHub</title>
  <link rel="icon" href="../static/images/librashub-logo.png">
  <link rel="stylesheet" href="../static/css/style.css">
  <link rel="stylesheet" href="../static/css/app-shell.css">
  <link rel="stylesheet" href="../static/css/sidebar.css">
  <link rel="stylesheet" href="../static/css/moderation.css?v=66">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="app-shell app-dashboard moderation-page">
<?php $sidebarId='sidebarMenu'; include __DIR__.'/partials/sidebar.php'; ?>
<main class="content" id="conteudo-principal">
  <section class="moderation-shell" aria-labelledby="moderationTitle">
    <header class="moderation-header">
      <div>
        <span class="ui-eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Moderação</span>
        <h1 id="moderationTitle">Denúncias</h1>
        <p>Analise denúncias de pessoas, comunidades, publicações e comentários.</p>
      </div>
      <button class="ui-button ui-button--secondary" type="button" id="refreshReports"><i class="fa-solid fa-rotate" aria-hidden="true"></i>Atualizar</button>
    </header>

    <nav class="moderation-filters" aria-label="Filtrar denúncias">
      <button type="button" class="moderation-filter is-active" data-status="pendente">Pendentes <span data-count="pendente">0</span></button>
      <button type="button" class="moderation-filter" data-status="analise">Em análise <span data-count="analise">0</span></button>
      <button type="button" class="moderation-filter" data-status="resolvida">Resolvidas <span data-count="resolvida">0</span></button>
      <button type="button" class="moderation-filter" data-status="arquivada">Arquivadas <span data-count="arquivada">0</span></button>
      <button type="button" class="moderation-filter" data-status="todas">Todas</button>
    </nav>

    <p class="moderation-status" id="moderationStatus" role="status" aria-live="polite"></p>
    <div class="moderation-list" id="moderationList"><div class="moderation-empty">Carregando denúncias...</div></div>
  </section>
</main>
<script>
const REPORT_API='ajax/social_api.php',REPORT_CSRF=<?= json_encode($_SESSION['social_csrf']) ?>;
let reportFilter='pendente';
const reportList=document.getElementById('moderationList'),reportStatus=document.getElementById('moderationStatus');
const escapeReport=value=>String(value??'').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
const typeMeta={post:['Publicação','fa-newspaper'],comentario:['Comentário','fa-comment'],usuario:['Pessoa','fa-user'],comunidade:['Comunidade','fa-users'],local:['Local','fa-location-dot']};
const reasonLabels={spam:'Spam ou conteúdo repetitivo',odio:'Discurso de ódio',assedio:'Assédio ou intimidação',violencia:'Violência ou ameaça',conteudo_improprio:'Conteúdo impróprio',outro:'Outro motivo'};
const statusLabels={pendente:'Pendente',analise:'Em análise',resolvida:'Resolvida',arquivada:'Arquivada'};
async function reportApi(action,options={}){const response=await fetch(REPORT_API+'?'+new URLSearchParams({action,...(options.query||{})}),options.fetch);const result=await response.json().catch(()=>({ok:false,message:'Resposta inválida.'}));if(!response.ok||!result.ok)throw new Error(result.message||'Falha na operação.');return result}
function formatDate(value){if(!value)return '—';const normalized=String(value).replace(' ','T');const date=new Date(normalized);return Number.isNaN(date.getTime())?escapeReport(value):date.toLocaleString('pt-BR',{dateStyle:'short',timeStyle:'short'})}
function renderReport(report){const [typeLabel,typeIcon]=typeMeta[report.tipo_alvo]||['Conteúdo','fa-flag'];const targetTitle=report.alvo_titulo||`${typeLabel} #${report.id_alvo}`;const targetUrl=String(report.target_url||'').trim();const targetLink=targetUrl?`<a class="moderation-target-link" href="${escapeReport(targetUrl)}"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Abrir conteúdo</a>`:'';return `<article class="moderation-card ui-card" data-report-card="${Number(report.id_denuncia)}">
<header><div class="moderation-card__identity"><span class="moderation-type-icon"><i class="fa-solid ${typeIcon}" aria-hidden="true"></i></span><div><div class="moderation-card__badges"><span class="moderation-badge">${typeLabel}</span><span class="moderation-badge is-${escapeReport(report.status)}">${statusLabels[report.status]||escapeReport(report.status)}</span></div><h2>${escapeReport(targetTitle)}</h2></div></div><span class="moderation-id">#${Number(report.id_denuncia)}</span></header>
<div class="moderation-meta"><span><i class="fa-regular fa-user" aria-hidden="true"></i>Denunciado por <strong>${escapeReport(report.denunciante_nome)}</strong></span><span><i class="fa-regular fa-clock" aria-hidden="true"></i>${formatDate(report.criado_em)}</span>${report.moderador_nome?`<span><i class="fa-solid fa-user-shield" aria-hidden="true"></i>Moderador: <strong>${escapeReport(report.moderador_nome)}</strong></span>`:''}</div>
<section class="moderation-summary"><strong>${escapeReport(reasonLabels[report.motivo]||report.motivo)}</strong>${report.alvo_resumo?`<p class="moderation-target-summary">${escapeReport(report.alvo_resumo)}</p>`:''}${report.detalhes?`<div class="moderation-user-details"><span>Detalhes enviados</span><p>${escapeReport(report.detalhes)}</p></div>`:''}${targetLink}${report.criador_nome?`<p><strong>Criador da comunidade:</strong> ${escapeReport(report.criador_nome)}</p>`:''}${(report.anexos||[]).map(a=>`<a href="ajax/denuncia_anexo.php?id=${Number(a.id_anexo)}" class="moderation-target-link">Anexo: ${escapeReport(a.nome_original)}</a>`).join(' ')}</section>
<form class="moderation-form" data-moderation-form><label>Status<select name="status"><option value="pendente" ${report.status==='pendente'?'selected':''}>Pendente</option><option value="analise" ${report.status==='analise'?'selected':''}>Em análise</option><option value="resolvida" ${report.status==='resolvida'?'selected':''}>Resolvida</option><option value="arquivada" ${report.status==='arquivada'?'selected':''}>Arquivada</option></select></label><label class="moderation-resolution">Observação da moderação<textarea name="resolution" maxlength="1000" rows="3" placeholder="Registre a análise ou a decisão tomada.">${escapeReport(report.resolucao||'')}</textarea></label><div class="moderation-form__footer"><span data-form-status role="status"></span><button class="ui-button" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Salvar decisão</button></div></form>
</article>`}
async function loadReports(){reportStatus.textContent='Carregando denúncias...';reportList.innerHTML='<div class="moderation-empty">Carregando denúncias...</div>';try{const result=await reportApi('moderation_reports',{query:{status:reportFilter}});Object.entries(result.counts||{}).forEach(([key,value])=>{const counter=document.querySelector(`[data-count="${key}"]`);if(counter)counter.textContent=String(value)});reportList.innerHTML=result.reports?.length?result.reports.map(renderReport).join(''):'<div class="moderation-empty"><i class="fa-regular fa-circle-check" aria-hidden="true"></i><strong>Nenhuma denúncia nesta fila.</strong><span>Quando houver registros com este status, eles aparecerão aqui.</span></div>';reportStatus.textContent=`${result.reports?.length||0} denúncia(ões) exibida(s).`}catch(error){reportList.innerHTML=`<div class="ui-alert ui-alert--error">${escapeReport(error.message)}</div>`;reportStatus.textContent='Não foi possível carregar as denúncias.'}}
document.querySelector('.moderation-filters').addEventListener('click',event=>{const button=event.target.closest('[data-status]');if(!button)return;reportFilter=button.dataset.status;document.querySelectorAll('.moderation-filter').forEach(item=>item.classList.toggle('is-active',item===button));loadReports()});
document.getElementById('refreshReports').addEventListener('click',loadReports);
reportList.addEventListener('submit',async event=>{const form=event.target.closest('[data-moderation-form]');if(!form)return;event.preventDefault();const card=form.closest('[data-report-card]'),button=form.querySelector('[type="submit"]'),status=form.querySelector('[data-form-status]'),payload={csrf:REPORT_CSRF,report:Number(card.dataset.reportCard),status:form.elements.status.value,resolution:form.elements.resolution.value.trim()};button.disabled=true;status.textContent='Salvando...';try{const result=await reportApi('moderation_report_update',{fetch:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}});status.textContent=result.message;await loadReports()}catch(error){status.textContent=error.message;button.disabled=false}});
loadReports();
</script>
<script src="../static/js/acessibility.js?v=67" defer></script>
</body>
</html>
