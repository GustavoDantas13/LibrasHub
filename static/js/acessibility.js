/** LibrasHub — central global de acessibilidade. */
(function () {
  "use strict";
  const KEY = "librashub_accessibility_v2";
  const defaults = { theme: "auto", font: 100, contrast: false, filter: "none", readable: false, underline: false, motion: false, cursor: false };
  const colorFilters = {
    none: "Cores originais da página.",
    protanopia: "Reforça a separação entre azul e amarelo para baixa percepção do vermelho.",
    deuteranopia: "Aumenta a distinção entre azul, amarelo e magenta para baixa percepção do verde.",
    tritanopia: "Reforça tons quentes e frios para baixa percepção de azul e amarelo.",
    vivid: "Eleva contraste e saturação sem simular um tipo específico de daltonismo.",
    grayscale: "Remove as cores para conferir se informações continuam distinguíveis sem cor."
  };
  const settingLabels = {
    contrast: "Alto contraste",
    readable: "Leitura fácil",
    underline: "Destaque de links",
    motion: "Redução de animações",
    cursor: "Cursor ampliado"
  };
  let state = load();
  let speaking = false;
  let activeUtterance = null;
  let speechWatchdog = null;
  let lastFocusedBeforePanel = null;
  let inertedBackgroundElements = [];
  let fallbackInertRecords = [];
  let landingScrollLockRecord = null;
  const systemDarkThemeQuery = typeof window.matchMedia === "function"
    ? window.matchMedia("(prefers-color-scheme: dark)")
    : null;

  function ensureStylesheet() {
    // Aceita caminho relativo/absoluto e query string (?v=...), evitando CSS duplicado.
    if (document.querySelector('link[href*="static/css/accessibility.css"]')) return;
    const source = document.currentScript?.src || [...document.scripts].find(script => script.src.includes("/static/js/acessibility.js"))?.src;
    if (!source) return;
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = new URL("../css/accessibility.css?v=63", source).href;
    link.dataset.librashubAccessibility = "true";
    document.head.appendChild(link);
  }
  ensureStylesheet();

  function normalizeState(candidate = {}) {
    const normalized = { ...defaults };
    const allowedThemes = new Set(["auto", "light", "dark"]);

    normalized.theme = allowedThemes.has(candidate.theme) ? candidate.theme : defaults.theme;

    const parsedFont = Number(candidate.font);
    normalized.font = Number.isFinite(parsedFont)
      ? Math.min(150, Math.max(80, Math.round(parsedFont / 10) * 10))
      : defaults.font;

    normalized.filter = Object.prototype.hasOwnProperty.call(colorFilters, candidate.filter)
      ? candidate.filter
      : defaults.filter;

    ["contrast", "readable", "underline", "motion", "cursor"].forEach(key => {
      normalized[key] = candidate[key] === true;
    });

    return normalized;
  }

  function loadLegacyPreferences() {
    const legacy = {};
    try {
      const legacyTheme = localStorage.getItem("libras_theme");
      const legacyFont = localStorage.getItem("libras_fontsize");
      const legacyContrast = localStorage.getItem("libras_contrast");

      legacy.theme = ({ claro: "light", escuro: "dark", automatico: "auto" })[legacyTheme];
      legacy.font = ({ pequena: 90, media: 100, grande: 110 })[legacyFont];
      legacy.contrast = legacyContrast === "on";
    } catch (_) {}
    return legacy;
  }

  function load() {
    try {
      const raw = localStorage.getItem(KEY);
      if (raw) return normalizeState(JSON.parse(raw));
      return normalizeState(loadLegacyPreferences());
    } catch (_) {
      return { ...defaults };
    }
  }

  function save() {
    try {
      const normalized = normalizeState(state);
      state = normalized;
      localStorage.setItem(KEY, JSON.stringify(normalized));

      /* Mantém páginas legadas sincronizadas; KEY continua sendo a fonte da verdade. */
      localStorage.setItem(
        "libras_theme",
        ({ light: "claro", dark: "escuro", auto: "automatico" })[normalized.theme] || "automatico"
      );
      localStorage.setItem(
        "libras_fontsize",
        normalized.font <= 90 ? "pequena" : normalized.font >= 110 ? "grande" : "media"
      );
      localStorage.setItem("libras_contrast", normalized.contrast ? "on" : "off");
    } catch (_) {}
  }
  function theme() {
    if (state.theme !== "auto") return state.theme;
    return systemDarkThemeQuery?.matches ? "dark" : "light";
  }
  function icon(name) { return `<i class="fa-solid fa-${name}" aria-hidden="true"></i>`; }
  function action(iconName, label, name, toggle = true) {
    return `<button type="button" class="a11y-action" data-a11y="${name}"${toggle ? ' aria-pressed="false"' : ""}>${icon(iconName)}<span>${label}</span></button>`;
  }
  function apply({ persist = true } = {}) {
    state = normalizeState(state);
    const root = document.documentElement;
    root.dataset.theme = theme();
    root.dataset.colorFilter = state.filter;
    root.dataset.fontLevel = state.font >= 130 ? "max" : (state.font <= 90 ? "min" : "normal");
    root.style.setProperty("--font-scale", state.font / 100);
    ["contrast", "readable", "underline", "motion", "cursor"].forEach(key => root.classList.toggle("a11y-" + key, state[key]));
    root.classList.toggle("high-contrast", state.contrast);
    update();
    if (persist) save();
  }
  function mount() {
    if (document.getElementById("a11yPanel")) return;
    const main = document.querySelector("main,.content,[role=main]");
    if (main && !main.id) main.id = "conteudo-principal";

    const isLanding = document.body.classList.contains("app-preview");
    const settingsHost = document.querySelector("[data-a11y-settings]");
    /* A landing usa um botão flutuante minimalista para abrir o painel. */
    const hasFloatingControls = isLanding && !settingsHost;
    const skipMarkup = document.querySelector(".skip-link")
      ? ""
      : `<a class="skip-link" href="#${main?.id || "conteudo-principal"}">Pular para o conteúdo</a>`;
    const landingExtras = isLanding
      ? `<section class="a11y-landing" aria-labelledby="a11yBlindTitle"><h2 id="a11yBlindTitle">Navegação para pessoas cegas</h2><p>A página possui estrutura para leitores de tela, foco visível, leitura em voz alta e atalhos de teclado.</p><div class="a11y-landing-actions"><button type="button" class="a11y-action" data-a11y="focusMain">${icon("arrow-down")}<span>Ir ao conteúdo</span></button><button type="button" class="a11y-action" data-a11y="shortcuts" aria-expanded="false" aria-controls="a11yShortcuts">${icon("keyboard")}<span>Ver atalhos</span></button></div><div id="a11yShortcuts" class="a11y-shortcuts" hidden><dl><div><dt><kbd>Alt</kbd> + <kbd>A</kbd></dt><dd>Acessibilidade</dd></div><div><dt><kbd>Alt</kbd> + <kbd>C</kbd></dt><dd>Conteúdo principal</dd></div><div><dt><kbd>Alt</kbd> + <kbd>M</kbd></dt><dd>Menu principal</dd></div><div><dt><kbd>Alt</kbd> + <kbd>V</kbd></dt><dd>Ouvir ou parar leitura</dd></div><div><dt><kbd>Alt</kbd> + <kbd>S</kbd></dt><dd>Suporte</dd></div><div><dt><kbd>Alt</kbd> + <kbd>E</kbd></dt><dd>Entrar</dd></div><div><dt><kbd>Esc</kbd></dt><dd>Fechar painéis</dd></div></dl></div></section>`
      : "";
    let toggle = document.getElementById("a11yToggle");
    const toggleMarkup = hasFloatingControls && !toggle ? `<button id="a11yToggle" class="a11y-toggle" type="button" aria-label="Opções de Acessibilidade" aria-expanded="false" aria-controls="a11yPanel" aria-haspopup="dialog" title="Opções de Acessibilidade"><i class="fa-solid fa-universal-access a11y-toggle__icon" aria-hidden="true"></i></button>` : "";
    const backdropMarkup = hasFloatingControls ? `<div id="a11yBackdrop" class="a11y-backdrop" hidden aria-hidden="true"></div>` : "";
    document.body.insertAdjacentHTML("afterbegin", `${skipMarkup}${toggleMarkup}${backdropMarkup}<aside id="a11yPanel" class="a11y-panel" role="dialog" aria-modal="${hasFloatingControls ? "true" : "false"}" aria-labelledby="a11yPanelTitle" aria-describedby="a11yPanelDescription" aria-hidden="true"><header><div><strong id="a11yPanelTitle">Acessibilidade</strong><small id="a11yPanelDescription">Personalize sua experiência</small></div><button type="button" data-a11y="close" aria-label="Fechar">${icon("xmark")}</button></header><div class="a11y-font" role="group" aria-label="Tamanho do texto"><button data-a11y="fontDown" aria-label="Diminuir fonte">A−</button><output id="a11yFont">100%</output><button data-a11y="fontUp" aria-label="Aumentar fonte">A+</button></div><div class="a11y-grid">${action("circle-half-stroke", "Tema", "theme", false)}${action("circle", "Contraste", "contrast")}${action("volume-high", "Ler página", "speak")}${action("arrow-pointer", "Ler seleção", "select")}${action("book-open-reader", "Leitura fácil", "readable")}${action("underline", "Destacar links", "underline")}${action("person-walking-arrow-loop-left", "Menos animação", "motion")}${action("arrow-pointer", "Cursor ampliado", "cursor")}</div><div class="a11y-color-filter"><label class="a11y-select">Filtro para daltonismo<select data-a11y="filter" aria-describedby="a11yFilterDescription"><option value="none">Sem filtro</option><option value="protanopia">Baixa percepção do vermelho</option><option value="deuteranopia">Baixa percepção do verde</option><option value="tritanopia">Baixa percepção de azul/amarelo</option><option value="vivid">Cores reforçadas</option><option value="grayscale">Visão monocromática</option></select></label><div class="a11y-filter-preview" aria-hidden="true"><span></span><span></span><span></span><span></span></div><p id="a11yFilterDescription" class="a11y-filter-description"></p></div>${landingExtras}<button class="a11y-reset" type="button" data-a11y="reset">${icon("rotate-left")} Restaurar preferências</button><p class="a11y-hint">Atalho: Alt + A</p></aside><div class="a11y-toast" role="status" aria-live="polite" aria-atomic="true"></div><div id="a11yLive" class="a11y-sr-only" role="status" aria-live="polite" aria-atomic="true"></div><div id="a11yCursor" class="a11y-cursor-indicator" aria-hidden="true">${icon("arrow-pointer")}</div>`);
    toggle = document.getElementById("a11yToggle");
    if (settingsHost) {
      const controls = document.getElementById("a11yPanel");
      settingsHost.appendChild(controls);
      controls.setAttribute("aria-hidden", "false");
    }
    toggle?.setAttribute("aria-keyshortcuts", "Alt+A");
    document.querySelector('[data-a11y="speak"]')?.setAttribute("aria-keyshortcuts", "Alt+V");
    toggle?.addEventListener("click", panel);
    document.addEventListener("click", click);
    document.querySelector('[data-a11y="filter"]').addEventListener("change", event => { state.filter = event.target.value; apply(); note(event.target.options[event.target.selectedIndex].text + " ativado"); });
    document.addEventListener("keydown", keyboard);
    document.addEventListener("mouseup", event => { if (!document.documentElement.classList.contains("a11y-select-mode") || speaking || event.target.closest(".a11y-panel,.a11y-toggle")) return; const text = String(getSelection()).trim(); if (text) speak(text, true); });
    document.addEventListener("pointermove", event => { const cursor = document.getElementById("a11yCursor"); if (cursor) cursor.style.transform = `translate3d(${event.clientX}px,${event.clientY}px,0)`; }, { passive: true });
    if (isLanding) {
      document.addEventListener("pointerdown", handleOutsidePointerDown, true);
    } else {
      loadVLibras();
    }
    apply();
    document.dispatchEvent(new CustomEvent("librashub:a11yready", { detail: { state: { ...state } } }));
  }
  function click(event) {
    const button = event.target.closest("[data-a11y]"); if (!button) return;
    const actionName = button.dataset.a11y;
    if (actionName === "close") close({ restoreFocus: true });
    else if (actionName === "fontDown") { state.font = Math.max(80, state.font - 10); apply(); note(`Fonte em ${state.font}%`); }
    else if (actionName === "fontUp") { state.font = Math.min(150, state.font + 10); apply(); note(`Fonte em ${state.font}%`); }
    else if (actionName === "theme") {
      state.theme = theme() === "dark" ? "light" : "dark";
      apply();
      note(`Tema ${theme() === "dark" ? "escuro" : "claro"} ativado`);
    }
    else if (["contrast", "readable", "underline", "motion", "cursor"].includes(actionName)) {
      state[actionName] = !state[actionName];
      apply();
      const label = settingLabels[actionName] || "Opção de acessibilidade";
      note(`${label} ${state[actionName] ? "ativado" : "desativado"}`);
    }
    else if (actionName === "speak") { speaking ? stopSpeech() : speak((document.querySelector("main") || document.body).innerText); }
    else if (actionName === "select") { const enabled = !document.documentElement.classList.contains("a11y-select-mode"); if (!enabled && speaking) stopSpeech(); document.documentElement.classList.toggle("a11y-select-mode", enabled); update(); note(enabled ? "Selecione um texto para ouvir" : "Leitura por seleção desativada"); }
    else if (actionName === "focusMain") { closePanelThen(() => focusTarget(document.querySelector("main"))); }
    else if (actionName === "shortcuts") { toggleShortcuts(button); }
    else if (actionName === "reset") { state = { ...defaults }; stopSpeech(false); apply(); note("Preferências restauradas"); }
  }
  function keyboard(event) {
    if (event.key === "Escape") {
      const item = document.getElementById("a11yPanel");
      if (item && item.getAttribute("aria-hidden") === "false") {
        event.preventDefault();
        close({ restoreFocus: true });
      }
      return;
    }

    if (event.key === "Tab" && trapPanelFocus(event)) return;

    if (!event.altKey || event.ctrlKey || event.metaKey) return;
    const key = event.key.toLowerCase();
    if (key === "a") { event.preventDefault(); panel(); return; }
    if (!document.body.classList.contains("app-preview")) return;
    const shortcuts = {
      c: () => closePanelThen(() => focusTarget(document.querySelector("main"))),
      m: () => closePanelThen(focusNavigation),
      v: () => speaking ? stopSpeech() : speak((document.querySelector("main") || document.body).innerText),
      s: () => closePanelThen(() => focusTarget(document.getElementById("suporte"))),
      e: () => { window.location.href = "templates/Login.php"; }
    };
    if (!shortcuts[key]) return;
    event.preventDefault();
    shortcuts[key]();
  }
  function focusTarget(target) {
    if (!target) return;
    if (!target.hasAttribute("tabindex")) target.setAttribute("tabindex", "-1");
    target.scrollIntoView({ behavior: state.motion ? "auto" : "smooth", block: "start" });
    target.focus({ preventScroll: true });
  }

  function closePanelThen(run) {
    if (typeof run !== "function") return;
    const item = document.getElementById("a11yPanel");
    const isOpen = item?.getAttribute("aria-hidden") === "false";
    if (!isOpen) {
      run();
      return;
    }

    // Libera o conteúdo que estava inert antes de mover o foco para fora do diálogo.
    close({ restoreFocus: false });
    requestAnimationFrame(() => requestAnimationFrame(run));
  }
  function focusNavigation() {
    const hamburger = document.getElementById("hamburger");
    const compactNavigation = hamburger && getComputedStyle(hamburger).display !== "none";
    if (compactNavigation) {
      if (hamburger.getAttribute("aria-expanded") !== "true") hamburger.click();
      hamburger.focus();
      return;
    }
    focusTarget(document.querySelector(".site-nav"));
  }
  function toggleShortcuts(button) {
    const shortcuts = document.getElementById("a11yShortcuts");
    if (!shortcuts) return;
    const open = shortcuts.hidden;
    shortcuts.hidden = !open;
    button.setAttribute("aria-expanded", String(open));
    note(open ? "Atalhos exibidos" : "Atalhos ocultados");
  }

  function getPanelFocusableElements(item) {
    if (!item) return [];
    return [...item.querySelectorAll(
      'button:not([disabled]),select:not([disabled]),input:not([disabled]),textarea:not([disabled]),a[href],[tabindex]:not([tabindex="-1"])'
    )].filter(element => {
      if (!(element instanceof HTMLElement)) return false;
      if (element.hidden || element.getAttribute("aria-hidden") === "true") return false;
      return element.getClientRects().length > 0;
    });
  }

  function trapPanelFocus(event) {
    const item = document.getElementById("a11yPanel");
    if (!item || item.getAttribute("aria-hidden") !== "false") return false;
    if (item.getAttribute("aria-modal") !== "true") return false;

    const controls = getPanelFocusableElements(item);
    if (!controls.length) {
      event.preventDefault();
      item.setAttribute("tabindex", "-1");
      item.focus();
      return true;
    }

    const first = controls[0];
    const last = controls[controls.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !item.contains(active))) {
      event.preventDefault();
      last.focus();
      return true;
    }

    if (!event.shiftKey && (active === last || !item.contains(active))) {
      event.preventDefault();
      first.focus();
      return true;
    }

    return false;
  }
  function getLandingModalBackgroundTargets() {
    // O footer também usa id="suporte"; Set evita restaurar aria-hidden/tabindex duas vezes
    // no fallback para navegadores sem suporte nativo a inert.
    return [...new Set([
      document.getElementById("navbar"),
      document.getElementById("mobileNav"),
      document.getElementById("conteudo"),
      document.getElementById("suporte"),
      document.querySelector("body.app-preview > footer")
    ].filter(element => element instanceof HTMLElement))];
  }

  function setFallbackInert(targets, disabled) {
    const focusableSelector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]'
    ].join(',');

    if (disabled) {
      fallbackInertRecords = targets.map(element => {
        const focusables = [...element.querySelectorAll(focusableSelector)]
          .filter(control => control instanceof HTMLElement)
          .map(control => ({
            element: control,
            tabindex: control.getAttribute("tabindex")
          }));

        const record = {
          element,
          ariaHidden: element.getAttribute("aria-hidden"),
          focusables
        };

        element.setAttribute("aria-hidden", "true");
        focusables.forEach(({ element: control }) => {
          control.setAttribute("tabindex", "-1");
        });

        return record;
      });
      return;
    }

    fallbackInertRecords.forEach(record => {
      const { element, ariaHidden, focusables } = record;
      if (element.isConnected) {
        if (ariaHidden === null) element.removeAttribute("aria-hidden");
        else element.setAttribute("aria-hidden", ariaHidden);
      }

      focusables.forEach(({ element: control, tabindex }) => {
        if (!control.isConnected) return;
        if (tabindex === null) control.removeAttribute("tabindex");
        else control.setAttribute("tabindex", tabindex);
      });
    });

    fallbackInertRecords = [];
  }

  function restoreInlineStyle(element, property, value) {
    if (!element) return;
    if (value) element.style.setProperty(property, value);
    else element.style.removeProperty(property);
  }

  function setLandingScrollLocked(locked) {
    if (!document.body.classList.contains("app-preview")) return;

    const root = document.documentElement;
    const body = document.body;

    if (locked) {
      if (landingScrollLockRecord) return;

      const scrollY = window.scrollY || window.pageYOffset || 0;
      const scrollbarGap = Math.max(0, window.innerWidth - root.clientWidth);
      const computedPaddingRight = parseFloat(getComputedStyle(body).paddingRight) || 0;

      landingScrollLockRecord = {
        scrollY,
        rootOverflow: root.style.getPropertyValue("overflow"),
        rootOverscroll: root.style.getPropertyValue("overscroll-behavior"),
        bodyPosition: body.style.getPropertyValue("position"),
        bodyTop: body.style.getPropertyValue("top"),
        bodyLeft: body.style.getPropertyValue("left"),
        bodyRight: body.style.getPropertyValue("right"),
        bodyWidth: body.style.getPropertyValue("width"),
        bodyOverflow: body.style.getPropertyValue("overflow"),
        bodyOverscroll: body.style.getPropertyValue("overscroll-behavior"),
        bodyPaddingRight: body.style.getPropertyValue("padding-right")
      };

      root.classList.add("a11y-scroll-locked");
      root.style.setProperty("overflow", "hidden");
      root.style.setProperty("overscroll-behavior", "none");

      body.style.setProperty("position", "fixed");
      body.style.setProperty("top", `${-scrollY}px`);
      body.style.setProperty("left", "0");
      body.style.setProperty("right", "0");
      body.style.setProperty("width", "100%");
      body.style.setProperty("overflow", "hidden");
      body.style.setProperty("overscroll-behavior", "none");

      if (scrollbarGap > 0) {
        body.style.setProperty("padding-right", `${computedPaddingRight + scrollbarGap}px`);
      }
      return;
    }

    if (!landingScrollLockRecord) return;

    const record = landingScrollLockRecord;
    landingScrollLockRecord = null;

    root.classList.remove("a11y-scroll-locked");
    restoreInlineStyle(root, "overflow", record.rootOverflow);
    restoreInlineStyle(root, "overscroll-behavior", record.rootOverscroll);
    restoreInlineStyle(body, "position", record.bodyPosition);
    restoreInlineStyle(body, "top", record.bodyTop);
    restoreInlineStyle(body, "left", record.bodyLeft);
    restoreInlineStyle(body, "right", record.bodyRight);
    restoreInlineStyle(body, "width", record.bodyWidth);
    restoreInlineStyle(body, "overflow", record.bodyOverflow);
    restoreInlineStyle(body, "overscroll-behavior", record.bodyOverscroll);
    restoreInlineStyle(body, "padding-right", record.bodyPaddingRight);

    window.scrollTo(0, record.scrollY);
  }

  function setLandingBackgroundInert(disabled) {
    if (!document.body.classList.contains("app-preview")) return;

    const targets = getLandingModalBackgroundTargets();
    const supportsNativeInert = "inert" in HTMLElement.prototype;

    document.body.classList.toggle("a11y-dialog-open", disabled);
    setLandingScrollLocked(disabled);

    if (!supportsNativeInert) {
      setFallbackInert(targets, disabled);
      return;
    }

    if (disabled) {
      inertedBackgroundElements = targets.filter(element => !element.inert);
      targets.forEach(element => { element.inert = true; });
      return;
    }

    inertedBackgroundElements.forEach(element => {
      if (element.isConnected) element.inert = false;
    });
    inertedBackgroundElements = [];
  }

  function setBackdropVisible(visible) {
    const backdrop = document.getElementById("a11yBackdrop");
    if (!backdrop) return;
    backdrop.hidden = !visible;
  }

  function handleOutsidePointerDown(event) {
    const item = document.getElementById("a11yPanel");
    const toggle = document.getElementById("a11yToggle");
    if (!document.body.classList.contains("app-preview") || !item) return;
    if (item.getAttribute("aria-hidden") === "true") return;
    if (item.contains(event.target) || toggle?.contains(event.target)) return;

    /* Impede que o clique que fecha o modal também acione algo no fundo. */
    if (event.cancelable) event.preventDefault();
    event.stopPropagation();
    close({ restoreFocus: true, restoreTo: "toggle" });
  }

  function announce(message) {
    const live = document.getElementById("a11yLive");
    if (!live) return;
    live.textContent = "";
    requestAnimationFrame(() => {
      live.textContent = message;
    });
  }

  function openPanel() {
    const item = document.getElementById("a11yPanel");
    const toggle = document.getElementById("a11yToggle");
    if (!item || item.getAttribute("aria-hidden") === "false") return;

    lastFocusedBeforePanel = document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null;

    setLandingBackgroundInert(true);
    setBackdropVisible(true);
    item.setAttribute("aria-hidden", "false");
    item.classList.add("open");
    toggle?.setAttribute("aria-expanded", "true");
    toggle?.setAttribute("aria-label", "Fechar opções de Acessibilidade");
    announce("Painel de opções de acessibilidade aberto.");

    const firstControl = getPanelFocusableElements(item)[0];
    firstControl?.focus();
  }
  function close({ restoreFocus = false, restoreTo = "previous" } = {}) {
    const item = document.getElementById("a11yPanel");
    const toggle = document.getElementById("a11yToggle");
    if (!item || item.getAttribute("aria-hidden") === "true") return;

    item.setAttribute("aria-hidden", "true");
    item.classList.remove("open");
    setBackdropVisible(false);
    setLandingBackgroundInert(false);
    toggle?.setAttribute("aria-expanded", "false");
    toggle?.setAttribute("aria-label", "Opções de Acessibilidade");
    announce("Painel de opções de acessibilidade fechado.");

    if (restoreFocus) {
      const focusTarget = restoreTo === "toggle"
        ? toggle
        : (lastFocusedBeforePanel?.isConnected ? lastFocusedBeforePanel : toggle);

      /* Aguarda a restauração do fundo/scroll antes de devolver o foco. */
      requestAnimationFrame(() => {
        if (!focusTarget?.isConnected) return;
        try {
          focusTarget.focus({ preventScroll: true });
        } catch (_) {
          focusTarget.focus();
        }
      });
    }
    lastFocusedBeforePanel = null;
  }
  function panel() {
    const item = document.getElementById("a11yPanel");
    if (!item) return;
    const isClosed = item.getAttribute("aria-hidden") === "true";
    isClosed ? openPanel() : close({ restoreFocus: true });
  }
  function stopSpeech(showNote = true) { activeUtterance = null; clearInterval(speechWatchdog); speechWatchdog = null; if ("speechSynthesis" in window) speechSynthesis.cancel(); speaking = false; document.documentElement.classList.remove("a11y-select-mode"); update(); if (showNote) note("Leitura interrompida"); }
  function speak(text, selectionMode = false) { if (!("speechSynthesis" in window)) { note("Leitura em voz alta indisponível"); return; } const content = String(text || "").trim(); if (!content) { note("Nenhum texto disponível para leitura"); return; } activeUtterance = null; clearInterval(speechWatchdog); speechSynthesis.cancel(); const utterance = new SpeechSynthesisUtterance(content.slice(0, 12000)); activeUtterance = utterance; utterance.lang = "pt-BR"; utterance.rate = .95; const finish = event => { if (activeUtterance !== utterance) return; activeUtterance = null; clearInterval(speechWatchdog); speechWatchdog = null; speaking = false; if (selectionMode) document.documentElement.classList.remove("a11y-select-mode"); update(); note(event.type === "end" ? "Leitura concluída" : "Leitura encerrada"); }; utterance.onstart = () => { if (activeUtterance !== utterance) return; speaking = true; update(); note("Leitura iniciada"); speechWatchdog = setInterval(() => { if (activeUtterance === utterance && !speechSynthesis.speaking && !speechSynthesis.pending) finish({type:"end"}); }, 500); }; utterance.onend = finish; utterance.onerror = finish; speechSynthesis.speak(utterance); }
  function note(message) { const toast = document.querySelector(".a11y-toast"); if (!toast) return; toast.textContent = message; toast.classList.add("show"); clearTimeout(toast._timer); toast._timer = setTimeout(() => toast.classList.remove("show"), 2500); }
  function update() { if (!document.body) return; ["contrast", "readable", "underline", "motion", "cursor"].forEach(key => { const button = document.querySelector(`[data-a11y="${key}"]`); if (button) button.setAttribute("aria-pressed", String(state[key])); }); const speech = document.querySelector('[data-a11y="speak"]'); if (speech) speech.setAttribute("aria-pressed", String(speaking)); const selection = document.querySelector('[data-a11y="select"]'); if (selection) selection.setAttribute("aria-pressed", String(document.documentElement.classList.contains("a11y-select-mode"))); const size = document.getElementById("a11yFont"); if (size) size.textContent = state.font + "%"; const filter = document.querySelector('[data-a11y="filter"]'); if (filter) filter.value = state.filter; const description = document.getElementById("a11yFilterDescription"); if (description) description.textContent = colorFilters[state.filter] || colorFilters.none; }

  function loadVLibras() {
    if (document.querySelector('script[data-librashub-vlibras]')) return;

    let buttonObserver = null;
    let findTimer = null;

    const setImportant = (element, property, value) => {
      if (!element) return;
      const currentValue = element.style.getPropertyValue(property);
      const currentPriority = element.style.getPropertyPriority(property);
      if (currentValue === value && currentPriority === 'important') return;
      element.style.setProperty(property, value, 'important');
    };

    const hideDuplicateVLibrasButtons = primaryButton => {
      const hideInRoot = root => {
        if (!root?.querySelectorAll) return;

        root.querySelectorAll('[vw-access-button], .vpw-access-button').forEach(candidate => {
          if (
            candidate === primaryButton ||
            candidate.contains(primaryButton) ||
            primaryButton.contains(candidate)
          ) return;

          candidate.dataset.librashubVlibrasDuplicate = 'true';
          candidate.setAttribute('aria-hidden', 'true');
          setImportant(candidate, 'display', 'none');
          setImportant(candidate, 'visibility', 'hidden');
          setImportant(candidate, 'pointer-events', 'none');
        });

        root.querySelectorAll('*').forEach(element => {
          if (element.shadowRoot) hideInRoot(element.shadowRoot);
        });
      };

      hideInRoot(document);
    };

    const keepOfficialButtonCentered = () => {
      const button = window.VLibrasWidget?.initBtn;
      if (!button) return false;

      button.dataset.librashubVlibrasPrimary = 'true';

      /* Mantém somente o botão principal do VLibras no centro direito. */
      setImportant(button, 'display', 'block');
      setImportant(button, 'visibility', 'visible');
      setImportant(button, 'pointer-events', 'auto');
      setImportant(button, 'position', 'fixed');
      setImportant(button, 'top', '50%');
      setImportant(button, 'right', '18px');
      setImportant(button, 'bottom', 'auto');
      setImportant(button, 'left', 'auto');
      setImportant(button, 'margin', '0');
      setImportant(button, 'transform', 'translate3d(0,-50%,0)');
      setImportant(button, 'z-index', '1000000');
      setImportant(button, 'width', '52px');
      setImportant(button, 'height', '52px');
      setImportant(button, 'min-width', '52px');
      setImportant(button, 'min-height', '52px');
      setImportant(button, 'overflow', 'visible');
      setImportant(button, 'border-radius', '14px');
      setImportant(button, 'box-shadow', '0 10px 28px rgba(23,71,232,.28)');

      hideDuplicateVLibrasButtons(button);

      if (!button.dataset.librashubPositionBound) {
        button.dataset.librashubPositionBound = 'true';
        button.addEventListener('click', () => {
          [0, 120, 400, 900].forEach(delay =>
            setTimeout(keepOfficialButtonCentered, delay)
          );
        });
      }

      if (!buttonObserver) {
        buttonObserver = new MutationObserver(() => {
          keepOfficialButtonCentered();
        });
        buttonObserver.observe(button, {
          attributes: true,
          attributeFilter: ['style', 'class']
        });
      }

      return true;
    };

    const waitForOfficialButton = () => {
      let attempts = 0;
      clearInterval(findTimer);
      findTimer = setInterval(() => {
        attempts += 1;
        if (keepOfficialButtonCentered() || attempts >= 200) {
          clearInterval(findTimer);
          findTimer = null;
        }
      }, 100);
    };

    const duplicateObserver = new MutationObserver(() => {
      const primaryButton = window.VLibrasWidget?.initBtn;
      if (primaryButton) hideDuplicateVLibrasButtons(primaryButton);
    });
    duplicateObserver.observe(document.documentElement, {
      childList: true,
      subtree: true
    });

    const script = document.createElement('script');
    script.src = 'https://vlibras.gov.br/app/vlibras-plugin.js';
    script.async = true;
    script.dataset.librashubVlibras = 'true';
    script.addEventListener('load', waitForOfficialButton, { once: true });
    document.body.appendChild(script);

    /* O script oficial inicializa o próprio widget. Nenhum botão extra é criado. */
    waitForOfficialButton();
    window.addEventListener('resize', keepOfficialButtonCentered, { passive: true });
    window.addEventListener('scroll', keepOfficialButtonCentered, { passive: true });
  }

  window.LibrasA11y = { apply, setTheme: value => { state.theme = { claro: "light", escuro: "dark", automatico: "auto" }[value] || value; apply(); }, setFontSize: value => { state.font = { pequena: 90, media: 100, grande: 115 }[value] || Number(value) || 100; apply(); }, setContrast: value => { state.contrast = value === "on" || value === true; apply(); }, setPreference: (key,value) => { if (Object.prototype.hasOwnProperty.call(defaults,key)) { state[key]=value; apply(); } }, getState: () => ({...state}), getTheme: () => state.theme, getFontSize: () => state.font, getContrast: () => state.contrast };
  function syncPreferencesFromStorage(event) {
    if (event.storageArea !== localStorage || event.key !== KEY || !event.newValue) return;

    try {
      const incoming = normalizeState(JSON.parse(event.newValue));
      if (JSON.stringify(incoming) === JSON.stringify(state)) return;
      state = incoming;
      apply({ persist: false });
      note("Preferências de acessibilidade sincronizadas");
      document.dispatchEvent(new CustomEvent("librashub:a11ychange", {
        detail: { source: "storage", state: { ...state } }
      }));
    } catch (_) {}
  }

  window.addEventListener("storage", syncPreferencesFromStorage);

  function handleSystemThemeChange(event) {
    if (state.theme !== "auto") return;

    apply({ persist: false });
    const resolvedTheme = event?.matches ? "escuro" : "claro";
    note(`Tema automático atualizado para ${resolvedTheme}`);

    document.dispatchEvent(new CustomEvent("librashub:a11ychange", {
      detail: {
        source: "system-theme",
        state: { ...state },
        resolvedTheme: theme()
      }
    }));
  }

  if (systemDarkThemeQuery) {
    if (typeof systemDarkThemeQuery.addEventListener === "function") {
      systemDarkThemeQuery.addEventListener("change", handleSystemThemeChange);
    } else if (typeof systemDarkThemeQuery.addListener === "function") {
      /* Compatibilidade com Safari antigo. */
      systemDarkThemeQuery.addListener(handleSystemThemeChange);
    }
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", mount); else mount();
})();
