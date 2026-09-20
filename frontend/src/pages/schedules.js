import { api } from "../api.js";
import { table, form, formData, toast } from "../ui.js";

export default async function schedulesPage() {
  const { data, meta } = await api.list("schedules", { per_page: 50 });

  setTimeout(() => {
    const f = document.getElementById("create-form");
    if (!f) return;
    f.onsubmit = async (e) => {
      e.preventDefault();
      try {
        await api.create("schedules", formData(f));
        toast("Jadwal dibuat");
        location.reload();
      } catch (e) {
        toast(e.message, false);
      }
    };
  }, 0);

  return `
    <h2>Jadwal (${meta.total})</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "Customer", get: (r) => r.customer_name },
      { label: "Kurir", get: (r) => r.user_name },
      { label: "Waktu", get: (r) => r.scheduled_at },
      { label: "Status", get: (r) => r.status },
      {
        label: "Aksi",
        html: true,
        get: (r) => `
          <button onclick="__sched(${r.id},'done')">Done</button>
          <button onclick="__sched(${r.id},'cancelled')">Cancel</button>`,
      },
    ])}
    <h3>Buat Jadwal</h3>
    <form id="create-form">
      ${form([
        {
          label: "Customer ID",
          name: "customer_id",
          type: "number",
          required: true,
        },
        { label: "User ID", name: "user_id", type: "number", required: true },
        {
          label: "Scheduled At (YYYY-MM-DD HH:MM:SS)",
          name: "scheduled_at",
          required: true,
          default: new Date(Date.now() + 3600000)
            .toISOString()
            .slice(0, 19)
            .replace("T", " "),
        },
      ])}
      <button type="submit">Buat</button>
    </form>
  `;
}

window.__sched = async (id, status) => {
  const { api } = await import("../api.js");
  try {
    await api.updateSched(id, { status });
    location.reload();
  } catch (e) {
    alert(e.message);
  }
};
