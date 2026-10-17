import api from './api'

export const unitService = {
  list(params = {}) {
    return api.get('/units', { params })
  },
  options() {
    return api.get('/units/options')
  },
  create(payload) {
    return api.post('/units', payload)
  },
  update(id, payload) {
    return api.put(`/units/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/units/${id}`)
  },
}
