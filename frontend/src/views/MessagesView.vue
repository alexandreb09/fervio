<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const conversations = ref([])
const loading = ref(true)

function avatarUrl(u) {
  if (!u) return ''
  if (u.avatar) return u.avatar.startsWith('http') ? u.avatar : `http://localhost:8000${u.avatar}`
  return `https://ui-avatars.com/api/?name=${u.firstName}+${u.lastName}&background=FEF0E6&color=C25228&bold=true&size=80`
}

function timeAgo(d) {
  const diff = Date.now() - new Date(d).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'à l\'instant'
  if (mins < 60) return `il y a ${mins} min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `il y a ${hours}h`
  return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  const res = await api.get('/messages/conversations')
  conversations.value = res.data
  loading.value = false
})
</script>

<template>
  <div class="page-sm">
    <div class="messages-header">
      <p class="fin-label messages-header-label">Boîte de réception</p>
      <h1 class="messages-title">Messages</h1>
    </div>

    <div v-if="loading" class="skeleton-list">
      <v-skeleton-loader v-for="i in 4" :key="i" type="list-item-avatar-two-line" />
    </div>

    <div v-else-if="conversations.length" class="conv-list">
      <router-link
        v-for="(conv, i) in conversations"
        :key="conv.partner.publicId"
        :to="`/messages/${conv.partner.publicId}`"
        class="conv-row"
        :class="{ 'conv-row--last': i === conversations.length - 1 }"
      >
        <!-- Avatar with unread dot -->
        <div class="conv-avatar-wrap">
          <v-avatar size="44">
            <v-img :src="avatarUrl(conv.partner)" :alt="`Photo de ${conv.partner.firstName} ${conv.partner.lastName}`" />
          </v-avatar>
          <span v-if="conv.unreadCount > 0" class="conv-unread-dot">{{ conv.unreadCount }}</span>
        </div>

        <!-- Content -->
        <div class="conv-content">
          <div class="conv-top">
            <span class="conv-name" :class="{ 'conv-name--unread': conv.unreadCount > 0 }">
              {{ conv.partner.firstName }} {{ conv.partner.lastName }}
            </span>
            <span v-if="conv.lastMessage" class="conv-time">{{ timeAgo(conv.lastMessage.createdAt) }}</span>
          </div>
          <div
            v-if="conv.lastMessage"
            class="conv-preview"
            :class="{ 'conv-preview--unread': conv.unreadCount > 0 }"
          >
            {{ conv.lastMessage.content }}
          </div>
        </div>

        <v-icon size="14" color="border-light">mdi-chevron-right</v-icon>
      </router-link>
    </div>

    <div v-else class="empty-state">
      <v-icon size="40" color="border-light" class="mb-3">mdi-message-off-outline</v-icon>
      <p class="empty-state-title">Aucun message pour le moment</p>
      <p class="empty-state-subtitle">Contactez un joueur ou un organisateur d'annonce.</p>
      <router-link to="/joueurs" class="empty-state-cta">Voir les joueurs →</router-link>
    </div>
  </div>
</template>

<style scoped>
.messages-header { margin-bottom: 24px; }
.messages-header-label { margin: 0 0 4px; }
.messages-title { font-size: 24px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }

.skeleton-list { display: flex; flex-direction: column; gap: 8px; }

/* ── Conversation list ── */
.conv-list { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; overflow: hidden; }
.conv-row {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--c-hover);
  transition: background 0.1s;
}
.conv-row:hover { background: var(--c-bg); }
.conv-row--last { border-bottom: none; }

/* ── Avatar ── */
.conv-avatar-wrap { position: relative; flex-shrink: 0; }
.conv-unread-dot {
  position: absolute;
  top: 0;
  right: 0;
  min-width: 18px;
  height: 18px;
  border-radius: 99px;
  background: var(--c-primary);
  border: 2px solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  padding: 0 3px;
}

/* ── Content ── */
.conv-content { flex: 1; min-width: 0; }
.conv-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 2px; }
.conv-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.conv-name--unread { font-weight: 700; }
.conv-time { font-size: 11px; color: var(--c-text-sm); flex-shrink: 0; font-weight: 500; }
.conv-preview {
  font-size: 13px;
  color: var(--c-text-sm);
  font-weight: 400;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.conv-preview--unread { color: var(--c-text-dk); font-weight: 500; }

/* ── Empty state ── */
.empty-state {
  text-align: center;
  padding: 60px 24px;
  border: 1px dashed var(--c-border);
  border-radius: 12px;
}
.empty-state-title { font-size: 15px; font-weight: 600; color: var(--c-text-dk); margin: 0 0 6px; }
.empty-state-subtitle { font-size: 13px; color: var(--c-text-sm); margin: 0 0 20px; }
.empty-state-cta {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-primary);
  padding: 9px 18px;
  border: 1px solid #F5D4C2;
  border-radius: 8px;
  background: var(--c-primary-bg);
}
</style>
