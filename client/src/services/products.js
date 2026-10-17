import api from './api'

export const productService = {
  list(params = {}) {
    return api.get('/products', { params })
  },
  options(params = {}) {
    return api.get('/products/options', { params })
  },
  get(id) {
    return api.get(`/products/${id}`)
  },
  create(payload) {
    return api.post('/products', payload)
  },
  update(id, payload) {
    return api.put(`/products/${id}`, payload)
  },
  archive(id) {
    return api.delete(`/products/${id}`)
  },
  restore(id) {
    return api.post(`/products/${id}/restore`)
  },
}
