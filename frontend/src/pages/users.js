import { api } from "../api.js";
import { table } from "../ui.js";

export default async function usersPage() {
  const { data, meta } = await api.list("users", { per_page: 50 });

  return `
    <h2>User (${meta.total})</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "Nama", get: (r) => r.name },
      { label: "Email", get: (r) => r.email },
      { label: "Role", get: (r) => r.role_name },
      { label: "Aktif", get: (r) => (r.is_active ? "✔" : "✘") },
      { label: "Last Login", get: (r) => r.last_login_at || "-" },
      {
        label: "Aksi",
        html: true,
        get: (r) =>
          `<button onclick="__delete('users', ${r.id})">Nonaktifkan</button>`,
      },
    ])}
  `;
}
