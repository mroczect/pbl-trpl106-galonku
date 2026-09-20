import { useAuth } from "../contexts/AuthContext";
import { Card } from "../components/ui";

export default function Dashboard() {
  const { user } = useAuth();

  return (
    <div className="animate-fade-in space-y-6">
      <div>
        <h1 className="text-xl lg:text-2xl font-semibold text-stone-900 tracking-tight">
          Halo, {user?.name?.split(" ")[0] ?? "User"} 👋
        </h1>
        <p className="text-sm text-stone-500 mt-1">
          Selamat datang di dashboard Galonku.
        </p>
      </div>

      <Card className="p-6">
        <h2 className="text-sm font-semibold text-stone-900 mb-4">
          Informasi Akun
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-xs text-stone-500">Nama</p>
            <p className="text-stone-900 font-medium mt-0.5">{user?.name}</p>
          </div>
          <div>
            <p className="text-xs text-stone-500">Email</p>
            <p className="text-stone-900 font-medium mt-0.5">{user?.email}</p>
          </div>
          <div>
            <p className="text-xs text-stone-500">Telepon</p>
            <p className="text-stone-900 font-medium mt-0.5">
              {user?.phone || "—"}
            </p>
          </div>
          <div>
            <p className="text-xs text-stone-500">Role</p>
            <p className="text-stone-900 font-medium mt-0.5 capitalize">
              {user?.role_name}
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
