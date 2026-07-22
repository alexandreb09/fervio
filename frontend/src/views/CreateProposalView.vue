<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import CityInput from '@/components/CityInput.vue'

const router = useRouter()
const loading = ref(false)
const errors = ref({})
const globalError = ref('')

const FFT_RANKINGS = [
  'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
  '15/5', '15/4', '15/3', '15/2', '15/1', '15',
  '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
]

const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const form = ref({
  title: '',
  description: '',
  city: '',
  postalCode: null,
  address: '',
  scheduledAt: '',
  duration: null,
  gameType: null,
  maxPlayers: 1,
  minRanking: null,
  maxRanking: null,
  surface: null,
})

const gameTypes = [
  { value: 'simple', label: 'Simple', icon: 'mdi-account', desc: '1v1' },
  { value: 'double', label: 'Double', icon: 'mdi-account-multiple', desc: '2v2' },
  { value: 'double_mixte', label: 'Double mixte', icon: 'mdi-account-group', desc: 'Mixte' },
]

const surfaces = [
  { value: 'terre_battue', title: 'Terre battue' },
  { value: 'gazon', title: 'Gazon' },
  { value: 'dur', title: 'Dur' },
  { value: 'synthetique', title: 'Synthétique' },
  { value: 'indoor', title: 'Indoor' },
]

const durations = [
  { value: 60, title: '1h' },
  { value: 90, title: '1h30' },
  { value: 120, title: '2h' },
  { value: 150, title: '2h30' },
  { value: 180, title: '3h' },
]

async function submit() {
  loading.value = true
  errors.value = {}
  globalError.value = ''
  try {
    const res = await api.post('/proposals', form.value)
    router.push(`/parties/${res.data.publicId}`)
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else if (e.response?.status === 400) {
      globalError.value = e.response.data.error || 'Une erreur est survenue.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page-sm">
    <!-- Header -->
    <div class="mb-6">
      <p class="fin-label" style="margin:0 0 4px;">Nouvelle partie</p>
      <h1 class="create-page-title">Proposer une partie</h1>
      <p class="create-page-subtitle">Créez une partie pour trouver des partenaires près de chez vous</p>
    </div>

    <v-alert
      v-if="globalError"
      type="error"
      variant="tonal"
      class="mb-4 create-alert"
      closable
      @click:close="globalError = ''"
    >
      {{ globalError }}
    </v-alert>

    <v-form @submit.prevent="submit">
      <!-- Section 1: Infos générales -->
      <div class="form-section">
        <h2 class="form-section-title">
          <span class="form-step-badge">1</span>
          Informations générales
        </h2>

        <v-text-field
          v-model="form.title"
          label="Titre de la partie *"
          placeholder="Ex : Partie de simple samedi matin à Paris 15e"
          :error-messages="errors.title"
          hide-details="auto"
          class="mb-4"
        />

        <v-row>
          <v-col cols="12" sm="6">
            <div class="form-field-city">
              <label class="field-label">Ville *</label>
              <CityInput
                v-model="form.city"
                placeholder="Paris"
                input-class="field-input"
                @city-selected="e => { form.city = e.name; form.postalCode = e.postalCode }"
              />
              <p v-if="errors.city" class="field-error">{{ errors.city }}</p>
            </div>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.address"
              label="Club / Adresse"
              prepend-inner-icon="mdi-home-map-marker"
              hide-details
              placeholder="Ex : Tennis Club de Paris"
            />
          </v-col>
        </v-row>

        <v-row class="mt-0">
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.scheduledAt"
              label="Date et heure *"
              type="datetime-local"
              prepend-inner-icon="mdi-calendar-clock"
              :min="minDateTime"
              :error-messages="errors.scheduledAt"
              hide-details="auto"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.duration"
              :items="durations"
              item-title="title"
              item-value="value"
              label="Durée estimée"
              prepend-inner-icon="mdi-timer-outline"
              clearable
              hide-details
            />
          </v-col>
        </v-row>

        <v-textarea
          v-model="form.description"
          label="Description (optionnelle)"
          placeholder="Décrivez votre partie, le club, vos préférences..."
          rows="3"
          hide-details
          class="mt-4"
        />
      </div>

      <!-- Section 2: Type de partie -->
      <div class="form-section">
        <h2 class="form-section-title">
          <span class="form-step-badge">2</span>
          Type de partie
        </h2>

        <div class="game-type-group">
          <p class="game-type-label">Format</p>
          <div class="game-type-list">
            <button
              v-for="g in gameTypes"
              :key="g.value"
              type="button"
              class="game-type-btn"
              :class="{ 'game-type-btn--active': form.gameType === g.value }"
              @click="form.gameType = form.gameType === g.value ? null : g.value"
            >
              <div class="game-type-btn-header">
                <v-icon size="16" :color="form.gameType === g.value ? 'primary' : 'text-subtle'">{{ g.icon }}</v-icon>
                <span class="game-type-btn-label" :class="{ 'game-type-btn-label--active': form.gameType === g.value }">{{ g.label }}</span>
              </div>
              <span class="game-type-btn-desc">{{ g.desc }}</span>
            </button>
          </div>
        </div>

        <v-row>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.maxPlayers"
              :items="[
                { value: 1, title: '1 joueur recherché' },
                { value: 2, title: '2 joueurs recherchés' },
                { value: 3, title: '3 joueurs recherchés' },
              ]"
              item-title="title"
              item-value="value"
              label="Joueurs recherchés *"
              prepend-inner-icon="mdi-account-search"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.surface"
              :items="surfaces"
              item-title="title"
              item-value="value"
              label="Surface"
              prepend-inner-icon="mdi-grass"
              clearable
              hide-details
            />
          </v-col>
        </v-row>
      </div>

      <!-- Section 3: Classement -->
      <div class="form-section form-section--last">
        <h2 class="form-section-title">
          <span class="form-step-badge">3</span>
          Classement FFT requis
          <span class="form-section-optional">(optionnel)</span>
        </h2>
        <p class="form-section-hint">Laissez vide pour accepter tous les classements.</p>

        <v-row>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.minRanking"
              :items="FFT_RANKINGS"
              label="Classement minimum"
              clearable
              hide-details
              hint="Le moins bon classement accepté"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-select
              v-model="form.maxRanking"
              :items="FFT_RANKINGS"
              label="Classement maximum"
              clearable
              hide-details
              hint="Le meilleur classement accepté"
            />
          </v-col>
        </v-row>
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="loading" class="btn-primary">
          <v-progress-circular v-if="loading" size="14" width="2" color="white" indeterminate />
          Publier la partie
        </button>
        <button type="button" class="btn-secondary" @click="router.back()">Annuler</button>
      </div>
    </v-form>
  </div>
</template>

<style scoped>
.create-page-title { font-size: 26px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }
.create-page-subtitle { font-size: 14px; color: var(--c-text-md); margin-top: 6px; }
.create-alert { border-radius: 14px; }

.form-section {
  background: white;
  border-radius: 12px;
  padding: 24px 28px;
  margin-bottom: 16px;
  border: 1px solid var(--c-border);
}
.form-section--last { margin-bottom: 28px; }

.form-section-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.form-step-badge {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: var(--c-primary-bg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: var(--c-primary);
  flex-shrink: 0;
}
.form-section-optional { font-weight: 400; font-size: 12px; color: var(--c-text-sm); }
.form-section-hint { font-size: 13px; color: var(--c-text-sm); margin-bottom: 16px; }

/* Game type selector */
.game-type-group { margin-bottom: 20px; }
.game-type-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--c-text-md);
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.game-type-list { display: flex; gap: 10px; flex-wrap: wrap; }
.game-type-btn {
  flex: 1;
  min-width: 100px;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1.5px solid var(--c-border);
  background: white;
  cursor: pointer;
  transition: all 0.15s;
  text-align: left;
}
.game-type-btn:hover { border-color: #A5B4FC; }
.game-type-btn--active { border-color: var(--c-primary); background: var(--c-primary-bg); }
.game-type-btn-header { display: flex; align-items: center; gap: 8px; margin-bottom: 2px; }
.game-type-btn-label { font-size: 13px; font-weight: 700; color: var(--c-text-dk); }
.game-type-btn-label--active { color: var(--c-primary); }
.game-type-btn-desc { font-size: 11px; color: var(--c-text-sm); }

/* City field wrapper (replaces v-text-field for CityInput) */
.form-field-city { margin-bottom: 0; }
.form-field-city .field-label { display: block; margin-bottom: 4px; }
.form-field-city .field-input { width: 100%; }

/* Actions */
.form-actions { display: flex; gap: 12px; align-items: center; }
</style>
