import { test, expect } from '@playwright/test'
import { mockPublicApis, MOCK_PROPOSALS, MOCK_PLAYERS } from './helpers.js'

// ── Page d'accueil ────────────────────────────────────────────────────────────

test.describe("Page d'accueil (/)", () => {
  test('affiche le titre principal et la barre de recherche', async ({ page }) => {
    await mockPublicApis(page)
    await page.goto('/')
    await expect(page.locator('h1')).toContainText('Trouvez votre')
    await expect(page.locator('.hero-search-btn')).toBeVisible()
    await expect(page.locator('text=Comment ça marche')).toBeVisible()
  })

  test('affiche les annonces et joueurs récents chargés depuis l\'API', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS, players: MOCK_PLAYERS })
    await page.goto('/')
    await expect(page.locator('text=Partie de simple samedi matin')).toBeVisible()
    await expect(page.locator('text=Double mixte dimanche après-midi')).toBeVisible()
    await expect(page.locator('text=Alice Dupont')).toBeVisible()
    await expect(page.locator('text=Bob Martin')).toBeVisible()
  })

  test('affiche un message vide si aucune annonce disponible', async ({ page }) => {
    await mockPublicApis(page, { proposals: [], players: [] })
    await page.goto('/')
    await expect(page.locator('text=Aucune annonce disponible')).toBeVisible()
  })

  test('la recherche par ville redirige vers /joueurs?city=...', async ({ page }) => {
    await mockPublicApis(page, { proposals: [], players: [] })
    await page.goto('/')
    await page.fill('.hero-search-input', 'Bordeaux')
    await page.click('.hero-search-btn')
    await expect(page).toHaveURL(/\/joueurs\?city=Bordeaux/)
  })

  test('le lien "Voir les joueurs" navigue vers /joueurs', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS, players: MOCK_PLAYERS })
    await page.goto('/')
    await page.click('text=→ Voir les joueurs')
    await expect(page).toHaveURL('/joueurs')
  })

  test('le lien "Voir les annonces" navigue vers /annonces', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS, players: MOCK_PLAYERS })
    await page.goto('/')
    await page.click('text=→ Voir les annonces')
    await expect(page).toHaveURL('/annonces')
  })

  test('le lien "Rejoindre gratuitement" navigue vers /inscription', async ({ page }) => {
    await mockPublicApis(page, { proposals: [], players: [] })
    await page.goto('/')
    await page.click('text=Rejoindre gratuitement')
    await expect(page).toHaveURL('/inscription')
  })
})

// ── Page des annonces ─────────────────────────────────────────────────────────

test.describe('Page des annonces (/annonces)', () => {
  test('affiche la liste des annonces avec titre et badges de statut', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS })
    await page.goto('/annonces')
    await expect(page.locator('h1')).toContainText('Annonces')
    await expect(page.locator('text=Partie de simple samedi matin')).toBeVisible()
    await expect(page.locator('text=Double mixte dimanche après-midi')).toBeVisible()
    await expect(page.locator('.badge-green').first()).toContainText('Disponible')
    await expect(page.locator('.badge-amber').first()).toContainText('Complet')
  })

  test('affiche le nombre d\'annonces trouvées', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS })
    await page.goto('/annonces')
    await expect(page.locator('.list-count')).toContainText('2 annonces')
  })

  test('affiche l\'état vide quand aucune annonce ne correspond', async ({ page }) => {
    await mockPublicApis(page, { proposals: [] })
    await page.goto('/annonces')
    await expect(page.locator('text=Aucune annonce trouvée')).toBeVisible()
  })

  test('ne montre pas le bouton "Nouvelle annonce" pour un visiteur non connecté', async ({ page }) => {
    await mockPublicApis(page, { proposals: [] })
    await page.goto('/annonces')
    await expect(page.locator('text=Nouvelle annonce')).not.toBeVisible()
  })

  test('un clic sur une annonce navigue vers sa page de détail', async ({ page }) => {
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS })
    await page.goto('/annonces')
    await page.click('text=Partie de simple samedi matin')
    await expect(page).toHaveURL('/annonces/1')
  })
})

// ── Page des joueurs ──────────────────────────────────────────────────────────

test.describe('Page des joueurs (/joueurs)', () => {
  test('affiche le titre et la liste des joueurs', async ({ page }) => {
    await mockPublicApis(page, { players: MOCK_PLAYERS })
    await page.goto('/joueurs')
    await expect(page.locator('h1')).toContainText('Joueurs')
    await expect(page.locator('text=Alice Dupont')).toBeVisible()
    await expect(page.locator('text=Bob Martin')).toBeVisible()
  })
})

// ── Redirections des routes protégées ────────────────────────────────────────

test.describe('Garde de routes (non connecté)', () => {
  test('/profil redirige vers /connexion avec paramètre redirect', async ({ page }) => {
    await page.goto('/profil')
    // Vue Router ne encode pas le "/" dans le paramètre redirect
    await expect(page).toHaveURL(/\/connexion.*redirect.*profil/)
    await expect(page.locator('h1')).toContainText('Connexion')
  })

  test('/messages redirige vers /connexion avec paramètre redirect', async ({ page }) => {
    await page.goto('/messages')
    await expect(page).toHaveURL(/\/connexion.*redirect.*messages/)
    await expect(page.locator('h1')).toContainText('Connexion')
  })

  test('/annonces/nouvelle redirige vers /connexion avec paramètre redirect', async ({ page }) => {
    await page.goto('/annonces/nouvelle')
    await expect(page).toHaveURL(/\/connexion.*redirect.*annonces/)
    await expect(page.locator('h1')).toContainText('Connexion')
  })

  test('/messages/:id redirige vers /connexion', async ({ page }) => {
    await page.goto('/messages/42')
    await expect(page).toHaveURL(/\/connexion.*redirect.*messages/)
  })
})

// ── Pages statiques ───────────────────────────────────────────────────────────

test.describe('Pages statiques (connexion / inscription / mot de passe)', () => {
  test('la page de connexion affiche les champs email et mot de passe', async ({ page }) => {
    await page.goto('/connexion')
    await expect(page.locator('h1')).toContainText('Connexion')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toContainText('Se connecter')
  })

  test('la page d\'inscription affiche le formulaire complet', async ({ page }) => {
    await page.goto('/inscription')
    await expect(page.locator('h1')).toContainText('Créer un compte')
    await expect(page.locator('input[placeholder="Alice"]')).toBeVisible()
    await expect(page.locator('input[placeholder="Martin"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toContainText('Créer mon compte')
  })

  test('la page "Mot de passe oublié" affiche le champ email', async ({ page }) => {
    await page.goto('/mot-de-passe-oublie')
    await expect(page.locator('h1')).toContainText('Mot de passe oublié')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toContainText('Envoyer le lien')
  })

  test('une URL inconnue met à jour le titre de la page avec 404', async ({ page }) => {
    await page.goto('/cette-page-nexiste-pas')
    await expect(page).toHaveTitle(/404/)
  })
})

// ── Navigation globale ─────────────────────────────────────────────────────────

test.describe('Navigation globale (non connecté)', () => {
  test('affiche les boutons Connexion et S\'inscrire dans la navbar', async ({ page }) => {
    await mockPublicApis(page)
    await page.goto('/')
    // Utiliser les classes CSS uniques (le drawer mobile a aussi des liens /connexion)
    await expect(page.locator('.btn-login')).toBeVisible()
    await expect(page.locator('.btn-register')).toBeVisible()
    await expect(page.locator('.user-menu-btn')).not.toBeVisible()
  })

  test('le logo navigue vers la page d\'accueil', async ({ page }) => {
    await mockPublicApis(page)
    await page.goto('/connexion')
    await page.click('.nav-logo')
    await expect(page).toHaveURL('/')
  })

  test('le lien "Joueurs" dans la navbar navigue vers /joueurs', async ({ page }) => {
    await mockPublicApis(page, { players: MOCK_PLAYERS })
    await page.goto('/')
    await page.click('.nav-links a:has-text("Joueurs")')
    await expect(page).toHaveURL('/joueurs')
  })
})
