import {
  createContext,
  useContext,
  useEffect,
  useRef,
  useState,
  useCallback,
} from "react";
import { authApi } from "../api/auth";
import { tokenStore } from "../api/client";

const AuthContext = createContext(null);

function bootstrapAuth() {
  const token = tokenStore.getAccess();

  if (!token) {
    tokenStore.clear();
    return Promise.resolve({ user: null });
  }

  return authApi
    .me()
    .then((res) => {
      const u = res.data?.data ?? null;
      if (u) {
        tokenStore.setUser(u);
        return { user: u };
      }
      tokenStore.clear();
      return { user: null };
    })
    .catch(() => {
      tokenStore.clear();
      return { user: null };
    });
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const token = tokenStore.getAccess();
    return token ? tokenStore.getUser() : null;
  });
  const [loading, setLoading] = useState(true);
  const bootPromise = useRef(null);

  useEffect(() => {
    if (!bootPromise.current) {
      bootPromise.current = bootstrapAuth();
    }

    let cancelled = false;

    bootPromise.current
      .then((result) => {
        if (cancelled) return;
        setUser(result.user);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const login = useCallback(async (email, password) => {
    const res = await authApi.login({ email, password });
    const payload = res.data?.data ?? {};
    const { user: u, access_token, refresh_token } = payload;
    tokenStore.set(access_token, refresh_token, u);
    setUser(u);
    return u;
  }, []);

  const register = useCallback(async (payload) => {
    const res = await authApi.register(payload);
    return res.data?.data ?? null;
  }, []);

  const logout = useCallback(async () => {
    try {
      const refresh = tokenStore.getRefresh();
      await authApi.logout(refresh ? { refresh_token: refresh } : {});
    } catch {
    } finally {
      tokenStore.clear();
      setUser(null);
    }
  }, []);

  const value = { user, loading, login, register, logout };
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
}
