import { api } from "../api.js";
import { table, form, formData, toast, escapeHtml } from "../ui.js";

const TYPE_LABEL = { in: "Masuk", out: "Keluar", adjustment: "Koreksi" };
const TYPE_COLOR = { in: "green", out: "red", adjustment: "orange" };

export default async function stockMovementsPage() {
  const [{ data, meta }, products] = await Promise.all([
    api.list("stock-movements", { per_page: 100 }),
    api.list("products", { per_page: 200 }),
  ]);

  const productOptions = [
    { value: "", label: "-- pilih produk --" },
    ...products.data.map((p) => ({
      value: p.id,
      label: `${p.name} (stok: ${p.stock})`,
    })),
  ];

  setTimeout(() => {
    const f = document.getElementById("adjust-form");
    if (!f) return;
    f.onsubmit = async (e) => {
      e.preventDefault();
      const body = formData(f);
      body.product_id = Number(body.product_id);
      body.new_stock = Number(body.new_stock);
      if (!body.product_id) {
        toast("Pilih produk dulu", false);
        return;
      }
      if (body.new_stock < 0) {
        toast("Stok tidak boleh negatif", false);
        return;
      }
      try {
        const res = await api.stockAdjust(body);
        const d = res.data;
        if (d.changed) toast(`Stok dikoreksi: ${d.before} → ${d.after}`);
        else toast(`Stok tidak berubah (${d.after})`);
        setTimeout(() => location.reload(), 700);
      } catch (e) {
        toast(e.message, false);
      }
    };
  }, 0);

  const sign = (t) => (t === "in" ? "+" : t === "out" ? "−" : "±");

  return `
    <h2>Riwayat Stok (${meta.total})</h2>
    ${table(data, [
      { label: "Waktu", get: (r) => r.created_at },
      {
        label: "Produk",
        html: true,
        get: (r) =>
          `${escapeHtml(r.product_name)}<br/><small>${escapeHtml(r.sku)}</small>`,
      },
      {
        label: "Tipe",
        html: true,
        get: (r) =>
          `<span style="color:${TYPE_COLOR[r.type] || "black"}">${TYPE_LABEL[r.type] || r.type}</span>`,
      },
      { label: "Qty", get: (r) => sign(r.type) + r.qty },
      {
        label: "Stok",
        html: true,
        get: (r) => `${r.stock_before} → <b>${r.stock_after}</b>`,
      },
      { label: "Alasan", get: (r) => r.reason },
      { label: "Oleh", get: (r) => r.user_name || "-" },
    ])}

    <h3>Koreksi Stok</h3>
    <form id="adjust-form">
      ${form([
        {
          label: "Produk",
          name: "product_id",
          type: "select",
          options: productOptions,
          required: true,
        },
        {
          label: "Stok Baru (fisik)",
          name: "new_stock",
          type: "number",
          required: true,
        },
        {
          label: "Alasan",
          name: "reason",
          required: true,
          default: "Stok opname",
        },
        { label: "Catatan", name: "notes", type: "textarea" },
      ])}
      <button type="submit">Simpan Koreksi</button>
    </form>
  `;
}
