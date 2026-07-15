import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { 'Content-Type': 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const isAuthEndpoint = err.config?.url?.includes('/auth/')
    const hadToken = !!localStorage.getItem('jwt_token')
    // Clear stale token on 401, but let the Vue Router guard handle the redirect
    // (using window.location.href here would abort in-flight login flows)
    if (err.response?.status === 401 && !isAuthEndpoint && hadToken) {
      localStorage.removeItem('jwt_token')
    }
    return Promise.reject(err)
  }
)

export default api
