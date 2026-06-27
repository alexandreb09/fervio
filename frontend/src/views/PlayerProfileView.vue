<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'
import PartnerBtn from '@/components/PartnerBtn.vue'
import ReportModal from '@/components/ReportModal.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const player = ref(null)
const loading = ref(true)
const playerProposals = ref([])
const msgDialog = ref(false)
const msgText = ref('')
const msgSending = ref(false)
const msgSent = ref(false)

const reportDialog = ref(false)

const privateDialog = ref(false)
const privateSending = ref(false)
const privateSent = ref(false)
const privateError = ref('')
const privateForm = ref({ title: '', city: '', scheduledAt: '', gameType: '', description: '' })

const GAME_TYPES = [
  { value: 'simple', label: 'Simple' },
  { value: 'double', label: 'Double' },
  { value: 'double_mixte', label: 'Double mixte' },
]

onMounted(async () => {
  try {
    const res = await api.get(`/users/${route.params.id}`)
    player.value = res.data
    api.get('/proposals', { params: { authorId: res.data.id } })
      .then(r => { playerProposals.value = r.data })
      .catch(() => {})
  } catch { router.push('/joueurs') }
  finally { loading.value = false }
})

async function sendMessage() {
  if (!msgText.value.trim()) return
  msgSending.value = true
  try {
    await api.post('/messages', { recipientId: player.value.id, content: msgText.value.trim() })
    msgSent.value = true
    msgText.value = ''
    setTimeout(() => { msgDialog.value = false; msgSent.value = false }, 1800)
  } finally { msgSending.value = false }
}

function onContact() {
  if (auth.isLoggedIn) {
    msgDialog.value = true
  } else {
    router.push(`/connexion?redirect=/joueurs/${player.value.publicId}`)
  }
}

function onPrivateProposal() {
  if (!auth.isLoggedIn) {
    router.push(`/connexion?redirect=/joueurs/${player.value.publicId}`)
    return
  }
  privateForm.value = { title: '', city: player.value.city ?? '', scheduledAt: '', gameType: '', description: '' }
  privateError.value = ''
  privateSent.value = false
  privateDialog.value = true
}

async function sendPrivateProposal() {
  privateError.value = ''
  if (!privateForm.value.title.trim() || !privateForm.value.city.trim() || !privateForm.value.scheduledAt) {
    privateError.value = 'Le titre, la ville et la date sont requis.'
    return
  }
  privateSending.value = true
  try {
    await api.post('/proposals', {
      title: privateForm.value.title.trim(),
      city: privateForm.value.city.trim(),
      scheduledAt: privateForm.value.scheduledAt,
      gameType: privateForm.value.gameType || null,
      description: privateForm.value.description.trim() || null,
      isPrivate: true,
      targetUserId: player.value.id,
    })
    privateSent.value = true
    setTimeout(() => { privateDialog.value = false; privateSent.value = false }, 2000)
  } catch (e) {
    privateError.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally {
    privateSending.value = false
  }
}

function avatarUrl(u) {
  if (!u) return ''
  if (u.avatar) return u.avatar.startsWith('http') ? u.avatar : `http://localhost:8000${u.avatar}`
  return `https://ui-avatars.com/api/?name=${u.firstName}+${u.lastName}&background=FEF0E6&color=C25228&bold=true&size=120`
}

function timeAgo(d) {
  if (!d) return null
  const diff = Date.now() - new Date(d).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 60) return 'Actif récemment'
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `Actif il y a ${hours}h`
  const days = Math.floor(hours / 24)
  if (days < 7) return `Actif il y a ${days}j`
  return null
}

function proposalDay(d) {
  return new Date(d).getDate()
}

function proposalMonth(d) {
  return new Date(d).toLocaleDateString('fr-FR', { month: 'short' })
}
</script>

<template>
  <div class="page-sm">
    <div v-if="loading" class="loading-wrap">
      <v-progress-circular size="32" color="primary" indeterminate />
    </div>

    <template v-else-if="player">
      <router-link to="/joueurs" class="back-link">
        <v-icon size="14">mdi-arrow-left</v-icon> Joueurs
      </router-link>

      <!-- Profile card -->
      <div class="profile-card">
        <div class="profile-banner" />

        <div class="profile-body">
          <div class="profile-header">
            <v-avatar size="80" class="profile-avatar">
              <v-img :src="avatarUrl(player)" />
            </v-avatar>
            <span v-if="timeAgo(player.lastActivityAt)" class="activity-pill">
              <span class="activity-dot" />
              {{ timeAgo(player.lastActivityAt) }}
            </span>
          </div>

          <h1 class="profile-name">{{ player.firstName }} {{ player.lastName }}</h1>

          <div class="badge-row">
            <span v-if="player.fftRanking" class="badge badge-purple">
              <v-icon size="11">mdi-trophy-outline</v-icon>{{ player.fftRanking }}
            </span>
            <span v-if="player.gender === 'M'" class="badge badge-blue">Homme</span>
            <span v-else-if="player.gender === 'F'" class="badge badge-pink">Femme</span>
            <span v-else-if="player.gender === 'A'" class="badge badge-gray">Autre</span>
            <span v-if="player.city" class="badge badge-gray">
              <v-icon size="11">mdi-map-marker-outline</v-icon>{{ player.city }}
            </span>
            <span v-if="player.age" class="badge badge-gray">{{ player.age }} ans</span>
          </div>

          <p v-if="player.description" class="profile-description">{{ player.description }}</p>
          <p v-else class="profile-description--empty">Aucune description renseignée.</p>

          <!-- Tennis profile section -->
          <template v-if="player.handedness || (player.hasCourt !== null && player.hasCourt !== undefined) || player.preferredSurface?.length || player.age">
            <div class="tennis-section">
              <p class="tennis-label">Profil de jeu</p>
              <div class="tennis-grid">
                <div v-if="player.age" class="tennis-item">
                  <span class="tennis-item-label">Âge</span>
                  <span class="tennis-item-value">{{ player.age }} ans</span>
                </div>
                <div v-if="player.handedness" class="tennis-item">
                  <span class="tennis-item-label">Latéralité</span>
                  <span class="tennis-item-value">{{ player.handedness === 'R' ? 'Droitier(e)' : 'Gaucher(e)' }}</span>
                </div>
                <div v-if="player.hasCourt !== null && player.hasCourt !== undefined" class="tennis-item">
                  <span class="tennis-item-label">Terrain</span>
                  <span class="tennis-item-value">{{ player.hasCourt ? 'Disponible' : 'Non disponible' }}</span>
                </div>
                <div v-if="player.preferredSurface?.length" class="tennis-item">
                  <span class="tennis-item-label">Surface{{ player.preferredSurface.length > 1 ? 's préférées' : ' préférée' }}</span>
                  <span class="tennis-item-value">{{ player.preferredSurface.map(s => ({ hard: 'Dur', clay: 'Terre battue', grass: 'Gazon', carpet: 'Moquette' }[s])).join(', ') }}</span>
                </div>
              </div>
            </div>
          </template>

          <!-- Contact section - only shown if not the user themselves -->
          <div v-if="auth.user?.id !== player.id" class="contact-section">
            <button v-if="auth.isLoggedIn" class="report-link" @click="reportDialog = true">
              <v-icon size="13">mdi-flag-outline</v-icon> Signaler ce profil
            </button>
            <button v-if="player.acceptMessages !== false" class="contact-btn" @click="onContact">
              <v-icon size="16">mdi-email-outline</v-icon>
              Envoyer un message
            </button>
            <div v-else class="no-messages-notice">
              <v-icon size="14" color="border-light">mdi-message-off-outline</v-icon>
              Ce joueur n'accepte pas les messages.
            </div>
            <button v-if="player.acceptPrivateProposals !== false" class="private-btn" @click="onPrivateProposal">
              <v-icon size="16">mdi-lock-outline</v-icon>
              Proposer une partie privée
            </button>
            <div v-else class="no-messages-notice">
              <v-icon size="14" color="border-light">mdi-lock-off-outline</v-icon>
              Ce joueur n'accepte pas les parties privées.
            </div>
            <PartnerBtn :user="player" labeled />
          </div>
        </div>
      </div>

      <ReportModal
        v-if="reportDialog"
        target-type="user"
        :target-id="player.id"
        @close="reportDialog = false"
      />

      <!-- Player's proposals -->
      <template v-if="playerProposals.length">
        <div class="proposals-header">
          <h2 class="proposals-title">Annonces de {{ player.firstName }}</h2>
          <span class="proposals-count">{{ playerProposals.length }}</span>
        </div>
        <div class="proposals-list">
          <router-link
            v-for="p in playerProposals"
            :key="p.publicId"
            :to="`/annonces/${p.publicId}`"
            class="fin-card proposal-row"
          >
            <div class="proposal-date">
              <span class="proposal-day">{{ proposalDay(p.scheduledAt) }}</span>
              <span class="proposal-month">{{ proposalMonth(p.scheduledAt) }}</span>
            </div>
            <div class="proposal-divider" />
            <div class="proposal-content">
              <div class="proposal-title">{{ p.title }}</div>
              <div class="proposal-city">{{ p.city }}</div>
            </div>
            <span :class="p.status === 'full' ? 'badge badge-amber' : 'badge badge-green'" class="proposal-status">
              {{ p.status === 'full' ? 'Complet' : 'Disponible' }}
            </span>
          </router-link>
        </div>
      </template>
    </template>

    <!-- Private proposal dialog -->
    <v-dialog v-model="privateDialog" max-width="480">
      <div class="dialog-box">
        <div class="dialog-header">
          <v-avatar size="36">
            <v-img :src="avatarUrl(player)" />
          </v-avatar>
          <div>
            <div class="dialog-player-name">{{ player?.firstName }} {{ player?.lastName }}</div>
            <div class="dialog-subtitle">
              <v-icon size="12" color="primary">mdi-lock-outline</v-icon>
              Partie privée
            </div>
          </div>
        </div>

        <div v-if="privateSent" class="dialog-success">
          <v-icon size="40" color="success" class="mb-2">mdi-check-circle-outline</v-icon>
          <p class="dialog-success-text">Invitation envoyée !</p>
        </div>

        <template v-else>
          <div v-if="privateError" class="error-banner">{{ privateError }}</div>

          <div class="private-form-grid">
            <div class="private-field">
              <label class="field-label">Titre <span class="private-required">*</span></label>
              <input v-model="privateForm.title" type="text" class="field-input" placeholder="Partie simple amicale…" />
            </div>
            <div class="private-field">
              <label class="field-label">Ville <span class="private-required">*</span></label>
              <input v-model="privateForm.city" type="text" class="field-input" placeholder="Paris…" />
            </div>
          </div>

          <div class="private-field">
            <label class="field-label">Date et heure <span class="private-required">*</span></label>
            <input v-model="privateForm.scheduledAt" type="datetime-local" class="field-input" />
          </div>

          <div class="private-field">
            <label class="field-label">Type de jeu <span class="private-optional">(optionnel)</span></label>
            <select v-model="privateForm.gameType" class="field-select">
              <option value="">Non précisé</option>
              <option v-for="g in GAME_TYPES" :key="g.value" :value="g.value">{{ g.label }}</option>
            </select>
          </div>

          <div class="private-field">
            <label class="field-label">Message <span class="private-optional">(optionnel)</span></label>
            <textarea v-model="privateForm.description" class="dialog-textarea" rows="3"
              placeholder="Bonjour, je cherche un partenaire pour…" />
          </div>

          <div class="dialog-actions">
            <button class="btn-cancel" @click="privateDialog = false">Annuler</button>
            <button class="btn-send" :disabled="privateSending" @click="sendPrivateProposal">
              <v-progress-circular v-if="privateSending" size="14" width="2" color="white" indeterminate />
              <v-icon v-else size="14">mdi-lock-outline</v-icon>
              {{ privateSending ? 'Envoi…' : 'Envoyer l\'invitation' }}
            </button>
          </div>
        </template>
      </div>
    </v-dialog>

    <!-- Message dialog -->
    <v-dialog v-model="msgDialog" max-width="420">
      <div class="dialog-box">
        <div class="dialog-header">
          <v-avatar size="36">
            <v-img :src="avatarUrl(player)" />
          </v-avatar>
          <div>
            <div class="dialog-player-name">{{ player?.firstName }} {{ player?.lastName }}</div>
            <div class="dialog-subtitle">Nouveau message</div>
          </div>
        </div>

        <div v-if="msgSent" class="dialog-success">
          <v-icon size="40" color="success" class="mb-2">mdi-check-circle-outline</v-icon>
          <p class="dialog-success-text">Message envoyé !</p>
        </div>

        <template v-else>
          <textarea
            v-model="msgText"
            class="dialog-textarea"
            placeholder="Bonjour, je cherche un partenaire pour…"
            rows="4"
          />
          <div class="dialog-actions">
            <button class="btn-cancel" @click="msgDialog = false">Annuler</button>
            <button class="btn-send" :disabled="msgSending || !msgText.trim()" @click="sendMessage">
              <v-progress-circular v-if="msgSending" size="14" width="2" color="white" indeterminate />
              <v-icon v-else size="14">mdi-send</v-icon>
              {{ msgSending ? 'Envoi…' : 'Envoyer' }}
            </button>
          </div>
        </template>
      </div>
    </v-dialog>
  </div>
</template>

<style scoped>
/* ── Layout ── */
.loading-wrap {
  display: flex;
  justify-content: center;
  padding: 80px;
}

/* ── Back link ── */
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  font-weight: 500;
  color: var(--c-text-sm);
  text-decoration: none;
  margin-bottom: 20px;
  transition: color 0.1s;
}
.back-link:hover { color: var(--c-text-muted); }

/* ── Profile card ── */
.profile-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 16px;
}

.profile-banner {
  height: 96px;
  background: linear-gradient(135deg, var(--c-primary) 0%, #D47A52 60%, #A5B4FC 100%);
}

.profile-body {
  padding: 0 24px 20px;
}

.profile-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-top: -40px;
  margin-bottom: 14px;
  gap: 12px;
  flex-wrap: wrap;
}

.profile-avatar {
  border: 4px solid #fff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  flex-shrink: 0;
}

.activity-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  color: #16A34A;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 99px;
  padding: 3px 10px;
  white-space: nowrap;
}

.activity-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #16A34A;
  display: inline-block;
}

.profile-name {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 0 0 8px;
  line-height: 1.2;
}

.badge-row {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.profile-description {
  font-size: 14px;
  color: var(--c-text-muted);
  line-height: 1.65;
  margin: 0 0 20px;
  padding: 14px 16px;
  background: var(--c-bg);
  border-radius: 10px;
  border-left: 3px solid #F5D4C2;
}

.profile-description--empty {
  font-size: 13px;
  color: var(--c-border-lt);
  font-style: italic;
  margin: 0 0 20px;
}

.contact-section {
  border-top: 1px solid var(--c-hover);
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.contact-btn {
  width: 100%;
  padding: 11px 16px;
  font-size: 14px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: var(--c-primary);
  color: #fff !important;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background 0.15s, box-shadow 0.15s;
}
.contact-btn:hover {
  background: var(--c-primary-dk);
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.private-btn {
  width: 100%;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: #fff;
  color: var(--c-primary) !important;
  border: 1px solid #C7D2FE;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: border-color 0.15s, background 0.15s;
}
.private-btn:hover {
  border-color: #D47A52;
  background: var(--c-primary-bg);
}

.private-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.private-field { margin-bottom: 12px; }
.private-required { color: var(--c-error); font-size: 12px; }
.private-optional { font-weight: 400; color: var(--c-text-sm); font-size: 12px; }

/* ── Proposals section ── */
.proposals-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.proposals-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
  letter-spacing: -0.01em;
  margin: 0;
}

.proposals-count {
  font-size: 12px;
  color: var(--c-text-sm);
  font-weight: 500;
}

.proposals-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.proposal-row {
  text-decoration: none;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.proposal-date {
  flex-shrink: 0;
  text-align: center;
  width: 38px;
}

.proposal-day {
  display: block;
  font-size: 17px;
  font-weight: 800;
  color: var(--c-primary);
  line-height: 1;
  letter-spacing: -0.02em;
}

.proposal-month {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.proposal-divider {
  width: 1px;
  background: var(--c-hover);
  align-self: stretch;
  flex-shrink: 0;
}

.proposal-content {
  flex: 1;
  min-width: 0;
}

.proposal-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}

.proposal-city {
  font-size: 12px;
  color: var(--c-text-sm);
}

.proposal-status {
  flex-shrink: 0;
  font-size: 11px;
}

/* ── Tennis profile section ── */
.tennis-section {
  border-top: 1px solid var(--c-hover);
  padding-top: 16px;
  margin-bottom: 16px;
}

.tennis-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--c-primary);
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin: 0 0 12px;
}

.tennis-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.tennis-item {
  background: var(--c-bg);
  border: 1px solid var(--c-hover);
  border-radius: 8px;
  padding: 10px 12px;
}

.tennis-item-label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 3px;
}

.tennis-item-value {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-text);
}

.no-messages-notice {
  font-size: 13px;
  color: var(--c-text-sm);
  font-style: italic;
  text-align: center;
  padding: 8px 0;
}
.report-link {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 12px;
  color: var(--c-border-lt);
  font-family: Inter, sans-serif;
  padding: 4px 0;
  display: flex;
  align-items: center;
  gap: 4px;
  align-self: flex-end;
  transition: color 0.1s;
}
.report-link:hover { color: var(--c-error); }

/* ── Message dialog ── */
.dialog-box {
  background: #fff;
  border-radius: 16px;
  padding: 28px;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.dialog-player-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
  line-height: 1.2;
}

.dialog-subtitle {
  font-size: 12px;
  color: var(--c-text-sm);
}

.dialog-success {
  text-align: center;
  padding: 20px 0;
}

.dialog-success-text {
  color: #16A34A;
  font-weight: 600;
  font-size: 14px;
  margin: 8px 0 0;
}

.dialog-textarea {
  width: 100%;
  padding: 11px 13px;
  font-size: 14px;
  font-family: Inter, sans-serif;
  border: 1px solid var(--c-border);
  border-radius: 10px;
  outline: none;
  resize: vertical;
  color: var(--c-text);
  box-sizing: border-box;
  background: var(--c-bg);
  line-height: 1.5;
  transition: border-color 0.1s, background 0.1s;
}
.dialog-textarea:focus {
  border-color: var(--c-primary);
  background: #fff;
}

.dialog-actions {
  display: flex;
  gap: 8px;
  margin-top: 14px;
}

.btn-cancel {
  flex: 1;
  padding: 10px;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: #fff;
  color: var(--c-text-md);
  border: 1px solid var(--c-border);
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.1s;
}
.btn-cancel:hover { border-color: var(--c-border-lt); }

.btn-send {
  flex: 2;
  padding: 10px;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: var(--c-primary);
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: background 0.1s;
}
.btn-send:hover:not(:disabled) { background: var(--c-primary-dk); }
.btn-send:disabled { opacity: 0.6; cursor: default; }
</style>
