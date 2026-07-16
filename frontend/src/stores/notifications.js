import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref([])

  async function fetch() {
    try {
      const res = await api.get('/notifications')
      items.value = res.data
    } catch {}
  }

  async function readAll() {
    if (!items.value.length) return
    try {
      await api.post('/notifications/read-all')
      items.value = []
    } catch {}
  }

  function reset() {
    items.value = []
  }

  return { items, fetch, readAll, reset }
})
