import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useMessagesStore = defineStore('messages', () => {
  const unreadCount = ref(0)

  async function fetchUnread() {
    try {
      const res = await api.get('/messages/unread-count')
      unreadCount.value = res.data.count
    } catch {}
  }

  return { unreadCount, fetchUnread }
})
