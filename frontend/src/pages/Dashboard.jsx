import { useEffect, useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { productsApi } from '../api/products'
import { transactionsApi } from '../api/transactions'
import { customersApi } from '../api/customers'
import { Card } from '../components/ui'
import { Package, Receipt, AlertTriangle, Users, ArrowUpRight } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function Dashboard() {
  const { user } = useAuth()
  const [stats, setStats] = useState({ products: 0, lowStock: 0, transactions: 0, customers: 0 })
  const [recent, setRecent] = useState([])

  useEffect(() => {
    Promise.all([
      productsApi.list({ per_page: 1 }),
      productsApi.lowStock(10),
      transactionsApi.list({ per_page: 1 }),
      customersApi.list({ per_page: 1 }),
      transactionsApi.list({ per_page: 5 }),
    ]).then(([p, l, t, c, recentTx]) => {
      setStats({
        products: p.data.meta?.total || 0,
        lowStock: l.data.data?.length || 0,
        transactions: t.data.meta?.total || 0,
        customers: c.data.meta?.total || 0,
      })
      setRecent(recentTx.data.data || [])
    }).catch(() => {})
  }, [])

  const cards = [
    { label: 'Produk',      value: stats.products,     icon: Package,       to: '/products',     color: 'text-blue-600 bg-blue-50' },
    { label: 'Stok Rendah', value: stats.lowStock,     icon: AlertTriangle, to: '/products',     color: 'text-amber-600 bg-amber-50' },
    { label: 'Transaksi',   value: stats.transactions, icon: Receipt,       to: '/transactions', color: 'text-emerald-600 bg-emerald-50' },
    { label: 'Pelanggan',   value: stats.customers,    icon: Users,         to: '/customers',    color: 'text-purple-600 bg-purple-50' },
  ]

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-xl font-semibold text-stone-900">
          Halo, {user?.name?.split(' ')[0]} 👋
        </h1>
        <p className="text-sm text-stone-500 mt-0.5">
          Ringkasan aktivitas depot hari ini
        </p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {cards.map((c) => (
          <Link key={c.label} to={c.to}>
            <Card className="p-5 hover:border-brand-300 transition cursor-pointer group">
              <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${c.color}`}>
                <c.icon className="w-4 h-4" />
              </div>
              <p className="text-xs text-stone-500 mt-4">{c.label}</p>
              <div className="flex items-end justify-between mt-1">
                <p className="text-2xl font-semibold text-stone-900">{c.value}</p>
                <ArrowUpRight className="w-4 h-4 text-stone-300 group-hover:text-brand-600 transition" />
              </div>
            </Card>
          </Link>
        ))}
      </div>

      <Card>
        <div className="px-5 py-4 border-b border-stone-200 flex items-center justify-between">
          <h2 className="text-sm font-semibold text-stone-900">Transaksi terbaru</h2>
          <Link to="/transactions" className="text-xs text-brand-600 hover:text-brand-700 font-medium">
            Lihat semua
          </Link>
        </div>
        {recent.length === 0 ? (
          <div className="py-12 text-center text-sm text-stone-400">Belum ada transaksi</div>
        ) : (
          <div className="divide-y divide-stone-100">
            {recent.map((t) => (
              <div key={t.id} className="px-5 py-3 flex items-center justify-between">
                <div className="min-w-0">
                  <p className="text-sm font-medium text-stone-900 truncate">{t.customer_name}</p>
                  <p className="text-xs text-stone-400 font-mono">{t.invoice_no}</p>
                </div>
                <div className="text-right shrink-0 ml-4">
                  <p className="text-sm font-medium text-stone-900">
                    Rp {Number(t.total_amount).toLocaleString('id-ID')}
                  </p>
                  <p className="text-xs text-stone-400 capitalize">{t.status}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </Card>
    </div>
  )
}
