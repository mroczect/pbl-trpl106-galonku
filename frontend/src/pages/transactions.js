import { api } from "../api.js";
import { table, form, formData, toast } from "../ui.js";

export default async function transactionsPage({ id }) {
  if (id) {
    const { data: t } = await api.show("transactions", id);
    setTimeout(() => {
      const f = document.getElementById("status-form");
      if (!f) return;
      f.onsubmit = async (e) => {
        e.preventDefault();
        try {
          await api.updateTrx(id, formData(f));
          toast("Status diupdate");
          location.reload();
        } catch (e) {
          toast(e.message, false);
        }
      };
    }, 0);

    return `
      <h2>Transaksi ${t.invoice_no}</h2>
      <p>Customer: ${t.customer_name} — User: ${t.user_name}</p>
      <p>Total: ${t.total_amount} | Status: <b>${t.status}</b></p>
      <h3>Items</h3>
      ${table(t.items, [
        { label: "Produk", get: (r) => r.product_name },
        { label: "SKU", get: (r) => r.sku },
        { label: "Qty", get: (r) => r.qty },
        { label: "Harga", get: (r) => r.unit_price },
        { label: "Subtotal", get: (r) => r.subtotal },
      ])}
      <h3>Update Status</h3>
      <form id="status-form">
        ${form([
          {
            label: "Status",
            name: "status",
            type: "select",
            options: [
              { value: "pending", label: "pending" },
              { value: "paid", label: "paid" },
              { value: "partial", label: "partial" },
              { value: "cancelled", label: "cancelled" },
            ],
            default: t.status,
          },
          {
            label: "Paid Amount",
            name: "paid_amount",
            type: "number",
            default: t.paid_amount,
          },
        ])}
        <button type="submit">Update</button>
      </form>
      <button onclick="__nav('/transactions')">← Kembali</button>
    `;
  }

  const { data, meta } = await api.list("transactions", { per_page: 50 });

  setTimeout(() => {
    const f = document.getElementById("create-form");
    if (!f) return;
    f.onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(f);
      const body = {
        customer_id: Number(fd.get("customer_id")),
        type: fd.get("type"),
        items: [
          {
            product_id: Number(fd.get("product_id")),
            qty: Number(fd.get("qty")),
          },
        ],
      };
      try {
        await api.create("transactions", body);
        toast("Transaksi dibuat");
        location.reload();
      } catch (e) {
        toast(e.message, false);
      }
    };
  }, 0);

  return `
    <h2>Transaksi (${meta.total})</h2>
    ${table(data, [
      {
        label: "ID",
        html: true,
        get: (r) => `<a href="#/transactions/${r.id}">${r.id}</a>`,
      },
      { label: "Invoice", get: (r) => r.invoice_no },
      { label: "Customer", get: (r) => r.customer_name },
      {
        label: "Total",
        get: (r) => `Rp ${Number(r.total_amount).toLocaleString("id-ID")}`,
      },
      { label: "Status", get: (r) => r.status },
      { label: "Tipe", get: (r) => r.type },
    ])}
    <h3>Buat Transaksi</h3>
    <form id="create-form">
      ${form([
        {
          label: "Customer ID",
          name: "customer_id",
          type: "number",
          required: true,
        },
        {
          label: "Product ID",
          name: "product_id",
          type: "number",
          required: true,
        },
        {
          label: "Qty",
          name: "qty",
          type: "number",
          default: 1,
          required: true,
        },
        {
          label: "Tipe",
          name: "type",
          type: "select",
          options: [
            { value: "sale", label: "sale" },
            { value: "delivery", label: "delivery" },
            { value: "return", label: "return" },
          ],
        },
      ])}
      <button type="submit">Buat</button>
    </form>
  `;
}
