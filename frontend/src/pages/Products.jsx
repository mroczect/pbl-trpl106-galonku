import { useEffect, useState } from "react";
import { productsApi } from "../api/products";
import { useAuth } from "../contexts/AuthContext";
import {
  Button,
  Input,
  Select,
  Card,
  Badge,
  PageHeader,
  EmptyState,
  Loading,
} from "../components/ui";
import toast from "react-hot-toast";
import { Plus, Pencil, Trash2, Package, X } from "lucide-react";

export default function Products() {
  const { user } = useAuth();
  const isAdmin = user?.role_name === "admin";
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    sku: "",
    name: "",
    category: "galon",
    price: "",
    stock: "",
  });

  const load = async () => {
    setLoading(true);
    try {
      const res = await productsApi.list({ per_page: 100 });
      setItems(res.data.data || []);
    } catch {
      toast.error("Gagal memuat produk");
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => {
    load();
  }, []);

  const openCreate = () => {
    setEditing(null);
    setForm({ sku: "", name: "", category: "galon", price: "", stock: "" });
    setShowForm(true);
  };

  const openEdit = (p) => {
    setEditing(p);
    setForm({
      sku: p.sku,
      name: p.name,
      category: p.category,
      price: p.price,
      stock: p.stock,
    });
    setShowForm(true);
  };

  const submit = async (e) => {
    e.preventDefault();
    try {
      const payload = {
        ...form,
        price: Number(form.price),
        stock: Number(form.stock),
      };
      if (editing) await productsApi.update(editing.id, payload);
      else await productsApi.create(payload);
      toast.success(editing ? "Produk diperbarui" : "Produk ditambahkan");
      setShowForm(false);
      load();
    } catch (err) {
      toast.error(err.response?.data?.message || "Gagal menyimpan");
    }
  };

  const remove = async (id) => {
    if (!confirm("Nonaktifkan produk ini?")) return;
    try {
      await productsApi.remove(id);
      toast.success("Produk dinonaktifkan");
      load();
    } catch {
      toast.error("Gagal");
    }
  };

  return (
    <div>
      <PageHeader
        title="Produk"
        description="Kelola katalog produk depot"
        action={
          isAdmin && (
            <Button icon={Plus} onClick={openCreate}>
              Tambah produk
            </Button>
          )
        }
      />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState
            icon={Package}
            title="Belum ada produk"
            description="Tambahkan produk pertama untuk memulai"
            action={
              isAdmin && (
                <Button icon={Plus} onClick={openCreate}>
                  Tambah produk
                </Button>
              )
            }
          />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">SKU</th>
                <th className="text-left px-5 py-3">Nama</th>
                <th className="text-left px-5 py-3">Kategori</th>
                <th className="text-right px-5 py-3">Harga</th>
                <th className="text-right px-5 py-3">Stok</th>
                {isAdmin && <th className="w-24" />}
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((p) => (
                <tr
                  key={p.id}
                  className={`hover:bg-stone-50/50 transition ${!p.is_active ? "opacity-40" : ""}`}
                >
                  <td className="px-5 py-3 font-mono text-xs text-stone-500">
                    {p.sku}
                  </td>
                  <td className="px-5 py-3 font-medium text-stone-900">
                    {p.name}
                  </td>
                  <td className="px-5 py-3">
                    <Badge>{p.category}</Badge>
                  </td>
                  <td className="px-5 py-3 text-right tabular-nums">
                    Rp {Number(p.price).toLocaleString("id-ID")}
                  </td>
                  <td className="px-5 py-3 text-right tabular-nums">
                    {Number(p.stock) <= 10 ? (
                      <Badge variant="warning">{p.stock}</Badge>
                    ) : (
                      <span className="text-stone-700">{p.stock}</span>
                    )}
                  </td>
                  {isAdmin && (
                    <td className="px-5 py-3 text-right">
                      <div className="flex justify-end gap-1">
                        <button
                          onClick={() => openEdit(p)}
                          className="p-1.5 rounded-md text-stone-400 hover:text-brand-600 hover:bg-brand-50 transition"
                        >
                          <Pencil className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => remove(p.id)}
                          className="p-1.5 rounded-md text-stone-400 hover:text-rose-600 hover:bg-rose-50 transition"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {}
      {showForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm">
          <div className="bg-white rounded-xl w-full max-w-lg shadow-xl">
            <div className="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-stone-900">
                {editing ? "Edit produk" : "Tambah produk"}
              </h2>
              <button
                onClick={() => setShowForm(false)}
                className="p-1 text-stone-400 hover:text-stone-700 rounded-md"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <form onSubmit={submit} className="p-6 space-y-4">
              <Input
                label="SKU"
                required
                value={form.sku}
                disabled={!!editing}
                onChange={(e) => setForm({ ...form, sku: e.target.value })}
              />
              <Input
                label="Nama produk"
                required
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
              <div className="grid grid-cols-2 gap-4">
                <Select
                  label="Kategori"
                  value={form.category}
                  onChange={(e) =>
                    setForm({ ...form, category: e.target.value })
                  }
                >
                  {["galon", "air", "aksesoris", "lain"].map((c) => (
                    <option key={c} value={c}>
                      {c}
                    </option>
                  ))}
                </Select>
                <Input
                  label="Harga"
                  type="number"
                  required
                  value={form.price}
                  onChange={(e) => setForm({ ...form, price: e.target.value })}
                />
              </div>
              <Input
                label="Stok"
                type="number"
                value={form.stock}
                onChange={(e) => setForm({ ...form, stock: e.target.value })}
              />

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1">
                  {editing ? "Simpan perubahan" : "Tambah"}
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
