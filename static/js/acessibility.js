/**
 * LibrasHub - Acessibilidade
 * Aplica tema, tamanho de fonte e alto contraste em todas as páginas.
 * Usa localStorage quando disponível; se o navegador bloquear
 * (comum ao abrir arquivos direto via file://), cai para um
 * armazenamento em memória, para os botões continuarem funcionando
 * durante a sessão mesmo sem persistência entre páginas.
 */
(function () {
  const KEYS = {
    theme: "libras_theme",       // "claro" | "escuro" | "automatico"
    fontSize: "libras_fontsize", // "pequena" | "media" | "grande"
    contrast: "libras_contrast"  // "on" | "off"
  };

  const FONT_SCALES = {
    pequena: 0.9,
    media: 1,
    grande: 1.15
  };

  const memoryStore = {};

  function safeGet(key, fallback) {
    try {
      const v = localStorage.getItem(key);
      return v !== null ? v : fallback;
    } catch (e) {
      return key in memoryStore ? memoryStore[key] : fallback;
    }
  }

  function safeSet(key, value) {
    memoryStore[key] = value;
    try {
      localStorage.setItem(key, value);
    } catch (e) {
      // localStorage indisponível (ex: aberto via file://) - segue só em memória
    }
  }

  function systemPrefersDark() {
    return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  }

  function applyTheme(theme) {
    const html = document.documentElement;
    let effective = theme;
    if (theme === "automatico") {
      effective = systemPrefersDark() ? "escuro" : "claro";
    }
    if (effective === "escuro") {
      html.setAttribute("data-theme", "dark");
    } else {
      html.removeAttribute("data-theme");
    }
  }

  function applyFontSize(size) {
    const scale = FONT_SCALES[size] || 1;
    document.documentElement.style.setProperty("--font-scale", scale);
  }

  function applyContrast(state) {
    document.documentElement.classList.toggle("high-contrast", state === "on");
  }

  function applyAll() {
    applyTheme(safeGet(KEYS.theme, "claro"));
    applyFontSize(safeGet(KEYS.fontSize, "media"));
    applyContrast(safeGet(KEYS.contrast, "off"));
  }

  // API exposta para as páginas usarem - definida ANTES de aplicar,
  // para garantir que window.LibrasA11y sempre exista mesmo se algo falhar abaixo.
  window.LibrasA11y = {
    setTheme(theme) {
      safeSet(KEYS.theme, theme);
      applyTheme(theme);
    },
    setFontSize(size) {
      safeSet(KEYS.fontSize, size);
      applyFontSize(size);
    },
    setContrast(state) {
      safeSet(KEYS.contrast, state);
      applyContrast(state);
    },
    getTheme() { return safeGet(KEYS.theme, "claro"); },
    getFontSize() { return safeGet(KEYS.fontSize, "media"); },
    getContrast() { return safeGet(KEYS.contrast, "off"); }
  };

  try {
    applyAll();
  } catch (e) {
    console.error("LibrasHub A11y: falha ao aplicar preferências.", e);
  }

  if (window.matchMedia) {
    try {
      window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
        if (safeGet(KEYS.theme, "claro") === "automatico") applyTheme("automatico");
      });
    } catch (e) { /* navegador antigo sem addEventListener em MediaQueryList */ }
  }
})();