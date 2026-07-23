import { useAuthStore } from '@/stores/auth'
import { findCityBySlug } from '@/data/cities'
import { FAQ_ITEMS } from '@/data/faq'

const SITE_URL = 'https://fervio.fr'
const DEFAULT_TITLE = 'Fervio — Trouvez votre partenaire de tennis en France'
const DEFAULT_DESCRIPTION = 'Fervio, la plateforme gratuite pour trouver un partenaire de tennis près de chez vous. Publiez une partie, rejoignez des joueurs de votre niveau et progressez ensemble.'

function breadcrumbJsonLd(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map(([name, path], i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name,
      item: `${SITE_URL}${path}`,
    })),
  }
}

export const routes = [
  {
    path: '/',
    component: () => import('@/views/HomeView.vue'),
    meta: {
      title: 'Accueil',
      description: DEFAULT_DESCRIPTION,
    },
  },
  {
    path: '/faq',
    component: () => import('@/views/FaqView.vue'),
    meta: {
      title: 'FAQ',
      description: 'Toutes les réponses à vos questions sur Fervio : gratuité, classement FFT, messagerie, modération et plus encore.',
      jsonLd: () => ({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: FAQ_ITEMS.map((f) => ({
          '@type': 'Question',
          name: f.question,
          acceptedAnswer: { '@type': 'Answer', text: f.answer },
        })),
      }),
    },
  },
  {
    path: '/joueurs',
    component: () => import('@/views/PlayersView.vue'),
    meta: {
      title: 'Joueurs',
      description: 'Annuaire des joueurs de tennis disponibles près de chez vous. Filtrez par ville, classement FFT et genre pour trouver le partenaire raquette idéal.',
      jsonLd: () => ({
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: 'Joueurs de tennis disponibles sur Fervio',
        description: 'Annuaire des joueurs de tennis disponibles en France, filtrable par ville, classement FFT et genre.',
        url: `${SITE_URL}/joueurs`,
      }),
    },
  },
  {
    path: '/joueurs/:id',
    component: () => import('@/views/PlayerProfileView.vue'),
    meta: {
      title: 'Profil joueur',
      description: 'Profil joueur de tennis sur Fervio. Contactez ce joueur pour organiser une partie de tennis près de chez vous.',
      jsonLd: (to) => breadcrumbJsonLd([
        ['Accueil', '/'],
        ['Joueurs', '/joueurs'],
        ['Profil joueur', to.path],
      ]),
    },
  },
  {
    path: '/parties',
    component: () => import('@/views/ProposalsView.vue'),
    meta: {
      title: 'Parties de tennis',
      description: 'Parties de tennis disponibles partout en France. Rejoignez une partie ou publiez la vôtre pour trouver un partenaire de tennis.',
      jsonLd: () => ({
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: 'Parties de tennis sur Fervio',
        description: 'Parties de tennis disponibles partout en France.',
        url: `${SITE_URL}/parties`,
      }),
    },
  },
  {
    path: '/parties/nouvelle',
    component: () => import('@/views/CreateProposalView.vue'),
    meta: {
      title: 'Nouvelle partie',
      requiresAuth: true,
    },
  },
  {
    path: '/parties/:id',
    component: () => import('@/views/ProposalDetailView.vue'),
    meta: {
      title: 'Détail de la partie',
      description: 'Rejoignez cette partie de tennis ou contactez l\'organisateur. Fervio — trouvez votre partenaire de tennis.',
      jsonLd: (to) => breadcrumbJsonLd([
        ['Accueil', '/'],
        ['Parties', '/parties'],
        ['Détail de la partie', to.path],
      ]),
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
      description: 'Connectez-vous à Fervio pour accéder à votre espace partenaire de tennis et retrouver vos parties et messages.',
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
    path: '/verifier-email',
    component: () => import('@/views/VerifyEmailView.vue'),
    meta: { title: 'Vérifiez vos emails' },
  },
  {
    path: '/confirmer-email',
    component: () => import('@/views/EmailConfirmedView.vue'),
    meta: { title: 'Email confirmé' },
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
    path: '/pourquoi-fervio',
    component: () => import('@/views/AboutView.vue'),
    meta: {
      title: 'Pourquoi Fervio',
      description: 'Découvrez pourquoi Fervio a été créé : une plateforme gratuite et moderne pour trouver un partenaire de tennis en France.',
      jsonLd: () => ({
        '@context': 'https://schema.org',
        '@type': 'AboutPage',
        name: 'Pourquoi Fervio',
        url: `${SITE_URL}/pourquoi-fervio`,
      }),
    },
  },
  {
    path: '/partenaire-tennis/:ville',
    name: 'city-landing',
    component: () => import('@/views/CityLandingView.vue'),
  },
  {
    path: '/:pathMatch(.*)*',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page introuvable' },
  },
]

function resolveMeta(to) {
  if (to.name === 'city-landing') {
    const city = findCityBySlug(to.params.ville)
    return {
      title: `Partenaire de tennis à ${city.name}`,
      description: `Trouvez un partenaire de tennis à ${city.name} : consultez les joueurs inscrits et les parties disponibles sur Fervio, gratuit et sans engagement.`,
      jsonLd: {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `Partenaire de tennis à ${city.name}`,
        description: `Joueurs et parties de tennis disponibles à ${city.name}.`,
        url: `${SITE_URL}/partenaire-tennis/${city.slug}`,
      },
    }
  }
  return {
    title: to.meta.title,
    description: to.meta.description,
    jsonLd: typeof to.meta.jsonLd === 'function' ? to.meta.jsonLd(to) : null,
  }
}

function setMetaContent(selector, value) {
  const el = document.querySelector(selector)
  if (el && value) el.setAttribute('content', value)
}

function updateJsonLd(data) {
  let el = document.getElementById('route-jsonld')
  if (!data) {
    if (el) el.remove()
    return
  }
  if (!el) {
    el = document.createElement('script')
    el.type = 'application/ld+json'
    el.id = 'route-jsonld'
    document.head.appendChild(el)
  }
  el.textContent = JSON.stringify(data)
}

export function setupRouterGuards(router) {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()
    await auth.ready
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
      return { path: '/connexion', query: { redirect: to.fullPath } }
    }

    if (to.name === 'city-landing' && !findCityBySlug(to.params.ville)) {
      return { path: '/ville-introuvable' }
    }

    const { title, description, jsonLd } = resolveMeta(to)
    const fullTitle = title ? `${title} — Fervio` : DEFAULT_TITLE
    const fullDescription = description || DEFAULT_DESCRIPTION
    const url = `${SITE_URL}${to.path}`

    document.title = fullTitle

    setMetaContent('meta[name="description"]', fullDescription)
    setMetaContent('meta[property="og:title"]', fullTitle)
    setMetaContent('meta[property="og:description"]', fullDescription)
    setMetaContent('meta[property="og:url"]', url)
    setMetaContent('meta[name="twitter:title"]', fullTitle)
    setMetaContent('meta[name="twitter:description"]', fullDescription)

    const canonical = document.querySelector('link[rel="canonical"]')
    if (canonical) canonical.setAttribute('href', url)

    updateJsonLd(jsonLd)
  })
}
