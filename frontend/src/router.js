import { useAuthStore } from '@/stores/auth'

export const routes = [
  {
    path: '/',
    component: () => import('@/views/HomeView.vue'),
    meta: {
      title: 'Accueil',
      description: 'Trouvez votre partenaire de tennis sur Fervio. Rejoignez la communauté de joueurs en France, publiez des annonces de parties et trouvez des partenaires de votre niveau. Gratuit.',
    },
  },
  {
    path: '/joueurs',
    component: () => import('@/views/PlayersView.vue'),
    meta: {
      title: 'Joueurs',
      description: 'Annuaire des joueurs de tennis disponibles près de chez vous. Filtrez par ville, classement FFT et genre pour trouver le partenaire raquette idéal.',
    },
  },
  {
    path: '/joueurs/:id',
    component: () => import('@/views/PlayerProfileView.vue'),
    meta: {
      title: 'Profil joueur',
      description: 'Profil joueur de tennis sur Fervio. Contactez ce joueur pour organiser une partie de tennis près de chez vous.',
    },
  },
  {
    path: '/annonces',
    component: () => import('@/views/ProposalsView.vue'),
    meta: {
      title: 'Annonces de parties',
      description: 'Annonces de parties de tennis disponibles partout en France. Rejoignez une partie ou publiez votre annonce pour trouver un partenaire de tennis.',
    },
  },
  {
    path: '/annonces/nouvelle',
    component: () => import('@/views/CreateProposalView.vue'),
    meta: {
      title: 'Nouvelle annonce',
      requiresAuth: true,
    },
  },
  {
    path: '/annonces/:id',
    component: () => import('@/views/ProposalDetailView.vue'),
    meta: {
      title: 'Détail de la partie',
      description: 'Rejoignez cette partie de tennis ou contactez l\'organisateur. Fervio — trouvez votre partenaire de tennis.',
    },
  },
  {
    path: '/messages',
    component: () => import('@/views/MessagesView.vue'),
    meta: { title: 'Messages', requiresAuth: true },
  },
  {
    path: '/messages/:id',
    component: () => import('@/views/ConversationView.vue'),
    meta: { title: 'Conversation', requiresAuth: true },
  },
  {
    path: '/profil',
    component: () => import('@/views/MyProfileView.vue'),
    meta: { title: 'Mon profil', requiresAuth: true },
  },
  {
    path: '/connexion',
    component: () => import('@/views/LoginView.vue'),
    meta: {
      title: 'Connexion',
      description: 'Connectez-vous à Fervio pour accéder à votre espace partenaire de tennis et retrouver vos annonces et messages.',
    },
  },
  {
    path: '/inscription',
    component: () => import('@/views/RegisterView.vue'),
    meta: {
      title: 'Inscription gratuite',
      description: 'Créez votre profil gratuitement sur Fervio et trouvez votre partenaire de tennis en quelques minutes. Aucun abonnement requis.',
    },
  },
  {
    path: '/mot-de-passe-oublie',
    component: () => import('@/views/ForgotPasswordView.vue'),
    meta: { title: 'Mot de passe oublié' },
  },
  {
    path: '/reinitialiser-mot-de-passe',
    component: () => import('@/views/ResetPasswordView.vue'),
    meta: { title: 'Réinitialiser le mot de passe' },
  },
  {
    path: '/:pathMatch(.*)*',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page introuvable' },
  },
]

export function setupRouterGuards(router) {
  router.beforeEach((to) => {
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
      return { path: '/connexion', query: { redirect: to.fullPath } }
    }

    document.title = to.meta.title
      ? `${to.meta.title} — Fervio`
      : 'Fervio — Trouvez votre partenaire de tennis en France'

    const metaDesc = document.querySelector('meta[name="description"]')
    if (metaDesc && to.meta.description) {
      metaDesc.setAttribute('content', to.meta.description)
    }
  })
}
