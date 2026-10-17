import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  headers: {
    Accept: 'application/json',
  },
  timeout: 20000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('stokku_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.includes('/login')

    if (error.response?.status === 401 && !isLoginRequest) {
      window.dispatchEvent(new Event('stokku:unauthorized'))
    }

    return Promise.reject(error)
  },
)

export default api
