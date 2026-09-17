# Galonku Frontend

React + Vite frontend for the Galonku water depot management system. Consumes the Galonku Backend API (PHP 8.1 + PDO). Provides authentication, dashboard analytics, product inventory, customer records, transaction processing, delivery scheduling, user management, and activity logs across role-based interfaces.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Getting Started](#getting-started)
  - [Installation](#installation)
  - [Environment Variables](#environment-variables)
  - [Running the Dev Server](#running-the-dev-server)
  - [Building for Production](#building-for-production)
- [Project Structure](#project-structure)
- [Routing](#routing)
- [Role-Based Access](#role-based-access)
- [API Integration](#api-integration)
  - [Axios Client](#axios-client)
  - [Interceptors](#interceptors)
  - [Endpoint Modules](#endpoint-modules)
- [Authentication Flow](#authentication-flow)
- [State Management](#state-management)
- [UI System](#ui-system)
  - [Design Tokens](#design-tokens)
  - [Shared Components](#shared-components)
- [Pages](#pages)
- [Styling](#styling)
- [Icons and Assets](#icons-and-assets)
- [Error Handling](#error-handling)
- [Notifications](#notifications)
- [Development Notes](#development-notes)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Overview

Galonku Frontend is a single-page application (SPA) that serves three roles:

- **Admin** — full access: manage users, activity logs, products, customers, transactions, and delivery schedules.
- **Kurir (Courier)** — operational access: view products, manage customers, process transactions, and handle delivery schedules.
- **Pelanggan (Customer)** — limited access: view dashboard and product catalog.

The app is built around a sidebar layout with role-filtered navigation, backed by a JWT-based authentication system, and uses a shared UI component library to keep styling consistent across all pages.

---

## Tech Stack

| Layer         | Technology                                   |
| ------------- | -------------------------------------------- |
| Framework     | React 19                                     |
| Build Tool    | Vite 8 (Rolldown-powered)                    |
| Routing       | React Router DOM 7                           |
| HTTP Client   | Axios 1.20                                   |
| Styling       | Tailwind CSS v4 (via `@tailwindcss/vite`)    |
| Icons         | Lucide React                                 |
| Notifications | React Hot Toast                              |
| Linting       | ESLint 10 with React Hooks & Refresh plugins |
| Package Mgr   | Bun (lockfile present) — npm/pnpm also work  |

---

## Requirements

| Component | Version                                                |
| --------- | ------------------------------------------------------ |
| Node.js   | 20.19+ or 22.12+                                       |
| Bun       | 1.1+ (recommended)                                     |
| Backend   | Galonku Backend API running on `http://localhost:8000` |

If you use npm or pnpm instead of Bun, remove `bun.lockb` and install with your preferred manager.

---

## Getting Started

### Installation

```bash
git clone https://github.com/mroczect/pbl-trpl106-galonku
cd pbl-trpl106-galonku/frontend
bun install
```

Or with npm:

```bash
npm install
```

### Environment Variables

Vite reads variables prefixed with `VITE_` from a `.env` file at the project root.

Create `.env`:

```bash
VITE_API_URL=http://localhost:8000/api/v1
```

| Variable       | Default                        | Description                         |
| -------------- | ------------------------------ | ----------------------------------- |
| `VITE_API_URL` | `http://localhost:8000/api/v1` | Base URL of the Galonku Backend API |

If `VITE_API_URL` is unset, the client falls back to `http://localhost:8000/api/v1`.

### Running the Dev Server

```bash
bun dev
```

The app starts at `http://localhost:5173`.

`vite.config.js` includes a dev proxy for `/api`:

```js
server: {
  port: 5173,
  proxy: {
    '/api': {
      target: 'http://localhost:8000',
      changeOrigin: true,
    },
  },
}
```

This lets you call `/api/v1/...` from the browser and have Vite forward the request to the backend during development. It is only used if you point `VITE_API_URL` at `/api/v1` (relative).

### Building for Production

```bash
bun run build
```

Output is written to `dist/`. Serve it from any static host (Nginx, Caddy, Netlify, Vercel, Cloudflare Pages) with a fallback to `index.html` for client-side routing.

Example Nginx block:

```nginx
server {
    listen 80;
    server_name app.example.com;
    root /var/www/galonku-frontend/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

Preview the production build locally:

```bash
bun run preview
```

---

## Project Structure

```
frontend/
├── public/
│   ├── favicon.svg
│   └── icons.svg
├── src/
│   ├── api/                    Axios client and endpoint modules
│   │   ├── auth.js
│   │   ├── client.js
│   │   ├── customers.js
│   │   ├── products.js
│   │   ├── schedules.js
│   │   ├── transactions.js
│   │   └── users.js
│   ├── components/             Shared UI and layout
│   │   ├── Layout.jsx
│   │   ├── ProtectedRoute.jsx
│   │   └── ui.jsx
│   ├── contexts/
│   │   └── AuthContext.jsx
│   ├── pages/
│   │   ├── Customers.jsx
│   │   ├── Dashboard.jsx
│   │   ├── Login.jsx
│   │   ├── Logs.jsx
│   │   ├── Products.jsx
│   │   ├── Register.jsx
│   │   ├── Schedules.jsx
│   │   ├── Transactions.jsx
│   │   └── Users.jsx
│   ├── App.jsx                 Route tree and providers
│   ├── index.css               Tailwind theme and global styles
│   └── main.jsx                React entry point
├── eslint.config.js
├── index.html
├── package.json
├── vite.config.js
└── README.md
```

---

## Routing

Routes are declared in `src/App.jsx` using `react-router-dom` v7.

| Path            | Component      | Access                     |
| --------------- | -------------- | -------------------------- |
| `/login`        | `Login`        | Public                     |
| `/register`     | `Register`     | Public                     |
| `/dashboard`    | `Dashboard`    | All authenticated          |
| `/products`     | `Products`     | All authenticated          |
| `/customers`    | `Customers`    | Admin, Kurir               |
| `/transactions` | `Transactions` | Admin, Kurir               |
| `/schedules`    | `Schedules`    | Admin, Kurir               |
| `/users`        | `Users`        | Admin only                 |
| `/logs`         | `Logs`         | Admin only                 |
| `*`             | Redirect       | Falls back to `/dashboard` |

The layout nests protected routes under `ProtectedRoute` and `Layout`:

```jsx
<Route element={<ProtectedRoute />}>
  <Route element={<Layout />}>
    <Route path="/dashboard" element={<Dashboard />} />
    {/* ... */}
    <Route element={<ProtectedRoute roles={["admin"]} />}>
      <Route path="/users" element={<Users />} />
      <Route path="/logs" element={<Logs />} />
    </Route>
  </Route>
</Route>
```

---

## Role-Based Access

`ProtectedRoute` reads the `user` object from `AuthContext` and enforces two conditions:

1. **Authentication** — if no user is present, redirect to `/login`.
2. **Authorization** — if `roles` is provided and the user's `role_name` is not in the list, redirect to `/dashboard`.

```jsx
export default function ProtectedRoute({ roles }) {
  const { user, loading } = useAuth();
  if (loading) return <Loader />;
  if (!user) return <Navigate to="/login" replace />;
  if (roles && !roles.includes(user.role_name)) {
    return <Navigate to="/dashboard" replace />;
  }
  return <Outlet />;
}
```

The sidebar in `Layout.jsx` also filters its menu items based on `user.role_name`, so unauthorized links are hidden from navigation entirely.

Role constants used throughout the app:

| Role     | `role_name` | Description             |
| -------- | ----------- | ----------------------- |
| Admin    | `admin`     | Full system access      |
| Courier  | `kurir`     | Operational access      |
| Customer | `pelanggan` | Read-only on most pages |

---

## API Integration

### Axios Client

All HTTP calls go through a single Axios instance in `src/api/client.js`:

```js
import axios from "axios";

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api/v1",
  headers: { "Content-Type": "application/json" },
});
```

### Interceptors

**Request interceptor** — attaches the JWT access token from `localStorage` to every outgoing request:

```js
client.interceptors.request.use((config) => {
  const token = localStorage.getItem("access_token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

**Response interceptor** — on a `401` response (except from `/auth/login`), clears stored credentials and redirects to `/login`:

```js
client.interceptors.response.use(
  (res) => res,
  (err) => {
    if (
      err.response?.status === 401 &&
      !err.config.url.includes("/auth/login")
    ) {
      localStorage.removeItem("access_token");
      localStorage.removeItem("refresh_token");
      localStorage.removeItem("user");
      window.location.href = "/login";
    }
    return Promise.reject(err);
  },
);
```

### Endpoint Modules

Each API resource has its own module under `src/api/`, exposing typed wrapper functions.

| Module            | Functions                                                |
| ----------------- | -------------------------------------------------------- |
| `auth.js`         | `register`, `login`, `refresh`, `me`, `logout`           |
| `products.js`     | `list`, `show`, `lowStock`, `create`, `update`, `remove` |
| `customers.js`    | `list`, `show`, `create`, `update`, `remove`             |
| `transactions.js` | `list`, `show`, `create`, `updateStatus`                 |
| `schedules.js`    | `list`, `show`, `create`, `updateStatus`                 |
| `users.js`        | `list`, `show`, `update`, `remove`, `roles`, `logs`      |

Example from `products.js`:

```js
export const productsApi = {
  list: (params) => client.get("/products", { params }),
  show: (id) => client.get(`/products/${id}`),
  lowStock: (threshold = 10) =>
    client.get("/products/low-stock", { params: { threshold } }),
  create: (data) => client.post("/products", data),
  update: (id, data) => client.put(`/products/${id}`, data),
  remove: (id) => client.delete(`/products/${id}`),
};
```

---

## Authentication Flow

Authentication is handled by `AuthContext` (`src/contexts/AuthContext.jsx`), a React context that provides `user`, `loading`, `login`, `register`, and `logout` to the rest of the app.

### Boot sequence

1. On mount, `AuthProvider` reads `user` from `localStorage` (if any) so the UI renders instantly with a cached identity.
2. If an `access_token` exists, it calls `authApi.me()` to validate the token and refresh the user record.
3. If validation fails, `localStorage` is cleared and the user is set to `null`.
4. `loading` is set to `false` once the check completes.

### Login

```js
const login = async (email, password) => {
  const res = await authApi.login({ email, password });
  const { user, access_token, refresh_token } = res.data.data;
  localStorage.setItem("access_token", access_token);
  localStorage.setItem("refresh_token", refresh_token);
  localStorage.setItem("user", JSON.stringify(user));
  setUser(user);
  return user;
};
```

### Logout

```js
const logout = async () => {
  try {
    await authApi.logout();
  } catch {}
  localStorage.clear();
  setUser(null);
};
```

The logout call is best-effort: even if the API request fails, the local session is cleared. This prevents a stuck session if the backend is unreachable.

### Stored keys

| Key             | Content                        |
| --------------- | ------------------------------ |
| `access_token`  | JWT access token (short-lived) |
| `refresh_token` | JWT refresh token (long-lived) |
| `user`          | JSON-serialized user object    |

> **Note:** Token refresh is not currently automated. When the access token expires, the next request returns 401 and the response interceptor redirects to `/login`. To add silent refresh, hook into the response interceptor and call `authApi.refresh(refresh_token)` before redirecting.

---

## State Management

The app uses React's built-in state primitives rather than a state library:

- **AuthContext** — global authentication state (user, loading).
- **Local component state** — page-level lists, forms, modals, filters.
- **URL state** — routing via React Router.

There is no Redux, Zustand, or React Query. Data fetching is done directly inside `useEffect` hooks in each page, with manual `load()` calls after mutations.

If you plan to expand, consider adding [TanStack Query](https://tanstack.com/query) for caching and background refetching, or [Zustand](https://zustand-demo.pmnd.rs/) for cross-page state.

---

## UI System

### Design Tokens

The design system is defined in `src/index.css` using Tailwind CSS v4's `@theme` directive.

**Typography** — Inter from Google Fonts, declared in `index.html`.

**Brand palette** — a teal scale inspired by water:

| Token               | Value     | Usage                            |
| ------------------- | --------- | -------------------------------- |
| `--color-brand-50`  | `#f0fdfa` | Backgrounds, hover states        |
| `--color-brand-100` | `#ccfbf1` | Avatars, badges                  |
| `--color-brand-200` | `#99f6e4` | Borders on brand elements        |
| `--color-brand-500` | `#14b8a6` | Focus rings, accents             |
| `--color-brand-600` | `#0d9488` | Primary buttons, links           |
| `--color-brand-700` | `#0f766e` | Hover on primary, brand panel bg |
| `--color-brand-800` | `#115e59` | Deep accents                     |
| `--color-brand-900` | `#134e4a` | Contrast text on light brand bg  |

**Neutrals** — Tailwind's `stone` scale is used for all grays.

**Global styles** — `body` is set to `bg-stone-50 text-stone-900`, and a custom thin scrollbar is applied via `::-webkit-scrollbar`.

### Shared Components

All shared primitives live in `src/components/ui.jsx`.

#### `<Button>`

```jsx
<Button variant="primary" size="md" loading={false} icon={Plus} onClick={...}>
  Tambah produk
</Button>
```

| Prop      | Values                                          | Default   |
| --------- | ----------------------------------------------- | --------- |
| `variant` | `primary`, `secondary`, `ghost`, `danger`       | `primary` |
| `size`    | `sm`, `md`, `lg`                                | `md`      |
| `loading` | boolean — shows spinner and disables the button | `false`   |
| `icon`    | Lucide component                                | —         |

#### `<Input>`

```jsx
<Input label="Nama" hint="Nama lengkap" error={errors.name} value={...} />
```

Renders a label, an input with a focus ring in brand color, and optional hint or error text.

#### `<Select>`

Same label styling as `Input`, wraps a native `<select>`.

#### `<Card>`

A `div` with `bg-white border border-stone-200 rounded-xl`. Compose freely inside.

#### `<Badge>`

```jsx
<Badge variant="success">Aktif</Badge>
```

| Variant   | Color scheme |
| --------- | ------------ |
| `default` | Stone        |
| `success` | Emerald      |
| `warning` | Amber        |
| `danger`  | Rose         |
| `info`    | Blue         |
| `brand`   | Teal (brand) |

#### `<StatusBadge>`

Maps backend status strings to Badge variants:

| Status      | Variant   |
| ----------- | --------- |
| `pending`   | `warning` |
| `paid`      | `success` |
| `partial`   | `info`    |
| `cancelled` | `danger`  |
| `on_route`  | `info`    |
| `done`      | `success` |

#### `<PageHeader>`

```jsx
<PageHeader
  title="Produk"
  description="Kelola katalog produk depot"
  action={<Button icon={Plus}>Tambah</Button>}
/>
```

#### `<EmptyState>`

Renders an icon inside a circular background, a title, a description, and an optional action button. Used when lists return zero items.

#### `<Loading>`

A centered spinner shown while data is being fetched.

---

## Pages

### `Login.jsx`

Split-screen layout. Left panel: teal brand panel with a headline and copyright. Right panel: sign-in form with three clickable demo account buttons that auto-fill credentials:

- `admin@galonku.com` / `admin123`
- `kurir@galonku.com` / `kurir123`
- `user@galonku.com` / `pelanggan123`

On success, redirects to `/dashboard`.

### `Register.jsx`

Self-contained registration form. Creates a new user with the `pelanggan` role. Displays server-side validation errors by flattening the `errors` object into a single toast message.

### `Dashboard.jsx`

Fetches aggregate stats in parallel:

- Total products
- Count of low-stock products (threshold 10)
- Total transactions
- Total customers

Also displays the five most recent transactions with customer name, invoice number, total, and status.

The stat cards link to their respective pages and use a subtle hover state that shifts the border to brand color.

### `Products.jsx`

Paginated product table with columns: SKU, name, category, price, stock, and actions.

- Stock ≤ 10 is highlighted with a warning badge.
- Inactive products are dimmed to 40% opacity.
- Admins see edit and delete buttons; other roles see a read-only view.
- Create/edit uses a centered modal with a blurred backdrop.

### `Customers.jsx`

Same pattern as Products, adapted for customer data (name, phone, address, notes).

- Edit is available to all authenticated users.
- Delete (soft) is admin-only.
- Empty state and loading state both use the shared components.

### `Transactions.jsx`

Header + line items model. The table shows invoice number, customer, total, status, date, and an inline status dropdown.

The create modal supports multi-item transactions:

- Add or remove line items dynamically.
- Product picker shows live stock in parentheses.
- Quantity input enforces a minimum of 1.

Status dropdowns update via `updateStatus(id, { status })`.

### `Schedules.jsx`

Delivery schedules with columns: customer, courier, scheduled date, status, and an inline status dropdown.

Create form fields: customer (select), courier user ID (numeric input), scheduled datetime (converted to `YYYY-MM-DD HH:mm:ss`), and notes.

### `Users.jsx`

Admin-only. Table shows name, email, role badge, active status badge, and a toggle button to activate/deactivate.

Role badge variant:

- `admin` → `brand`
- `kurir` → `info`
- `pelanggan` → `default`

### `Logs.jsx`

Admin-only. Table shows timestamp, user name, action badge, entity reference, and IP address.

Action badge variant is inferred from the action string:

- Contains `create` → `success`
- Contains `update` → `info`
- Contains `delete` → `danger`
- Otherwise → `default`

---

## Styling

Styling is fully Tailwind CSS v4 — no CSS modules, no styled-components, no Sass.

- **Utility-first.** Components compose Tailwind classes inline.
- **No `tailwind.config.js`.** All customization happens in `src/index.css` via the `@theme` block.
- **Dark mode** is not currently implemented. To add it, extend the theme with a `.dark` variant and toggle a class on `<html>`.
- **Responsive breakpoints** follow Tailwind defaults. The sidebar is fixed at 240px on all breakpoints in the current implementation — consider making it collapsible for mobile if you extend the app.

---

## Icons and Assets

- **`public/favicon.svg`** — the browser tab icon. Currently a placeholder from the Vite template; replace it with the Galonku droplet mark before deploy.
- **`public/icons.svg`** — an SVG sprite with social/UI symbols (Bluesky, Discord, GitHub, X, docs, social). Not currently referenced from the app; kept for future use.
- **Lucide React** — all in-app icons (sidebar, buttons, table actions, form indicators) come from `lucide-react`. Common icons used: `Droplets`, `LayoutDashboard`, `Package`, `Users`, `Receipt`, `Calendar`, `Shield`, `ScrollText`, `LogOut`, `Plus`, `Pencil`, `Trash2`, `X`, `ArrowUpRight`, `AlertTriangle`, `Loader2`.

---

## Error Handling

Errors are surfaced through toast notifications via `react-hot-toast`.

**API errors** — most pages wrap their fetch and mutation calls in `try/catch` and show a toast using the backend's message when available:

```js
toast.error(err.response?.data?.message || "Gagal menyimpan");
```

**Validation errors** — the backend returns a structured `errors` object on 422. In `Register.jsx`, all messages are flattened for display:

```js
const errors = err.response?.data?.errors;
const msg = errors
  ? Object.values(errors).flat().join(", ")
  : err.response?.data?.message || "Registrasi gagal";
toast.error(msg);
```

**Session expiry** — handled globally by the Axios response interceptor, which redirects to `/login` on 401.

**Toast position** — set once at the app root:

```jsx
<Toaster position="top-right" />
```

---

## Notifications

`react-hot-toast` is used for all transient feedback. Conventions:

- **Success** — past-tense, specific: `'Produk diperbarui'`, `'Transaksi dibuat'`, `'Jadwal baru berhasil dibuat'`.
- **Error** — describe what failed: `'Gagal memuat produk'`, `'Gagal menyimpan'`, `'Gagal membuat transaksi'`.
- **Welcome** — after login: `` `Welcome back, ${user.name}` ``.

Avoid bare `'Success'` or `'Error'` messages — the toast should tell the user what changed.

---

## Development Notes

**Adding a new page:**

1. Create `src/pages/YourPage.jsx`.
2. Import the shared primitives from `../components/ui` (`PageHeader`, `Card`, `Loading`, `EmptyState`, `Button`, `Input`, `Badge`, etc.).
3. Fetch data with `useEffect` + the appropriate API module.
4. Register the route in `App.jsx`. Wrap in `<ProtectedRoute roles={[...]} />` if it should be role-restricted.
5. Add a menu entry in `Layout.jsx` under `menu` or `adminMenu`.

**Adding a new API module:**

1. Create `src/api/yourResource.js`.
2. Export an object of thin wrapper functions that call `client`.
3. Keep the naming convention: `list`, `show`, `create`, `update`, `remove` (or `updateStatus` for status endpoints).

**Adding a new UI primitive:**

1. Add the export to `src/components/ui.jsx`.
2. Follow the existing pattern: accept `className` and spread `...props` so callers can extend.
3. Document variants in a comment block at the top of the component.

**Coding conventions:**

- Functional components with hooks only.
- No class components.
- Default exports for pages and layout components; named exports for everything in `ui.jsx`.
- Prefer `const` and arrow functions.
- Two-space indentation.
- Single quotes for strings, no semicolons (the codebase style is consistent on both).

---

## Troubleshooting

### Blank page after login

Open the browser console. A 401 on `/auth/me` immediately after login usually means `VITE_API_URL` points at the wrong backend, or the JWT secret on the backend has changed and existing tokens are invalid. Clear `localStorage` in devtools and log in again.

### `Network Error` on every request

The backend is not running, or CORS is blocking the request. Verify:

```bash
curl http://localhost:8000/api/v1/
```

If it responds, check the backend's `CORS_ALLOWED_ORIGINS` — it must include `http://localhost:5173`.

### `401 Unauthorized` on a page you just logged into

The access token expired. Currently the app does not silently refresh; you will be redirected to `/login`. To avoid this during development, increase `JWT_ACCESS_EXPIRE` in the backend `.env`.

### Route renders the wrong page

Ensure the `<Route>` order in `App.jsx` is correct. Nested routes must be inside their parent `<Route>` element, not siblings.

### Toast notifications do not appear

Check that `<Toaster />` is rendered inside `App.jsx` — it is, by default. If you wrapped the app in a different provider tree, verify the `Toaster` is still mounted at the top level.

### `Cannot find module '@/...'` (if you add path aliases)

Vite does not resolve `@/` by default. Add it to `vite.config.js`:

```js
resolve: {
  alias: {
    '@': fileURLToPath(new URL('./src', import.meta.url)),
  },
},
```

And to `jsconfig.json` or `tsconfig.json` if you use JS/TS tooling.

### Tailwind classes not applying

Tailwind v4 uses automatic content detection. If a class is generated in a way that the scanner misses (e.g., template literals with dynamic segments), add an explicit `@source` directive in `src/index.css`:

```css
@source "../src/**/*.jsx";
```

### `bun install` fails on a fresh machine

Verify Bun is installed (`bun --version`) and Node is on a supported version. If `bun.lockb` is incompatible, delete it and run `bun install` again to regenerate.

---

## License

GPL-3.0-only. See the `LICENSE` file at the repository root.
