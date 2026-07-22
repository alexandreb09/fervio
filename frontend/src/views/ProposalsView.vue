<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'
import CityInput from '@/components/CityInput.vue'
import SelectInput from '@/components/SelectInput.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const proposals = ref([])
const loading = ref(true)

const surfaceLabels = { terre_battue: 'Terre battue', gazon: 'Gazon', dur: 'Dur', synthetique: 'Synthétique', indoor: 'Indoor' }
const gameTypeLabels = { simple: 'Simple', double: 'Double', double_mixte: 'Mixte' }

const filters = ref({
  city: route.query.city || '',
  gameType: route.query.gameType || '',
  surface: route.query.surface || '',
  status: route.query.status || '',
})

async function fetch() {
  loading.value = true
  const params = {}
  if (filters.value.city)     params.city     = filters.value.city
  if (filters.value.gameType) params.gameType = filters.value.gameType
  if (filters.value.surface)  params.surface  = filters.value.surface
  if (filters.value.status)   params.status   = filters.value.status
  const res = await api.get('/proposals', { params })
  proposals.value = res.data
  loading.value = false
  router.replace({ query: params })
}

function reset() {
  filters.value = { city: '', gameType: '', surface: '', status: '' }
  fetch()
}

onMounted(fetch)

function formatDate(d) {
  const date = new Date(d)
  const today = new Date()
  const tomorrow = new Date(); tomorrow.setDate(today.getDate() + 1)
  if (date.toDateString() === today.toDateString()) return `Aujourd'hui · ${date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })}`
  if (date.toDateString() === tomorrow.toDateString()) return `Demain · ${date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })}`
  return date.toLocaleDateString('fr-FR', { day:'numeric', month:'short' }) + ' · ' + date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
}
</script>

<template>
  <div class="page">
    <!-- Header -->
    <div class="page-top">
      <div>
        <p class="fin-label page-top-label">Communauté</p>
        <h1 class="page-heading">Parties</h1>
      </div>
      <router-link v-if="auth.isLoggedIn" to="/parties/nouvelle" class="btn-primary btn-sm">
        <v-icon size="15">mdi-plus</v-icon> Nouvelle partie
      </router-link>
    </div>

    <div class="layout-sidebar">
      <!-- Filters -->
      <aside class="sidebar">
        <div class="filter-panel">
          <div class="filter-panel-header">
            <p class="filter-panel-title">Filtres</p>
            <button class="btn-ghost" @click="reset">Réinitialiser</button>
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Ville</label>
            <CityInput v-model="filters.city" @search="fetch" input-class="field-input field-input--sm" />
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Format</label>
            <SelectInput
              v-model="filters.gameType"
              placeholder="Tous"
              :options="[
                { value: 'simple', label: 'Simple' },
                { value: 'double', label: 'Double' },
                { value: 'double_mixte', label: 'Double mixte' },
              ]"
              @change="fetch"
            />
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Surface</label>
            <SelectInput
              v-model="filters.surface"
              placeholder="Toutes"
              :options="[
                { value: 'terre_battue', label: 'Terre battue' },
                { value: 'gazon', label: 'Gazon' },
                { value: 'dur', label: 'Dur' },
                { value: 'synthetique', label: 'Synthétique' },
                { value: 'indoor', label: 'Indoor' },
              ]"
              @change="fetch"
            />
          </div>

          <div class="filter-field">
            <label class="field-label field-label--sm">Statut</label>
            <SelectInput
              v-model="filters.status"
              placeholder="Tous"
              :options="[
                { value: 'open', label: 'Disponible' },
                { value: 'full', label: 'Complet' },
              ]"
              @change="fetch"
            />
          </div>

          <button class="btn-primary btn-block" @click="fetch">Rechercher</button>
        </div>
      </aside>

      <!-- List -->
      <div class="list-area">
        <p class="list-count">
          <template v-if="!loading">{{ proposals.length }} partie{{ proposals.length !== 1 ? 's' : '' }}</template>
          <template v-else>Chargement…</template>
        </p>

        <div v-if="loading" class="skeleton-list">
          <v-skeleton-loader v-for="i in 5" :key="i" type="list-item-avatar-two-line" />
        </div>

        <div v-else-if="proposals.length" class="proposal-list">
          <router-link
            v-for="p in proposals"
            :key="p.publicId"
            :to="`/parties/${p.publicId}`"
            class="fin-card proposal-row"
          >
            <!-- Date block -->
            <div class="proposal-date">
              <div class="proposal-date-day">{{ new Date(p.scheduledAt).getDate() }}</div>
              <div class="proposal-date-month">{{ new Date(p.scheduledAt).toLocaleDateString('fr-FR', { month:'short' }) }}</div>
            </div>

            <div class="proposal-divider" />

            <!-- Content -->
            <div class="proposal-content">
              <div class="proposal-badges">
                <span :class="p.status === 'full' ? 'badge badge-amber' : 'badge badge-green'">
                  {{ p.status === 'full' ? 'Complet' : 'Disponible' }}
                </span>
                <span v-if="p.gameType" class="badge badge-purple">{{ gameTypeLabels[p.gameType] }}</span>
                <span v-if="p.surface" class="badge badge-gray">{{ surfaceLabels[p.surface] }}</span>
              </div>
              <h3 class="proposal-title">{{ p.title }}</h3>
              <p class="proposal-meta">
                <span class="proposal-meta-item">
                  <v-icon size="12">mdi-clock-outline</v-icon>
                  {{ formatDate(p.scheduledAt) }}
                </span>
                <span v-if="p.city" class="proposal-meta-item">
                  <v-icon size="12">mdi-map-marker</v-icon> {{ p.city }}
                </span>
                <span class="proposal-meta-item">
                  <v-icon size="12">mdi-account-multiple</v-icon> {{ p.participantCount }}/{{ p.maxPlayers }}
                </span>
              </p>
            </div>

            <!-- Progress -->
            <div class="proposal-progress">
              <span class="proposal-progress-label">{{ p.participantCount }}/{{ p.maxPlayers }}</span>
              <div class="progress-track">
                <div
                  class="progress-fill"
                  :class="{ 'progress-fill--full': p.status === 'full' }"
                  :style="{ width: Math.round((p.participantCount / p.maxPlayers) * 100) + '%' }"
                />
              </div>
            </div>
          </router-link>
        </div>

        <div v-else class="empty-state">
          <v-icon size="36" color="border-light" class="mb-2">mdi-calendar-search</v-icon>
          <p class="empty-state-text">Aucune partie trouvée</p>
          <router-link v-if="auth.isLoggedIn" to="/parties/nouvelle" class="empty-state-cta">
            Créer la première partie
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Page header ── */
.page-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}
.page-top-label { margin: 0 0 4px; }
.page-heading { font-size: 26px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }

.btn-sm { font-size: 13px; padding: 9px 16px; }
.btn-block { width: 100%; }
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

/* ── Layout ── */
.layout-sidebar { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
.sidebar { width: 220px; flex-shrink: 0; }
.list-area { flex: 1; min-width: 0; }

@media (max-width: 768px) {
  .layout-sidebar { flex-direction: column; }
  .sidebar { width: 100%; }
}

/* ── Filter panel ── */
.filter-panel { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 18px; }
.filter-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.filter-panel-title { font-size: 13px; font-weight: 700; color: var(--c-text); margin: 0; }
.filter-field { margin-bottom: 12px; }

/* ── List ── */
.list-count { font-size: 13px; color: var(--c-text-sm); margin: 0 0 12px; font-weight: 500; }
.skeleton-list { display: flex; flex-direction: column; gap: 8px; }
.proposal-list { display: flex; flex-direction: column; gap: 8px; }

/* ── Proposal row ── */
.proposal-row {
  text-decoration: none;
  padding: 16px 20px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.proposal-date { flex-shrink: 0; width: 48px; text-align: center; padding-top: 2px; }
.proposal-date-day { font-size: 18px; font-weight: 800; color: var(--c-primary); line-height: 1; letter-spacing: -0.03em; }
.proposal-date-month { font-size: 11px; font-weight: 600; color: var(--c-text-sm); text-transform: uppercase; letter-spacing: 0.04em; }
.proposal-divider { width: 1px; background: var(--c-hover); align-self: stretch; flex-shrink: 0; }
.proposal-content { flex: 1; min-width: 0; }
.proposal-badges { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
.proposal-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
  margin: 0 0 4px;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.proposal-meta { font-size: 12px; color: var(--c-text-md); margin: 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.proposal-meta-item { display: flex; align-items: center; gap: 3px; }

/* ── Progress ── */
.proposal-progress {
  flex-shrink: 0;
  width: 60px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  padding-top: 2px;
}
.proposal-progress-label { font-size: 11px; font-weight: 600; color: var(--c-text-sm); }
.progress-track { width: 40px; height: 4px; background: var(--c-hover); border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; background: var(--c-primary); border-radius: 99px; }
.progress-fill--full { background: #F59E0B; }

/* ── Empty state ── */
.empty-state {
  padding: 48px;
  text-align: center;
  border: 1px dashed var(--c-border);
  border-radius: 12px;
}
.empty-state-text { color: var(--c-text-sm); margin: 8px 0 16px; font-size: 14px; }
.empty-state-cta {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-primary);
  padding: 8px 16px;
  border: 1px solid #F5D4C2;
  border-radius: 7px;
  background: var(--c-primary-bg);
}
</style>
