import { useEffect, useState } from "react";
import { schedulesApi } from "../api/schedules";
import { customersApi } from "../api/customers";
import { usersApi } from "../api/users";
import { useAuth } from "../contexts/AuthContext";
import {
  Button,
  Input,
  Select,
  Card,
  PageHeader,
  EmptyState,
  Loading,
  StatusBadge,
} from "../components/ui";
import toast from "react-hot-toast";
import { Plus, Calendar, X } from "lucide-react";

const toBackendDateTime = (localValue) => {
  if (!localValue) return "";
  const [d, t] = localValue.split("T");
  if (!t) return "";
  const [hh, mm] = t.split(":");
  return `${d} ${hh}:${mm}:00`;
};

export default function Schedules() {
  const { user } = useAuth();
  const role = user?.role_name ?? null;
  const isAdmin = role === "admin";
  const isKurir = role === "kurir";

  const [items, setItems] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [couriers, setCouriers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({
    customer_id: "",
    user_id: "",
    scheduled_at: "",
    notes: "",
  });

  const load = async () => {
    setLoading(true);
    try {
      const [s, c] = await Promise.all([
        schedulesApi.list({ per_page: 50 }),
        customersApi.list({ per_page: 100 }),
      ]);
      setItems(s.data?.data ?? []);
      setCustomers(c.data?.data ?? []);
    } catch {
      toast.error("Gagal memuat data");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!isAdmin) return;
    let cancelled = false;
    usersApi
      .list({ per_page: 100 })
      .then((r) => {
        if (cancelled) return;
        const all = r.data?.data ?? [];
        setCouriers(all.filter((x) => x.role_name === "kurir"));
      })
      .catch(() => {
        if (!cancelled) setCouriers([]);
      });
    return () => {
      cancelled = true;
    };
  }, [isAdmin]);

  useEffect(() => {
    load();
  }, []);

  const openCreate = () => {
    setForm({
      customer_id: "",
      user_id: isKurir && user?.id ? String(user.id) : "",
      scheduled_at: "",
      notes: "",
    });
    setShowForm(true);
  };

  const submit = async (e) => {
    e.preventDefault();

    const resolvedUserId = isKurir ? user?.id : Number(form.user_id);

    if (!form.customer_id || !resolvedUserId || !form.scheduled_at) {
      toast.error("Lengkapi pelanggan, kurir, dan jadwal");
      return;
    }

    setSubmitting(true);
    try {
      await schedulesApi.create({
        customer_id: Number(form.customer_id),
        user_id: resolvedUserId,
        scheduled_at: toBackendDateTime(form.scheduled_at),
        notes: form.notes || null,
      });
      toast.success("Jadwal dibuat");
      setShowForm(false);
      load();
    } catch (err) {
      const data = err.response?.data;
      const errors = data?.errors;
      toast.error(
        errors
          ? Object.values(errors).flat().join(", ")
          : data?.message || "Gagal membuat jadwal",
      );
    } finally {
      setSubmitting(false);
    }
  };

  const updateStatus = async (id, status) => {
    try {
      await schedulesApi.updateStatus(id, status);
      toast.success("Status diperbarui");
      load();
    } catch {
      toast.error("Gagal memperbarui status");
    }
  };

  return (
    <div>
      <PageHeader
        title="Jadwal"
        description="Kelola jadwal pengantaran"
        action={
          <Button icon={Plus} onClick={openCreate}>
            Jadwal baru
          </Button>
        }
      />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState
            icon={Calendar}
            title="Belum ada jadwal"
            description="Buat jadwal pengantaran pertama"
            action={
              <Button icon={Plus} onClick={openCreate}>
                Jadwal baru
              </Button>
            }
          />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">Pelanggan</th>
                <th className="text-left px-5 py-3">Kurir</th>
                <th className="text-left px-5 py-3">Jadwal</th>
                <th className="text-center px-5 py-3">Status</th>
                <th className="text-right px-5 py-3 w-40">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((s) => (
                <tr key={s.id} className="hover:bg-stone-50/50 transition">
                  <td className="px-5 py-3 font-medium text-stone-900">
                    {s.customer_name ?? "—"}
                  </td>
                  <td className="px-5 py-3 text-stone-600">
                    {s.user_name ?? "—"}
                  </td>
                  <td className="px-5 py-3 text-xs text-stone-400">
                    {s.scheduled_at}
                  </td>
                  <td className="px-5 py-3 text-center">
                    <StatusBadge status={s.status} />
                  </td>
                  <td className="px-5 py-3 text-right">
                    <select
                      value={s.status}
                      onChange={(e) => updateStatus(s.id, e.target.value)}
                      className="text-xs h-8 px-2 bg-white border border-stone-200 rounded-md focus:outline-none focus:border-brand-500"
                    >
                      {["pending", "on_route", "done", "cancelled"].map(
                        (st) => (
                          <option key={st}>{st}</option>
                        ),
                      )}
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
          <div className="bg-white rounded-xl w-full max-w-lg shadow-xl">
            <div className="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-stone-900">
                Jadwal baru
              </h2>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="p-1 text-stone-400 hover:text-stone-700 rounded-md"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <form onSubmit={submit} className="p-6 space-y-4">
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

              {isAdmin ? (
                <Select
                  label="Kurir"
                  required
                  value={form.user_id}
                  onChange={(e) =>
                    setForm({ ...form, user_id: e.target.value })
                  }
                >
                  <option value="">Pilih kurir</option>
                  {couriers.length === 0 ? (
                    <option value="" disabled>
                      Tidak ada kurir tersedia
                    </option>
                  ) : (
                    couriers.map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name}
                      </option>
                    ))
                  )}
                </Select>
              ) : (
                <Input
                  label="Kurir"
                  value={user?.name ?? "Anda"}
                  disabled
                  hint="Jadwal otomatis ditugaskan ke Anda"
                />
              )}

              <Input
                label="Jadwal"
                type="datetime-local"
                required
                value={form.scheduled_at}
                onChange={(e) =>
                  setForm({ ...form, scheduled_at: e.target.value })
                }
              />

              <Input
                label="Catatan"
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
              />

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1" loading={submitting}>
                  Buat jadwal
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
