export function table(rows, cols) {
  if (!rows?.length) return "<p><em>Tidak ada data.</em></p>";
  const head = cols.map((c) => `<th>${escapeHtml(c.label)}</th>`).join("");
  const body = rows
    .map(
      (r) =>
        "<tr>" +
        cols
          .map((c) => {
            const v = c.get(r);
            return `<td>${c.html ? (v ?? "") : escapeHtml(v)}</td>`;
          })
          .join("") +
        "</tr>",
    )
    .join("");
  return `<table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
}

export function form(fields, values = {}, action = "submit") {
  return fields
    .map((f) => {
      const val = values[f.name] ?? f.default ?? "";
      if (f.type === "select") {
        const opts = f.options
          .map(
            (o) =>
              `<option value="${o.value}" ${String(val) === String(o.value) ? "selected" : ""}>${o.label}</option>`,
          )
          .join("");
        return `<label>${f.label}: <select name="${f.name}">${opts}</select></label>`;
      }
      if (f.type === "textarea") {
        return `<label>${f.label}: <textarea name="${f.name}">${escapeHtml(val)}</textarea></label>`;
      }
      return `<label>${f.label}: <input name="${f.name}" type="${f.type || "text"}" value="${escapeHtml(val)}" ${f.required ? "required" : ""} /></label>`;
    })
    .join("<br/>");
}

export function formData(formEl) {
  const fd = new FormData(formEl);
  const out = {};
  for (const [k, v] of fd.entries()) {
    if (v === "") continue;
    if (
      /^\d+$/.test(v) &&
      ![
        "phone",
        "name",
        "sku",
        "notes",
        "address",
        "email",
        "password",
      ].includes(k)
    )
      out[k] = Number(v);
    else out[k] = v;
  }
  return out;
}

export function escapeHtml(s) {
  if (s == null) return "";
  return String(s).replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
}

export function toast(msg, ok = true) {
  const el = document.createElement("div");
  el.className = ok ? "ok" : "error";
  el.textContent = (ok ? "✔ " : "✘ ") + msg;
  document.body.prepend(el);
  setTimeout(() => el.remove(), 2500);
}

window.__delete = async (res, id) => {
  if (!confirm(`Hapus ${res} #${id}?`)) return;
  const { api } = await import("./api.js");
  try {
    await api.del(res, id);
    toast("Dihapus");
    location.reload();
  } catch (e) {
    toast(e.message, false);
  }
};

window.__nav = (path) => (location.hash = "#" + path);
