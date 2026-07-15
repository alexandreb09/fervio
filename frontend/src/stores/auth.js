import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('jwt_token'))

  const isLoggedIn = computed(() => !!token.value && !!user.value)

  async function login(email, password) {
    const res = await api.post('/auth/login', { username: email, password })
    token.value = res.data.token
    localStorage.setItem('jwt_token', token.value)
    await fetchMe()
    if (!user.value) {
      token.value = null
      localStorage.removeItem('jwt_token')
      throw { response: { data: { error: 'Connexion impossible. Veuillez réessayer.' } } }
    }
    return user.value
  }

  async function register(data) {
    const res = await api.post('/auth/register', data)
    return res.data
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const res = await api.get('/auth/me')
      user.value = res.data
    } catch {
      logout()
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('jwt_token')
  }

  if (token.value) fetchMe()

  return { user, token, isLoggedIn, login, register, fetchMe, logout }
})
