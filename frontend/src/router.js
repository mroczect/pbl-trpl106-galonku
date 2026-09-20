const routes = {};

export function route(path, handler) {
  routes[path] = handler;
}

export function navigate(path) {
  location.hash = "#" + path;
}

export function startRouter(authGuard) {
  const render = async () => {
    const path = location.hash.slice(1) || "/";
    const [base, id] = path.split("/").filter(Boolean).length
      ? [
          "/" + path.split("/").filter(Boolean)[0],
          path.split("/").filter(Boolean)[1],
        ]
      : ["/", null];

    if (authGuard && !authGuard(path)) return;

    const handler = routes[base] || routes["/"];
    const app = document.getElementById("app");
    app.innerHTML = "Loading...";
    try {
      const html = await handler({ id, path });
      app.innerHTML = html || "";
      // jalankan script yang di-inject via data-run
      app.querySelectorAll("[data-run]").forEach((el) => {
        const fn = window[el.dataset.run];
        if (typeof fn === "function") fn(el);
      });
      highlightNav(base);
    } catch (e) {
      app.innerHTML = `<p class="error">Error: ${e.message}</p>`;
    }
  };

  window.addEventListener("hashchange", render);
  render();
}

function highlightNav(base) {
  document.querySelectorAll("#nav a").forEach((a) => {
    a.classList.toggle("active", a.getAttribute("href") === "#" + base);
  });
}
