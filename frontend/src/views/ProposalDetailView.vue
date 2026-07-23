<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import api from '@/api'
import { avatarUrl } from '@/utils/avatar'
import ReportModal from '@/components/ReportModal.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { isLoggedIn, user } = storeToRefs(auth)

const proposal = ref(null)
const loading = ref(true)
const actionLoading = ref(false)
const msgDialog = ref(false)
const reportDialog = ref(false)
const msgText = ref('')
const msgSending = ref(false)
const msgSent = ref(false)
const hasPendingRequest = ref(false)
const pendingRequests = ref([])
const requestsLoading = ref(false)
const requestLoadingIds = ref([])
const joinError = ref('')
const requestError = ref('')

function isRequestLoading(id) { return requestLoadingIds.value.includes(id) }

const surfaceLabels = { terre_battue: 'Terre battue', gazon: 'Gazon', dur: 'Dur', synthetique: 'Synthétique', indoor: 'Indoor' }
const gameTypeLabels = { simple: 'Simple', double: 'Double', double_mixte: 'Double mixte' }

onMounted(async () => {
  try {
    const res = await api.get(`/proposals/${route.params.id}`)
    proposal.value = res.data
    hasPendingRequest.value = !!res.data.viewerHasPendingRequest
  } catch { router.push('/parties') }
  finally { loading.value = false }

  if (proposal.value?.joinMode === 'approval') {
    loadPendingRequests()
  }
})

const isAuthor = computed(() => user.value?.id === proposal.value?.author?.id)
const isParticipant = computed(() => proposal.value?.participants?.some(p => p.id === user.value?.id))
const canJoin = computed(() => isLoggedIn.value && !isAuthor.value && !isParticipant.value && proposal.value?.status === 'open')

async function joinLeave() {
  actionLoading.value = true
  joinError.value = ''
  try {
    const res = isParticipant.value
      ? await api.delete(`/proposals/${route.params.id}/leave`)
      : await api.post(`/proposals/${route.params.id}/join`)
    proposal.value = res.data
    hasPendingRequest.value = !!res.data.viewerHasPendingRequest
  } catch (e) {
    joinError.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally { actionLoading.value = false }
}

async function cancelMyRequest() {
  actionLoading.value = true
  joinError.value = ''
  try {
    await api.delete(`/proposals/${route.params.id}/join-requests/mine`)
    hasPendingRequest.value = false
  } catch (e) {
    joinError.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally { actionLoading.value = false }
}

async function loadPendingRequests() {
  requestsLoading.value = true
  try {
    const res = await api.get(`/proposals/${route.params.id}/join-requests`)
    pendingRequests.value = res.data
  } finally { requestsLoading.value = false }
}

async function acceptRequest(requestId) {
  requestLoadingIds.value = [...requestLoadingIds.value, requestId]
  requestError.value = ''
  try {
    const res = await api.post(`/proposals/${route.params.id}/join-requests/${requestId}/accept`)
    proposal.value = res.data
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== requestId)
  } catch (e) {
    requestError.value = e.response?.data?.error || "Impossible d'accepter cette demande."
  } finally {
    requestLoadingIds.value = requestLoadingIds.value.filter(id => id !== requestId)
  }
}

async function declineRequest(requestId) {
  requestLoadingIds.value = [...requestLoadingIds.value, requestId]
  requestError.value = ''
  try {
    await api.delete(`/proposals/${route.params.id}/join-requests/${requestId}`)
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== requestId)
  } catch (e) {
    requestError.value = e.response?.data?.error || 'Impossible de refuser cette demande.'
  } finally {
    requestLoadingIds.value = requestLoadingIds.value.filter(id => id !== requestId)
  }
}

async function sendMessage() {
  if (!msgText.value.trim()) return
  msgSending.value = true
  try {
    await api.post('/messages', { receiverPublicId: proposal.value.author.publicId, content: msgText.value.trim() })
    msgSent.value = true
    setTimeout(() => { msgDialog.value = false; msgSent.value = false; msgText.value = '' }, 1800)
  } finally { msgSending.value = false }
}

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  return date.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) + ' à ' + date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
}

const fillPercent = computed(() => proposal.value ? Math.round((proposal.value.participantCount / proposal.value.maxPlayers) * 100) : 0)
</script>

<template>
  <div class="page-sm">
    <div v-if="loading" class="loading-center">
      <v-progress-circular size="32" color="primary" indeterminate />
    </div>

    <template v-else-if="proposal">
      <!-- Back -->
      <router-link to="/parties" class="back-link">
        <v-icon size="14">mdi-arrow-left</v-icon> Retour aux parties
      </router-link>

      <!-- Main card -->
      <div class="detail-card">
        <div class="detail-card-head">
          <!-- Status + badges -->
          <div class="detail-badges">
            <span :class="proposal.status === 'full' ? 'badge badge-amber badge--md' : 'badge badge-green badge--md'">
              {{ proposal.status === 'full' ? 'Complet' : 'Disponible' }}
            </span>
            <span v-if="proposal.isPrivate" class="badge badge-purple badge--md">
              <v-icon size="11">mdi-lock-outline</v-icon> Privée
            </span>
            <span v-if="proposal.joinMode === 'approval'" class="badge badge-blue badge--md">
              <v-icon size="11">mdi-clipboard-check-outline</v-icon> Validation requise
            </span>
            <span v-if="proposal.gameType" class="badge badge-purple">{{ gameTypeLabels[proposal.gameType] }}</span>
            <span v-if="proposal.surface" class="badge badge-gray">{{ surfaceLabels[proposal.surface] }}</span>
          </div>

          <h1 class="detail-title">{{ proposal.title }}</h1>

          <div v-if="proposal.isPrivate && proposal.targetUser" class="private-target-notice">
            <v-icon size="14" color="primary">mdi-lock-outline</v-icon>
            Partie privée proposée à
            <router-link :to="`/joueurs/${proposal.targetUser.publicId}`" class="private-target-link">
              {{ proposal.targetUser.firstName }} {{ proposal.targetUser.lastName }}
            </router-link>
          </div>
        </div>

        <!-- Info grid -->
        <div class="detail-info-grid">
          <div
            v-for="info in [
              { icon:'mdi-calendar', label:'Date', value:formatDate(proposal.scheduledAt) },
              { icon:'mdi-map-marker', label:'Ville', value:proposal.city || '—' },
              { icon:'mdi-timer', label:'Durée', value:proposal.duration ? `${proposal.duration} min` : '—' },
              { icon:'mdi-grass', label:'Surface', value:surfaceLabels[proposal.surface] || '—' },
            ]"
            :key="info.label"
            class="detail-info-cell"
          >
            <div class="detail-info-cell-header">
              <v-icon size="13" color="primary">{{ info.icon }}</v-icon>
              <span class="detail-info-label">{{ info.label }}</span>
            </div>
            <div class="detail-info-value">{{ info.value }}</div>
          </div>
        </div>

        <!-- Players progress -->
        <div class="detail-progress">
          <div class="detail-progress-header">
            <span class="detail-progress-text">
              <v-icon size="14" color="primary">mdi-account-multiple</v-icon>
              Participants
            </span>
            <span class="detail-progress-count">{{ proposal.participantCount }} / {{ proposal.maxPlayers }}</span>
          </div>
          <div class="progress-track">
            <div
              class="progress-fill"
              :class="{ 'progress-fill--full': proposal.status === 'full' }"
              :style="{ width: fillPercent + '%' }"
            />
          </div>
        </div>

        <!-- Participants list -->
        <div class="detail-participants">
          <p class="detail-participants-label">Joueurs inscrits</p>
          <div class="participants-list">
            <!-- Author always first -->
            <router-link
              v-if="proposal.author"
              :to="`/joueurs/${proposal.author.publicId}`"
              class="participant-row participant-row--link"
            >
              <v-avatar size="32">
                <v-img :src="avatarUrl(proposal.author)" :alt="`Photo de ${proposal.author.firstName} ${proposal.author.lastName}`" />
              </v-avatar>
              <div class="participant-info">
                <span class="participant-name">{{ proposal.author.firstName }} {{ proposal.author.lastName }}</span>
              </div>
              <span class="badge badge-purple badge--xs">Organisateur</span>
              <span v-if="proposal.author.fftRanking" class="badge badge-gray">{{ proposal.author.fftRanking }}</span>
            </router-link>
            <!-- Joined participants -->
            <router-link
              v-for="p in proposal.participants"
              :key="p.id"
              :to="`/joueurs/${p.publicId}`"
              class="participant-row participant-row--link"
            >
              <v-avatar size="32">
                <v-img :src="avatarUrl(p)" :alt="`Photo de ${p.firstName} ${p.lastName}`" />
              </v-avatar>
              <div class="participant-info">
                <span class="participant-name">{{ p.firstName }} {{ p.lastName }}</span>
              </div>
              <span v-if="p.fftRanking" class="badge badge-gray">{{ p.fftRanking }}</span>
            </router-link>
            <div v-if="!proposal.participants?.length" class="participants-empty">
              <v-icon size="13" color="border-light">mdi-account-off-outline</v-icon>
              Aucun joueur inscrit pour l'instant.
            </div>
          </div>
        </div>
      </div>

      <!-- Demandes en attente (visibles de tous, actions réservées à l'organisateur) -->
      <div v-if="proposal.joinMode === 'approval'" class="detail-requests">
        <p class="detail-participants-label">Demandes en attente</p>
        <div v-if="requestError" class="error-banner mb-3">{{ requestError }}</div>
        <div v-if="requestsLoading" class="loading-center loading-center--sm">
          <v-progress-circular size="22" color="primary" indeterminate />
        </div>
        <div v-else class="participants-list">
          <div v-for="r in pendingRequests" :key="r.id" class="participant-row request-row">
            <v-avatar size="32">
              <v-img :src="avatarUrl(r.requester)" :alt="`Photo de ${r.requester.firstName} ${r.requester.lastName}`" />
            </v-avatar>
            <div class="participant-info">
              <span class="participant-name">{{ r.requester.firstName }} {{ r.requester.lastName }}</span>
            </div>
            <span v-if="r.requester.fftRanking" class="badge badge-gray">{{ r.requester.fftRanking }}</span>
            <div v-if="isAuthor" class="request-actions">
              <button
                class="btn-request btn-request--accept"
                :disabled="isRequestLoading(r.id)"
                @click="acceptRequest(r.id)"
              >
                <v-icon size="14">mdi-check</v-icon> Accepter
              </button>
              <button
                class="btn-request btn-request--decline"
                :disabled="isRequestLoading(r.id)"
                @click="declineRequest(r.id)"
              >
                <v-icon size="14">mdi-close</v-icon> Refuser
              </button>
            </div>
          </div>
          <div v-if="!pendingRequests.length" class="participants-empty">
            <v-icon size="13" color="border-light">mdi-clipboard-text-off-outline</v-icon>
            Aucune demande en attente pour l'instant.
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="proposal.description" class="detail-description">
        <p class="detail-description-label">Description</p>
        <p class="detail-description-text">{{ proposal.description }}</p>
      </div>

      <!-- Author -->
      <div class="detail-author">
        <router-link :to="`/joueurs/${proposal.author?.publicId}`" class="detail-author-link">
          <v-avatar size="40">
            <v-img :src="avatarUrl(proposal.author)" :alt="`Photo de ${proposal.author?.firstName} ${proposal.author?.lastName}`" />
          </v-avatar>
          <div class="detail-author-info">
            <p class="detail-author-role">Organisateur</p>
            <p class="detail-author-name">{{ proposal.author?.firstName }} {{ proposal.author?.lastName }}</p>
          </div>
        </router-link>
      </div>

      <!-- Actions -->
      <div v-if="joinError" class="error-banner mb-3">{{ joinError }}</div>
      <div class="detail-actions">
        <template v-if="isLoggedIn">
          <button
            v-if="!isAuthor && !hasPendingRequest"
            class="btn-join"
            :class="isParticipant ? 'btn-join--leave' : 'btn-join--join'"
            :disabled="actionLoading || (proposal.status === 'full' && !isParticipant)"
            @click="joinLeave"
          >
            <v-progress-circular v-if="actionLoading" size="14" width="2" :color="isParticipant ? 'error' : 'white'" indeterminate />
            <v-icon v-else size="15">{{ isParticipant ? 'mdi-exit-to-app' : 'mdi-plus' }}</v-icon>
            {{ isParticipant ? 'Se désinscrire' : (proposal.status === 'full' ? 'Complet' : (proposal.joinMode === 'approval' ? 'Demander à rejoindre' : 'Rejoindre')) }}
          </button>

          <button
            v-if="!isAuthor && hasPendingRequest"
            class="btn-join btn-join--pending"
            :disabled="actionLoading"
            @click="cancelMyRequest"
          >
            <v-progress-circular v-if="actionLoading" size="14" width="2" color="text-subtle" indeterminate />
            <v-icon v-else size="15">mdi-clock-outline</v-icon>
            {{ actionLoading ? '' : 'Demande envoyée — Annuler' }}
          </button>

          <button
            v-if="!isAuthor"
            class="btn-secondary btn-contact"
            @click="msgDialog = true"
          >
            <v-icon size="15">mdi-message-outline</v-icon> Contacter l'organisateur
          </button>
          <button v-if="!isAuthor" class="proposal-report-link" @click="reportDialog = true">
            <v-icon size="13">mdi-flag-outline</v-icon> Signaler cette partie
          </button>

          <router-link v-if="isAuthor" :to="`/parties/${route.params.id}/modifier`" class="btn-secondary btn-contact">
            <v-icon size="15">mdi-pencil-outline</v-icon> Modifier la partie
          </router-link>
        </template>

        <router-link v-else to="/connexion" class="btn-primary btn-login-cta">
          <v-icon size="15">mdi-login</v-icon> Se connecter pour rejoindre
        </router-link>
      </div>
    </template>

    <!-- Message dialog -->
    <v-dialog v-model="msgDialog" max-width="400">
      <div class="dialog-box">
        <h3 class="dialog-title">Message à {{ proposal?.author?.firstName }}</h3>

        <div v-if="msgSent" class="dialog-success">
          <v-icon size="36" color="success" class="mb-2">mdi-check-circle</v-icon>
          <p class="dialog-success-text">Message envoyé !</p>
        </div>

        <template v-else>
          <textarea
            v-model="msgText"
            placeholder="Bonjour, je suis intéressé par votre partie…"
            rows="4"
            class="field-input dialog-textarea"
          />
          <div class="dialog-actions">
            <button class="btn-secondary dialog-btn-cancel" @click="msgDialog = false">Annuler</button>
            <button
              class="btn-primary dialog-btn-send"
              :disabled="msgSending || !msgText.trim()"
              @click="sendMessage"
            >
              {{ msgSending ? 'Envoi…' : 'Envoyer' }}
            </button>
          </div>
        </template>
      </div>
    </v-dialog>

    <ReportModal
      v-if="reportDialog && proposal"
      target-type="proposal"
      :target-id="proposal.publicId"
      @close="reportDialog = false"
    />
  </div>
</template>

<style scoped>
.loading-center { display: flex; justify-content: center; padding: 80px; }
.proposal-report-link {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 12px;
  color: var(--c-border-lt);
  font-family: Inter, sans-serif;
  padding: 4px 0;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: color 0.1s;
}
.proposal-report-link:hover { color: var(--c-error); }

/* ── Back link ── */
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  color: var(--c-text-md);
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 24px;
}
.back-link:hover { color: var(--c-text-dk); }

/* ── Main card ── */
.detail-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 16px;
}
.detail-card-head { padding: 24px 24px 0; }
.detail-badges { display: flex; align-items: center; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
.detail-title { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0 0 8px; line-height: 1.2; }
.private-target-notice {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--c-primary);
  background: var(--c-primary-bg);
  border: 1px solid #DDD6FE;
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 20px;
  font-weight: 500;
}
.private-target-link { color: var(--c-primary-dk); font-weight: 700; text-decoration: none; }
.private-target-link:hover { text-decoration: underline; }

/* ── Info grid ── */
.detail-info-grid { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--c-hover); }
.detail-info-cell { padding: 14px 20px; border-right: 1px solid var(--c-hover); border-bottom: 1px solid var(--c-hover); }
.detail-info-cell-header { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.detail-info-label { font-size: 11px; font-weight: 700; color: var(--c-text-sm); text-transform: uppercase; letter-spacing: 0.06em; }
.detail-info-value { font-size: 13px; font-weight: 600; color: var(--c-text); }

/* ── Progress ── */
.detail-progress { padding: 16px 20px; border-bottom: 1px solid var(--c-hover); }
.detail-progress-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.detail-progress-text { font-size: 13px; font-weight: 600; color: var(--c-text-dk); display: flex; align-items: center; gap: 5px; }
.detail-progress-count { font-size: 13px; font-weight: 700; color: var(--c-primary); }
.progress-track { height: 6px; background: var(--c-hover); border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; background: var(--c-primary); border-radius: 99px; transition: width 0.3s; }
.progress-fill--full { background: #F59E0B; }

/* ── Participants ── */
.detail-participants { padding: 16px 20px 20px; }
.detail-participants-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0 0 10px;
}
.participants-list { display: flex; flex-direction: column; gap: 8px; }
.participant-row { display: flex; align-items: center; gap: 10px; }
.participant-row--link {
  text-decoration: none;
  border-radius: 8px;
  padding: 4px 6px;
  margin: -4px -6px;
  transition: background .12s;
}
.participant-row--link:hover { background: var(--c-hover); }
.participant-info { flex: 1; }
.participant-name { font-size: 13px; font-weight: 600; color: var(--c-text); }
.participants-empty { font-size: 13px; color: var(--c-text-sm); display: flex; align-items: center; gap: 6px; }

/* ── Demandes en attente ── */
.detail-requests {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 16px 20px 20px;
  margin-bottom: 16px;
}
.loading-center--sm { padding: 20px; }
.request-row { padding: 4px 0; }
.request-actions { display: flex; gap: 8px; }
.btn-request {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  border: none;
  border-radius: 7px;
  cursor: pointer;
  transition: background 0.1s;
}
.btn-request:disabled { opacity: 0.6; cursor: default; }
.btn-request--accept { background: var(--c-primary-bg); color: var(--c-primary); }
.btn-request--accept:hover:not(:disabled) { background: #DDD6FE; }
.btn-request--decline { background: #FEF2F2; color: #B91C1C; }
.btn-request--decline:hover:not(:disabled) { background: #FEE2E2; }

/* ── Description ── */
.detail-description {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 16px;
}
.detail-description-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0 0 8px;
}
.detail-description-text { font-size: 14px; color: var(--c-text-muted); line-height: 1.6; margin: 0; }

/* ── Author ── */
.detail-author {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
}
.detail-author-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.detail-author-info { flex: 1; }
.detail-author-role {
  font-size: 11px;
  font-weight: 700;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0 0 2px;
}
.detail-author-name { font-size: 14px; font-weight: 700; color: var(--c-text); margin: 0; }

/* ── Actions ── */
.detail-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-join {
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.1s;
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-join:disabled { opacity: 0.65; cursor: default; }
.btn-join--join { background: var(--c-primary); color: #fff; }
.btn-join--join:hover:not(:disabled) { background: var(--c-primary-dk); }
.btn-join--leave { background: #FEF2F2; color: #B91C1C; }
.btn-join--leave:hover:not(:disabled) { background: #FEE2E2; }
.btn-join--pending { background: var(--c-hover); color: var(--c-text-md); }
.btn-join--pending:hover:not(:disabled) { background: var(--c-border); }

.btn-contact { font-size: 14px; padding: 10px 20px; }
.btn-login-cta { padding: 10px 20px; font-size: 14px; }

/* ── Dialog ── */
.dialog-box { background: #fff; border-radius: 16px; padding: 24px; }
.dialog-title { font-size: 17px; font-weight: 800; color: var(--c-text); margin: 0 0 16px; }
.dialog-success { text-align: center; padding: 24px 0; }
.dialog-success-text { color: #16A34A; font-weight: 600; font-size: 14px; margin: 0; }
.dialog-textarea { resize: vertical; }
.dialog-actions { display: flex; gap: 8px; margin-top: 12px; }
.dialog-btn-cancel { flex: 1; padding: 10px; font-size: 13px; }
.dialog-btn-send { flex: 1; padding: 10px; font-size: 13px; }
</style>
