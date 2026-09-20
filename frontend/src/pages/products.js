import { api } from "../api.js";
import { table, form, formData, toast } from "../ui.js";

export default async function productsPage({ id }) {
  if (id) return detail(id);

  const { data, meta } = await api.list("products", { per_page: 50 });
  const isAdmin =
    JSON.parse(localStorage.getItem("user") || "{}").role_name ===
    "administrator";

  setTimeout(() => {
    const f = document.getElementById("create-form");
    if (!f) return;
    f.onsubmit = async (e) => {
      e.preventDefault();
      try {
        await api.create("products", formData(f));
        toast("Produk dibuat");
        location.reload();
      } catch (e) {
        toast(e.message, false);
      }
    };
  }, 0);

  return `
    <h2>Produk (${meta.total})</h2>
    ${table(data, [
      { label: "ID", get: (r) => r.id },
      { label: "SKU", get: (r) => r.sku },
      { label: "Nama", get: (r) => r.name },
      { label: "Kategori", get: (r) => r.category },
      {
        label: "Harga",
        get: (r) => `Rp ${Number(r.price).toLocaleString("id-ID")}`,
      },
      { label: "Stok", get: (r) => r.stock },
      { label: "Aktif", get: (r) => (r.is_active ? "✔" : "✘") },
      {
        label: "Aksi",
        html: true,
        get: (r) =>
          isAdmin
            ? `<button onclick="__delete('products', ${r.id})">Hapus</button>`
            : "",
      },
    ])}
    ${
      isAdmin
        ? `
      <h3>Tambah Produk</h3>
      <form id="create-form">
        ${form([
          { label: "SKU", name: "sku", required: true },
          { label: "Nama", name: "name", required: true },
          {
            label: "Kategori",
            name: "category",
            type: "select",
            options: [
              { value: "galon", label: "galon" },
              { value: "air", label: "air" },
              { value: "aksesoris", label: "aksesoris" },
              { value: "lain", label: "lain" },
            ],
          },
          {
            label: "Harga",
            name: "price",
            type: "number",
            required: true,
            default: 0,
          },
          { label: "Stok", name: "stock", type: "number", default: 0 },
        ])}
        <button type="submit">Simpan</button>
      </form>
    `
        : ""
    }
  `;
}

async function detail(id) {
  const { data: p } = await api.show("products", id);
  return `<h2>Produk #${id}</h2><pre>${JSON.stringify(p, null, 2)}</pre>
    <button onclick="__nav('/products')">← Kembali</button>`;
}
