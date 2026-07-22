<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import { CITIES, findCityBySlug } from '@/data/cities'

const route = useRoute()
const city = computed(() => findCityBySlug(route.params.ville))

const players = ref([])
const proposals = ref([])
const loading = ref(true)

const otherCities = computed(() => CITIES.filter((c) => c.slug !== city.value.slug).slice(0, 8))

onMounted(async () => {
  try {
    const [playersRes, proposalsRes] = await Promise.all([
      api.get('/users', { params: { city: city.value.name } }),
      api.get('/proposals', { params: { city: city.value.name, status: 'open' } }),
    ])
    players.value = playersRes.data
    proposals.value = proposalsRes.data
  } finally {
    loading.value = false
  }
})

function formatDate(d) {
  const date = new Date(d)
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
  return u?.avatar ? u.avatar : `https://ui-avatars.com/api/?name=${u?.firstName}+${u?.lastName}&background=F5F0EB&color=${c}&bold=true`
}
</script>

<template>
  <div class="page">
    <header class="city-hero">
      <p class="fin-label">Partenaire Tennis · {{ city.name }}</p>
      <h1 class="city-title">Trouver un partenaire de tennis à {{ city.name }}</h1>

      <p v-if="!loading" class="city-intro">
        <template v-if="players.length || proposals.length">
          {{ players.length }} joueur{{ players.length > 1 ? 's' : '' }} inscrit{{ players.length > 1 ? 's' : '' }} et
          {{ proposals.length }} partie{{ proposals.length > 1 ? 's' : '' }} disponible{{ proposals.length > 1 ? 's' : '' }}
          en ce moment à {{ city.name }} sur Fervio.
        </template>
        <template v-else>
          Soyez parmi les premiers joueurs à {{ city.name }} sur Fervio : créez votre profil gratuitement pour trouver un partenaire de tennis près de chez vous.
        </template>
      </p>

      <p class="city-desc">
        Fervio vous permet de trouver gratuitement un partenaire de tennis à {{ city.name }}, quel que soit votre niveau.
        Filtrez les joueurs par classement FFT, rejoignez une partie de simple, double ou double mixte, ou publiez la vôtre pour organiser une rencontre.
      </p>

      <div class="city-cta-row">
        <router-link :to="`/joueurs?city=${city.name}`" class="btn-primary">Voir les joueurs à {{ city.name }}</router-link>
        <router-link :to="`/parties?city=${city.name}`" class="btn-secondary">Voir les parties à {{ city.name }}</router-link>
      </div>
    </header>

    <section v-if="!loading && proposals.length" class="home-section">
      <div class="section-header">
        <h2 class="section-title">Parties disponibles à {{ city.name }}</h2>
        <router-link :to="`/parties?city=${city.name}`" class="section-see-all">Tout voir →</router-link>
      </div>
      <div class="city-grid">
        <router-link
          v-for="p in proposals.slice(0, 4)"
          :key="p.publicId"
          :to="`/parties/${p.publicId}`"
          class="fin-card city-card"
        >
          <span :class="p.status === 'full' ? 'badge badge-amber' : 'badge badge-green'">
            {{ p.status === 'full' ? 'Complet' : 'Disponible' }}
          </span>
          <h3 class="city-card-title">{{ p.title }}</h3>
          <p class="city-card-meta">{{ formatDate(p.scheduledAt) }}</p>
        </router-link>
      </div>
    </section>

    <section v-if="!loading && players.length" class="home-section">
      <div class="section-header">
        <h2 class="section-title">Joueurs à {{ city.name }}</h2>
        <router-link :to="`/joueurs?city=${city.name}`" class="section-see-all">Tout voir →</router-link>
      </div>
      <div class="city-grid">
        <router-link
          v-for="p in players.slice(0, 6)"
          :key="p.id"
          :to="`/joueurs/${p.publicId}`"
          class="fin-card city-card city-card--player"
        >
          <v-avatar size="44">
            <v-img :src="avatarUrl(p)" :alt="`Photo de ${p.firstName} ${p.lastName}`" />
          </v-avatar>
          <div class="city-card-title">{{ p.firstName }} {{ p.lastName }}</div>
          <span v-if="p.fftRanking" class="badge badge-purple">{{ p.fftRanking }}</span>
        </router-link>
      </div>
    </section>

    <section class="city-links">
      <p class="fin-label">Partenaire de tennis dans d'autres villes</p>
      <div class="city-links-row">
        <router-link v-for="c in otherCities" :key="c.slug" :to="`/partenaire-tennis/${c.slug}`" class="city-link-chip">
          {{ c.name }}
        </router-link>
      </div>
    </section>
  </div>
</template>

<style scoped>
.city-hero { max-width: 680px; margin: 0 auto 48px; text-align: center; }
.city-title {
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 8px 0 16px;
}
.city-intro { font-size: 14px; font-weight: 600; color: var(--c-primary); margin: 0 0 12px; }
.city-desc { font-size: 14px; color: var(--c-text-md); line-height: 1.65; margin: 0 0 24px; }
.city-cta-row { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

.home-section { margin-bottom: 48px; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.section-title { font-size: 18px; font-weight: 700; letter-spacing: -0.03em; color: var(--c-text); margin: 0; }
.section-see-all { font-size: 13px; font-weight: 600; color: var(--c-primary); text-decoration: none; }
.section-see-all:hover { text-decoration: underline; }

.city-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.city-card { text-decoration: none; display: flex; flex-direction: column; align-items: flex-start; gap: 8px; padding: 16px 18px; }
.city-card--player { align-items: center; text-align: center; }
.city-card-title { font-size: 14px; font-weight: 700; color: var(--c-text); margin: 0; }
.city-card-meta { font-size: 12px; color: var(--c-text-sm); margin: 0; }

.city-links { text-align: center; padding-top: 16px; border-top: 1px solid var(--c-border); }
.city-links-row { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 12px; }
.city-link-chip {
  font-size: 13px;
  font-weight: 500;
  color: var(--c-text-muted);
  text-decoration: none;
  padding: 6px 14px;
  border: 1px solid var(--c-border);
  border-radius: 999px;
  transition: all 0.12s;
}
.city-link-chip:hover { color: var(--c-primary); border-color: var(--c-primary); background: var(--c-primary-bg); }
</style>
