import { useEffect, useState } from 'react'
import { customersApi } from '../api/customers'
import { useAuth } from '../contexts/AuthContext'
import {
  Button, Input, Card, PageHeader, EmptyState, Loading
} from '../components/ui'
import toast from 'react-hot-toast'
import { Plus, Pencil, Trash2, Users, X } from 'lucide-react'

export default function Customers() {
  const { user } = useAuth()
  const isAdmin = user?.role_name === 'admin'
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState({ name: '', phone: '', address: '', notes: '' })

  const load = async () => {
    setLoading(true)
    try {
      const res = await customersApi.list({ per_page: 100 })
      setItems(res.data.data || [])
    } catch {
      toast.error('Gagal memuat pelanggan')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  const openCreate = () => {
    setEditing(null)
    setForm({ name: '', phone: '', address: '', notes: '' })
    setShowForm(true)
  }

  const openEdit = (c) => {
    setEditing(c)
    setForm({
      name: c.name,
      phone: c.phone,
      address: c.address || '',
      notes: c.notes || '',
    })
    setShowForm(true)
  }

  const submit = async (e) => {
    e.preventDefault()
    try {
      if (editing) await customersApi.update(editing.id, form)
      else await customersApi.create(form)
      toast.success(editing ? 'Pelanggan diperbarui' : 'Pelanggan ditambahkan')
      setShowForm(false)
      setEditing(null)
      load()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Gagal menyimpan')
    }
  }

  const remove = async (id) => {
    if (!confirm('Nonaktifkan pelanggan ini?')) return
    try {
      await customersApi.remove(id)
      toast.success('Pelanggan dinonaktifkan')
      load()
    } catch {
      toast.error('Gagal')
    }
  }

  return (
    <div>
      <PageHeader
        title="Pelanggan"
        description="Kelola data pelanggan depot"
        action={<Button icon={Plus} onClick={openCreate}>Tambah pelanggan</Button>}
      />

      <Card className="overflow-hidden">
        {loading ? (
          <Loading />
        ) : items.length === 0 ? (
          <EmptyState
            icon={Users}
            title="Belum ada pelanggan"
            description="Tambahkan pelanggan pertama untuk memulai"
            action={<Button icon={Plus} onClick={openCreate}>Tambah pelanggan</Button>}
          />
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-stone-50 text-stone-500 text-xs font-medium">
              <tr>
                <th className="text-left px-5 py-3">Nama</th>
                <th className="text-left px-5 py-3">Telepon</th>
                <th className="text-left px-5 py-3">Alamat</th>
                <th className="w-24" />
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100">
              {items.map((c) => (
                <tr
                  key={c.id}
                  className={`hover:bg-stone-50/50 transition ${!c.is_active ? 'opacity-40' : ''}`}
                >
                  <td className="px-5 py-3 font-medium text-stone-900">{c.name}</td>
                  <td className="px-5 py-3 text-stone-600">{c.phone}</td>
                  <td className="px-5 py-3 text-stone-500">{c.address || '—'}</td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      <button
                        onClick={() => openEdit(c)}
                        className="p-1.5 rounded-md text-stone-400 hover:text-brand-600 hover:bg-brand-50 transition"
                      >
                        <Pencil className="w-3.5 h-3.5" />
                      </button>
                      {isAdmin && (
                        <button
                          onClick={() => remove(c.id)}
                          className="p-1.5 rounded-md text-stone-400 hover:text-rose-600 hover:bg-rose-50 transition"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
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
                {editing ? 'Edit pelanggan' : 'Tambah pelanggan'}
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
                label="Nama"
                required
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
              <Input
                label="Telepon"
                required
                value={form.phone}
                onChange={(e) => setForm({ ...form, phone: e.target.value })}
              />
              <Input
                label="Alamat"
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
              />
              <Input
                label="Catatan"
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
              />

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1">
                  {editing ? 'Simpan perubahan' : 'Tambah'}
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
  )
}
