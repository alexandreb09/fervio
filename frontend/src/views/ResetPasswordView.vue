<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'

const route = useRoute()
const router = useRouter()

const token = ref('')
const password = ref('')
const confirm = ref('')
const showPwd = ref(false)
const showConfirm = ref(false)
const loading = ref(false)
const success = ref(false)
const error = ref('')
const fieldErrors = ref({})

onMounted(() => {
  token.value = route.query.token ?? ''
  if (!token.value) {
    error.value = 'Lien invalide. Veuillez refaire une demande de réinitialisation.'
  }
})

async function submit() {
  error.value = ''
  fieldErrors.value = {}

  if (password.value.length < 8) {
    fieldErrors.value.password = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  if (password.value !== confirm.value) {
    fieldErrors.value.confirm = 'Les mots de passe ne correspondent pas.'
    return
  }

  loading.value = true
  try {
    await api.post('/auth/reset-password', { token: token.value, password: password.value })
    success.value = true
    setTimeout(() => router.push('/connexion'), 3000)
  } catch (e) {
    error.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page-xs">
    <div class="auth-container">
      <div class="auth-header">
        <div class="auth-icon">
          <v-icon color="white" size="22">mdi-lock-reset</v-icon>
        </div>
        <h1 class="auth-title">Nouveau mot de passe</h1>
        <p class="auth-subtitle">Choisissez un mot de passe sécurisé.</p>
      </div>

      <div class="auth-card">
        <!-- Success -->
        <div v-if="success" class="success-state">
          <v-icon size="48" color="success" class="mb-3">mdi-check-circle-outline</v-icon>
          <p class="success-title">Mot de passe mis à jour !</p>
          <p class="success-desc">Vous allez être redirigé vers la page de connexion…</p>
        </div>

        <!-- Invalid token -->
        <div v-else-if="!token" class="error-state">
          <div class="error-banner">{{ error }}</div>
          <router-link to="/mot-de-passe-oublie" class="btn-primary auth-submit">
            Refaire une demande
          </router-link>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="submit">
          <div v-if="error" class="error-banner">{{ error }}</div>

          <div class="form-field">
            <label class="field-label">Nouveau mot de passe</label>
            <div class="pwd-wrap">
              <input
                v-model="password"
                :type="showPwd ? 'text' : 'password'"
                required
                autofocus
                placeholder="8 caractères minimum"
                class="field-input field-input--pwd"
                :class="{ 'field-input--error': fieldErrors.password }"
              />
              <button type="button" class="pwd-toggle" @click="showPwd = !showPwd">
                <v-icon size="17">{{ showPwd ? 'mdi-eye-off' : 'mdi-eye' }}</v-icon>
              </button>
            </div>
            <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
          </div>

          <div class="form-field">
            <label class="field-label">Confirmer le mot de passe</label>
            <div class="pwd-wrap">
              <input
                v-model="confirm"
                :type="showConfirm ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="field-input field-input--pwd"
                :class="{ 'field-input--error': fieldErrors.confirm }"
              />
              <button type="button" class="pwd-toggle" @click="showConfirm = !showConfirm">
                <v-icon size="17">{{ showConfirm ? 'mdi-eye-off' : 'mdi-eye' }}</v-icon>
              </button>
            </div>
            <p v-if="fieldErrors.confirm" class="field-error">{{ fieldErrors.confirm }}</p>
          </div>

          <button type="submit" :disabled="loading || !token" class="btn-primary auth-submit">
            <v-progress-circular v-if="loading" size="16" width="2" color="white" indeterminate />
            <span>{{ loading ? 'Enregistrement…' : 'Enregistrer le mot de passe' }}</span>
          </button>
        </form>
      </div>

      <p class="auth-footer">
        <router-link to="/connexion" class="auth-footer-link">
          <v-icon size="13">mdi-arrow-left</v-icon> Retour à la connexion
        </router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-container { max-width: 400px; margin: 0 auto; padding: 40px 0; }

.auth-header { text-align: center; margin-bottom: 24px; }
.auth-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, var(--c-primary), #D47A52);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 4px 14px rgba(99,102,241,0.35);
}
.auth-title { font-size: 22px; font-weight: 800; color: var(--c-text); letter-spacing: -0.03em; margin: 0 0 6px; }
.auth-subtitle { font-size: 14px; color: var(--c-text-md); margin: 0; }

.auth-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 28px 24px;
  margin-bottom: 16px;
}

.form-field { margin-bottom: 16px; }
.pwd-wrap { position: relative; }
.pwd-toggle {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; color: var(--c-text-sm); padding: 2px;
}
.auth-submit { width: 100%; padding: 11px; font-size: 14px; margin-top: 4px; }

/* Success / error states */
.success-state, .error-state { text-align: center; }
.success-title { font-size: 16px; font-weight: 700; color: var(--c-text); margin: 0 0 8px; }
.success-desc { font-size: 14px; color: var(--c-text-muted); margin: 0; }

/* Footer */
.auth-footer { text-align: center; font-size: 13px; color: var(--c-text-md); margin-top: 16px; }
.auth-footer-link {
  color: var(--c-primary); font-weight: 600; text-decoration: none;
  display: inline-flex; align-items: center; gap: 4px;
}
.auth-footer-link:hover { text-decoration: underline; }
</style>
