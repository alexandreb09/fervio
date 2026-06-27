<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const REDIRECT_HINTS = {
  '/annonces/nouvelle': { icon: 'mdi-calendar-plus-outline', text: 'Connectez-vous pour publier une annonce de partie.' },
  '/messages':          { icon: 'mdi-message-outline',       text: 'Connectez-vous pour accéder à vos messages.' },
  '/profil':            { icon: 'mdi-account-outline',       text: 'Connectez-vous pour accéder à votre profil.' },
}

const redirectHint = computed(() => {
  const r = route.query.redirect
  if (!r) return null
  const key = Object.keys(REDIRECT_HINTS).find(k => r.startsWith(k))
  return key ? REDIRECT_HINTS[key] : { icon: 'mdi-lock-outline', text: 'Connectez-vous pour accéder à cette page.' }
})
const email = ref('')
const password = ref('')
const showPwd = ref(false)
const loading = ref(false)
const error = ref('')
const suspended = ref(false)

async function login() {
  error.value = ''
  suspended.value = false
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push(route.query.redirect || '/profil')
  } catch (e) {
    if (e.response?.data?.suspended) {
      suspended.value = true
    } else {
      error.value = e.response?.data?.error || 'Email ou mot de passe incorrect.'
    }
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
        <h1 class="auth-title">Connexion</h1>
        <p class="auth-subtitle">{{ redirectHint ? 'Un compte est nécessaire pour continuer.' : 'Heureux de vous revoir.' }}</p>
      </div>

      <div v-if="redirectHint" class="redirect-hint">
        <v-icon size="15" color="primary">{{ redirectHint.icon }}</v-icon>
        {{ redirectHint.text }}
      </div>

      <div class="auth-card">
        <div v-if="error" class="error-banner">{{ error }}</div>
        <div v-if="suspended" class="suspended-banner">
          <v-icon size="18" color="#DC2626">mdi-account-lock-outline</v-icon>
          <div>
            <strong>Compte temporairement suspendu</strong>
            <p>Des signalements ont été reçus sur votre compte. Notre équipe de modération analyse la situation. Si vous pensez qu'il s'agit d'une erreur, contactez-nous à <a href="mailto:support@fervio.fr">support@fervio.fr</a>.</p>
          </div>
        </div>

        <form @submit.prevent="login">
          <div class="form-field">
            <label class="field-label">Email</label>
            <input
              v-model="email"
              type="email"
              required
              placeholder="email@domain.com"
              class="field-input"
            />
          </div>

          <div class="form-field">
            <label class="field-label">Mot de passe</label>
            <div class="pwd-wrap">
              <input
                v-model="password"
                :type="showPwd ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="field-input field-input--pwd"
              />
              <button type="button" class="pwd-toggle" @click="showPwd = !showPwd">
                <v-icon size="17">{{ showPwd ? 'mdi-eye-off' : 'mdi-eye' }}</v-icon>
              </button>
            </div>
          </div>

          <div class="forgot-row">
            <router-link to="/mot-de-passe-oublie" class="forgot-link">Mot de passe oublié ?</router-link>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary auth-submit">
            <v-progress-circular v-if="loading" size="16" width="2" color="white" indeterminate />
            <span>{{ loading ? 'Connexion…' : 'Se connecter' }}</span>
          </button>
        </form>
      </div>

      <p class="auth-footer">
        Pas encore inscrit ?
        <router-link to="/inscription" class="auth-footer-link">Créer un compte</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.redirect-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--c-primary-bg);
  border: 1px solid var(--c-border);
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 500;
  color: var(--c-primary);
}
.auth-page {
  min-height: calc(100vh - 60px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  background: var(--c-bg);
}
.auth-container { width: 100%; max-width: 380px; }
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
.form-field { margin-bottom: 14px; }
.form-field:last-of-type { margin-bottom: 20px; }
.pwd-wrap { position: relative; }
.pwd-toggle {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  color: var(--c-text-sm);
}
.forgot-row { text-align: right; margin-bottom: 14px; }
.forgot-link { font-size: 12px; color: var(--c-primary); text-decoration: none; font-weight: 500; }
.forgot-link:hover { text-decoration: underline; }
.auth-submit { width: 100%; padding: 11px; font-size: 14px; }
.auth-footer { text-align: center; font-size: 13px; color: var(--c-text-md); margin-top: 20px; }
.auth-footer-link { color: var(--c-primary); font-weight: 600; text-decoration: none; }
.auth-footer-link:hover { text-decoration: underline; }
</style>
