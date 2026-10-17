import api from './api'

export const categoryService = {
  list(params = {}) {
    return api.get('/categories', { params })
  },
  options() {
    return api.get('/categories/options')
  },
  create(payload) {
    return api.post('/categories', payload)
  },
  update(id, payload) {
    return api.put(`/categories/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/categories/${id}`)
  },
}
