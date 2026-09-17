import client from "./client";

export const usersApi = {
  list: (params) => client.get("/users", { params }),
  show: (id) => client.get(`/users/${id}`),
  update: (id, data) => client.put(`/users/${id}`, data),
  remove: (id) => client.delete(`/users/${id}`),
  roles: () => client.get("/roles"),
  logs: (limit = 100) => client.get("/logs", { params: { limit } }),
};
