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
    const isLoginEndpoint = err.config?.url?.includes('/auth/login')
    const hadToken = !!localStorage.getItem('jwt_token')
    // Only redirect to login if the user WAS authenticated (expired token), not for anonymous requests
    if (err.response?.status === 401 && !isLoginEndpoint && hadToken) {
      localStorage.removeItem('jwt_token')
      window.location.href = '/connexion'
    }
    return Promise.reject(err)
  }
)

export default api
