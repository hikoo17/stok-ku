export function getErrorMessage(error, fallback = 'Terjadi kesalahan. Silakan coba lagi.') {
  if (!error) {
    return fallback
  }

  if (error.code === 'ECONNABORTED') {
    return 'Permintaan memakan waktu terlalu lama. Silakan coba lagi.'
  }

  if (!error.response) {
    return 'Tidak dapat terhubung ke server. Periksa koneksi Anda dan pastikan backend berjalan.'
  }

  return error.response.data?.message || fallback
}

export function getValidationErrors(error) {
  if (error?.response?.status === 422 && error.response.data?.errors) {
    return Object.fromEntries(
      Object.entries(error.response.data.errors).map(([field, messages]) => [
        field,
        Array.isArray(messages) ? messages[0] : messages,
      ]),
    )
  }

  return {}
}
