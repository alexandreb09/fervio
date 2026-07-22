<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '@/api'
import { avatarUrl } from '@/utils/avatar'
import CityInput from '@/components/CityInput.vue'
import PartnerBtn from '@/components/PartnerBtn.vue'
import { usePartnersStore } from '@/stores/partners'

const auth = useAuthStore()
const router = useRouter()
const { user } = storeToRefs(auth)
const partnersStore = usePartnersStore()

// ── Profile editing ───────────────────────────────────────────────
const editing = ref(false)
const saving = ref(false)
const uploadingAvatar = ref(false)
const avatarInput = ref(null)
const proposals = ref([])
const loadingProposals = ref(true)

const FFT_RANKINGS = [
  'NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
  '15/5', '15/4', '15/3', '15/2', '15/1', '15',
  '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30',
]

const SURFACES = [
  { value: 'hard', label: 'Dur' },
  { value: 'clay', label: 'Terre battue' },
  { value: 'grass', label: 'Gazon' },
  { value: 'carpet', label: 'Moquette' },
]

const form = ref({
  firstName: '', lastName: '', city: '', postalCode: null, description: '',
  gender: null, fftRanking: null,
  handedness: null, birthYear: null, hasCourt: null, preferredSurface: [],
})
const fieldErrors = ref({})
const saveError = ref('')

// ── Account settings ─────────────────────────────────────────────
const editingAccount = ref(false)
const savingAccount = ref(false)
const accountForm = ref({
  email: '', newPassword: '', confirmPassword: '',
  acceptMessages: true, notifyMessages: true, notifyProposalReplies: true, acceptPrivateProposals: true,
})
const receivedPrivate = ref([])
const loadingReceivedPrivate = ref(true)
const accountErrors = ref({})
const accountSaveError = ref('')
const accountSaveSuccess = ref(false)

const statusLabels = { open: 'Disponible', full: 'Complet', cancelled: 'Annulée' }

// ── Delete account ────────────────────────────────────────────────
const deleteDialog = ref(false)
const deleting = ref(false)
const deleteError = ref('')
const deleteEmailConfirm = ref('')

async function deleteAccount() {
  deleting.value = true
  deleteError.value = ''
  try {
    await api.delete('/users/me')
    auth.logout()
    router.push('/')
  } catch {
    deleteError.value = 'Une erreur est survenue. Veuillez réessayer.'
    deleting.value = false
  }
}
const statusClass = { open: 'badge badge-green', full: 'badge badge-amber', cancelled: 'badge badge-red' }

function playerAvatar(p) { return avatarUrl(p) }

function initForms() {
  if (!user.value) return
  form.value = {
    firstName: user.value.firstName || '',
    lastName: user.value.lastName || '',
    city: user.value.city || '',
    postalCode: user.value.postalCode || null,
    description: user.value.description || '',
    gender: user.value.gender || null,
    fftRanking: user.value.fftRanking || null,
    handedness: user.value.handedness || null,
    birthYear: user.value.birthYear || null,
    hasCourt: user.value.hasCourt ?? null,
    preferredSurface: Array.isArray(user.value.preferredSurface) ? [...user.value.preferredSurface] : [],
  }
  accountForm.value = {
    email: user.value.email || '',
    newPassword: '',
    confirmPassword: '',
    acceptMessages: user.value.acceptMessages ?? true,
    notifyMessages: user.value.notifyMessages ?? true,
    notifyProposalReplies: user.value.notifyProposalReplies ?? true,
    acceptPrivateProposals: user.value.acceptPrivateProposals ?? true,
  }
}

onMounted(() => {
  initForms()

  api.get('/proposals', { params: { authorId: user.value?.id, status: 'all', includePast: true } })
    .then(res => { proposals.value = res.data })
    .finally(() => { loadingProposals.value = false })

  api.get('/proposals/received-private')
    .then(res => { receivedPrivate.value = res.data })
    .finally(() => { loadingReceivedPrivate.value = false })

  partnersStore.fetch()
})

async function save() {
  saving.value = true
  fieldErrors.value = {}
  saveError.value = ''
  try {
    const payload = { ...form.value }
    if (!payload.birthYear) payload.birthYear = null
    const res = await api.put('/users/me', payload)
    auth.user = res.data
    editing.value = false
    initForms()
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors ?? {}
    } else {
      saveError.value = e.response?.data?.error || 'Une erreur est survenue.'
    }
  } finally {
    saving.value = false
  }
}

async function saveAccount() {
  accountSaveError.value = ''
  accountErrors.value = {}
  accountSaveSuccess.value = false

  if (accountForm.value.newPassword && accountForm.value.newPassword !== accountForm.value.confirmPassword) {
    accountErrors.value.confirmPassword = 'Les mots de passe ne correspondent pas.'
    return
  }

  savingAccount.value = true
  try {
    const payload = {
      email: accountForm.value.email,
      acceptMessages: accountForm.value.acceptMessages,
      notifyMessages: accountForm.value.notifyMessages,
      notifyProposalReplies: accountForm.value.notifyProposalReplies,
      acceptPrivateProposals: accountForm.value.acceptPrivateProposals,
    }
    if (accountForm.value.newPassword) {
      payload.password = accountForm.value.newPassword
    }

    const emailChanged = payload.email !== user.value.email
    const res = await api.put('/users/me', payload)

    if (emailChanged) {
      auth.logout()
      partnersStore.reset()
      router.push('/connexion?emailChanged=1')
      return
    }

    auth.user = res.data
    accountForm.value.newPassword = ''
    accountForm.value.confirmPassword = ''
    accountSaveSuccess.value = true
    editingAccount.value = false
  } catch (e) {
    if (e.response?.status === 422) {
      accountErrors.value = e.response.data.errors ?? {}
    } else {
      accountSaveError.value = e.response?.data?.error || 'Une erreur est survenue.'
    }
  } finally {
    savingAccount.value = false
  }
}

async function deleteProposal(id) {
  if (!confirm('Supprimer cette annonce ?')) return
  await api.delete(`/proposals/${id}`)
  proposals.value = proposals.value.filter(p => p.id !== id)
}

async function handleAvatarChange(event) {
  const file = event.target.files[0]
  if (!file) return
  uploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const res = await api.post(`/users/${user.value.id}/avatar`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    auth.user = { ...auth.user, avatar: res.data.avatar }
  } catch (e) {
    alert(e.response?.data?.error || 'Erreur lors du téléchargement.')
  } finally {
    uploadingAvatar.value = false
    event.target.value = ''
  }
}

function myAvatarUrl(u) { return avatarUrl(u, 120) }

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) + ' · ' + new Date(d).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function surfaceLabel(v) {
  return SURFACES.find(s => s.value === v)?.label ?? v
}
</script>

<template>
  <div class="page-sm">
    <!-- ── Profile card ── -->
    <div class="profile-card">
      <div class="profile-banner" />
      <div class="profile-body">
        <div class="profile-top-row">
          <!-- Avatar upload -->
          <div class="avatar-wrap" :title="uploadingAvatar ? 'Chargement…' : 'Changer la photo'"
            @click="avatarInput?.click()">
            <v-avatar size="64" class="avatar-ring">
              <v-img :src="myAvatarUrl(user)" :alt="`Photo de ${user?.firstName} ${user?.lastName}`" />
            </v-avatar>
            <div class="avatar-overlay">
              <v-icon v-if="!uploadingAvatar" size="18" color="white">mdi-camera</v-icon>
              <v-progress-circular v-else size="18" width="2" color="white" indeterminate />
            </div>
          </div>
          <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="avatar-input"
            @change="handleAvatarChange" />

          <button v-if="!editing" class="btn-secondary" @click="editing = true">
            <v-icon size="13">mdi-pencil</v-icon> Modifier le profil
          </button>
        </div>

        <!-- ── View mode ── -->
        <template v-if="!editing">
          <h1 class="profile-name">{{ user?.firstName }} {{ user?.lastName }}</h1>
          <div class="profile-badges">
            <span v-if="user?.fftRanking" class="badge badge-purple badge--md">{{ user.fftRanking }}</span>
            <span v-if="user?.gender === 'M'" class="badge badge-blue badge--md">Homme</span>
            <span v-else-if="user?.gender === 'F'" class="badge badge-pink badge--md">Femme</span>
            <span v-else-if="user?.gender === 'A'" class="badge badge-gray badge--md">Autre</span>
            <span v-if="user?.city" class="badge badge-gray badge--md">
              <v-icon size="11">mdi-map-marker</v-icon> {{ user.city }}
            </span>
            <span v-if="user?.age" class="badge badge-gray badge--md">{{ user.age }} ans</span>
          </div>
          <p v-if="user?.description" class="profile-description">{{ user.description }}</p>
          <p v-else class="profile-description--empty">Aucune description pour l'instant.</p>

          <!-- Tennis info in view mode -->
          <template
            v-if="user?.handedness || (user?.hasCourt !== null && user?.hasCourt !== undefined) || user?.preferredSurface?.length">
            <div class="form-divider" />
            <p class="section-label">Profil de jeu</p>
            <div class="play-badges">
              <span v-if="user.handedness" class="badge badge-gray badge--sm">
                <v-icon size="11">mdi-hand-back-right-outline</v-icon>
                {{ user.handedness === 'R' ? 'Droitier' : 'Gaucher' }}
              </span>
              <span v-if="user.hasCourt === true" class="badge badge-green badge--sm">
                <v-icon size="11">mdi-tennis-ball</v-icon> Terrain disponible
              </span>
              <span v-if="user.hasCourt === false" class="badge badge-gray badge--sm">Pas de terrain</span>
              <span v-for="s in (user.preferredSurface || [])" :key="s" class="badge badge-amber badge--sm">
                {{ surfaceLabel(s) }}
              </span>
            </div>
          </template>
        </template>

        <!-- ── Edit mode ── -->
        <form v-else @submit.prevent="save">
          <div v-if="saveError" class="error-banner">{{ saveError }}</div>

          <!-- Identité -->
          <p class="section-label">Identité</p>
          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Prénom *</label>
              <input v-model="form.firstName" class="field-input"
                :class="{ 'field-input--error': fieldErrors.firstName }" />
              <p v-if="fieldErrors.firstName" class="field-error">{{ fieldErrors.firstName }}</p>
            </div>
            <div class="form-field">
              <label class="field-label">Nom <span class="label-optional">(optionnel)</span></label>
              <input v-model="form.lastName" class="field-input"
                :class="{ 'field-input--error': fieldErrors.lastName }" />
              <p v-if="fieldErrors.lastName" class="field-error">{{ fieldErrors.lastName }}</p>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Ville</label>
              <CityInput v-model="form.city" placeholder="Paris" input-class="field-input"
                @city-selected="e => { form.city = e.name; form.postalCode = e.postalCode }" />
            </div>
            <div class="form-field">
              <label class="field-label">Genre</label>
              <select v-model="form.gender" class="field-select">
                <option :value="null">Non renseigné</option>
                <option value="M">Homme</option>
                <option value="F">Femme</option>
                <option value="A">Autre</option>
              </select>
            </div>
          </div>

          <div class="form-field form-field--mb">
            <label class="field-label">Description</label>
            <textarea v-model="form.description" placeholder="Parlez de votre jeu, vos disponibilités…" rows="3"
              class="field-input field-textarea" />
          </div>

          <!-- Profil de jeu -->
          <div class="form-divider" />
          <p class="section-label">Profil de jeu</p>

          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Classement FFT</label>
              <select v-model="form.fftRanking" class="field-select">
                <option :value="null">Non classé</option>
                <option v-for="r in FFT_RANKINGS" :key="r" :value="r">{{ r }}</option>
              </select>
            </div>
            <div class="form-field">
              <label class="field-label">Droitier/Gaucher</label>
              <select v-model="form.handedness" class="field-select">
                <option :value="null">Non renseigné</option>
                <option value="R">Droitier(e)</option>
                <option value="L">Gaucher(e)</option>
              </select>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-field">
              <label class="field-label">Année de naissance</label>
              <input v-model.number="form.birthYear" type="number" placeholder="1990" min="1920" max="2010"
                class="field-input" />
            </div>
            <div class="form-field">
              <label class="field-label">Terrain disponible</label>
              <select :value="form.hasCourt === null ? '' : form.hasCourt ? 'true' : 'false'" class="field-select"
                @change="e => form.hasCourt = e.target.value === '' ? null : e.target.value === 'true'">
                <option value="">Non renseigné</option>
                <option value="true">Oui, j'ai un terrain</option>
                <option value="false">Non</option>
              </select>
            </div>
          </div>

          <div class="form-field form-field--mb">
            <label class="field-label">
              Surfaces préférées <span class="label-optional">(plusieurs choix possibles)</span>
            </label>
            <div class="surface-pills">
              <button v-for="s in SURFACES" :key="s.value" type="button"
                :class="form.preferredSurface.includes(s.value) ? 'surface-pill surface-pill--on' : 'surface-pill'"
                @click="form.preferredSurface.includes(s.value)
                  ? form.preferredSurface.splice(form.preferredSurface.indexOf(s.value), 1)
                  : form.preferredSurface.push(s.value)">
                {{ s.label }}
              </button>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="editing = false; fieldErrors = {}; saveError = ''">
              Annuler
            </button>
            <button type="submit" :disabled="saving" class="btn-primary btn-with-icon">
              <v-progress-circular v-if="saving" size="14" width="2" color="white" indeterminate />
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Account settings card ── -->
    <div class="settings-card">
      <div class="settings-card-header">
        <div class="settings-card-title-row">
          <div class="settings-icon">
            <v-icon size="16" color="primary">mdi-cog-outline</v-icon>
          </div>
          <h2 class="settings-card-title">Paramètres du compte</h2>
        </div>
        <button v-if="!editingAccount" class="btn-secondary btn-sm"
          @click="editingAccount = true; accountSaveSuccess = false">
          <v-icon size="12">mdi-pencil</v-icon> Modifier
        </button>
      </div>

      <div class="settings-card-body">
        <!-- View mode -->
        <template v-if="!editingAccount">
          <div v-if="accountSaveSuccess" class="success-banner">
            <v-icon size="15" color="success">mdi-check-circle</v-icon> Paramètres enregistrés.
          </div>
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-email-outline</v-icon> Email</span>
            <span class="settings-value">{{ user?.email }}</span>
          </div>
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-lock-outline</v-icon> Mot de
              passe</span>
            <span class="settings-value settings-value--muted">••••••••</span>
          </div>
          <div class="form-divider" />
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-message-outline</v-icon> Recevoir des
              messages</span>
            <span :class="user?.acceptMessages ? 'badge badge-green badge--sm' : 'badge badge-gray badge--sm'">
              {{ user?.acceptMessages ? 'Activé' : 'Désactivé' }}
            </span>
          </div>
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-bell-outline</v-icon> Email pour
              chaque message</span>
            <span :class="user?.notifyMessages ? 'badge badge-green badge--sm' : 'badge badge-gray badge--sm'">
              {{ user?.notifyMessages ? 'Activé' : 'Désactivé' }}
            </span>
          </div>
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-bell-outline</v-icon> Email pour les
              réponses aux parties</span>
            <span :class="user?.notifyProposalReplies ? 'badge badge-green badge--sm' : 'badge badge-gray badge--sm'">
              {{ user?.notifyProposalReplies ? 'Activé' : 'Désactivé' }}
            </span>
          </div>
          <div class="settings-row">
            <span class="settings-label"><v-icon size="14" color="text-subtle">mdi-lock-outline</v-icon> Recevoir des
              parties privées</span>
            <span
              :class="user?.acceptPrivateProposals !== false ? 'badge badge-green badge--sm' : 'badge badge-gray badge--sm'">
              {{ user?.acceptPrivateProposals !== false ? 'Activé' : 'Désactivé' }}
            </span>
          </div>
        </template>

        <!-- Edit mode -->
        <form v-else @submit.prevent="saveAccount">
          <div v-if="accountSaveError" class="error-banner">{{ accountSaveError }}</div>

          <p class="section-label">Connexion</p>
          <div class="form-field">
            <label class="field-label">Adresse email</label>
            <input v-model="accountForm.email" type="email" class="field-input"
              :class="{ 'field-input--error': accountErrors.email }" />
            <p v-if="accountErrors.email" class="field-error">{{ accountErrors.email }}</p>
            <p v-if="accountForm.email !== user?.email" class="email-warning">
              <v-icon size="12" color="#D97706">mdi-alert-outline</v-icon>
              Changer l'email vous déconnectera.
            </p>
          </div>

          <div class="form-grid-2 form-field--mb">
            <div class="form-field">
              <label class="field-label">Nouveau mot de passe <span class="label-optional">(optionnel)</span></label>
              <input v-model="accountForm.newPassword" type="password" placeholder="Laisser vide pour ne pas changer"
                class="field-input" />
            </div>
            <div class="form-field">
              <label class="field-label">Confirmer le mot de passe</label>
              <input v-model="accountForm.confirmPassword" type="password" placeholder="Confirmer" class="field-input"
                :class="{ 'field-input--error': accountErrors.confirmPassword }" />
              <p v-if="accountErrors.confirmPassword" class="field-error">{{ accountErrors.confirmPassword }}</p>
            </div>
          </div>

          <div class="form-divider" />
          <p class="section-label">Confidentialité &amp; Notifications</p>

          <div class="toggle-row">
            <div>
              <div class="toggle-title">Accepter les messages</div>
              <div class="toggle-desc">Permettre aux autres joueurs de vous envoyer des messages</div>
            </div>
            <button type="button" :class="accountForm.acceptMessages ? 'toggle-btn toggle-on' : 'toggle-btn toggle-off'"
              @click="accountForm.acceptMessages = !accountForm.acceptMessages">
              <span class="toggle-knob" />
            </button>
          </div>

          <div class="toggle-row">
            <div>
              <div class="toggle-title">Notification par email — Messages</div>
              <div class="toggle-desc">Recevoir un email à chaque nouveau message reçu</div>
            </div>
            <button type="button" :class="accountForm.notifyMessages ? 'toggle-btn toggle-on' : 'toggle-btn toggle-off'"
              @click="accountForm.notifyMessages = !accountForm.notifyMessages">
              <span class="toggle-knob" />
            </button>
          </div>

          <div class="toggle-row">
            <div>
              <div class="toggle-title">Notification par email — Parties publiques</div>
              <div class="toggle-desc">Recevoir un email quand quelqu'un répond à vos annonces</div>
            </div>
            <button type="button"
              :class="accountForm.notifyProposalReplies ? 'toggle-btn toggle-on' : 'toggle-btn toggle-off'"
              @click="accountForm.notifyProposalReplies = !accountForm.notifyProposalReplies">
              <span class="toggle-knob" />
            </button>
          </div>

          <div class="toggle-row toggle-row--last">
            <div>
              <div class="toggle-title">Accepter les parties privées</div>
              <div class="toggle-desc">Permettre aux autres joueurs de vous proposer une partie en privé</div>
            </div>
            <button type="button"
              :class="accountForm.acceptPrivateProposals ? 'toggle-btn toggle-on' : 'toggle-btn toggle-off'"
              @click="accountForm.acceptPrivateProposals = !accountForm.acceptPrivateProposals">
              <span class="toggle-knob" />
            </button>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary"
              @click="editingAccount = false; accountErrors = {}; accountSaveError = ''; initForms()">
              Annuler
            </button>
            <button type="submit" :disabled="savingAccount" class="btn-primary btn-with-icon">
              <v-progress-circular v-if="savingAccount" size="14" width="2" color="white" indeterminate />
              {{ savingAccount ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── My partners ── -->
    <div class="section-block">
      <div class="section-block-header">
        <h2 class="section-block-title">Mes partenaires</h2>
        <div class="partners-header-actions">
          <span class="section-block-count">{{ partnersStore.partners.length }}</span>
          <router-link to="/joueurs" class="find-players-link">
            <v-icon size="14">mdi-account-search-outline</v-icon>
            Trouver des joueurs
          </router-link>
        </div>
      </div>

      <div v-if="partnersStore.partners.length" class="partners-grid">
        <router-link v-for="p in partnersStore.partners" :key="p.id" :to="`/joueurs/${p.publicId}`"
          class="partner-card">
          <PartnerBtn :user="p" class="partner-card-btn" />
          <v-avatar size="48">
            <v-img :src="playerAvatar(p)" :alt="`Photo de ${p.firstName} ${p.lastName}`" />
          </v-avatar>
          <div>
            <div class="partner-card-name">{{ p.firstName }} {{ p.lastName }}</div>
            <div v-if="p.city" class="partner-card-city">{{ p.city }}</div>
          </div>
          <span v-if="p.fftRanking" class="badge badge-purple badge--xs">{{ p.fftRanking }}</span>
        </router-link>
      </div>

      <div v-else class="empty-state">
        <v-icon size="28" color="border-light" class="mb-2">mdi-bookmark-outline</v-icon>
        <p class="empty-state-text">Aucun partenaire enregistré.</p>
        <router-link to="/joueurs" class="empty-state-cta">
          <v-icon size="13">mdi-account-search-outline</v-icon> Trouver des joueurs
        </router-link>
      </div>
    </div>

    <!-- ── My proposals ── -->
    <div class="section-block">
      <div class="section-block-header">
        <h2 class="section-block-title">Mes annonces</h2>
        <router-link to="/annonces/nouvelle" class="btn-propose-sm">
          <v-icon size="13">mdi-plus</v-icon> Créer
        </router-link>
      </div>

      <div v-if="loadingProposals" class="skeleton-list">
        <v-skeleton-loader v-for="i in 3" :key="i" type="list-item-two-line" />
      </div>

      <div v-else-if="proposals.length" class="proposals-list">
        <div v-for="p in proposals" :key="p.publicId" class="fin-card proposal-row">
          <div class="proposal-info">
            <router-link :to="`/annonces/${p.publicId}`" class="proposal-link">
              <div class="proposal-title">{{ p.title }}</div>
            </router-link>
            <div class="proposal-meta">{{ formatDate(p.scheduledAt) }} · {{ p.city }}</div>
          </div>
          <span :class="statusClass[p.status]" class="badge--xs">{{ statusLabels[p.status] }}</span>
          <button class="btn-delete" @click="deleteProposal(p.id)">Supprimer</button>
        </div>
      </div>

      <div v-else class="empty-state">
        <v-icon size="32" color="border-light" class="mb-2">mdi-calendar-plus</v-icon>
        <p class="empty-state-text">Vous n'avez pas encore créé d'annonce.</p>
        <router-link to="/annonces/nouvelle" class="empty-state-cta">Créer ma première annonce</router-link>
      </div>
    </div>

    <!-- ── Parties privées reçues ── -->
    <div class="section-block">
      <div class="section-block-header">
        <h2 class="section-block-title">
          <v-icon size="16" color="primary" class="mr-1">mdi-lock-outline</v-icon>
          Parties privées reçues
        </h2>
        <span class="section-block-count">{{ receivedPrivate.length }}</span>
      </div>

      <div v-if="loadingReceivedPrivate" class="skeleton-list">
        <v-skeleton-loader v-for="i in 2" :key="i" type="list-item-two-line" />
      </div>

      <div v-else-if="receivedPrivate.length" class="proposals-list">
        <div v-for="p in receivedPrivate" :key="p.publicId" class="fin-card proposal-row">
          <div class="proposal-info">
            <router-link :to="`/annonces/${p.publicId}`" class="proposal-link">
              <div class="proposal-title">{{ p.title }}</div>
            </router-link>
            <div class="proposal-meta">
              {{ formatDate(p.scheduledAt) }} · {{ p.city }}
              <span class="private-from"> — de {{ p.author?.firstName }} {{ p.author?.lastName }}</span>
            </div>
          </div>
          <span :class="statusClass[p.status] ?? 'badge badge-gray'" class="badge--xs">{{ statusLabels[p.status] ??
            p.status
            }}</span>
        </div>
      </div>

      <div v-else class="empty-state">
        <v-icon size="28" color="border-light" class="mb-2">mdi-lock-outline</v-icon>
        <p class="empty-state-text">Aucune partie privée reçue.</p>
        <p class="empty-state-hint">Les invitations privées d'autres joueurs apparaîtront ici.</p>
      </div>
    </div>

    <!-- ── Danger zone ── -->
    <div class="danger-zone">
      <div class="danger-zone-header">
        <v-icon size="15" color="error">mdi-alert-circle-outline</v-icon>
        ACTION IRREVERSIBLE
      </div>
      <div class="danger-zone-body">
        <div>
          <div class="danger-zone-label">Supprimer mon compte</div>
          <div class="danger-zone-hint">Toutes vos données seront définitivement supprimées. Cette action est
            irréversible.
          </div>
        </div>
        <button class="btn-danger" @click="deleteDialog = true; deleteEmailConfirm = ''">Supprimer</button>
      </div>
    </div>

    <!-- Delete confirmation dialog -->
    <div v-if="deleteDialog" class="dialog-backdrop" @click.self="deleteDialog = false; deleteEmailConfirm = ''">
      <div class="dialog-box">
        <div class="dialog-icon">
          <v-icon size="22" color="error">mdi-trash-can-outline</v-icon>
        </div>
        <h3 class="dialog-title">Supprimer mon compte</h3>
        <p class="dialog-text">
          Votre profil, vos annonces et vos messages seront définitivement supprimés.<br>
          Cette action est <strong>irréversible</strong>.
        </p>
        <div class="dialog-confirm-field">
          <label class="field-label field-label--sm">Confirmez en saisissant votre email</label>
          <input v-model="deleteEmailConfirm" type="email" :placeholder="user?.email"
            class="field-input field-input--danger-focus" />
        </div>
        <div v-if="deleteError" class="error-banner">{{ deleteError }}</div>
        <div class="dialog-actions">
          <button class="btn-secondary" :disabled="deleting"
            @click="deleteDialog = false; deleteEmailConfirm = ''">Annuler</button>
          <button class="btn-danger" :disabled="deleting || deleteEmailConfirm !== user?.email" @click="deleteAccount">
            <v-progress-circular v-if="deleting" size="13" width="2" color="white" indeterminate />
            {{ deleting ? 'Suppression…' : 'Oui, supprimer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Profile card ── */
.profile-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 20px;
}

.profile-banner {
  height: 80px;
  background: linear-gradient(135deg, var(--c-primary), #D47A52);
}

.profile-body {
  padding: 0 24px 24px;
}

.profile-top-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-top: -32px;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}

/* ── Avatar upload ── */
.avatar-wrap {
  position: relative;
  cursor: pointer;
  flex-shrink: 0;
}

.avatar-ring {
  border: 3px solid #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: block;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.45);
  border: 3px solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.15s;
}

.avatar-wrap:hover .avatar-overlay {
  opacity: 1;
}

.avatar-input {
  display: none;
}

/* ── Profile view mode ── */
.profile-name {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 0 0 8px;
}

.profile-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.profile-description {
  font-size: 14px;
  color: var(--c-text-muted);
  line-height: 1.6;
  background: var(--c-bg);
  border-radius: 8px;
  padding: 12px 14px;
  margin: 0 0 16px;
  border: 1px solid var(--c-hover);
}

.profile-description--empty {
  font-size: 13px;
  color: var(--c-text-sm);
  font-style: italic;
  margin: 0 0 16px;
}

.play-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

/* ── Form helpers ── */
.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.form-field {
  margin-bottom: 12px;
}

.form-field--mb {
  margin-bottom: 16px;
}

.label-optional {
  font-weight: 400;
  color: var(--c-text-sm);
  font-size: 12px;
}

.field-textarea {
  resize: vertical;
  background: #fff;
}

.surface-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.form-actions {
  display: flex;
  gap: 8px;
}

.btn-with-icon {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-sm {
  font-size: 12px;
  padding: 5px 12px;
}

/* ── Account settings card ── */
.settings-card {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  margin-bottom: 20px;
  overflow: hidden;
}

.settings-card-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--c-hover);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.settings-card-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.settings-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--c-primary-bg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.settings-card-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
  margin: 0;
}

.settings-card-body {
  padding: 20px 24px;
}

/* Account view */
.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 0;
  border-bottom: 1px solid var(--c-bg);
  gap: 12px;
}

.settings-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: var(--c-text-dk);
  font-weight: 500;
}

.settings-value {
  font-size: 13px;
  color: var(--c-text);
  font-weight: 500;
}

.settings-value--muted {
  color: var(--c-text-sm);
}

/* Account form */
.email-warning {
  font-size: 11px;
  color: #D97706;
  margin: 4px 0 0;
  font-weight: 500;
}

/* Toggle switch */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 10px 0;
  border-bottom: 1px solid var(--c-bg);
}

.toggle-row--last {
  margin-bottom: 20px;
}

.toggle-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--c-text);
  margin-bottom: 2px;
}

.toggle-desc {
  font-size: 12px;
  color: var(--c-text-sm);
}

.toggle-btn {
  width: 40px;
  height: 22px;
  border-radius: 99px;
  border: none;
  cursor: pointer;
  padding: 2px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  transition: background 0.2s;
}

.toggle-on {
  background: var(--c-primary);
  justify-content: flex-end;
}

.toggle-off {
  background: var(--c-border-lt);
  justify-content: flex-start;
}

.toggle-knob {
  display: block;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Surface pills */
.surface-pill {
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  border: 1.5px solid var(--c-border);
  border-radius: 99px;
  background: #fff;
  color: var(--c-text-md);
  cursor: pointer;
  transition: all 0.15s;
}

.surface-pill:hover {
  border-color: var(--c-primary);
  color: var(--c-primary-dk);
}

.surface-pill--on {
  background: var(--c-primary-bg);
  border-color: var(--c-primary);
  color: var(--c-primary-dk);
}

/* ── Section blocks ── */
.section-block {
  margin-bottom: 24px;
}

.section-block-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.section-block-title {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--c-text);
  margin: 0;
}

.section-block-count {
  font-size: 13px;
  color: var(--c-text-sm);
  font-weight: 500;
}

/* Partner header actions */
.partners-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.find-players-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--c-primary-bg);
  border: 1px solid var(--c-border);
  border-radius: 8px;
  padding: 5px 11px;
  font-size: 12px;
  font-weight: 600;
  color: var(--c-primary);
  text-decoration: none;
  transition: background 0.15s, border-color 0.15s;
}

.find-players-link:hover {
  background: var(--c-hover);
  border-color: var(--c-primary);
}

/* Partners */
.partners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
}

.partner-card {
  text-decoration: none;
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 16px 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  position: relative;
  transition: border-color 0.15s;
}

.partner-card:hover {
  border-color: var(--c-border-lt);
}

.partner-card-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 3px 5px;
}

.partner-card-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--c-text);
}

.partner-card-city {
  font-size: 12px;
  color: var(--c-text-sm);
  margin-top: 2px;
}

/* Proposals list */
.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.proposals-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.proposal-row {
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.proposal-info {
  flex: 1;
  min-width: 0;
}

.proposal-link {
  text-decoration: none;
}

.proposal-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 3px;
}

.proposal-meta {
  font-size: 12px;
  color: var(--c-text-sm);
}

.private-from {
  color: var(--c-primary);
  font-weight: 500;
}

/* ── Danger zone ── */
.danger-zone {
  border: 1px solid #FECACA;
  border-radius: 12px;
  background: #FFF;
  overflow: hidden;
  margin-top: 8px;
}

.danger-zone-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: #FEF2F2;
  border-bottom: 1px solid #FECACA;
  font-size: 12px;
  font-weight: 700;
  color: #B91C1C;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.danger-zone-body {
  padding: 16px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.danger-zone-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--c-text);
  margin-bottom: 2px;
}

.danger-zone-hint {
  font-size: 12px;
  color: var(--c-text-sm);
}

.btn-danger {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: var(--c-error);
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-danger:hover:not(:disabled) {
  background: #DC2626;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── Confirmation dialog ── */
.dialog-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 16px;
}

.dialog-box {
  background: #fff;
  border-radius: 16px;
  padding: 28px 24px;
  width: 100%;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
}

.dialog-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.dialog-title {
  font-size: 17px;
  font-weight: 800;
  color: var(--c-text);
  margin: 0 0 10px;
  letter-spacing: -0.02em;
}

.dialog-text {
  font-size: 14px;
  color: var(--c-text-muted);
  line-height: 1.6;
  margin: 0 0 20px;
}

.dialog-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.btn-delete {
  flex-shrink: 0;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  background: #FEF2F2;
  color: #B91C1C;
  border: 1px solid #FECACA;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.1s;
}

.btn-delete:hover {
  background: #FEE2E2;
}

.btn-propose-sm {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-primary);
  padding: 6px 12px;
  border: 1px solid var(--c-hover);
  border-radius: 7px;
  background: var(--c-primary-bg);
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Empty states */
.empty-state {
  text-align: center;
  padding: 32px;
  border: 1px dashed var(--c-border);
  border-radius: 12px;
}

.empty-state-text {
  color: var(--c-text-sm);
  font-size: 13px;
  margin: 6px 0 0;
}

.empty-state-hint {
  color: var(--c-border-lt);
  font-size: 12px;
  margin: 4px 0 0;
}

.empty-state-cta {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  font-family: Inter, sans-serif;
  color: var(--c-primary);
  padding: 8px 16px;
  border: 1px solid var(--c-hover);
  border-radius: 7px;
  background: var(--c-primary-bg);
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 16px;
  cursor: pointer;
}

/* ── Dialog confirm field ── */
.dialog-confirm-field {
  margin-bottom: 16px;
}

.field-input--danger-focus:focus {
  border-color: var(--c-error) !important;
}
</style>