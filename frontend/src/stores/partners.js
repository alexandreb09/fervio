import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/api'

export const usePartnersStore = defineStore('partners', () => {
  const partners = ref([])
  const partnerIds = ref(new Set())
  const loaded = ref(false)

  async function fetch() {
    const res = await api.get('/partners')
    partners.value = res.data
    partnerIds.value = new Set(res.data.map(p => p.id))
    loaded.value = true
  }

  function isPartner(userId) {
    return partnerIds.value.has(userId)
  }

  async function toggle(user) {
    if (isPartner(user.id)) {
      await api.delete(`/partners/${user.id}`)
      partnerIds.value.delete(user.id)
      partners.value = partners.value.filter(p => p.id !== user.id)
    } else {
      await api.post(`/partners/${user.id}`)
      partnerIds.value.add(user.id)
      partners.value.push(user)
    }
  }

  function reset() {
    partners.value = []
    partnerIds.value = new Set()
    loaded.value = false
  }

  return { partners, loaded, isPartner, fetch, toggle, reset }
})
