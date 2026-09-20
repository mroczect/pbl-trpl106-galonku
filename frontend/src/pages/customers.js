import { api } from "../api.js";
import { table, form, formData, toast } from "../ui.js";

export default async function customersPage() {
  const { data, meta } = await api.list("customers", { per_page: 50 });

  setTimeout(() => {
    const f = document.getElementById("create-form");
    if (!f) return;
    f.onsubmit = async (e) => {
      e.preventDefault();
      try {
        await api.create("customers", formData(f));
        toast("Customer dibuat");
        location.reload();
      } catch (e) {
        toast(e.message, false);
      }
    };
  }, 0);

  return `
    <h2>Customer (${meta.total})</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "Nama", get: (r) => r.name },
      { label: "Telepon", get: (r) => r.phone },
      { label: "Alamat", get: (r) => r.address },
      { label: "Aktif", get: (r) => (r.is_active ? "✔" : "✘") },
      {
        label: "Aksi",
        html: true,
        get: (r) =>
          `<button onclick="__delete('customers', ${r.id})">Hapus</button>`,
      },
    ])}
    <h3>Tambah Customer</h3>
    <form id="create-form">
      ${form([
        { label: "Nama", name: "name", required: true },
        { label: "Telepon", name: "phone", required: true },
        { label: "Alamat", name: "address", type: "textarea" },
        { label: "Catatan", name: "notes", type: "textarea" },
      ])}
      <button type="submit">Simpan</button>
    </form>
  `;
}
