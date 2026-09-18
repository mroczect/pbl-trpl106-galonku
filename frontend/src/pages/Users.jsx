import { useEffect, useState } from "react";
import { usersApi } from "../api/users";
import {
  Button,
  Card,
  Badge,
  PageHeader,
  EmptyState,
  Loading,
} from "../components/ui";
import toast from "react-hot-toast";
import { Shield } from "lucide-react";

export default function Users() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const res = await usersApi.list({ per_page: 100 });
      setItems(res.data.data || []);
    } catch {
      toast.error("Gagal memuat users");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const toggle = async (u) => {
    try {
      await usersApi.update(u.id, { is_active: u.is_active ? false : true });
      toast.success(u.is_active ? "User dinonaktifkan" : "User diaktifkan");
      load();
    } catch (err) {
      toast.error(err.response?.data?.message || "Gagal memperbarui user");
    }
  };

  const roleVariant = (role) => {
    if (role === "admin") return "brand";
    if (role === "kurir") return "info";
    return "default";
  };

  return (
    <div>
      <PageHeader title="Users" description="Kelola akun dan hak akses" />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState icon={Shield} title="Belum ada user" />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">Nama</th>
                <th className="text-left px-5 py-3">Email</th>
                <th className="text-left px-5 py-3">Role</th>
                <th className="text-center px-5 py-3">Status</th>
                <th className="text-right px-5 py-3 w-32" />
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((u) => (
                <tr key={u.id} className="hover:bg-stone-50/50 transition">
                  <td className="px-5 py-3 font-medium text-stone-900">
                    {u.name}
                  </td>
                  <td className="px-5 py-3 text-stone-500">{u.email}</td>
                  <td className="px-5 py-3">
                    <Badge variant={roleVariant(u.role_name)}>
                      {u.role_name}
                    </Badge>
                  </td>
                  <td className="px-5 py-3 text-center">
                    {u.is_active ? (
                      <Badge variant="success">Aktif</Badge>
                    ) : (
                      <Badge variant="default">Nonaktif</Badge>
                    )}
                  </td>
                  <td className="px-5 py-3 text-right">
                    <Button size="sm" variant="ghost" onClick={() => toggle(u)}>
                      {u.is_active ? "Nonaktifkan" : "Aktifkan"}
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  );
}
