<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router = useRouter()
const search = ref('')
const recentProposals = ref([])
const recentPlayers = ref([])
const loading = ref(true)

const surfaceLabels = { terre_battue: 'Terre battue', gazon: 'Gazon', dur: 'Dur', synthetique: 'Synthétique', indoor: 'Indoor' }
const gameTypeLabels = { simple: 'Simple', double: 'Double', double_mixte: 'Double mixte' }

onMounted(async () => {
  try {
    const [props, players] = await Promise.all([api.get('/proposals?status=open'), api.get('/users')])
    recentProposals.value = props.data.slice(0, 4)
    recentPlayers.value = players.data.slice(0, 6)
  } finally { loading.value = false }
})

function doSearch() {
  if (search.value.trim()) router.push(`/joueurs?city=${encodeURIComponent(search.value.trim())}`)
}

function formatDate(d) {
  const date = new Date(d)
  const today = new Date()
  const tomorrow = new Date(); tomorrow.setDate(today.getDate() + 1)
  if (date.toDateString() === today.toDateString())
    return `Aujourd'hui · ${date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })}`
  if (date.toDateString() === tomorrow.toDateString())
    return `Demain · ${date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })}`
  return date.toLocaleDateString('fr-FR', { day:'numeric', month:'short' }) + ' · ' + date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
}

const ACCENTS = ['C25228','D97706','059669','2563EB','7C3AED','DB2777','0891B2','65A30D']

function accent(u) {
  const s = `${u?.firstName}${u?.lastName}` || ''
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0
  return ACCENTS[h % ACCENTS.length]
}

function avatarUrl(u) {
  const c = accent(u)
  return u?.avatar ? u.avatar : `https://ui-avatars.com/api/?name=${u?.firstName}+${u?.lastName}&background=F5F0EB&color=${c}&bold=true`
}
</script>

<template>
  <div>
    <!-- ── Hero ── -->
    <section class="hero-section">
      <div class="hero-glow" aria-hidden="true"></div>
      <!-- Watermark balle de tennis -->
      <div class="hero-ball" aria-hidden="true">
        <span class="mdi mdi-tennis-ball"></span>
      </div>

      <div class="hero-inner">
        <span class="hero-label">
          <span class="mdi mdi-tennis-ball" style="font-size:12px;opacity:.8"></span>
          Partenaire raquette · Gratuit · France
        </span>
        <h1 class="hero-title">
          Trouvez votre<br>partenaire<br>de tennis.
        </h1>
        <p class="hero-subtitle">
          Rejoignez des joueurs de votre niveau près de chez vous. Publiez une annonce, rejoignez une partie, progressez ensemble.
        </p>

        <!-- Search -->
        <div class="hero-search">
          <v-icon color="text-subtle" size="18">mdi-map-marker</v-icon>
          <input
            v-model="search"
            placeholder="Votre ville..."
            class="hero-search-input"
            @keyup.enter="doSearch"
          />
          <button class="hero-search-btn" @click="doSearch">Rechercher</button>
        </div>

        <div class="hero-cta-row">
          <router-link to="/joueurs" class="hero-cta-link">
            <v-icon size="13">mdi-account-group-outline</v-icon> Voir les joueurs
          </router-link>
          <router-link to="/annonces" class="hero-cta-link">
            <v-icon size="13">mdi-calendar-search-outline</v-icon> Voir les parties
          </router-link>
        </div>
      </div>
    </section>

    <!-- ── Stats ── -->
    <section class="stats-section">
      <div class="stats-inner">
        <div
          v-for="stat in [
            { n: '100%', label: 'Gratuit', icon: 'mdi-gift-outline' },
            { n: recentPlayers.length + '+', label: 'Joueurs inscrits', icon: 'mdi-account-group-outline' },
            { n: recentProposals.length + '+', label: 'Parties disponibles', icon: 'mdi-calendar-check-outline' },
          ]"
          :key="stat.label"
          class="fin-stat stat-item"
        >
          <v-icon :icon="stat.icon" color="primary" size="20" class="mb-1" />
          <div class="stat-number">{{ stat.n }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </div>
      </div>
    </section>

    <div class="page">
      <!-- ── Annonces récentes ── -->
      <section class="home-section">
        <div class="section-header">
          <div>
            <p class="fin-label section-header-label">Dernières annonces</p>
            <h2 class="section-title">Parties disponibles</h2>
          </div>
          <router-link to="/annonces" class="section-see-all">Tout voir →</router-link>
        </div>

        <div v-if="!loading" class="proposals-grid">
          <router-link
            v-for="p in recentProposals"
            :key="p.publicId"
            :to="`/annonces/${p.publicId}`"
            class="fin-card proposal-card"
          >
            <div class="proposal-card-top">
              <span :class="p.status === 'full' ? 'badge badge-amber' : 'badge badge-green'">
                {{ p.status === 'full' ? 'Complet' : 'Disponible' }}
              </span>
              <span class="proposal-count">{{ p.participantCount }}/{{ p.maxPlayers }}</span>
            </div>
            <h3 class="proposal-card-title">{{ p.title }}</h3>
            <p class="proposal-card-date">
              <v-icon size="12">mdi-calendar-clock</v-icon> {{ formatDate(p.scheduledAt) }}
            </p>
            <p class="proposal-card-city">
              <v-icon size="12">mdi-map-marker</v-icon> {{ p.city }}
            </p>
            <div class="proposal-card-tags">
              <span v-if="p.gameType" class="badge badge-purple">{{ gameTypeLabels[p.gameType] }}</span>
              <span v-if="p.surface" class="badge badge-gray">{{ surfaceLabels[p.surface] }}</span>
            </div>
          </router-link>
          <div v-if="recentProposals.length === 0" class="empty-grid-msg">
            Aucune annonce disponible pour le moment.
          </div>
        </div>
        <div v-else class="proposals-grid">
          <v-skeleton-loader v-for="i in 4" :key="i" type="card" />
        </div>
      </section>

      <!-- ── Joueurs ── -->
      <section class="home-section">
        <div class="section-header">
          <div>
            <p class="fin-label section-header-label">Communauté</p>
            <h2 class="section-title">Joueurs disponibles</h2>
          </div>
          <router-link to="/joueurs" class="section-see-all">Tout voir →</router-link>
        </div>

        <div v-if="!loading" class="players-grid">
          <router-link
            v-for="p in recentPlayers"
            :key="p.id"
            :to="`/joueurs/${p.publicId}`"
            class="fin-card player-card"
            :style="{ '--accent': `#${accent(p)}` }"
          >
            <v-avatar size="52" class="player-card-avatar">
              <v-img :src="avatarUrl(p)" />
            </v-avatar>
            <div class="player-card-name">{{ p.firstName }} {{ p.lastName }}</div>
            <div class="player-card-city">{{ p.city || '—' }}</div>
            <span v-if="p.fftRanking" class="badge badge-purple">{{ p.fftRanking }}</span>
          </router-link>
        </div>
        <div v-else class="players-grid">
          <v-skeleton-loader v-for="i in 6" :key="i" type="card" />
        </div>
      </section>

      <!-- ── Comment ça marche ── -->
      <section class="how-section">
        <div class="how-header">
          <p class="fin-label">Simple &amp; rapide</p>
          <h2 class="how-title">Comment ça marche ?</h2>
        </div>
        <div class="how-grid">
          <div
            v-for="(step, i) in [
              { icon:'mdi-account-plus-outline', title:'Créez votre profil', desc:'Inscrivez-vous gratuitement et renseignez votre classement FFT.' },
              { icon:'mdi-magnify', title:'Trouvez un partenaire raquette', desc:'Recherchez un partenaire de tennis par ville, classement FFT ou type de jeu — simple, double ou mixte.' },
              { icon:'mdi-message-outline', title:'Jouez ensemble', desc:'Contactez les joueurs, organisez vos parties et progressez.' },
            ]"
            :key="i"
            class="how-step"
          >
            <div class="how-step-icon">
              <v-icon :icon="step.icon" color="primary" size="18" />
            </div>
            <div class="how-step-number">Étape {{ i + 1 }}</div>
            <div class="how-step-title">{{ step.title }}</div>
            <div class="how-step-desc">{{ step.desc }}</div>
          </div>
        </div>
        <div class="how-cta">
          <router-link to="/inscription" class="btn-primary">
            Rejoindre gratuitement →
          </router-link>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
/* ── Hero ── */
.hero-section {
  position: relative;
  overflow: hidden;
  background: linear-gradient(150deg, #1C0A03 0%, #5C200E 45%, #8B3214 100%);
  padding: 88px 24px 80px;
}
/* Halo lumineux décalé en haut à droite */
.hero-glow {
  position: absolute;
  top: -120px;
  right: -80px;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,180,120,0.18) 0%, transparent 70%);
  pointer-events: none;
}
/* Tennis ball watermark */
.hero-ball {
  position: absolute;
  right: -120px;
  top: -120px;
  pointer-events: none;
  opacity: 0.055;
  line-height: 1;
}
.hero-ball .mdi { font-size: 560px; color: #fff; display: block; }

.hero-inner {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 1;
}
.hero-label {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 22px;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 999px;
  padding: 5px 14px 5px 10px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: rgba(255,255,255,.8);
}
.hero-title {
  font-family: 'Barlow Condensed', 'Inter', sans-serif;
  font-size: clamp(2.2rem, 6vw, 3.8rem);
  font-weight: 800;
  letter-spacing: -0.01em;
  line-height: 0.92;
  text-transform: uppercase;
  color: #fff;
  margin: 0 0 22px;
}
.hero-subtitle {
  font-size: 16px;
  color: rgba(255,255,255,.65);
  line-height: 1.65;
  max-width: 460px;
  margin: 0 auto 40px;
  font-weight: 400;
}
.hero-search {
  display: flex;
  align-items: center;
  gap: 8px;
  border: none;
  border-radius: 12px;
  padding: 6px 6px 6px 14px;
  max-width: 460px;
  margin: 0 auto 28px;
  background: #fff;
  box-shadow: 0 6px 28px rgba(0, 0, 0, 0.28);
}
.hero-search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  font-family: Inter, sans-serif;
  color: var(--c-text);
  background: transparent;
}
.hero-search-input::placeholder { color: var(--c-text-sm); }
.hero-search-btn {
  background: var(--c-primary);
  color: #fff;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  white-space: nowrap;
  transition: background 0.1s;
}
.hero-search-btn:hover { background: var(--c-primary-dk); }
.hero-cta-row {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}
.hero-cta-link {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,.72);
  padding: 8px 16px;
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 8px;
  background: rgba(255,255,255,.07);
  backdrop-filter: blur(4px);
  transition: all 0.12s;
}
.hero-cta-link:hover {
  background: rgba(255,255,255,.15);
  color: #fff;
  border-color: rgba(255,255,255,.35);
}

/* ── Stats ── */
.stats-section {
  background: #F5E8DC;
  border-bottom: 1px solid #DFC0A5;
  padding: 24px;
}
.stats-inner {
  max-width: 1120px;
  margin: 0 auto;
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}
.stat-item { flex: 1; min-width: 140px; max-width: 180px; }
.stat-number { font-size: 22px; font-weight: 800; color: #7A2E12; letter-spacing: -0.03em; }
.stat-label { font-size: 12px; color: var(--c-text-md); font-weight: 500; margin-top: 2px; }

/* ── Sections ── */
.home-section { margin-bottom: 56px; }
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.section-header-label { margin: 0 0 4px; }
.section-title { font-size: 20px; font-weight: 700; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }
.section-see-all { font-size: 13px; font-weight: 600; color: var(--c-primary); text-decoration: none; }
.section-see-all:hover { text-decoration: underline; }

/* ── Proposals grid ── */
.proposals-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.proposal-card { text-decoration: none; display: block; padding: 18px 20px; }
.proposal-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 10px;
}
.proposal-count { font-size: 12px; color: var(--c-text-sm); font-weight: 500; }
.proposal-card-title { font-size: 14px; font-weight: 700; color: var(--c-text); letter-spacing: -0.01em; margin: 0 0 8px; line-height: 1.3; }
.proposal-card-date {
  font-size: 12px;
  color: var(--c-primary);
  font-weight: 600;
  margin: 0 0 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.proposal-card-city {
  font-size: 12px;
  color: var(--c-text-sm);
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.proposal-card-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.empty-grid-msg {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  color: var(--c-text-sm);
  border: 1px dashed var(--c-border);
  border-radius: 12px;
}

/* ── Players grid ── */
.players-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
.player-card {
  text-decoration: none;
  padding: 20px 16px;
  text-align: center;
  display: block;
  border-top: 3px solid var(--accent, var(--c-primary));
}
.player-card-avatar { margin-bottom: 10px; }
.player-card-name { font-size: 13px; font-weight: 700; color: var(--c-text); margin-bottom: 2px; }
.player-card-city { font-size: 12px; color: var(--c-text-sm); margin-bottom: 8px; }

/* ── How it works ── */
.how-section {
  background: var(--c-bg);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 40px 36px;
}
.how-header { text-align: center; margin-bottom: 32px; }
.how-title { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }
.how-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  max-width: 820px;
  margin: 0 auto 32px;
}
@media (max-width: 600px) {
  .how-grid { grid-template-columns: 1fr; max-width: 100%; }
}
.how-step {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 24px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.how-step-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--c-primary-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  flex-shrink: 0;
}
.how-step-number { font-size: 11px; font-weight: 700; color: var(--c-primary); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.how-step-title { font-size: 14px; font-weight: 700; color: var(--c-text); margin-bottom: 6px; letter-spacing: -0.01em; }
.how-step-desc { font-size: 13px; color: var(--c-text-md); line-height: 1.5; }
.how-cta { text-align: center; }
</style>
