import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { Button, Input } from '../components/ui'
import toast from 'react-hot-toast'
import { Droplets } from 'lucide-react'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setLoading(true)
    try {
      const user = await login(form.email, form.password)
      toast.success(`Welcome back, ${user.name}`)
      navigate('/dashboard')
    } catch (err) {
      toast.error(err.response?.data?.message || 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  const fill = (email, password) => setForm({ email, password })

  return (
    <div className="min-h-screen flex">
      {}
      <div className="hidden lg:flex lg:w-1/2 bg-brand-700 relative overflow-hidden">
        <div className="absolute inset-0 opacity-10"
          style={{
            backgroundImage: 'radial-gradient(circle at 20% 30%, white 0%, transparent 40%), radial-gradient(circle at 80% 70%, white 0%, transparent 40%)'
          }}
        />
        <div className="relative z-10 flex flex-col justify-between p-12 text-white">
          <div className="flex items-center gap-2">
            <Droplets className="w-6 h-6" />
            <span className="font-semibold text-lg">Galonku</span>
          </div>
          <div>
            <h1 className="text-3xl font-semibold leading-tight mb-3">
              Kelola depot air<br />lebih rapi.
            </h1>
            <p className="text-brand-100 text-sm max-w-sm">
              Pemesanan, stok, transaksi, dan jadwal pengantaran dalam satu dashboard.
            </p>
          </div>
          <p className="text-xs text-brand-200">© 2026 Galonku</p>
        </div>
      </div>

      {}
      <div className="flex-1 flex items-center justify-center px-6 py-12 bg-stone-50">
        <div className="w-full max-w-sm">
          <div className="lg:hidden flex items-center gap-2 mb-8">
            <div className="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
              <Droplets className="w-4 h-4 text-white" />
            </div>
            <span className="font-semibold text-stone-900">Galonku</span>
          </div>

          <h2 className="text-xl font-semibold text-stone-900 mb-1">Sign in</h2>
          <p className="text-sm text-stone-500 mb-6">Masuk untuk melanjutkan</p>

          <form onSubmit={submit} className="space-y-4">
            <Input
              label="Email"
              type="email"
              required
              placeholder="admin@galonku.com"
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
            />
            <Input
              label="Password"
              type="password"
              required
              placeholder="••••••••"
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
            />
            <Button type="submit" loading={loading} className="w-full" size="lg">
              Sign in
            </Button>
          </form>

          <p className="text-sm text-center text-stone-500 mt-6">
            Belum punya akun?{' '}
            <Link to="/register" className="text-brand-600 font-medium hover:text-brand-700">
              Daftar
            </Link>
          </p>

          <div className="mt-8 pt-6 border-t border-stone-200">
            <p className="text-[11px] font-semibold text-stone-400 uppercase tracking-wider mb-3">
              Demo accounts
            </p>
            <div className="space-y-1.5">
              {[
                ['admin@galonku.com', 'admin123', 'Admin'],
                ['kurir@galonku.com', 'kurir123', 'Courier'],
                ['user@galonku.com', 'pelanggan123', 'Customer'],
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
        </div>
      </div>
    </div>
  )
}
