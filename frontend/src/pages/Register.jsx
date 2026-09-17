import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import { Button, Input } from "../components/ui";
import toast from "react-hot-toast";
import { Droplets } from "lucide-react";

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    phone: "",
  });
  const [loading, setLoading] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      await register(form);
      toast.success("Registrasi berhasil, silakan login");
      navigate("/login");
    } catch (err) {
      const errors = err.response?.data?.errors;
      const msg = errors
        ? Object.values(errors).flat().join(", ")
        : err.response?.data?.message || "Registrasi gagal";
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center px-6 py-12 bg-stone-50">
      <div className="w-full max-w-sm">
        <div className="flex items-center gap-2 mb-8">
          <div className="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
            <Droplets className="w-4 h-4 text-white" />
          </div>
          <span className="font-semibold text-stone-900">Galonku</span>
        </div>

        <h2 className="text-xl font-semibold text-stone-900 mb-1">Buat akun</h2>
        <p className="text-sm text-stone-500 mb-6">Daftar sebagai pelanggan</p>

        <form onSubmit={submit} className="space-y-4">
          <Input
            label="Nama lengkap"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
          />
          <Input
            label="Email"
            type="email"
            required
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
          />
          <Input
            label="Password"
            type="password"
            required
            minLength={6}
            hint="Minimal 6 karakter"
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
          />
          <Input
            label="No. HP"
            hint="Opsional"
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
          />

          <Button type="submit" loading={loading} className="w-full" size="lg">
            Buat akun
          </Button>
        </form>

        <p className="text-sm text-center text-stone-500 mt-6">
          Sudah punya akun?{" "}
          <Link
            to="/login"
            className="text-brand-600 font-medium hover:text-brand-700"
          >
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}
