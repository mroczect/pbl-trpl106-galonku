import { useEffect, useState } from "react";
import { transactionsApi } from "../api/transactions";
import { customersApi } from "../api/customers";
import { productsApi } from "../api/products";
import {
  Button,
  Card,
  PageHeader,
  EmptyState,
  Loading,
  StatusBadge,
  Select,
} from "../components/ui";
import toast from "react-hot-toast";
import { Plus, Receipt, X, Trash2 } from "lucide-react";

export default function Transactions() {
  const [items, setItems] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ customer_id: "", items: [] });

  const load = async () => {
    setLoading(true);
    try {
      const [t, c, p] = await Promise.all([
        transactionsApi.list({ per_page: 50 }),
        customersApi.list({ per_page: 100 }),
        productsApi.list({ per_page: 100 }),
      ]);
      setItems(t.data.data || []);
      setCustomers(c.data.data || []);
      setProducts(p.data.data || []);
    } catch {
      toast.error("Gagal memuat data");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const openCreate = () => {
    setForm({ customer_id: "", items: [{ product_id: "", qty: 1 }] });
    setShowForm(true);
  };

  const addItem = () =>
    setForm({ ...form, items: [...form.items, { product_id: "", qty: 1 }] });

  const updateItem = (i, key, val) => {
    const next = [...form.items];
    next[i][key] = val;
    setForm({ ...form, items: next });
  };

  const removeItem = (i) =>
    setForm({ ...form, items: form.items.filter((_, idx) => idx !== i) });

  const submit = async (e) => {
    e.preventDefault();
    try {
      await transactionsApi.create({
        customer_id: Number(form.customer_id),
        items: form.items.map((it) => ({
          product_id: Number(it.product_id),
          qty: Number(it.qty),
        })),
      });
      toast.success("Transaksi dibuat");
      setShowForm(false);
      load();
    } catch (err) {
      toast.error(err.response?.data?.message || "Gagal membuat transaksi");
    }
  };

  const updateStatus = async (id, status) => {
    try {
      await transactionsApi.updateStatus(id, { status });
      toast.success("Status diperbarui");
      load();
    } catch {
      toast.error("Gagal");
    }
  };

  return (
    <div>
      <PageHeader
        title="Transaksi"
        description="Kelola transaksi penjualan depot"
        action={
          <Button icon={Plus} onClick={openCreate}>
            Transaksi baru
          </Button>
        }
      />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState
            icon={Receipt}
            title="Belum ada transaksi"
            description="Buat transaksi pertama untuk memulai"
            action={
              <Button icon={Plus} onClick={openCreate}>
                Transaksi baru
              </Button>
            }
          />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">Invoice</th>
                <th className="text-left px-5 py-3">Pelanggan</th>
                <th className="text-right px-5 py-3">Total</th>
                <th className="text-center px-5 py-3">Status</th>
                <th className="text-left px-5 py-3">Tanggal</th>
                <th className="text-right px-5 py-3 w-40">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((t) => (
                <tr key={t.id} className="hover:bg-stone-50/50 transition">
                  <td className="px-5 py-3 font-mono text-xs text-stone-500">
                    {t.invoice_no}
                  </td>
                  <td className="px-5 py-3 font-medium text-stone-900">
                    {t.customer_name}
                  </td>
                  <td className="px-5 py-3 text-right tabular-nums">
                    Rp {Number(t.total_amount).toLocaleString("id-ID")}
                  </td>
                  <td className="px-5 py-3 text-center">
                    <StatusBadge status={t.status} />
                  </td>
                  <td className="px-5 py-3 text-xs text-stone-400">
                    {t.created_at}
                  </td>
                  <td className="px-5 py-3 text-right">
                    <select
                      value={t.status}
                      onChange={(e) => updateStatus(t.id, e.target.value)}
                      className="text-xs h-8 px-2 bg-white border border-stone-200 rounded-md focus:outline-none focus:border-brand-500"
                    >
                      {["pending", "paid", "partial", "cancelled"].map((s) => (
                        <option key={s}>{s}</option>
                      ))}
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {showForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm">
          <div className="bg-white rounded-xl w-full max-w-2xl shadow-xl max-h-[90vh] overflow-auto">
            <div className="px-6 py-4 border-b border-stone-200 flex items-center justify-between sticky top-0 bg-white">
              <h2 className="text-sm font-semibold text-stone-900">
                Transaksi baru
              </h2>
              <button
                onClick={() => setShowForm(false)}
                className="p-1 text-stone-400 hover:text-stone-700 rounded-md"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <form onSubmit={submit} className="p-6 space-y-5">
              <Select
                label="Pelanggan"
                required
                value={form.customer_id}
                onChange={(e) =>
                  setForm({ ...form, customer_id: e.target.value })
                }
              >
                <option value="">Pilih pelanggan</option>
                {customers.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name} — {c.phone}
                  </option>
                ))}
              </Select>

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="text-xs font-medium text-stone-700">
                    Item
                  </label>
                  <button
                    type="button"
                    onClick={addItem}
                    className="text-xs text-brand-600 hover:text-brand-700 font-medium"
                  >
                    + Tambah item
                  </button>
                </div>
                <div className="space-y-2">
                  {form.items.map((it, i) => (
                    <div key={i} className="flex gap-2">
                      <select
                        required
                        value={it.product_id}
                        onChange={(e) =>
                          updateItem(i, "product_id", e.target.value)
                        }
                        className="flex-1 h-9 px-3 text-sm bg-white border border-stone-200 rounded-lg focus:outline-none focus:border-brand-500"
                      >
                        <option value="">Pilih produk</option>
                        {products.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.name} (stok: {p.stock})
                          </option>
                        ))}
                      </select>
                      <input
                        type="number"
                        min="1"
                        required
                        value={it.qty}
                        onChange={(e) => updateItem(i, "qty", e.target.value)}
                        className="w-20 h-9 px-3 text-sm bg-white border border-stone-200 rounded-lg focus:outline-none focus:border-brand-500"
                      />
                      <button
                        type="button"
                        onClick={() => removeItem(i)}
                        className="p-2 text-stone-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1">
                  Buat transaksi
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setShowForm(false)}
                >
                  Batal
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
