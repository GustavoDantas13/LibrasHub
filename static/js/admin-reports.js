(() => {
  "use strict";
  const root = document.querySelector("[data-admin-reports]");
  if (!root) return;
  const apiUrl = root.dataset.api;
  const csrf = root.dataset.csrf;
  const list = root.querySelector("[data-report-list]");
  const statusText = root.querySelector("[data-report-status-text]");
  let filter = "pendente";
  let loaded = false;
  const escapeHtml = (value) => String(value ?? "").replace(/[&<>'"]/g, (char) => ({"&":"&amp;","<":"&lt;",">":"&gt;","'":"&#39;",'"':"&quot;"}[char]));
  const labels = {post:"Publicação",comentario:"Comentário",usuario:"Pessoa",comunidade:"Comunidade"};
  const statusLabels = {pendente:"Pendente",analise:"Em análise",resolvida:"Resolvida",arquivada:"Arquivada"};
  const reasonLabels = {spam:"Spam ou conteúdo repetitivo",odio:"Discurso de ódio",assedio:"Assédio ou intimidação",violencia:"Violência ou ameaça",conteudo_improprio:"Conteúdo impróprio",outro:"Outro motivo"};
  async function request(action, options = {}) {
    const response = await fetch(`${apiUrl}?${new URLSearchParams({action, ...(options.query || {})})}`, options.fetch);
    const result = await response.json().catch(() => ({ok:false,message:"Resposta inválida do servidor."}));
    if (!response.ok || !result.ok) throw new Error(result.message || "Falha na operação.");
    return result;
  }
  function card(report) {
    const id = Number(report.id_denuncia);
    const targetUrl = String(report.target_url || "").trim();
    return `<article class="moderation-card admin-card" data-report-card="${id}"><header><div class="moderation-card__identity"><span class="moderation-type-icon"><i class="fa-solid fa-flag"></i></span><div><div class="moderation-card__badges"><span class="moderation-badge">${escapeHtml(labels[report.tipo_alvo] || "Conteúdo")}</span><span class="moderation-badge is-${escapeHtml(report.status)}">${escapeHtml(statusLabels[report.status] || report.status)}</span></div><h2>${escapeHtml(report.alvo_titulo || `Conteúdo #${report.id_alvo}`)}</h2></div></div><span class="moderation-id">#${id}</span></header><div class="moderation-meta"><span>Denunciado por <strong>${escapeHtml(report.denunciante_nome)}</strong></span><span>${escapeHtml(report.criado_em)}</span>${report.moderador_nome ? `<span>Moderador: <strong>${escapeHtml(report.moderador_nome)}</strong></span>` : ""}</div><section class="moderation-summary"><strong>${escapeHtml(reasonLabels[report.motivo] || report.motivo)}</strong>${report.alvo_resumo ? `<p class="moderation-target-summary">${escapeHtml(report.alvo_resumo)}</p>` : ""}${report.detalhes ? `<div class="moderation-user-details"><span>Detalhes enviados</span><p>${escapeHtml(report.detalhes)}</p></div>` : ""}${targetUrl ? `<a class="moderation-target-link" href="${escapeHtml(targetUrl)}">Abrir conteúdo</a>` : ""}</section><form class="moderation-form"><label>Status<select name="status">${Object.entries(statusLabels).map(([value,label]) => `<option value="${value}" ${report.status === value ? "selected" : ""}>${label}</option>`).join("")}</select></label><label class="moderation-resolution">Observação da moderação<textarea name="resolution" maxlength="1000" rows="3">${escapeHtml(report.resolucao || "")}</textarea></label><div class="moderation-form__footer"><span data-save-status></span><button class="btn-primary" type="submit">Salvar decisão</button></div></form></article>`;
  }
  async function load() {
    loaded = true;
    statusText.textContent = "Carregando denúncias...";
    try {
      const result = await request("moderation_reports", {query:{status:filter}});
      Object.entries(result.counts || {}).forEach(([key,value]) => { const node = root.querySelector(`[data-report-count="${key}"]`); if (node) node.textContent = String(value); });
      const reports = Array.isArray(result.reports) ? result.reports.filter((report) => report && report.id_denuncia) : [];
      list.innerHTML = reports.length ? reports.map(card).join("") : '<div class="moderation-empty">Nenhuma denúncia nesta fila.</div>';
      statusText.textContent = `${reports.length} denúncia(ões) exibida(s).`;
    } catch (error) {
      list.innerHTML = `<div class="moderation-empty">${escapeHtml(error.message)}</div>`;
      statusText.textContent = "Não foi possível carregar as denúncias.";
    }
  }
  root.addEventListener("click", (event) => {
    const button = event.target.closest("[data-report-status]");
    if (!button) return;
    filter = button.dataset.reportStatus;
    root.querySelectorAll("[data-report-status]").forEach((item) => item.classList.toggle("is-active", item === button));
    load();
  });
  root.querySelector("[data-report-refresh]").addEventListener("click", load);
  list.addEventListener("submit", async (event) => {
    const form = event.target.closest("form");
    if (!form) return;
    event.preventDefault();
    const report = Number(form.closest("[data-report-card]").dataset.reportCard);
    const saveStatus = form.querySelector("[data-save-status]");
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    saveStatus.textContent = "Salvando...";
    try {
      await request("moderation_report_update", {fetch:{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({csrf,report,status:form.elements.status.value,resolution:form.elements.resolution.value.trim()})}});
      await load();
    } catch (error) {
      saveStatus.textContent = error.message;
      submit.disabled = false;
    }
  });
  const observer = new MutationObserver(() => {
    if (!loaded && document.getElementById("tab-denuncias")?.classList.contains("active")) load();
  });
  observer.observe(document.getElementById("tab-denuncias"), {attributes:true,attributeFilter:["class"]});
  if (document.getElementById("tab-denuncias")?.classList.contains("active")) load();
})();
