import { api } from "../api.js";

export default async function dashboardPage() {
  const [me, products, lowStock] = await Promise.all([
    api.me(),
    api.list("products", { per_page: 1 }),
    api.lowStock(10),
  ]);

  return `
    <h2>Dashboard</h2>
    <p><b>${me.data.name}</b> — ${me.data.email} (${me.data.role_name})</p>
    <h3>Ringkasan</h3>
    <ul>
      <li>Total produk: ${products.meta.total}</li>
      <li>Produk stok rendah (≤10): ${lowStock.data.length}</li>
    </ul>
  `;
}
