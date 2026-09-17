import client from './client'

export const schedulesApi = {
  list: (params) => client.get('/schedules', { params }),
  show: (id) => client.get(`/schedules/${id}`),
  create: (data) => client.post('/schedules', data),
  updateStatus: (id, status) => client.put(`/schedules/${id}/status`, { status }),
}
