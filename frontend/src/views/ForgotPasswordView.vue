<script setup>
import { ref } from 'vue'
import api from '@/api'

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  if (!email.value.trim()) {
    error.value = 'Veuillez saisir votre adresse email.'
    return
  }
  loading.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value.trim() })
    sent.value = true
  } catch {
    error.value = 'Une erreur est survenue. Veuillez réessayer.'
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
        <h1 class="auth-title">Mot de passe oublié</h1>
        <p class="auth-subtitle">
          {{ sent ? 'Email envoyé !' : 'Saisissez votre email pour recevoir un lien de réinitialisation.' }}
        </p>
      </div>

      <div class="auth-card">
        <!-- Success state -->
        <div v-if="sent" class="sent-state">
          <v-icon size="48" color="success" class="mb-3">mdi-check-circle-outline</v-icon>
          <p class="sent-title">Vérifiez vos emails</p>
          <p class="sent-desc">
            Si un compte existe pour <strong>{{ email }}</strong>, vous recevrez un lien
            de réinitialisation dans quelques minutes.
          </p>
          <p class="sent-hint">Pensez à vérifier vos spams si vous ne trouvez pas l'email.</p>
          <router-link to="/connexion" class="btn-primary auth-submit">
            Retour à la connexion
          </router-link>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="submit">
          <div v-if="error" class="error-banner">{{ error }}</div>

          <div class="form-field">
            <label class="field-label">Adresse email</label>
            <input
              v-model="email"
              type="email"
              required
              autofocus
              placeholder="email@exemple.fr"
              class="field-input"
            />
          </div>

          <button type="submit" :disabled="loading" class="btn-primary auth-submit">
            <v-progress-circular v-if="loading" size="16" width="2" color="white" indeterminate />
            <span>{{ loading ? 'Envoi…' : 'Envoyer le lien' }}</span>
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
.auth-submit { width: 100%; padding: 11px; font-size: 14px; margin-top: 4px; }

/* Sent state */
.sent-state { text-align: center; }
.sent-title { font-size: 16px; font-weight: 700; color: var(--c-text); margin: 0 0 10px; }
.sent-desc { font-size: 14px; color: var(--c-text-muted); line-height: 1.6; margin: 0 0 8px; }
.sent-hint { font-size: 12px; color: var(--c-text-sm); margin: 0 0 24px; }

/* Footer */
.auth-footer { text-align: center; font-size: 13px; color: var(--c-text-md); margin-top: 16px; }
.auth-footer-link {
  color: var(--c-primary); font-weight: 600; text-decoration: none;
  display: inline-flex; align-items: center; gap: 4px;
}
.auth-footer-link:hover { text-decoration: underline; }
</style>
