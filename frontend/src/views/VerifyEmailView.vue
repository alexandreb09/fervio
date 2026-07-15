<script setup>
import { useRoute } from 'vue-router'
const email = useRoute().query.email || ''
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-header">
        <div class="auth-icon">
          <v-icon color="white" size="22">mdi-email-outline</v-icon>
        </div>
        <h1 class="auth-title">Vérifiez vos emails</h1>
        <p class="auth-subtitle">Plus qu'une étape avant de jouer !</p>
      </div>

      <div class="auth-card">
        <div class="verify-body">
          <p class="verify-text">
            Un email de confirmation vient d'être envoyé à
            <template v-if="email"><strong class="verify-email">{{ email }}</strong>.</template>
            <template v-else>votre adresse.</template>
            Cliquez sur le lien qu'il contient pour activer votre compte.
          </p>
          <div class="verify-tips">
            <p class="tips-title">Vous ne voyez pas l'email ?</p>
            <ul class="tips-list">
              <li>Vérifiez votre dossier <strong>Spam</strong> ou <strong>Courrier indésirable</strong></li>
              <li>L'email peut prendre quelques minutes à arriver</li>
              <li>Vérifiez que l'adresse saisie est correcte</li>
            </ul>
          </div>
        </div>
      </div>

      <p class="auth-footer">
        Déjà un compte validé ?
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
.auth-title { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0 0 6px; }
.auth-subtitle { font-size: 14px; color: var(--c-text-md); margin: 0; }
.auth-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 28px;
}
.verify-body { display: flex; flex-direction: column; gap: 20px; }
.verify-text { font-size: 14px; color: var(--c-text); line-height: 1.6; margin: 0; }
.verify-email { color: var(--c-primary); font-weight: 700; word-break: break-all; }
.verify-tips {
  background: var(--c-bg);
  border: 1px solid var(--c-border);
  border-radius: 10px;
  padding: 14px 16px;
}
.tips-title { font-size: 12px; font-weight: 700; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 10px; }
.tips-list { margin: 0; padding-left: 18px; display: flex; flex-direction: column; gap: 6px; }
.tips-list li { font-size: 13px; color: var(--c-text-md); line-height: 1.5; }
.auth-footer { text-align: center; font-size: 13px; color: var(--c-text-md); margin-top: 20px; }
.auth-footer-link { color: var(--c-primary); font-weight: 600; text-decoration: none; }
.auth-footer-link:hover { text-decoration: underline; }
</style>
