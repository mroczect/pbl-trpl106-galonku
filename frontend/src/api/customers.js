import client from './client'

export const customersApi = {
  list: (params) => client.get('/customers', { params }),
  show: (id) => client.get(`/customers/${id}`),
  create: (data) => client.post('/customers', data),
  update: (id, data) => client.put(`/customers/${id}`, data),
  remove: (id) => client.delete(`/customers/${id}`),
}
