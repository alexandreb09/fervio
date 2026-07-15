<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'

const route = useRoute()
const router = useRouter()
const status = ref('loading')

onMounted(async () => {
  const token = route.query.token
  if (!token) { status.value = 'invalid'; return }
  try {
    await api.get(`/auth/verify-email?token=${token}`)
    status.value = 'success'
  } catch {
    status.value = 'invalid'
  }
})
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">

      <div v-if="status === 'loading'" class="auth-header">
        <v-progress-circular indeterminate color="primary" size="36" />
        <p class="auth-subtitle" style="margin-top:16px">Vérification en cours…</p>
      </div>

      <template v-else-if="status === 'success'">
        <div class="auth-header">
          <div class="auth-icon auth-icon--success">
            <v-icon color="white" size="22">mdi-check</v-icon>
          </div>
          <h1 class="auth-title">Email confirmé !</h1>
          <p class="auth-subtitle">Votre compte est maintenant actif.</p>
        </div>
        <div class="auth-card">
          <p class="confirm-text">
            Bienvenue sur Fervio ! Vous pouvez dès maintenant vous connecter et trouver vos partenaires de tennis.
          </p>
          <router-link to="/connexion" class="btn-primary confirm-btn">
            Se connecter
          </router-link>
        </div>
      </template>

      <template v-else>
        <div class="auth-header">
          <div class="auth-icon auth-icon--error">
            <v-icon color="white" size="22">mdi-alert-outline</v-icon>
          </div>
          <h1 class="auth-title">Lien invalide</h1>
          <p class="auth-subtitle">Ce lien a déjà été utilisé ou a expiré.</p>
        </div>
        <div class="auth-card">
          <p class="confirm-text">
            Si vous avez déjà confirmé votre email, vous pouvez vous connecter directement.
          </p>
          <router-link to="/connexion" class="btn-primary confirm-btn">
            Se connecter
          </router-link>
        </div>
      </template>

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
.auth-container { width: 100%; max-width: 420px; }
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
.auth-icon--success { background: #16A34A; }
.auth-icon--error   { background: var(--c-error); }
.auth-title { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0 0 6px; }
.auth-subtitle { font-size: 14px; color: var(--c-text-md); margin: 0; }
.auth-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.confirm-text { font-size: 14px; color: var(--c-text); line-height: 1.6; margin: 0; }
.confirm-btn { width: 100%; justify-content: center; padding: 11px; font-size: 14px; }
</style>
