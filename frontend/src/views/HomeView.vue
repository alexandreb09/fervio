<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { STORAGE_BASE } from '@/utils/avatar'
import CityInput from '@/components/CityInput.vue'

const router = useRouter()
const search = ref('')
const recentProposals = ref([])
const recentPlayers = ref([])
const loading = ref(true)
const statsDisplay = ref({ players: 0, proposals: 0, cities: 0 })

const surfaceLabels = { terre_battue: 'Terre battue', gazon: 'Gazon', dur: 'Dur', synthetique: 'Synthétique', indoor: 'Indoor' }
const gameTypeLabels = { simple: 'Simple', double: 'Double', double_mixte: 'Double mixte' }

onMounted(() => {
  loadHome()
  loadStats()
})

async function loadHome() {
  try {
    const [props, players] = await Promise.all([api.get('/proposals?status=open'), api.get('/users?sort=createdAt')])
    recentProposals.value = props.data.slice(0, 4)
    recentPlayers.value = players.data.slice(0, 6)
  } finally { loading.value = false }
}

async function loadStats() {
  try {
    const { data } = await api.get('/stats')
    animateStats(data)
  } catch { /* décoratif, on laisse les compteurs à 0 en cas d'échec */ }
}

function animateStats(target) {
  const duration = 900
  const start = performance.now()
  const from = { ...statsDisplay.value }
  function tick(now) {
    const progress = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - progress, 3)
    statsDisplay.value = {
      players: Math.round(from.players + (target.players - from.players) * eased),
      proposals: Math.round(from.proposals + (target.proposals - from.proposals) * eased),
      cities: Math.round(from.cities + (target.cities - from.cities) * eased),
    }
    if (progress < 1) requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

function doSearch() {
  if (search.value.trim()) router.push(`/joueurs?city=${encodeURIComponent(search.value.trim())}`)
}

function formatDate(d) {
  const date = new Date(d)
  const today = new Date()
  const tomorrow = new Date(); tomorrow.setDate(today.getDate() + 1)
  if (date.toDateString() === today.toDateString())
    return `Aujourd'hui · ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
  if (date.toDateString() === tomorrow.toDateString())
    return `Demain · ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) + ' · ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const ACCENTS = ['C25228', 'D97706', '059669', '2563EB', '7C3AED', 'DB2777', '0891B2', '65A30D']

function accent(u) {
  const s = `${u?.firstName}${u?.lastName}` || ''
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0
  return ACCENTS[h % ACCENTS.length]
}

function avatarUrl(u) {
  const c = accent(u)
  if (u?.avatar) return u.avatar.startsWith('http') ? u.avatar : `${STORAGE_BASE}${u.avatar}`
  return `https://ui-avatars.com/api/?name=${u?.firstName}+${u?.lastName}&background=F5F0EB&color=${c}&bold=true`
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
      <!-- Watermark raquette -->
      <div class="hero-racket" aria-hidden="true">
        <span class="mdi mdi-tennis"></span>
      </div>

      <div class="hero-inner">
        <div class="hero-badges">
          <span class="hero-label">
            <span class="mdi mdi-tennis-ball hero-label-icon"></span>
            Partenaires raquette · France
          </span>
          <span class="hero-label hero-label--free">GRATUIT</span>
        </div>
        <h1 class="hero-title">
          Chaque envie de tennis<br>mérite un partenaire
        </h1>
        <p class="hero-subtitle">
          Partout en France, des joueurs vous attendent.
        </p>

        <!-- Search -->
        <div class="hero-search">
          <v-icon color="text-subtle" size="18">mdi-map-marker</v-icon>
          <CityInput v-model="search" placeholder="Votre ville..." input-class="hero-search-input"
            @city-selected="e => { search = e.name }" @search="doSearch" />
          <button class="hero-search-btn" @click="doSearch">Rechercher</button>
        </div>

        <div class="hero-cta-row">
          <router-link to="/joueurs" class="hero-cta-btn">
            <v-icon size="16">mdi-account-search-outline</v-icon> Trouver un partenaire
          </router-link>
          <router-link to="/parties" class="hero-cta-btn">
            <v-icon size="16">mdi-calendar-search-outline</v-icon> Trouver une partie
          </router-link>
        </div>
      </div>
    </section>

    <!-- ── Stats ── -->
    <section class="stats-section">
      <div class="stats-inner">
        <div v-for="stat in [
          { n: statsDisplay.players, label: 'Joueurs inscrits', icon: 'mdi-account-group-outline' },
          { n: statsDisplay.proposals, label: 'Parties proposées', icon: 'mdi-calendar-check-outline' },
          { n: statsDisplay.cities, label: 'Villes concernées', icon: 'mdi-map-marker-outline' },
        ]" :key="stat.label" class="fin-stat stat-item">
          <v-icon :icon="stat.icon" color="primary" size="20" class="mb-1" />
          <div class="stat-number">{{ stat.n }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </div>
      </div>
    </section>

    <div class="page">
      <!-- ── Parties récentes ── -->
      <section class="home-section">
        <div class="section-header">
          <div>
            <p class="fin-label section-header-label">Dernières parties</p>
            <h2 class="section-title">Parties disponibles</h2>
          </div>
          <router-link to="/parties" class="section-see-all">Tout voir →</router-link>
        </div>

        <div v-if="!loading" class="proposals-grid">
          <router-link v-for="p in recentProposals" :key="p.publicId" :to="`/parties/${p.publicId}`"
            class="fin-card proposal-card">
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
            Aucune partie disponible pour le moment.
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
            <h2 class="section-title">Derniers joueurs inscrits</h2>
          </div>
          <router-link to="/joueurs" class="section-see-all">Tout voir →</router-link>
        </div>

        <div v-if="!loading" class="players-grid">
          <router-link v-for="p in recentPlayers" :key="p.id" :to="`/joueurs/${p.publicId}`"
            class="fin-card player-card" :style="{ '--accent': `#${accent(p)}` }">
            <v-avatar size="52" class="player-card-avatar">
              <v-img :src="avatarUrl(p)" :alt="`Photo de ${p.firstName} ${p.lastName}`" />
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

      <!-- ── Services proposés ── -->
      <section class="home-section services-section">
        <div class="section-header">
          <div>
            <p class="fin-label section-header-label">Services proposés</p>
            <h2 class="section-title">Jouez au tennis quand et où vous voulez</h2>
          </div>
        </div>
        <div class="services-grid">
          <div v-for="(service, i) in [
            { icon: 'mdi-trophy-outline', title: 'Préparez un tournoi', desc: 'Programmez des séances d\'entraînement régulières avec un ou plusieurs partenaires au classement adapté.' },
            { icon: 'mdi-bag-suitcase-outline', title: 'Préparez vos vacances', desc: 'Contactez des partenaires sur votre futur lieu de séjour — Côte d\'Azur, Bretagne, Alpes…' },
            { icon: 'mdi-map-marker-outline', title: 'Trouvez un joueur près de chez vous', desc: 'Recherchez des partenaires dans votre ville et les communes voisines.' },
            { icon: 'mdi-clock-fast', title: 'Trouvez un partenaire à la dernière minute', desc: 'Consultez les parties proposées pour jouer dès aujourd\'hui.' },
          ]" :key="i" class="service-item">
            <div class="service-item-icon">
              <v-icon :icon="service.icon" color="primary" size="18" />
            </div>
            <div class="service-item-title">{{ service.title }}</div>
            <div class="service-item-desc">{{ service.desc }}</div>
          </div>
        </div>
      </section>

      <!-- ── Rejoignez-nous ── -->
      <section class="home-section cta-section">
        <div class="cta-card">
          <p class="fin-label">Rejoignez-nous</p>
          <h2 class="cta-title">Enrichissez la communauté !</h2>
          <p class="cta-text">Chaque nouveau joueur enrichit la communauté et multiplie les possibilités de jeu.</p>
          <router-link to="/inscription" class="cta-btn">S'inscrire →</router-link>
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
  background: radial-gradient(circle, rgba(255, 180, 120, 0.18) 0%, transparent 70%);
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

.hero-ball .mdi {
  font-size: 560px;
  color: #fff;
  display: block;
}

/* Racket watermark */
.hero-racket {
  position: absolute;
  left: -100px;
  bottom: -140px;
  pointer-events: none;
  opacity: 0.055;
  line-height: 1;
  transform: rotate(-18deg);
}

.hero-racket .mdi {
  font-size: 420px;
  color: #fff;
  display: block;
}

.hero-inner {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 1;
}

.hero-badges {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 22px;
}

.hero-label {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: rgba(255, 255, 255, .1);
  border: 1px solid rgba(255, 255, 255, .18);
  border-radius: 999px;
  padding: 5px 14px 5px 10px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, .8);
}

.hero-label-icon {
  font-size: 12px;
  opacity: .8;
}

.hero-label--free {
  background: #E11D2E;
  border-color: #E11D2E;
  color: #fff;
  padding: 5px 14px;
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
  color: rgba(255, 255, 255, .65);
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

.hero-search :deep(.city-wrap) {
  flex: 1;
  min-width: 0;
}

@media (max-width: 480px) {
  .hero-search {
    flex-wrap: wrap;
    row-gap: 10px;
    border-radius: 16px;
  }

  .hero-search-btn {
    flex: 1 1 100%;
  }
}

.hero-search-input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 15px;
  font-family: Inter, sans-serif;
  color: var(--c-text);
  background: transparent;
}

.hero-search-input::placeholder {
  color: var(--c-text-sm);
}

.hero-search :deep(.city-dropdown) {
  z-index: 200;
}

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

.hero-search-btn:hover {
  background: var(--c-primary-dk);
}

.hero-cta-row {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

.hero-cta-btn {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 14px;
  font-weight: 700;
  color: #7A2E12;
  padding: 12px 22px;
  border: 1px solid #fff;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
  transition: all 0.12s;
}

.hero-cta-btn:hover {
  background: #F5E8DC;
  border-color: #F5E8DC;
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

.stat-item {
  flex: 1;
  min-width: 140px;
  max-width: 180px;
}

.stat-number {
  font-size: 22px;
  font-weight: 800;
  color: #7A2E12;
  letter-spacing: -0.03em;
}

.stat-label {
  font-size: 12px;
  color: var(--c-text-md);
  font-weight: 500;
  margin-top: 2px;
}

/* ── Sections ── */
.home-section {
  margin-bottom: 56px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.section-header-label {
  margin: 0 0 4px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 0;
}

.section-see-all {
  font-size: 13px;
  font-weight: 600;
  color: var(--c-primary);
  text-decoration: none;
}

.section-see-all:hover {
  text-decoration: underline;
}

/* ── Proposals grid ── */
.proposals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
}

.proposal-card {
  text-decoration: none;
  display: block;
  padding: 18px 20px;
}

.proposal-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 10px;
}

.proposal-count {
  font-size: 12px;
  color: var(--c-text-sm);
  font-weight: 500;
}

.proposal-card-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  letter-spacing: -0.01em;
  margin: 0 0 8px;
  line-height: 1.3;
}

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

.proposal-card-tags {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.empty-grid-msg {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  color: var(--c-text-sm);
  border: 1px dashed var(--c-border);
  border-radius: 12px;
}

/* ── Players grid ── */
.players-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px;
}

.player-card {
  text-decoration: none;
  padding: 20px 16px;
  text-align: center;
  display: block;
  border-top: 3px solid var(--accent, var(--c-primary));
}

.player-card-avatar {
  margin-bottom: 10px;
}

.player-card-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 2px;
}

.player-card-city {
  font-size: 12px;
  color: var(--c-text-sm);
  margin-bottom: 8px;
}

/* ── Services proposés ── */
.services-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

@media (max-width: 600px) {
  .services-grid {
    grid-template-columns: 1fr;
  }
}

.service-item {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 22px 20px;
}

.service-item-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--c-primary-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.service-item-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 6px;
  letter-spacing: -0.01em;
}

.service-item-desc {
  font-size: 13px;
  color: var(--c-text-md);
  line-height: 1.5;
}

/* ── Rejoignez-nous ── */
.cta-section {
  margin-bottom: 56px;
}

.cta-card {
  background: linear-gradient(150deg, #1C0A03 0%, #5C200E 45%, #8B3214 100%);
  border-radius: 16px;
  padding: 40px 36px;
  text-align: center;
}

.cta-card .fin-label {
  color: rgba(255, 255, 255, .7);
}

.cta-title {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #fff;
  margin: 0 0 10px;
}

.cta-text {
  font-size: 15px;
  color: rgba(255, 255, 255, .72);
  max-width: 480px;
  margin: 0 auto 24px;
  line-height: 1.6;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  color: #7A2E12;
  padding: 12px 24px;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
  transition: background 0.12s;
}

.cta-btn:hover {
  background: #F5E8DC;
}
</style>
