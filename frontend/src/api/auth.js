import client from "./client";

export const authApi = {
  register: (data) => client.post("/auth/register", data),
  login: (data) => client.post("/auth/login", data),
  refresh: (refresh_token) => client.post("/auth/refresh", { refresh_token }),
  me: () => client.get("/auth/me"),
  logout: () => client.post("/auth/logout"),
};
