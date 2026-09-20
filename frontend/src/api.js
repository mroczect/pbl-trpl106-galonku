const BASE = "/api/v1";

const store = {
  get access() {
    return localStorage.getItem("access_token");
  },
  get refresh() {
    return localStorage.getItem("refresh_token");
  },
  set(access, refresh) {
    if (access) localStorage.setItem("access_token", access);
    if (refresh) localStorage.setItem("refresh_token", refresh);
  },
  clear() {
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("user");
  },
  setUser(u) {
    localStorage.setItem("user", JSON.stringify(u));
  },
  getUser() {
    try {
      return JSON.parse(localStorage.getItem("user"));
    } catch {
      return null;
    }
  },
};

let refreshing = null;

async function request(path, opts = {}, retry = true) {
  const headers = {
    "Content-Type": "application/json",
    ...(opts.headers || {}),
  };
  if (store.access) headers.Authorization = `Bearer ${store.access}`;

  const res = await fetch(BASE + path, { ...opts, headers });

  // 401 → coba refresh sekali
  if (
    res.status === 401 &&
    retry &&
    store.refresh &&
    !path.startsWith("/auth/")
  ) {
    if (!refreshing) {
      refreshing = fetch(BASE + "/auth/refresh", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ refresh_token: store.refresh }),
      })
        .then(async (r) => {
          const j = await r.json();
          if (j.success) {
            store.set(j.data.access_token, j.data.refresh_token);
            return true;
          }
          store.clear();
          location.hash = "#/login";
          return false;
        })
        .finally(() => {
          refreshing = null;
        });
    }
    const ok = await refreshing;
    if (ok) return request(path, opts, false);
  }

  const body = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = new Error(body.message || `HTTP ${res.status}`);
    err.status = res.status;
    err.errors = body.errors;
    throw err;
  }
  return body;
}

export const api = {
  // Auth
  login: (email, password) =>
    request("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    }),
  register: (data) =>
    request("/auth/register", { method: "POST", body: JSON.stringify(data) }),
  me: () => request("/auth/me"),
  logout: () =>
    request("/auth/logout", {
      method: "POST",
      body: JSON.stringify({ refresh_token: store.refresh }),
    }),

  // Generic CRUD
  list: (res, params = {}) => request(`/${res}?` + new URLSearchParams(params)),
  show: (res, id) => request(`/${res}/${id}`),
  create: (res, data) =>
    request(`/${res}`, { method: "POST", body: JSON.stringify(data) }),
  update: (res, id, data) =>
    request(`/${res}/${id}`, { method: "PUT", body: JSON.stringify(data) }),
  del: (res, id) => request(`/${res}/${id}`, { method: "DELETE" }),

  // Custom endpoints
  lowStock: (t = 10) => request(`/products/low-stock?threshold=${t}`),
  stockAdjust: (data) =>
    request("/stock-movements/adjust", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  updateTrx: (id, d) =>
    request(`/transactions/${id}/status`, {
      method: "PUT",
      body: JSON.stringify(d),
    }),
  updateSched: (id, d) =>
    request(`/schedules/${id}/status`, {
      method: "PUT",
      body: JSON.stringify(d),
    }),
  logs: (limit = 100) => request(`/logs?limit=${limit}`),
};

export { store };
