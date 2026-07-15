<script setup>
import { ref, onMounted, nextTick, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMessagesStore } from '@/stores/messages'
import api from '@/api'
import PartnerBtn from '@/components/PartnerBtn.vue'
import ReportModal from '@/components/ReportModal.vue'

const route = useRoute()
const auth = useAuthStore()
const msgStore = useMessagesStore()

const partner = ref(null)
const messages = ref([])
const newMessage = ref('')
const loading = ref(true)
const loadError = ref(false)
const sending = ref(false)
const messagesContainer = ref(null)
const reportMsgId = ref(null)
let pollInterval = null

function avatarUrl(u) {
  if (!u) return ''
  if (u.avatar) return u.avatar.startsWith('http') ? u.avatar : `http://localhost:8000${u.avatar}`
  return `https://ui-avatars.com/api/?name=${u.firstName}+${u.lastName}&background=FEF0E6&color=C25228&bold=true&size=80`
}

function formatTime(d) {
  const date = new Date(d)
  const today = new Date()
  if (date.toDateString() === today.toDateString())
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) + ' ' +
    date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function isMine(msg) { return msg.sender.id === auth.user?.id }

async function fetchMessages() {
  const res = await api.get(`/messages/with/${route.params.id}`)
  messages.value = res.data
  await msgStore.fetchUnread()
  scrollToBottom()
}

async function sendMessage() {
  if (!newMessage.value.trim() || sending.value) return
  sending.value = true
  const text = newMessage.value.trim()
  newMessage.value = ''
  try {
    await api.post('/messages', { receiverPublicId: parseInt(route.params.id), content: text })
    await fetchMessages()
  } catch {
    newMessage.value = text
  } finally {
    sending.value = false
  }
}

async function scrollToBottom() {
  await nextTick()
  if (messagesContainer.value)
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
}

onMounted(async () => {
  try {
    const [partnerRes] = await Promise.all([api.get(`/users/${route.params.id}`), fetchMessages()])
    partner.value = partnerRes.data
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
  pollInterval = setInterval(async () => {
    try { await fetchMessages() } catch {}
  }, 10000)
})

onUnmounted(() => clearInterval(pollInterval))
</script>

<template>
  <div class="conv-layout">
    <div v-if="loading" class="loading-center">
      <v-progress-circular size="32" color="primary" indeterminate />
    </div>

    <div v-else-if="loadError" class="loading-center">
      <p class="conv-load-error">Impossible de charger la conversation. Veuillez réessayer.</p>
      <router-link to="/messages" class="conv-back-link">← Retour aux messages</router-link>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="conv-header">
        <router-link to="/messages" class="conv-back">
          <v-icon size="18">mdi-arrow-left</v-icon>
        </router-link>
        <v-avatar size="36">
          <v-img :src="avatarUrl(partner)" :alt="`Photo de ${partner?.firstName} ${partner?.lastName}`" />
        </v-avatar>
        <div class="conv-header-info">
          <div class="conv-header-name">{{ partner?.firstName }} {{ partner?.lastName }}</div>
          <router-link :to="`/joueurs/${partner?.publicId}`" class="conv-header-profile">Voir le profil →</router-link>
        </div>
        <PartnerBtn v-if="partner" :user="partner" />
      </div>

      <!-- Messages -->
      <div ref="messagesContainer" class="conv-messages">
        <div v-if="messages.length === 0" class="conv-empty">
          <v-icon size="40" color="border-light" class="mb-2">mdi-message-outline</v-icon>
          <p class="conv-empty-text">Démarrez la conversation !</p>
        </div>

        <div
          v-for="(msg, idx) in messages"
          :key="msg.id"
          class="msg-group"
          :class="isMine(msg) ? 'msg-group--mine' : 'msg-group--other'"
        >
          <!-- Date separator -->
          <div
            v-if="idx === 0 || new Date(msg.createdAt).toDateString() !== new Date(messages[idx-1].createdAt).toDateString()"
            class="date-separator"
          >
            {{ new Date(msg.createdAt).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' }) }}
          </div>

          <!-- Bubble -->
          <div class="msg-bubble-wrap" :class="isMine(msg) ? 'msg-bubble-wrap--mine' : 'msg-bubble-wrap--other'">
            <div class="msg-bubble" :class="isMine(msg) ? 'msg-bubble--mine' : 'msg-bubble--other'">
              {{ msg.content }}
            </div>
            <div class="msg-meta">
              <span class="msg-time">{{ formatTime(msg.createdAt) }}</span>
              <v-icon v-if="isMine(msg)" size="12" :color="msg.isRead ? 'primary' : 'border-light'">mdi-check-all</v-icon>
              <button v-if="!isMine(msg)" class="msg-report-btn" @click="reportMsgId = msg.id" title="Signaler">
                <v-icon size="11">mdi-flag-outline</v-icon>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="conv-input-bar">
        <input
          v-model="newMessage"
          placeholder="Votre message…"
          class="conv-input"
          @keyup.enter.exact="sendMessage"
        />
        <button
          class="conv-send-btn"
          :disabled="sending || !newMessage.trim()"
          @click="sendMessage"
        >
          <v-icon size="17" color="white">{{ sending ? 'mdi-loading' : 'mdi-send' }}</v-icon>
        </button>
      </div>
    </template>

    <ReportModal
      v-if="reportMsgId"
      target-type="message"
      :target-id="reportMsgId"
      @close="reportMsgId = null"
    />
  </div>
</template>

<style scoped>
.conv-layout {
  max-width: 720px;
  margin: 0 auto;
  padding: 24px 24px 0;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
}
.loading-center { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px; gap: 12px; }
.conv-load-error { font-size: 14px; color: var(--c-text-md); text-align: center; }
.conv-back-link { font-size: 13px; color: var(--c-primary); text-decoration: none; font-weight: 500; }
.conv-back-link:hover { text-decoration: underline; }

/* ── Header ── */
.conv-header {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px 12px 0 0;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}
.conv-back { color: var(--c-text-sm); display: flex; align-items: center; text-decoration: none; }
.conv-back:hover { color: var(--c-text-md); }
.conv-header-info { flex: 1; min-width: 0; }
.conv-header-name { font-size: 14px; font-weight: 700; color: var(--c-text); letter-spacing: -0.01em; }
.conv-header-profile { font-size: 12px; color: var(--c-primary); text-decoration: none; font-weight: 500; }
.conv-header-profile:hover { text-decoration: underline; }

/* ── Messages ── */
.conv-messages {
  flex: 1;
  overflow-y: auto;
  background: var(--c-bg);
  border-left: 1px solid var(--c-border);
  border-right: 1px solid var(--c-border);
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-height: 0;
}
.conv-empty { text-align: center; padding: 40px 0; color: var(--c-text-sm); }
.conv-empty-text { font-size: 14px; margin: 8px 0 0; }

/* ── Message groups ── */
.msg-group { display: flex; flex-direction: column; }
.msg-group--mine { align-items: flex-end; }
.msg-group--other { align-items: flex-start; }

.date-separator {
  align-self: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--c-text-sm);
  margin: 8px 0 4px;
  background: var(--c-hover);
  padding: 3px 10px;
  border-radius: 99px;
}

/* ── Bubble ── */
.msg-bubble-wrap { max-width: 72%; display: flex; flex-direction: column; }
.msg-bubble-wrap--mine { align-items: flex-end; }
.msg-bubble-wrap--other { align-items: flex-start; }
.msg-bubble {
  padding: 10px 14px;
  font-size: 14px;
  line-height: 1.5;
  word-break: break-word;
}
.msg-bubble--mine {
  background: var(--c-primary);
  color: #fff;
  border-radius: 16px 16px 4px 16px;
}
.msg-bubble--other {
  background: var(--c-border);
  color: var(--c-text);
  border-radius: 16px 16px 16px 4px;
}
.msg-meta { display: flex; align-items: center; gap: 4px; margin-top: 3px; }
.msg-report-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 2px;
  color: var(--c-border);
  line-height: 1;
  opacity: 0;
  transition: opacity 0.1s, color 0.1s;
}
.msg-group:hover .msg-report-btn { opacity: 1; }
.msg-report-btn:hover { color: var(--c-error); }
.msg-time { font-size: 11px; color: var(--c-text-sm); }

/* ── Input bar ── */
.conv-input-bar {
  background: #fff;
  border: 1px solid var(--c-border);
  border-top: none;
  border-radius: 0 0 12px 12px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  margin-bottom: 24px;
}
.conv-input {
  flex: 1;
  padding: 9px 12px;
  font-size: 14px;
  font-family: Inter, sans-serif;
  border: 1px solid var(--c-border);
  border-radius: 8px;
  outline: none;
  color: var(--c-text);
  background: var(--c-bg);
  box-sizing: border-box;
  transition: border-color 0.1s, background 0.1s;
}
.conv-input:focus { border-color: var(--c-primary); background: #fff; }
.conv-send-btn {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: var(--c-primary);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.1s;
}
.conv-send-btn:hover:not(:disabled) { background: var(--c-primary-dk); }
.conv-send-btn:disabled { opacity: 0.5; cursor: default; }
</style>
