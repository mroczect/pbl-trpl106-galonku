import { NavLink, Outlet, useNavigate } from "react-router-dom";
import { useState } from "react";
import { useAuth } from "../contexts/AuthContext";
import { LayoutDashboard, LogOut, Droplets, Menu, X } from "lucide-react";

const menu = [
  {
    to: "/dashboard",
    label: "Dashboard",
    icon: LayoutDashboard,
    roles: ["administrator", "agent", "customer"],
  },
];

function NavItem({ to, label, icon: Icon, onClick }) {
  return (
    <NavLink
      to={to}
      onClick={onClick}
      className={({ isActive }) =>
        `group flex items-center gap-2.5 px-3 h-10 rounded-lg text-sm font-medium transition-all relative ${
          isActive
            ? "bg-brand-50 text-brand-700"
            : "text-stone-600 hover:bg-stone-100 hover:text-stone-900"
        }`
      }
    >
      {({ isActive }) => (
        <>
          {isActive && (
            <span className="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full bg-brand-600" />
          )}
          <Icon className="w-4 h-4 shrink-0 ml-0.5" />
          <span className="truncate">{label}</span>
        </>
      )}
    </NavLink>
  );
}

function SidebarContent({ user, onNavigate, onLogout }) {
  const role = user?.role_name ?? user?.role ?? null;
  const visibleMenu = role ? menu.filter((m) => m.roles.includes(role)) : [];

  return (
    <div className="flex flex-col h-full">
      <div className="h-16 px-5 flex items-center gap-2.5 border-b border-stone-100 shrink-0">
        <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-sm">
          <Droplets className="w-4 h-4 text-white" />
        </div>
        <span className="font-semibold text-stone-900 tracking-tight">
          Galonku
        </span>
      </div>

      <nav className="flex-1 p-3 space-y-0.5 overflow-y-auto">
        {visibleMenu.map((m) => (
          <NavItem key={m.to} {...m} onClick={onNavigate} />
        ))}
      </nav>

      <div className="p-3 border-t border-stone-100 shrink-0">
        <div className="flex items-center gap-2.5 px-2 py-2 rounded-lg">
          <div className="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-semibold shrink-0">
            {user?.name?.charAt(0)?.toUpperCase() ?? "?"}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-xs font-semibold text-stone-900 truncate">
              {user?.name ?? "—"}
            </p>
            <p className="text-[11px] text-stone-500 capitalize truncate">
              {role ?? "—"}
            </p>
          </div>
          <button
            onClick={onLogout}
            title="Keluar"
            className="p-2 text-stone-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition shrink-0"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  );
}

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [drawer, setDrawer] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate("/login", { replace: true });
  };

  return (
    <div className="min-h-screen bg-stone-50">
      {}
      <div className="lg:hidden sticky top-0 z-30 h-14 px-4 flex items-center justify-between bg-white border-b border-stone-200">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center">
            <Droplets className="w-4 h-4 text-white" />
          </div>
          <span className="font-semibold text-stone-900">Galonku</span>
        </div>
        <button
          onClick={() => setDrawer(true)}
          className="p-2 -mr-2 text-stone-600 hover:bg-stone-100 rounded-md"
        >
          <Menu className="w-5 h-5" />
        </button>
      </div>

      {}
      <aside className="hidden lg:flex fixed inset-y-0 left-0 w-64 bg-white border-r border-stone-200 flex-col z-20">
        <SidebarContent user={user} onLogout={handleLogout} />
      </aside>

      {}
      {drawer && (
        <div className="lg:hidden fixed inset-0 z-50">
          <div
            className="absolute inset-0 bg-stone-900/40 backdrop-blur-sm"
            onClick={() => setDrawer(false)}
          />
          <aside className="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-white shadow-xl animate-slide-in-right">
            <div className="flex justify-end p-2">
              <button
                onClick={() => setDrawer(false)}
                className="p-2 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-md"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="h-[calc(100%-3.5rem)]">
              <SidebarContent
                user={user}
                onNavigate={() => setDrawer(false)}
                onLogout={handleLogout}
              />
            </div>
          </aside>
        </div>
      )}

      {}
      <main className="lg:pl-64">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
