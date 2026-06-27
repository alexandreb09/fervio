<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePartnersStore } from '@/stores/partners'

const props = defineProps({
  user: { type: Object, required: true },
  labeled: { type: Boolean, default: false },
})

const auth = useAuthStore()
const store = usePartnersStore()
const router = useRouter()

onMounted(async () => {
  if (auth.isLoggedIn && !store.loaded) await store.fetch()
})

const active = computed(() => store.isPartner(props.user.id))

async function toggle(e) {
  e.stopPropagation()
  e.preventDefault()
  if (!auth.isLoggedIn) {
    router.push(`/connexion?redirect=${router.currentRoute.value.fullPath}`)
    return
  }
  await store.toggle(props.user)
}
</script>

<template>
  <button
    :class="['partner-btn', { 'partner-btn--active': active, 'partner-btn--labeled': labeled }]"
    :title="active ? 'Retirer des partenaires' : 'Ajouter aux partenaires'"
    @click="toggle"
  >
    <v-icon :size="labeled ? 16 : 18">
      {{ active ? 'mdi-bookmark' : 'mdi-bookmark-outline' }}
    </v-icon>
    <span v-if="labeled">{{ active ? 'Partenaire enregistré' : 'Ajouter aux partenaires' }}</span>
  </button>
</template>

<style scoped>
.partner-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 8px;
  padding: 7px 10px;
  cursor: pointer;
  color: var(--c-text-sm);
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
  white-space: nowrap;
}
.partner-btn:hover {
  border-color: var(--c-primary);
  color: var(--c-primary);
  background: var(--c-primary-bg);
}
.partner-btn--active {
  border-color: var(--c-primary);
  color: var(--c-primary);
  background: var(--c-primary-bg);
}
.partner-btn--active:hover {
  border-color: var(--c-error);
  color: var(--c-error);
  background: #FEF2F2;
}
.partner-btn--labeled {
  padding: 9px 14px;
  width: 100%;
  justify-content: center;
}
</style>
