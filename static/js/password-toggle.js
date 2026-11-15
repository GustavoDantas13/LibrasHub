(() => {
  "use strict";
  document.querySelectorAll('input[type="password"]').forEach((input) => {
    if (input.dataset.passwordToggleReady === "true") return;
    input.dataset.passwordToggleReady = "true";
    const wrapper = document.createElement("div");
    wrapper.className = "password-field-wrap";
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    const button = document.createElement("button");
    button.type = "button";
    button.className = "password-toggle";
    button.setAttribute("aria-label", "Mostrar senha");
    button.setAttribute("aria-pressed", "false");
    button.innerHTML = '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
    wrapper.appendChild(button);
    button.addEventListener("click", () => {
      const visible = input.type === "text";
      input.type = visible ? "password" : "text";
      button.setAttribute("aria-pressed", String(!visible));
      button.setAttribute("aria-label", visible ? "Mostrar senha" : "Ocultar senha");
      button.innerHTML = visible ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>' : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
      input.focus({ preventScroll: true });
    });
  });
})();
