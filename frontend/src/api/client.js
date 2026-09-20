import axios from "axios";

const BASE_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api/v1";

const ACCESS_KEY = "access_token";
const REFRESH_KEY = "refresh_token";
const USER_KEY = "user";

export const tokenStore = {
  getAccess: () => localStorage.getItem(ACCESS_KEY),
  getRefresh: () => localStorage.getItem(REFRESH_KEY),
  getUser: () => {
    try {
      const raw = localStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  },
  setUser: (user) => {
    if (user) localStorage.setItem(USER_KEY, JSON.stringify(user));
    else localStorage.removeItem(USER_KEY);
  },
  set: (access, refresh, user) => {
    if (access) localStorage.setItem(ACCESS_KEY, access);
    if (refresh) localStorage.setItem(REFRESH_KEY, refresh);
    if (user !== null && user !== undefined) {
      localStorage.setItem(USER_KEY, JSON.stringify(user));
    }
  },
  setTokens: (access, refresh) => {
    if (access) localStorage.setItem(ACCESS_KEY, access);
    if (refresh) localStorage.setItem(REFRESH_KEY, refresh);
  },
  clear: () => {
    localStorage.removeItem(ACCESS_KEY);
    localStorage.removeItem(REFRESH_KEY);
    localStorage.removeItem(USER_KEY);
  },
};

const client = axios.create({
  baseURL: BASE_URL,
  headers: { "Content-Type": "application/json" },
  timeout: 20000,
});

client.interceptors.request.use((config) => {
  const token = tokenStore.getAccess();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

let refreshing = null;

const performRefresh = async () => {
  const refresh = tokenStore.getRefresh();
  if (!refresh) throw new Error("no_refresh_token");

  const { data } = await axios.post(
    `${BASE_URL}/auth/refresh`,
    { refresh_token: refresh },
    { headers: { "Content-Type": "application/json" } },
  );

  const payload = data?.data ?? {};
  tokenStore.setTokens(payload.access_token, payload.refresh_token);
  return payload.access_token;
};

client.interceptors.response.use(
  (res) => res,
  async (err) => {
    const status = err.response?.status;
    const original = err.config || {};
    const url = original.url || "";

    const isAuthEndpoint =
      url.includes("/auth/login") ||
      url.includes("/auth/register") ||
      url.includes("/auth/refresh");

    if (status === 401 && !isAuthEndpoint && !original._retry) {
      original._retry = true;

      try {
        if (!refreshing) {
          refreshing = performRefresh().finally(() => {
            refreshing = null;
          });
        }
        const newToken = await refreshing;
        original.headers = original.headers || {};
        original.headers.Authorization = `Bearer ${newToken}`;
        return client(original);
      } catch {
        tokenStore.clear();
        if (window.location.pathname !== "/login") {
          window.location.replace("/login");
        }
        return Promise.reject(err);
      }
    }

    return Promise.reject(err);
  },
);

export default client;
