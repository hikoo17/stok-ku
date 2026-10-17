import api from './api'

export const transactionService = {
  list(params = {}) {
    return api.get('/stock-transactions', { params })
  },
  get(id) {
    return api.get(`/stock-transactions/${id}`)
  },
  stockIn(payload) {
    return api.post('/stock-in', payload)
  },
  stockOut(payload) {
    return api.post('/stock-out', payload)
  },
}
