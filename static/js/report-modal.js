(function () {
  "use strict";

  const TYPE_LABELS = {
    post: "publicação",
    comentario: "comentário",
    usuario: "perfil",
    comunidade: "comunidade",
    local: "local"
  };

  const REASONS = [
    ["spam", "Spam ou conteúdo repetitivo", "Divulgação abusiva, fraude ou conteúdo repetido."],
    ["odio", "Discurso de ódio", "Ataque a pessoas ou grupos com base em características protegidas."],
    ["assedio", "Assédio ou intimidação", "Ameaças, perseguição, humilhação ou ataques direcionados."],
    ["violencia", "Violência ou ameaça", "Ameaça de agressão, incentivo à violência ou conteúdo perigoso."],
    ["conteudo_improprio", "Conteúdo impróprio", "Conteúdo sexual, gráfico ou inadequado para a plataforma."],
    ["outro", "Outro motivo", "Use os detalhes para explicar o problema."]
  ];

  let config = null;
  let target = null;
  let lastTrigger = null;
  let submitting = false;

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>'"]/g, char => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "'": "&#39;",
      '"': "&quot;"
    })[char]);
  }

  function deriveLabel(trigger, type) {
    const explicit = String(trigger?.dataset?.reportLabel || "").trim();
    if (explicit) return explicit.slice(0, 180);

    const scope = trigger?.closest(
      "[data-post],[data-community-post],[data-profile-post],[data-comment],[data-community-comment],[data-profile-comment],.community-hero,.profile-hero,.profile-card"
    );
    const title = scope?.querySelector("h1,h2,strong,a")?.textContent?.trim();
    if (title) return `${TYPE_LABELS[type] || "conteúdo"}: ${title}`.slice(0, 180);

    return `Denúncia de ${TYPE_LABELS[type] || "conteúdo"}`;
  }

  function getFocusable(container) {
    return [...container.querySelectorAll(
      'button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),a[href],[tabindex]:not([tabindex="-1"])'
    )].filter(element => !element.hidden && element.getClientRects().length > 0);
  }

  function mount() {
    if (document.getElementById("reportModal")) return;

    const reasonMarkup = REASONS.map(([value, title, description], index) => `
      <label class="report-reason">
        <input type="radio" name="reason" value="${value}" ${index === 0 ? "" : ""} required>
        <span class="report-reason__body">
          <strong>${escapeHtml(title)}</strong>
          <small>${escapeHtml(description)}</small>
        </span>
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      </label>
    `).join("");

    document.body.insertAdjacentHTML("beforeend", `
      <div class="report-modal" id="reportModal" hidden>
        <section class="report-modal__card" id="reportDialog" role="dialog" aria-modal="true" aria-labelledby="reportTitle" aria-describedby="reportIntro">
          <header class="report-modal__header">
            <div class="report-modal__heading">
              <span class="report-modal__icon" aria-hidden="true"><i class="fa-solid fa-flag"></i></span>
              <div>
                <strong id="reportTitle">Fazer denúncia</strong>
                <small id="reportTargetLabel"></small>
              </div>
            </div>
            <button type="button" class="report-modal__close" data-report-close aria-label="Fechar denúncia">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </header>

          <p class="report-modal__intro" id="reportIntro">A denúncia será enviada à moderação do LibrasHub. A pessoa ou comunidade denunciada não verá quem enviou a denúncia.</p>

          <form id="reportForm" novalidate>
            <fieldset class="report-reasons">
              <legend>O que aconteceu?</legend>
              ${reasonMarkup}
            </fieldset>

            <label class="report-details" for="reportDetails">
              <span>Detalhes <small>(opcional, exceto em “Outro motivo”)</small></span>
              <textarea id="reportDetails" name="details" maxlength="1000" rows="4" placeholder="Explique o que aconteceu e onde ocorreu."></textarea>
              <span class="report-details__counter"><output id="reportDetailsCount">0</output>/1000</span>
            </label>

            <div class="report-modal__notice">
              <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
              <p>Envie denúncias apenas quando houver uma possível violação. O envio repetido da mesma denúncia atualiza o registro existente para nova análise.</p>
            </div>

            <p class="report-modal__status" id="reportStatus" role="status" aria-live="polite"></p>

            <footer class="report-modal__footer">
              <button type="button" class="ui-button ui-button--secondary" data-report-close>Cancelar</button>
              <button type="submit" class="ui-button report-submit">
                <i class="fa-regular fa-flag" aria-hidden="true"></i>
                <span>Enviar denúncia</span>
              </button>
            </footer>
          </form>
        </section>
      </div>
    `);

    const modal = document.getElementById("reportModal");
    const dialog = document.getElementById("reportDialog");
    const form = document.getElementById("reportForm");
    const status = document.getElementById("reportStatus");
    const details = document.getElementById("reportDetails");
    const detailsCount = document.getElementById("reportDetailsCount");

    function resetForm() {
      form.reset();
      detailsCount.textContent = "0";
      status.textContent = "";
      status.className = "report-modal__status";
      form.querySelectorAll("input,textarea,button").forEach(control => { control.disabled = false; });
      const submit = form.querySelector('.report-submit');
      submit.innerHTML = '<i class="fa-regular fa-flag" aria-hidden="true"></i><span>Enviar denúncia</span>';
      submitting = false;
    }

    function close({ restoreFocus = true } = {}) {
      if (modal.hidden) return;
      modal.hidden = true;
      document.body.classList.remove("report-modal-open");
      target = null;
      resetForm();
      if (restoreFocus && lastTrigger?.isConnected) {
        requestAnimationFrame(() => lastTrigger.focus({ preventScroll: true }));
      }
      lastTrigger = null;
    }

    function open(trigger) {
      const type = String(trigger.dataset.reportType || "");
      const id = Number(trigger.dataset.reportId || 0);
      if (!TYPE_LABELS[type] || !Number.isInteger(id) || id < 1) return;

      target = { type, id, source: String(trigger.dataset.reportSource || (location.pathname + location.search + location.hash)).slice(0, 500) };
      lastTrigger = trigger;
      resetForm();
      document.getElementById("reportTargetLabel").textContent = deriveLabel(trigger, type);
      document.getElementById("reportTitle").textContent = `Denunciar ${TYPE_LABELS[type]}`;
      modal.hidden = false;
      document.body.classList.add("report-modal-open");
      requestAnimationFrame(() => form.querySelector('input[name="reason"]')?.focus());
    }

    document.addEventListener("click", event => {
      const trigger = event.target.closest("[data-report-type][data-report-id]");
      if (trigger) {
        event.preventDefault();
        event.stopPropagation();
        open(trigger);
        return;
      }

      if (!modal.hidden && (event.target.closest("[data-report-close]") || event.target === modal)) {
        event.preventDefault();
        close();
      }
    });

    document.addEventListener("keydown", event => {
      if (modal.hidden) return;
      if (event.key === "Escape") {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== "Tab") return;

      const focusable = getFocusable(dialog);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    details.addEventListener("input", () => {
      detailsCount.textContent = String(details.value.length);
    });

    form.addEventListener("change", event => {
      if (event.target.name !== "reason") return;
      if (event.target.value === "outro") {
        details.setAttribute("required", "required");
        details.placeholder = "Explique o motivo da denúncia (mínimo de 10 caracteres).";
      } else {
        details.removeAttribute("required");
        details.placeholder = "Explique o que aconteceu e onde ocorreu.";
      }
    });

    form.addEventListener("submit", async event => {
      event.preventDefault();
      if (!target || !config || submitting) return;

      const reason = form.querySelector('input[name="reason"]:checked')?.value || "";
      const detailText = details.value.trim();
      if (!reason) {
        status.textContent = "Selecione um motivo para continuar.";
        status.className = "report-modal__status is-error";
        form.querySelector('input[name="reason"]')?.focus();
        return;
      }
      if (reason === "outro" && detailText.length < 10) {
        status.textContent = "Explique o motivo em pelo menos 10 caracteres.";
        status.className = "report-modal__status is-error";
        details.focus();
        return;
      }

      const submit = form.querySelector('.report-submit');
      submitting = true;
      submit.disabled = true;
      submit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Enviando...</span>';
      status.textContent = "Enviando denúncia para a moderação...";
      status.className = "report-modal__status";

      try {
        const response = await fetch(config.api + "?action=report", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            csrf: config.csrf,
            type: target.type,
            id: target.id,
            reason,
            details: detailText,
            source: target.source
          })
        });
        const result = await response.json().catch(() => ({ ok: false, message: "Resposta inválida do servidor." }));
        if (!response.ok || !result.ok) throw new Error(result.message || "Não foi possível enviar a denúncia.");

        status.textContent = result.message || "Denúncia enviada para análise.";
        status.className = "report-modal__status is-success";
        form.querySelectorAll("input,textarea").forEach(control => { control.disabled = true; });
        submit.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i><span>Denúncia enviada</span>';
        setTimeout(() => close(), 1300);
      } catch (error) {
        status.textContent = error.message || "Não foi possível enviar a denúncia.";
        status.className = "report-modal__status is-error";
        submit.disabled = false;
        submit.innerHTML = '<i class="fa-regular fa-flag" aria-hidden="true"></i><span>Tentar novamente</span>';
        submitting = false;
      }
    });
  }

  window.LibrasHubReports = {
    init(options) {
      config = options || null;
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", mount, { once: true });
      } else {
        mount();
      }
    }
  };
})();
