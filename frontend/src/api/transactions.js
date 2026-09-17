import client from './client'

export const transactionsApi = {
  list: (params) => client.get('/transactions', { params }),
  show: (id) => client.get(`/transactions/${id}`),
  create: (data) => client.post('/transactions', data),
  updateStatus: (id, data) => client.put(`/transactions/${id}/status`, data),
}
