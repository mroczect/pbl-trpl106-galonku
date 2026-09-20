import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import { Button, Input } from "../components/ui";
import toast from "react-hot-toast";
import { Droplets, ArrowRight } from "lucide-react";

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: "", password: "" });
  const [loading, setLoading] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const u = await login(form.email, form.password);
      toast.success(`Selamat datang, ${u.name}`);
      navigate("/dashboard", { replace: true });
    } catch (err) {
      const status = err.response?.status;
      const msg = err.response?.data?.message;
      if (status === 429) {
        const retry = err.response?.headers?.["retry-after"];
        toast.error(
          `Terlalu banyak percobaan. Coba lagi dalam ${retry || 60} detik.`,
        );
      } else if (status === 401) {
        toast.error("Email atau password salah");
      } else {
        toast.error(msg || "Login gagal");
      }
    } finally {
      setLoading(false);
    }
  };

  const fill = (email, password) => setForm({ email, password });

  return (
    <div className="min-h-screen flex">
      {}
      <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950 relative overflow-hidden">
        <div
          className="absolute inset-0 opacity-20"
          style={{
            backgroundImage:
              "radial-gradient(circle at 20% 20%, white 0%, transparent 35%), radial-gradient(circle at 80% 70%, white 0%, transparent 35%)",
          }}
        />
        <div className="relative z-10 flex flex-col justify-between p-12 text-white">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center">
              <Droplets className="w-5 h-5" />
            </div>
            <span className="font-semibold text-lg tracking-tight">
              Galonku
            </span>
          </div>
          <div>
            <h1 className="text-4xl font-semibold leading-tight mb-4">
              Kelola depot air
              <br />
              lebih rapi & cepat.
            </h1>
            <p className="text-brand-100/90 text-sm max-w-sm leading-relaxed">
              Manajemen stok, transaksi, pelanggan, dan jadwal pengantaran dalam
              satu dashboard yang intuitif.
            </p>
          </div>
          <p className="text-xs text-brand-200/70">
            © 2026 Galonku. All rights reserved.
          </p>
        </div>
      </div>

      {}
      <div className="flex-1 flex items-center justify-center px-6 py-12 bg-stone-50">
        <div className="w-full max-w-sm animate-fade-in">
          <div className="lg:hidden flex items-center gap-2 mb-8">
            <div className="w-9 h-9 rounded-xl bg-brand-600 flex items-center justify-center">
              <Droplets className="w-5 h-5 text-white" />
            </div>
            <span className="font-semibold text-stone-900 text-lg">
              Galonku
            </span>
          </div>

          <h2 className="text-2xl font-semibold text-stone-900 tracking-tight">
            Masuk
          </h2>
          <p className="text-sm text-stone-500 mt-1 mb-7">
            Selamat datang kembali, silakan masuk
          </p>

          <form onSubmit={submit} className="space-y-4">
            <Input
              label="Email"
              type="email"
              required
              autoComplete="email"
              placeholder="admin@galonku.com"
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
            />
            <Input
              label="Password"
              type="password"
              required
              autoComplete="current-password"
              placeholder="••••••••"
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
            />
            <Button
              type="submit"
              loading={loading}
              className="w-full"
              size="lg"
            >
              Masuk <ArrowRight className="w-4 h-4" />
            </Button>
          </form>

          <p className="text-sm text-center text-stone-500 mt-6">
            Belum punya akun?{" "}
            <Link
              to="/register"
              className="text-brand-600 font-medium hover:text-brand-700"
            >
              Daftar
            </Link>
          </p>

          {import.meta.env.DEV && (
            <div className="mt-8 pt-6 border-t border-stone-200">
              <p className="text-[11px] font-semibold text-stone-400 uppercase tracking-wider mb-3">
                Akun Demo (dev)
              </p>
              <div className="space-y-1.5">
                {[
                  ["admin@galonku.com", "admin123", "Administrator"],
                  ["agent@galonku.com", "agent123", "Agent"],
                  ["customer@galonku.com", "customer123", "Customer"],
                ].map(([email, password, role]) => (
                  <button
                    key={email}
                    type="button"
                    onClick={() => fill(email, password)}
                    className="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg border border-stone-200 hover:border-brand-300 hover:bg-brand-50/50 transition text-left"
                  >
                    <span className="text-stone-700 font-medium">{role}</span>
                    <span className="text-stone-400">{email}</span>
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
