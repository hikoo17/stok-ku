import api from './api'

const REPORT_ENDPOINTS = {
  inventory: '/reports/inventory',
  movements: '/reports/stock-movements',
  lowStock: '/reports/low-stock',
}

export const reportService = {
  inventory(params = {}) {
    return api.get(REPORT_ENDPOINTS.inventory, { params })
  },
  stockMovements(params = {}) {
    return api.get(REPORT_ENDPOINTS.movements, { params })
  },
  lowStock(params = {}) {
    return api.get(REPORT_ENDPOINTS.lowStock, { params })
  },
  async download(type, params = {}, format = 'csv') {
    const endpoint = REPORT_ENDPOINTS[type]

    if (!endpoint) {
      throw new Error('Jenis laporan tidak dikenal.')
    }

    const response = await api.get(`${endpoint}/export`, {
      params: { ...params, format },
      responseType: 'blob',
    })

    const disposition = response.headers['content-disposition'] || ''
    const filenameMatch = disposition.match(/filename="?([^";]+)"?/i)
    const fallbackNames = {
      inventory: 'laporan-stok-saat-ini',
      movements: 'laporan-pergerakan-stok',
      lowStock: 'laporan-stok-menipis',
    }
    const fallback = `${fallbackNames[type] || `laporan-${type}`}-${new Date().toISOString().slice(0, 10)}.${format}`
    const filename = filenameMatch?.[1] || fallback

    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  },
}
