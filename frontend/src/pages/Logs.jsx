import { useEffect, useState } from 'react'
import { usersApi } from '../api/users'
import { Card, Badge, PageHeader, EmptyState, Loading } from '../components/ui'
import toast from 'react-hot-toast'
import { ScrollText } from 'lucide-react'

export default function Logs() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    usersApi.logs(200)
      .then((r) => setItems(r.data.data || []))
      .catch(() => toast.error('Gagal memuat logs'))
      .finally(() => setLoading(false))
  }, [])

  const actionVariant = (action) => {
    if (!action) return 'default'
    if (action.includes('create')) return 'success'
    if (action.includes('update')) return 'info'
    if (action.includes('delete')) return 'danger'
    return 'default'
  }

  return (
    <div>
      <PageHeader title="Activity Logs" description="Riwayat aktivitas sistem" />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState icon={ScrollText} title="Belum ada log" />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">Waktu</th>
                <th className="text-left px-5 py-3">User</th>
                <th className="text-left px-5 py-3">Aksi</th>
                <th className="text-left px-5 py-3">Entitas</th>
                <th className="text-left px-5 py-3">IP</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((l) => (
                <tr key={l.id} className="hover:bg-stone-50/50 transition">
                  <td className="px-5 py-3 font-mono text-xs text-stone-400">
                    {l.created_at}
                  </td>
                  <td className="px-5 py-3 text-stone-700">{l.user_name || '—'}</td>
                  <td className="px-5 py-3">
                    <Badge variant={actionVariant(l.action)}>{l.action}</Badge>
                  </td>
                  <td className="px-5 py-3 text-stone-600">
                    {l.entity} {l.entity_id ? `#${l.entity_id}` : ''}
                  </td>
                  <td className="px-5 py-3 font-mono text-xs text-stone-400">
                    {l.ip_address || '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}
