const currencyFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
})

const numberFormatter = new Intl.NumberFormat('id-ID', {
  maximumFractionDigits: 3,
})

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
})

const shortDateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})

function toDate(value) {
  if (!value) {
    return null
  }

  const date = value instanceof Date ? value : new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

export function formatCurrency(value) {
  return currencyFormatter.format(Number(value) || 0)
}

export function formatNumber(value) {
  return numberFormatter.format(Number(value) || 0)
}

export function formatDate(value) {
  const date = toDate(value)
  return date ? dateFormatter.format(date) : '-'
}

export function formatShortDate(value) {
  const date = toDate(value)
  return date ? shortDateFormatter.format(date) : '-'
}

export function formatDateTime(value) {
  const date = toDate(value)
  return date ? dateTimeFormatter.format(date) : '-'
}

/**
 * Transactions recorded with a date-only form are stored at midnight.
 * Show just the date in that case instead of a misleading "00.00" time.
 */
export function formatTransactionDateTime(value) {
  const date = toDate(value)

  if (!date) {
    return '-'
  }

  return date.getHours() === 0 && date.getMinutes() === 0
    ? dateFormatter.format(date)
    : dateTimeFormatter.format(date)
}

export function toDateInput(value = new Date()) {
  const date = toDate(value) || new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

export function startOfMonthInput() {
  const now = new Date()
  return toDateInput(new Date(now.getFullYear(), now.getMonth(), 1))
}

export function todayInput() {
  return toDateInput(new Date())
}

export function getStockStatusLabel(status) {
  return (
    {
      in_stock: 'Stok Aman',
      low_stock: 'Stok Menipis',
      out_of_stock: 'Stok Habis',
    }[status] || status
  )
}
