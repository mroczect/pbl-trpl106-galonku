import { api } from "../api.js";
import { table } from "../ui.js";

export default async function rolesPage() {
  const { data } = await api.list("roles");
  return `
    <h2>Role</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "Nama", get: (r) => r.name },
      { label: "Deskripsi", get: (r) => r.description },
    ])}
  `;
}
