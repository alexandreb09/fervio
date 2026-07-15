<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import CityInput from '@/components/CityInput.vue'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const error = ref('')
const fieldErrors = ref({})

const FFT_RANKINGS = [
  'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
  '15/5', '15/4', '15/3', '15/2', '15/1', '15',
  '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
]

const form = ref({
  firstName: '', lastName: '', email: '', password: '',
  city: '', gender: null, fftRanking: null,
})

async function submit() {
  error.value = ''
  fieldErrors.value = {}
  loading.value = true
  try {
    await auth.register(form.value)
    router.push('/verifier-email')
  } catch (e) {
    if (e.response?.status === 422) fieldErrors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-header">
        <div class="auth-icon">
          <v-icon color="white" size="22">mdi-tennis-ball</v-icon>
        </div>
        <h1 class="auth-title">Créer un compte</h1>
        <p class="auth-subtitle">Trouvez votre partenaire de tennis.</p>
      </div>

      <div class="auth-card">
        <div v-if="error" class="error-banner">{{ error }}</div>

        <form @submit.prevent="submit">
          <!-- Identity section -->
          <p class="section-label">Identité</p>

          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Prénom *</label>
              <input v-model="form.firstName" required placeholder="Alice"
                class="field-input" :class="{ 'field-input--error': fieldErrors.firstName }" />
              <p v-if="fieldErrors.firstName" class="field-error">{{ fieldErrors.firstName }}</p>
            </div>
            <div class="form-field">
              <label class="field-label">Nom *</label>
              <input v-model="form.lastName" required placeholder="Martin"
                class="field-input" :class="{ 'field-input--error': fieldErrors.lastName }" />
              <p v-if="fieldErrors.lastName" class="field-error">{{ fieldErrors.lastName }}</p>
            </div>
          </div>

          <div class="form-field">
            <label class="field-label">Email *</label>
            <input v-model="form.email" type="email" required placeholder="alice@email.com"
              class="field-input" :class="{ 'field-input--error': fieldErrors.email }" />
            <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</p>
          </div>

          <div class="form-field form-field--last">
            <label class="field-label">Mot de passe *</label>
            <input v-model="form.password" type="password" required placeholder="Minimum 8 caractères"
              class="field-input" :class="{ 'field-input--error': fieldErrors.password }" />
            <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
          </div>

          <!-- Tennis section -->
          <div class="form-divider" />
          <p class="section-label">
            Profil tennis
            <span class="section-label-optional">(optionnel)</span>
          </p>

          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Ville</label>
              <CityInput v-model="form.city" placeholder="Paris" input-class="field-input" />
            </div>
            <div class="form-field">
              <label class="field-label">Genre</label>
              <select v-model="form.gender" class="field-select">
                <option :value="null">Non renseigné</option>
                <option value="M">Homme</option>
                <option value="F">Femme</option>
                <option value="A">Autre</option>
              </select>
            </div>
          </div>

          <div class="form-field form-field--last">
            <label class="field-label">Classement FFT</label>
            <select v-model="form.fftRanking" class="field-select">
              <option :value="null">Sélectionner…</option>
              <option v-for="r in FFT_RANKINGS" :key="r" :value="r">{{ r }}</option>
            </select>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary auth-submit">
            <v-progress-circular v-if="loading" size="16" width="2" color="white" indeterminate />
            <span>{{ loading ? 'Création du compte…' : 'Créer mon compte' }}</span>
          </button>
        </form>
      </div>

      <p class="auth-footer">
        Déjà un compte ?
        <router-link to="/connexion" class="auth-footer-link">Se connecter</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: calc(100vh - 60px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  background: var(--c-bg);
}
.auth-container { width: 100%; max-width: 480px; }
.auth-header { text-align: center; margin-bottom: 32px; }
.auth-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--c-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}
.auth-title { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0 0 6px; }
.auth-subtitle { font-size: 14px; color: var(--c-text-md); margin: 0; }
.auth-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 28px;
}
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.form-field { margin-bottom: 12px; }
.form-field--last { margin-bottom: 20px; }
.section-label-optional { font-weight: 400; color: var(--c-text-sm); text-transform: none; letter-spacing: 0; font-size: 12px; }
.auth-submit { width: 100%; padding: 11px; font-size: 14px; }
.auth-footer { text-align: center; font-size: 13px; color: var(--c-text-md); margin-top: 20px; }
.auth-footer-link { color: var(--c-primary); font-weight: 600; text-decoration: none; }
.auth-footer-link:hover { text-decoration: underline; }
</style>
