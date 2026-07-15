<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMessagesStore } from '@/stores/messages'
import { usePartnersStore } from '@/stores/partners'
import { storeToRefs } from 'pinia'

const router = useRouter()
const auth = useAuthStore()
const messages = useMessagesStore()
const partners = usePartnersStore()
const { isLoggedIn, user } = storeToRefs(auth)
const { unreadCount } = storeToRefs(messages)
const drawer = ref(false)
const newMsgSnackbar = ref(false)
const prevUnread = ref(-1)

function logout() { auth.logout(); partners.reset(); router.push('/') }

onMounted(() => { if (isLoggedIn.value) messages.fetchUnread() })

watch(unreadCount, (newVal) => {
  if (prevUnread.value >= 0 && newVal > prevUnread.value) newMsgSnackbar.value = true
  prevUnread.value = newVal
})

watch(isLoggedIn, (val) => {
  if (val) {
    messages.fetchUnread()
    const t = setInterval(() => { if (isLoggedIn.value) messages.fetchUnread(); else clearInterval(t) }, 15000)
  } else {
    prevUnread.value = -1
  }
})
</script>

<template>
  <v-app>
    <!-- ── Top navigation ── -->
    <header class="app-header">
      <div class="app-header-inner">
        <!-- Logo -->
        <router-link to="/" class="nav-logo">
          <img src="/favicon.svg" alt="Fervio" class="nav-logo-img" />
          <span class="nav-logo-text">Ferv<span class="nav-logo-accent">io</span></span>
        </router-link>

        <!-- Desktop nav -->
        <nav class="d-none d-md-flex align-center nav-links">
          <router-link
            v-for="item in [{ to: '/joueurs', label: 'Joueurs' }, { to: '/annonces', label: 'Annonces' }]"
            :key="item.to"
            :to="item.to"
            class="nav-link"
            active-class="nav-active"
          >
            {{ item.label }}
          </router-link>
        </nav>

        <!-- Desktop auth -->
        <div class="d-none d-md-flex align-center nav-auth">
          <router-link to="/annonces/nouvelle" class="btn-propose">
            <v-icon size="14">mdi-plus</v-icon> Proposer une partie
          </router-link>

          <template v-if="isLoggedIn">
            <!-- Bell with notification dropdown -->
            <v-menu transition="slide-y-transition" :offset="[4, 0]">
              <template #activator="{ props }">
                <button v-bind="props" class="btn-bell" aria-label="Notifications">
                  <v-icon size="18">mdi-bell-outline</v-icon>
                  <span v-if="unreadCount > 0" class="unread-dot" />
                </button>
              </template>
              <div class="notif-dropdown">
                <div class="notif-header">Notifications</div>
                <div v-if="unreadCount === 0" class="notif-empty">
                  <v-icon size="24" color="border-light">mdi-bell-off-outline</v-icon>
                  <span>Aucune notification</span>
                </div>
                <router-link v-else to="/messages" class="notif-item">
                  <v-icon size="15" color="primary">mdi-message-outline</v-icon>
                  <span>{{ unreadCount }} message{{ unreadCount > 1 ? 's' : '' }} non lu{{ unreadCount > 1 ? 's' : '' }}</span>
                  <span class="badge badge-red badge--xs ml-auto">{{ unreadCount }}</span>
                </router-link>
                <div class="notif-footer">
                  <router-link to="/messages" class="notif-all-link">Voir tous les messages →</router-link>
                </div>
              </div>
            </v-menu>

            <!-- User menu -->
            <v-menu transition="slide-y-transition" :offset="[4, 0]">
              <template #activator="{ props }">
                <button v-bind="props" class="user-menu-btn">
                  <v-avatar size="24" class="user-avatar-bg">
                    <v-img v-if="user?.avatar" :src="user.avatar" :alt="`Photo de ${user?.firstName} ${user?.lastName}`" />
                    <span v-else class="user-initials">{{ user?.firstName?.[0] }}{{ user?.lastName?.[0] }}</span>
                  </v-avatar>
                  <span class="user-name">{{ user?.firstName }}</span>
                  <v-icon size="14" color="text-subtle">mdi-chevron-down</v-icon>
                </button>
              </template>

              <div class="nav-dropdown">
                <router-link to="/profil" class="nav-dropdown-item">
                  <v-icon size="15" color="primary">mdi-account-outline</v-icon> Mon profil
                </router-link>
                <router-link to="/messages" class="nav-dropdown-item">
                  <v-icon size="15" color="primary">mdi-message-outline</v-icon> Messages
                  <span v-if="unreadCount > 0" class="badge badge-red badge--xs ml-auto">{{ unreadCount }}</span>
                </router-link>
                <div class="nav-dropdown-divider" />
                <button @click="logout" class="nav-dropdown-logout">
                  <v-icon size="15" color="error">mdi-logout</v-icon> Déconnexion
                </button>
              </div>
            </v-menu>
          </template>

          <template v-else>
            <router-link to="/connexion" class="btn-login">Connexion</router-link>
            <router-link to="/inscription" class="btn-register">S'inscrire</router-link>
          </template>
        </div>

        <!-- Mobile: notification bell (logged in only) + burger -->
        <div class="d-flex d-md-none align-center mobile-actions">
          <router-link v-if="isLoggedIn" to="/messages" class="btn-bell">
            <v-icon size="18">mdi-bell-outline</v-icon>
            <span v-if="unreadCount > 0" class="unread-dot" />
          </router-link>
          <button class="mobile-burger" @click="drawer = !drawer">
            <v-icon size="18" color="text-muted">mdi-menu</v-icon>
          </button>
        </div>
      </div>
    </header>

    <!-- Mobile drawer -->
    <v-navigation-drawer v-model="drawer" temporary width="272">
      <div class="drawer-header">
        <img src="/favicon.svg" alt="Fervio" class="drawer-logo-img" />
        <span class="drawer-logo-text">Fervio</span>
      </div>
      <div class="drawer-nav">
        <router-link
          v-for="item in [{ to: '/joueurs', icon: 'mdi-account-group', label: 'Joueurs' }, { to: '/annonces', icon: 'mdi-calendar-search', label: 'Annonces' }]"
          :key="item.to"
          :to="item.to"
          class="drawer-link"
          @click="drawer = false"
        >
          <v-icon size="17" color="primary">{{ item.icon }}</v-icon> {{ item.label }}
        </router-link>

        <div class="drawer-divider" />
        <router-link to="/annonces/nouvelle" class="drawer-link" @click="drawer = false">
          <v-icon size="17" color="primary">mdi-plus-circle</v-icon> Proposer une partie
        </router-link>

        <template v-if="isLoggedIn">
          <router-link to="/messages" class="drawer-link" @click="drawer = false">
            <v-icon size="17" color="primary">mdi-message</v-icon> Messages
            <span v-if="unreadCount > 0" class="badge badge-red badge--xs ml-auto">{{ unreadCount }}</span>
          </router-link>
          <router-link to="/profil" class="drawer-link" @click="drawer = false">
            <v-icon size="17" color="primary">mdi-account</v-icon> Mon profil
          </router-link>
          <div class="drawer-divider" />
          <button class="drawer-logout" @click="logout(); drawer = false">
            <v-icon size="17" color="error">mdi-logout</v-icon> Déconnexion
          </button>
        </template>

        <template v-else>
          <div class="drawer-divider" />
          <router-link to="/connexion" class="drawer-link" @click="drawer = false">
            <v-icon size="17" color="primary">mdi-login</v-icon> Connexion
          </router-link>
          <router-link to="/inscription" class="drawer-register" @click="drawer = false">
            S'inscrire gratuitement
          </router-link>
        </template>
      </div>
    </v-navigation-drawer>

    <v-main class="app-main">
      <router-view />
    </v-main>

    <!-- ── Footer ── -->
    <footer class="app-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo-icon">
            <v-icon size="13" color="white">mdi-tennis-ball</v-icon>
          </div>
          <span class="footer-logo-text">Fervio</span>
        </div>
        <span class="footer-copy">
          © {{ new Date().getFullYear() }} Fervio — Trouvez votre partenaire de tennis en France
        </span>
        <div class="footer-links">
          <router-link to="/joueurs" class="footer-link">Joueurs</router-link>
          <router-link to="/annonces" class="footer-link">Annonces</router-link>
          <router-link to="/pourquoi-fervio" class="footer-link">Pourquoi Fervio</router-link>
        </div>
      </div>
    </footer>

    <!-- New message notification -->
    <v-snackbar
      v-model="newMsgSnackbar"
      location="top right"
      color="surface"
      :timeout="5000"
      rounded="lg"
      elevation="4"
    >
      <div class="snackbar-msg">
        <v-icon size="17" color="primary">mdi-message-outline</v-icon>
        <span>Vous avez reçu un nouveau message</span>
      </div>
      <template #actions>
        <router-link to="/messages" class="snackbar-link" @click="newMsgSnackbar = false">Voir</router-link>
        <v-btn icon size="x-small" variant="text" @click="newMsgSnackbar = false">
          <v-icon size="14">mdi-close</v-icon>
        </v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<style>
/* Global nav state */
.nav-active { color: var(--c-primary) !important; background: var(--c-primary-bg); }

/* ── Header ── */
.app-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--c-border);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 4px 20px rgba(0, 0, 0, 0.05);
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 24px;
}
.app-header-inner {
  max-width: 1120px;
  margin: 0 auto;
  width: 100%;
  display: flex;
  align-items: center;
  gap: 32px;
}

/* ── Logo ── */
.nav-logo {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 9px;
  flex-shrink: 0;
}
.nav-logo-img {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  object-fit: contain;
}
.nav-logo-text {
  font-weight: 700;
  font-size: 15px;
  letter-spacing: -0.03em;
  color: var(--c-text);
}
.nav-logo-accent { color: var(--c-primary); }

/* ── Desktop nav ── */
.nav-links { gap: 4px; flex: 1; }
.nav-link {
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  color: var(--c-text-md);
  padding: 6px 12px;
  border-radius: 7px;
  transition: all 0.1s;
}
.nav-link:hover { background: var(--c-bg); color: var(--c-text-dk); }

/* ── Desktop auth ── */
.nav-auth { gap: 8px; flex-shrink: 0; }

.btn-propose {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--c-primary);
  padding: 6px 12px;
  border: 1px solid #F5D4C2;
  border-radius: 7px;
  background: var(--c-primary-bg);
  transition: background 0.1s;
}
.btn-propose:hover { background: var(--c-primary-bg); }

.btn-bell {
  text-decoration: none;
  padding: 6px;
  border-radius: 7px;
  color: var(--c-text-md);
  position: relative;
  display: inline-flex;
  transition: background 0.1s;
}
.btn-bell:hover { background: var(--c-bg); }

.unread-dot {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--c-error);
  border: 1.5px solid #fff;
}

/* ── Notification dropdown ── */
.notif-dropdown {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  min-width: 220px;
  padding: 6px;
  margin-top: 4px;
}
.notif-header {
  font-size: 11px;
  font-weight: 700;
  color: var(--c-text-sm);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 4px 8px 6px;
}
.notif-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 8px;
  color: var(--c-text-sm);
  font-size: 13px;
}
.notif-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 7px;
  text-decoration: none;
  color: var(--c-text-dk);
  font-size: 13px;
  font-weight: 500;
  transition: background 0.1s;
}
.notif-item:hover { background: var(--c-bg); }
.notif-footer {
  border-top: 1px solid var(--c-hover);
  padding: 6px 8px 2px;
  margin-top: 4px;
}
.notif-all-link {
  font-size: 12px;
  color: var(--c-primary);
  text-decoration: none;
  font-weight: 500;
}
.notif-all-link:hover { text-decoration: underline; }

/* ── User menu ── */
.user-menu-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--c-border);
  background: #fff;
  border-radius: 8px;
  padding: 5px 10px 5px 6px;
  cursor: pointer;
  transition: border-color 0.1s;
}
.user-menu-btn:hover { border-color: var(--c-border-lt); }

.user-avatar-bg { background: var(--c-primary-bg); }
.user-initials { font-size: 10px; font-weight: 700; color: var(--c-primary); }
.user-name { font-size: 13px; font-weight: 500; color: var(--c-text-dk); }

.nav-dropdown {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  min-width: 180px;
  padding: 6px;
  margin-top: 4px;
}
.nav-dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 7px;
  text-decoration: none;
  color: var(--c-text-dk);
  font-size: 13px;
  font-weight: 500;
  transition: background 0.1s;
}
.nav-dropdown-item:hover { background: var(--c-bg); }
.nav-dropdown-divider { height: 1px; background: var(--c-hover); margin: 4px 0; }
.nav-dropdown-logout {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 7px;
  color: var(--c-error);
  font-size: 13px;
  font-weight: 500;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  transition: background 0.1s;
}
.nav-dropdown-logout:hover { background: #FEF2F2; }

/* ── Auth buttons ── */
.btn-login {
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  color: var(--c-text-muted);
  padding: 7px 14px;
  border-radius: 7px;
  border: 1px solid var(--c-border);
  background: #fff;
  transition: border-color 0.1s;
}
.btn-login:hover { border-color: var(--c-border-lt); }

.btn-register {
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  padding: 7px 16px;
  border-radius: 7px;
  background: var(--c-primary);
  transition: background 0.1s;
}
.btn-register:hover { background: var(--c-primary-dk); }

/* ── Mobile actions (bell + burger) ── */
.mobile-actions {
  margin-left: auto;
  gap: 6px;
}
.mobile-burger {
  background: transparent;
  border: 1px solid var(--c-border);
  border-radius: 7px;
  padding: 7px;
  cursor: pointer;
  transition: background 0.1s;
}
.mobile-burger:hover { background: var(--c-bg); }

/* ── Mobile drawer ── */
.drawer-header {
  padding: 20px 16px 12px;
  border-bottom: 1px solid var(--c-hover);
  display: flex;
  align-items: center;
  gap: 9px;
}
.drawer-logo-img {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  object-fit: contain;
}
.drawer-logo-text { font-weight: 700; font-size: 14px; letter-spacing: -0.02em; }
.drawer-nav { padding: 10px 8px; }
.drawer-link {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 10px;
  border-radius: 8px;
  text-decoration: none;
  color: var(--c-text-dk);
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 2px;
  transition: background 0.1s;
}
.drawer-link:hover { background: var(--c-bg); }
.drawer-divider { height: 1px; background: var(--c-hover); margin: 8px 0; }
.drawer-logout {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 10px;
  border-radius: 8px;
  color: var(--c-error);
  font-size: 14px;
  font-weight: 500;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 100%;
  transition: background 0.1s;
}
.drawer-logout:hover { background: #FEF2F2; }
.drawer-register {
  display: block;
  text-align: center;
  padding: 9px 10px;
  border-radius: 8px;
  text-decoration: none;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  background: var(--c-primary);
  margin-top: 4px;
  transition: background 0.1s;
}
.drawer-register:hover { background: var(--c-primary-dk); }

/* ── New message snackbar ── */
.snackbar-msg { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: var(--c-text-dk); }
.snackbar-link { font-size: 13px; font-weight: 700; color: var(--c-primary); text-decoration: none; padding: 4px 8px; }
.snackbar-link:hover { text-decoration: underline; }

/* ── Main ── */
.app-main { min-height: calc(100vh - 60px); }

/* ── Footer ── */
.app-footer {
  border-top: none;
  background: #1C0A03;
  padding: 36px 24px;
}
.footer-inner {
  max-width: 1120px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}
.footer-brand { display: flex; align-items: center; gap: 9px; }
.footer-logo-icon {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  background: var(--c-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}
.footer-logo-text { font-weight: 700; font-size: 14px; color: #fff; letter-spacing: -0.02em; }
.footer-copy { font-size: 13px; color: rgba(255,255,255,.38); }
.footer-links { display: flex; gap: 20px; }
.footer-link { font-size: 13px; color: rgba(255,255,255,.5); text-decoration: none; transition: color .12s; }
.footer-link:hover { color: #E8C4A8; }
</style>
