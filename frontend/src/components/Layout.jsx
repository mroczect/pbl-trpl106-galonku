import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import {
  LayoutDashboard, Package, Users, Receipt, Calendar,
  Shield, ScrollText, LogOut, Droplets
} from 'lucide-react'

const menu = [
  { to: '/dashboard',    label: 'Dashboard',    icon: LayoutDashboard, roles: ['admin', 'kurir', 'pelanggan'] },
  { to: '/products',     label: 'Products',     icon: Package,         roles: ['admin', 'kurir', 'pelanggan'] },
  { to: '/transactions', label: 'Transactions', icon: Receipt,         roles: ['admin', 'kurir'] },
  { to: '/customers',    label: 'Customers',    icon: Users,           roles: ['admin', 'kurir'] },
  { to: '/schedules',    label: 'Schedules',    icon: Calendar,        roles: ['admin', 'kurir'] },
]
const adminMenu = [
  { to: '/users', label: 'Users',         icon: Shield },
  { to: '/logs',  label: 'Activity Logs', icon: ScrollText },
]

export default function Layout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const NavItem = ({ to, label, icon: Icon }) => (
    <NavLink
      to={to}
      className={({ isActive }) =>
        `flex items-center gap-2.5 px-3 h-9 rounded-lg text-sm font-medium transition ${
          isActive
            ? 'bg-brand-50 text-brand-700'
            : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900'
        }`
      }
    >
      <Icon className="w-4 h-4 shrink-0" />
      <span className="truncate">{label}</span>
    </NavLink>
  )

  return (
    <div className="flex min-h-screen bg-stone-50">
      {}
      <aside className="w-60 bg-white border-r border-stone-200 flex flex-col shrink-0">
        <div className="h-14 px-4 flex items-center gap-2 border-b border-stone-200">
          <div className="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center">
            <Droplets className="w-4 h-4 text-white" />
          </div>
          <span className="font-semibold text-stone-900">Galonku</span>
        </div>

        <nav className="flex-1 p-3 space-y-0.5 overflow-y-auto">
          {menu
            .filter((m) => m.roles.includes(user?.role_name))
            .map((m) => <NavItem key={m.to} {...m} />)
          }

          {user?.role_name === 'admin' && (
            <>
              <div className="pt-4 pb-1 px-3">
                <p className="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">
                  Admin
                </p>
              </div>
              {adminMenu.map((m) => <NavItem key={m.to} {...m} />)}
            </>
          )}
        </nav>

        <div className="p-3 border-t border-stone-200">
          <div className="flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-stone-50">
            <div className="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 text-xs font-semibold shrink-0">
              {user?.name?.charAt(0)?.toUpperCase()}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-xs font-medium text-stone-900 truncate">{user?.name}</p>
              <p className="text-[11px] text-stone-500 capitalize truncate">{user?.role_name}</p>
            </div>
            <button
              onClick={handleLogout}
              title="Logout"
              className="p-1.5 text-stone-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition"
            >
              <LogOut className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </aside>

      {}
      <main className="flex-1 min-w-0 overflow-auto">
        <div className="max-w-7xl mx-auto px-6 py-6">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
