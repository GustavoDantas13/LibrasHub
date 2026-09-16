/** LibrasHub — central global de acessibilidade. */
(function () {
  "use strict";
  const KEY = "librashub_accessibility_v2";
  const defaults = { theme: "auto", font: 100, contrast: false, filter: "none", readable: false, underline: false, motion: false, cursor: false };
  let state = load();
  let speaking = false;
  let activeUtterance = null;
  let speechWatchdog = null;

  function ensureStylesheet() {
    if (document.querySelector('link[href*="/static/css/accessibility.css"],link[href$="static/css/accessibility.css"]')) return;
    const source = document.currentScript?.src || [...document.scripts].find(script => script.src.includes("/static/js/acessibility.js"))?.src;
    if (!source) return;
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = new URL("../css/accessibility.css", source).href;
    link.dataset.librashubAccessibility = "true";
    document.head.appendChild(link);
  }
  ensureStylesheet();

  function load() { try { return Object.assign({}, defaults, JSON.parse(localStorage.getItem(KEY) || "{}")); } catch (_) { return { ...defaults }; } }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (_) {} }
  function theme() { return state.theme === "auto" ? (matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light") : state.theme; }
  function icon(name) { return `<i class="fa-solid fa-${name}" aria-hidden="true"></i>`; }
  function action(iconName, label, name, toggle = true) {
    return `<button type="button" class="a11y-action" data-a11y="${name}"${toggle ? ' aria-pressed="false"' : ""}>${icon(iconName)}<span>${label}</span></button>`;
  }
  function apply() {
    const root = document.documentElement;
    root.dataset.theme = theme();
    root.dataset.colorFilter = state.filter;
    root.dataset.fontLevel = state.font >= 130 ? "max" : (state.font <= 90 ? "min" : "normal");
    root.style.setProperty("--font-scale", state.font / 100);
    ["contrast", "readable", "underline", "motion", "cursor"].forEach(key => root.classList.toggle("a11y-" + key, state[key]));
    root.classList.toggle("high-contrast", state.contrast);
    update(); save();
  }
  function mount() {
    if (document.getElementById("a11yPanel")) return;
    let toggle = document.getElementById("a11yToggle");
    const toggleMarkup = toggle ? "" : `<button id="a11yToggle" class="a11y-toggle" type="button" aria-label="Abrir recursos de acessibilidade" aria-expanded="false" aria-controls="a11yPanel">${icon("universal-access")}<span>Acessibilidade</span></button>`;
    document.body.insertAdjacentHTML("afterbegin", `<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>${toggleMarkup}<aside id="a11yPanel" class="a11y-panel" aria-label="Recursos de acessibilidade" aria-hidden="true"><header><div><strong>Acessibilidade</strong><small>Personalize sua experiência</small></div><button type="button" data-a11y="close" aria-label="Fechar">${icon("xmark")}</button></header><div class="a11y-font" role="group" aria-label="Tamanho do texto"><button data-a11y="fontDown" aria-label="Diminuir fonte">A−</button><output id="a11yFont">100%</output><button data-a11y="fontUp" aria-label="Aumentar fonte">A+</button></div><div class="a11y-grid">${action("circle-half-stroke", "Tema", "theme", false)}${action("circle", "Contraste", "contrast")}${action("volume-high", "Ler página", "speak")}${action("arrow-pointer", "Ler seleção", "select")}${action("book-open-reader", "Leitura fácil", "readable")}${action("underline", "Destacar links", "underline")}${action("person-walking-arrow-loop-left", "Menos animação", "motion")}${action("arrow-pointer", "Cursor ampliado", "cursor")}</div><label class="a11y-select">Filtro para daltonismo<select data-a11y="filter"><option value="none">Sem filtro</option><option value="protanopia">Protanopia</option><option value="deuteranopia">Deuteranopia</option><option value="tritanopia">Tritanopia</option><option value="grayscale">Escala de cinza</option></select></label><button class="a11y-reset" type="button" data-a11y="reset">${icon("rotate-left")} Restaurar preferências</button><p class="a11y-hint">Atalho: Alt + A</p></aside><div class="a11y-toast" role="status" aria-live="polite"></div><div id="a11yCursor" class="a11y-cursor-indicator" aria-hidden="true">${icon("arrow-pointer")}</div>`);
    toggle = document.getElementById("a11yToggle");
    toggle.addEventListener("click", panel);
    const main = document.querySelector("main,.content,[role=main]"); if (main && !main.id) main.id = "conteudo-principal";
    document.addEventListener("click", click);
    document.querySelector('[data-a11y="filter"]').addEventListener("change", event => { state.filter = event.target.value; apply(); });
    document.addEventListener("keydown", event => { if (event.altKey && event.key.toLowerCase() === "a") { event.preventDefault(); panel(); } if (event.key === "Escape") close(); });
    document.addEventListener("mouseup", event => { if (!document.documentElement.classList.contains("a11y-select-mode") || speaking || event.target.closest(".a11y-panel,.a11y-toggle")) return; const text = String(getSelection()).trim(); if (text) speak(text, true); });
    document.addEventListener("pointermove", event => { const cursor = document.getElementById("a11yCursor"); if (cursor) cursor.style.transform = `translate3d(${event.clientX}px,${event.clientY}px,0)`; }, { passive: true });
    loadVLibras(); apply();
  }
  function click(event) {
    const button = event.target.closest("[data-a11y]"); if (!button) return;
    const actionName = button.dataset.a11y;
    if (actionName === "close") close();
    else if (actionName === "fontDown") { state.font = Math.max(80, state.font - 10); apply(); note(`Fonte em ${state.font}%`); }
    else if (actionName === "fontUp") { state.font = Math.min(150, state.font + 10); apply(); note(`Fonte em ${state.font}%`); }
    else if (actionName === "theme") { state.theme = theme() === "dark" ? "light" : "dark"; apply(); }
    else if (["contrast", "readable", "underline", "motion", "cursor"].includes(actionName)) { state[actionName] = !state[actionName]; apply(); }
    else if (actionName === "speak") { speaking ? stopSpeech() : speak((document.querySelector("main") || document.body).innerText); }
    else if (actionName === "select") { const enabled = !document.documentElement.classList.contains("a11y-select-mode"); if (!enabled && speaking) stopSpeech(); document.documentElement.classList.toggle("a11y-select-mode", enabled); update(); note(enabled ? "Selecione um texto para ouvir" : "Leitura por seleção desativada"); }
    else if (actionName === "reset") { state = { ...defaults }; stopSpeech(false); apply(); note("Preferências restauradas"); }
  }
  function panel() { const item = document.getElementById("a11yPanel"); const open = item.getAttribute("aria-hidden") === "true"; item.setAttribute("aria-hidden", String(!open)); item.classList.toggle("open", open); document.getElementById("a11yToggle").setAttribute("aria-expanded", String(open)); if (open) item.querySelector("button").focus(); }
  function close() { const item = document.getElementById("a11yPanel"); if (!item) return; item.setAttribute("aria-hidden", "true"); item.classList.remove("open"); document.getElementById("a11yToggle").setAttribute("aria-expanded", "false"); }
  function stopSpeech(showNote = true) { activeUtterance = null; clearInterval(speechWatchdog); speechWatchdog = null; if ("speechSynthesis" in window) speechSynthesis.cancel(); speaking = false; document.documentElement.classList.remove("a11y-select-mode"); update(); if (showNote) note("Leitura interrompida"); }
  function speak(text, selectionMode = false) { if (!("speechSynthesis" in window)) { note("Leitura em voz alta indisponível"); return; } const content = String(text || "").trim(); if (!content) { note("Nenhum texto disponível para leitura"); return; } activeUtterance = null; clearInterval(speechWatchdog); speechSynthesis.cancel(); const utterance = new SpeechSynthesisUtterance(content.slice(0, 12000)); activeUtterance = utterance; utterance.lang = "pt-BR"; utterance.rate = .95; const finish = event => { if (activeUtterance !== utterance) return; activeUtterance = null; clearInterval(speechWatchdog); speechWatchdog = null; speaking = false; if (selectionMode) document.documentElement.classList.remove("a11y-select-mode"); update(); note(event.type === "end" ? "Leitura concluída" : "Leitura encerrada"); }; utterance.onstart = () => { if (activeUtterance !== utterance) return; speaking = true; update(); note("Leitura iniciada"); speechWatchdog = setInterval(() => { if (activeUtterance === utterance && !speechSynthesis.speaking && !speechSynthesis.pending) finish({type:"end"}); }, 500); }; utterance.onend = finish; utterance.onerror = finish; speechSynthesis.speak(utterance); }
  function note(message) { const toast = document.querySelector(".a11y-toast"); if (!toast) return; toast.textContent = message; toast.classList.add("show"); clearTimeout(toast._timer); toast._timer = setTimeout(() => toast.classList.remove("show"), 2500); }
  function update() { if (!document.body) return; ["contrast", "readable", "underline", "motion", "cursor"].forEach(key => { const button = document.querySelector(`[data-a11y="${key}"]`); if (button) button.setAttribute("aria-pressed", String(state[key])); }); const speech = document.querySelector('[data-a11y="speak"]'); if (speech) speech.setAttribute("aria-pressed", String(speaking)); const selection = document.querySelector('[data-a11y="select"]'); if (selection) selection.setAttribute("aria-pressed", String(document.documentElement.classList.contains("a11y-select-mode"))); const size = document.getElementById("a11yFont"); if (size) size.textContent = state.font + "%"; const filter = document.querySelector('[data-a11y="filter"]'); if (filter) filter.value = state.filter; }
  function loadVLibras() { if (document.querySelector("[vw]")) return; const container = document.createElement("div"); container.setAttribute("vw", ""); container.className = "enabled"; container.setAttribute("aria-label", "Tradução de conteúdo para Libras"); container.innerHTML = '<div vw-access-button class="active" role="button" tabindex="0" aria-label="Abrir tradutor de Libras" title="Traduzir esta página para Libras"></div><div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div>'; document.body.appendChild(container); const script = document.createElement("script"); script.src = "https://vlibras.gov.br/app/vlibras-plugin.js"; script.onload = () => { try { new window.VLibras.Widget("https://vlibras.gov.br/app"); } catch (_) {} }; document.body.appendChild(script); }
  window.LibrasA11y = { apply, setTheme: value => { state.theme = { claro: "light", escuro: "dark", automatico: "auto" }[value] || value; apply(); }, setFontSize: value => { state.font = { pequena: 90, media: 100, grande: 115 }[value] || Number(value) || 100; apply(); }, setContrast: value => { state.contrast = value === "on" || value === true; apply(); }, getTheme: () => state.theme, getFontSize: () => state.font, getContrast: () => state.contrast };
  try { matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => { if (state.theme === "auto") apply(); }); } catch (_) {}
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", mount); else mount();
})();
