import { api } from "../api.js";
import { table } from "../ui.js";

export default async function logsPage() {
  const { data } = await api.logs(200);
  return `
    <h2>Log Audit (200 terakhir)</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "User", get: (r) => r.user_name || "-" },
      { label: "Action", get: (r) => r.action },
      {
        label: "Entity",
        get: (r) => `${r.entity || "-"}#${r.entity_id || "-"}`,
      },
      { label: "IP", get: (r) => r.ip_address || "-" },
      { label: "Waktu", get: (r) => r.created_at },
    ])}
  `;
}
