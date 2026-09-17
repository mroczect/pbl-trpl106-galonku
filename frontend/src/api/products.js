import client from './client'

export const productsApi = {
  list: (params) => client.get('/products', { params }),
  show: (id) => client.get(`/products/${id}`),
  lowStock: (threshold = 10) => client.get('/products/low-stock', { params: { threshold } }),
  create: (data) => client.post('/products', data),
  update: (id, data) => client.put(`/products/${id}`, data),
  remove: (id) => client.delete(`/products/${id}`),
}
