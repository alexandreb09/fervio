<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import api from '@/api'
import CityInput from '@/components/CityInput.vue'
import SelectInput from '@/components/SelectInput.vue'
import PartnerBtn from '@/components/PartnerBtn.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { isLoggedIn } = storeToRefs(auth)

const players = ref([])
const loading = ref(true)

const FFT_RANKINGS = [
  'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
  '15/5', '15/4', '15/3', '15/2', '15/1', '15',
  '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
]

const filters = ref({
  city: route.query.city || '',
  minRanking: route.query.minRanking || '',
  maxRanking: route.query.maxRanking || '',
  gender: route.query.gender || '',
})

async function fetch() {
  loading.value = true
  const params = {}
  if (filters.value.city)       params.city       = filters.value.city
  if (filters.value.minRanking) params.minRanking = filters.value.minRanking
  if (filters.value.maxRanking) params.maxRanking = filters.value.maxRanking
  if (filters.value.gender)     params.gender     = filters.value.gender
  const res = await api.get('/users', { params })
  players.value = res.data
  loading.value = false
  router.replace({ query: params })
}

function resetFilters() {
  filters.value = { city: '', minRanking: '', maxRanking: '', gender: '' }
  fetch()
}

onMounted(fetch)

const ACCENTS = ['C25228','D97706','059669','2563EB','7C3AED','DB2777','0891B2','65A30D']

function accent(u) {
  const s = `${u?.firstName}${u?.lastName}` || ''
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0
  return ACCENTS[h % ACCENTS.length]
}

function avatarUrl(u) {
  const c = accent(u)
  return u?.avatar ? u.avatar : `https://ui-avatars.com/api/?name=${u.firstName}+${u.lastName}&background=F5F0EB&color=${c}&bold=true&size=80`
}

function genderBadge(g) {
  return g === 'M' ? 'badge badge-blue' : g === 'F' ? 'badge badge-pink' : 'badge badge-gray'
}
function genderLabel(g) {
  return g === 'M' ? 'Homme' : g === 'F' ? 'Femme' : g === 'A' ? 'Autre' : '—'
}
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-top">
      <p class="fin-label page-top-label">Annuaire</p>
      <h1 class="page-heading">Joueurs</h1>
    </div>

    <div class="layout-sidebar">
      <!-- Filters panel -->
      <aside class="sidebar">
        <div class="filter-panel">
          <div class="filter-panel-header">
            <p class="filter-panel-title">Filtres</p>
            <button class="btn-ghost" @click="resetFilters">Réinitialiser</button>
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Ville</label>
            <CityInput v-model="filters.city" @search="fetch" input-class="field-input field-input--sm" />
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Classement FFT</label>
            <div class="ranking-range">
              <SelectInput
                v-model="filters.minRanking"
                placeholder="Min"
                :options="FFT_RANKINGS.map(r => ({ value: r, label: r }))"
                @change="fetch"
              />
              <span class="ranking-range-sep">→</span>
              <SelectInput
                v-model="filters.maxRanking"
                placeholder="Max"
                :options="FFT_RANKINGS.map(r => ({ value: r, label: r }))"
                @change="fetch"
              />
            </div>
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Genre</label>
            <SelectInput
              v-model="filters.gender"
              placeholder="Tous"
              :options="[
                { value: 'M', label: 'Homme' },
                { value: 'F', label: 'Femme' },
                { value: 'A', label: 'Autre' },
              ]"
              @change="fetch"
            />
          </div>

          <button class="btn-primary btn-block" @click="fetch">Rechercher</button>
        </div>
      </aside>

      <!-- Grid -->
      <div class="list-area">
        <p class="list-count">
          <template v-if="!loading">{{ players.length }} joueur{{ players.length !== 1 ? 's' : '' }}</template>
          <template v-else>Chargement…</template>
        </p>

        <div v-if="loading" class="players-grid">
          <v-skeleton-loader v-for="i in 8" :key="i" type="card" />
        </div>

        <div v-else-if="players.length" class="players-grid">
          <router-link
            v-for="p in players"
            :key="p.id"
            :to="`/joueurs/${p.publicId}`"
            class="fin-card player-card"
            :style="{ '--accent': `#${accent(p)}` }"
          >
            <PartnerBtn
              v-if="isLoggedIn && auth.user?.id !== p.id"
              :user="p"
              class="player-card-btn"
            />
            <div class="player-card-body">
              <v-avatar size="56">
                <v-img :src="avatarUrl(p)" />
              </v-avatar>
              <div>
                <div class="player-card-name">{{ p.firstName }} {{ p.lastName }}</div>
                <div v-if="p.city" class="player-card-city">{{ p.city }}</div>
              </div>
              <div class="player-card-badges">
                <span v-if="p.fftRanking" class="badge badge-purple">{{ p.fftRanking }}</span>
                <span v-if="p.gender" :class="genderBadge(p.gender)">{{ genderLabel(p.gender) }}</span>
              </div>
            </div>
          </router-link>
        </div>

        <div v-else class="empty-state">
          <v-icon size="36" color="border-light" class="mb-2">mdi-account-search</v-icon>
          <p class="empty-state-text">Aucun joueur trouvé</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Page header ── */
.page-top { margin-bottom: 28px; }
.page-top-label { margin: 0 0 4px; }
.page-heading { font-size: 26px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }

.btn-ghost {
  font-size: 12px;
  color: var(--c-primary);
  font-weight: 600;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-family: Inter, sans-serif;
}
.btn-block { width: 100%; }

/* ── Layout ── */
.layout-sidebar { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
.sidebar { width: 220px; flex-shrink: 0; }
.list-area { flex: 1; min-width: 0; }

/* ── Filter panel ── */
.filter-panel { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 18px; }
.filter-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.filter-panel-title { font-size: 13px; font-weight: 700; color: var(--c-text); margin: 0; }
.filter-field { margin-bottom: 14px; }
.ranking-range { display: flex; align-items: center; gap: 6px; }
.ranking-range :deep(.select-wrap) { flex: 1; min-width: 0; }
.ranking-range-sep { font-size: 12px; color: var(--c-text-sm); flex-shrink: 0; }

/* ── Count ── */
.list-count { font-size: 13px; color: var(--c-text-sm); margin: 0 0 12px; font-weight: 500; }

/* ── Players grid ── */
.players-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
.player-card {
  text-decoration: none;
  padding: 16px;
  position: relative;
  border-top: 3px solid var(--accent, var(--c-primary));
}
.player-card-btn { position: absolute; top: 10px; right: 10px; padding: 4px 6px; }
.player-card-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  padding-top: 4px;
}
.player-card-name { font-size: 14px; font-weight: 700; color: var(--c-text); letter-spacing: -0.01em; }
.player-card-city { font-size: 12px; color: var(--c-text-sm); margin-top: 2px; }
.player-card-badges { display: flex; gap: 5px; flex-wrap: wrap; justify-content: center; margin-top: 4px; }

/* ── Empty state ── */
.empty-state { padding: 48px; text-align: center; border: 1px dashed var(--c-border); border-radius: 12px; }
.empty-state-text { color: var(--c-text-sm); margin: 8px 0 0; font-size: 14px; }
</style>
