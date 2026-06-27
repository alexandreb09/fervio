import { test, expect } from '@playwright/test'
import { loginAs, gotoProtected, mockPublicApis, MOCK_USER, MOCK_PROPOSALS } from './helpers.js'

// Tous les tests ici nécessitent une session authentifiée.
// loginAs() doit être appelé avant page.goto() car il enregistre un init script.
// gotoProtected() charge d'abord /annonces (non gardée), attend que fetchMe() soit
// complète, puis navigue via Vue Router côté client vers la page protégée.

// ── Page de profil (/profil) ──────────────────────────────────────────────────

test.describe('Page de profil (/profil)', () => {
  test('affiche le nom et les informations de l\'utilisateur connecté', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    await expect(page.locator('.profile-name')).toContainText('Alice Dupont')
    await expect(page.locator('text=alice@example.com')).toBeVisible()
    await expect(page.locator('text=15/2')).toBeVisible()
  })

  test('le bouton "Modifier le profil" bascule en mode édition', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    await page.click('button:has-text("Modifier le profil")')

    await expect(page.locator('button[type="submit"]:has-text("Enregistrer")')).toBeVisible()
    await expect(page.locator('button:has-text("Annuler")')).toBeVisible()
  })

  test('annuler l\'édition revient au mode lecture', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    await page.click('button:has-text("Modifier le profil")')
    await page.click('button:has-text("Annuler")')

    await expect(page.locator('.profile-name')).toContainText('Alice Dupont')
    await expect(page.locator('button:has-text("Modifier le profil")')).toBeVisible()
  })

  test('la section "Mes annonces" affiche l\'état vide quand l\'utilisateur n\'a pas d\'annonce', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    await expect(page.locator('text=Vous n\'avez pas encore créé d\'annonce.')).toBeVisible()
  })

  test('la section "Mes annonces" liste les annonces existantes', async ({ page }) => {
    await loginAs(page)
    // Override le catch-all pour les proposals : retourner des annonces
    await page.route(/\/api\/proposals/, (route) => {
      const url = route.request().url()
      if (route.request().method() !== 'GET') return route.fallback()
      if (url.includes('/received-private')) return route.fulfill({ json: [] })
      return route.fulfill({ json: MOCK_PROPOSALS })
    })

    await gotoProtected(page, '/profil')

    await expect(page.locator('text=Partie de simple samedi matin')).toBeVisible()
  })

  test('le bouton "Supprimer" ouvre la boîte de dialogue de confirmation', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    // "Supprimer" apparaît dans la zone de danger
    await page.locator('.btn-danger').first().click()

    await expect(page.locator('.dialog-box')).toBeVisible()
    await expect(page.locator('.dialog-title')).toContainText('Supprimer mon compte')
  })

  test('la boîte de confirmation se ferme avec Annuler', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/profil')

    await page.locator('.btn-danger').first().click()
    await page.locator('.dialog-actions button:has-text("Annuler")').click()

    await expect(page.locator('.dialog-box')).not.toBeVisible()
  })
})

// ── Bouton "Nouvelle annonce" (authentifié) ───────────────────────────────────

test.describe('Page des annonces (connecté)', () => {
  test('affiche le bouton "Nouvelle annonce" pour un utilisateur connecté', async ({ page }) => {
    await loginAs(page)
    await mockPublicApis(page, { proposals: MOCK_PROPOSALS })

    await page.goto('/annonces')
    await page.locator('.user-menu-btn').waitFor()

    await expect(page.locator('text=Nouvelle annonce').first()).toBeVisible()
  })

  test('le bouton "Nouvelle annonce" navigue vers /annonces/nouvelle', async ({ page }) => {
    await loginAs(page)
    await mockPublicApis(page, { proposals: [] })

    await page.goto('/annonces')
    await page.locator('.user-menu-btn').waitFor()
    await page.locator('.page-top a:has-text("Nouvelle annonce")').click()

    await expect(page).toHaveURL('/annonces/nouvelle')
  })
})

// ── Création d'une annonce (/annonces/nouvelle) ───────────────────────────────

test.describe('Création d\'annonce (/annonces/nouvelle)', () => {
  test('affiche le formulaire de création avec les 3 sections', async ({ page }) => {
    await loginAs(page)
    await gotoProtected(page, '/annonces/nouvelle')

    await expect(page.locator('h1')).toContainText('Proposer une partie')
    await expect(page.locator('text=Informations générales')).toBeVisible()
    await expect(page.locator('text=Type de partie')).toBeVisible()
    await expect(page.locator('text=Classement FFT requis')).toBeVisible()
  })

  test('la soumission réussie redirige vers la page de détail de l\'annonce', async ({ page }) => {
    await loginAs(page)
    // Mock de la création de l'annonce
    await page.route(/\/api\/proposals/, (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({ json: { id: 99, title: 'Partie test' } })
      }
      return route.fulfill({ json: [] })
    })

    await gotoProtected(page, '/annonces/nouvelle')

    // Remplir les champs obligatoires via les inputs Vuetify
    const textFields = page.locator('.v-text-field input')
    await textFields.nth(0).fill('Partie test dimanche')
    await textFields.nth(1).fill('Paris')

    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    tomorrow.setHours(10, 0, 0, 0)
    await textFields.nth(2).fill(tomorrow.toISOString().slice(0, 16))

    await page.click('button:has-text("Publier l\'annonce")')

    await expect(page).toHaveURL('/annonces/99')
  })

  test('une erreur de validation 422 affiche les messages sous les champs', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals/, (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 422,
          json: { errors: { title: 'Le titre est obligatoire.', city: 'La ville est obligatoire.' } },
        })
      }
      return route.fulfill({ json: [] })
    })

    await gotoProtected(page, '/annonces/nouvelle')
    await page.click('button:has-text("Publier l\'annonce")')

    await expect(page.locator('.v-messages__message').first()).toBeVisible()
    await expect(page).toHaveURL('/annonces/nouvelle')
  })
})

// ── Navbar (connecté) ─────────────────────────────────────────────────────────

test.describe('Navbar (connecté)', () => {
  test('affiche le prénom de l\'utilisateur et le menu dans la navbar', async ({ page }) => {
    await loginAs(page)
    await mockPublicApis(page, { proposals: [], players: [] })

    await page.goto('/')
    await page.locator('.user-menu-btn').waitFor()

    await expect(page.locator('.user-name')).toContainText('Alice')
    await expect(page.locator('.btn-propose')).toBeVisible()
  })

  test('le menu utilisateur propose Profil, Messages et Déconnexion', async ({ page }) => {
    await loginAs(page)
    await mockPublicApis(page, { proposals: [], players: [] })

    await page.goto('/')
    await page.locator('.user-menu-btn').waitFor()
    await page.click('.user-menu-btn')

    await expect(page.locator('.nav-dropdown-item:has-text("Mon profil")')).toBeVisible()
    await expect(page.locator('.nav-dropdown-item:has-text("Messages")')).toBeVisible()
    await expect(page.locator('.nav-dropdown-logout')).toBeVisible()
  })
})

// ── Déconnexion ───────────────────────────────────────────────────────────────

test.describe('Déconnexion', () => {
  test('la déconnexion supprime la session et affiche les boutons de connexion', async ({ page }) => {
    await loginAs(page)
    await mockPublicApis(page, { proposals: [], players: [] })

    await page.goto('/')
    await page.locator('.user-menu-btn').waitFor()

    await page.click('.user-menu-btn')
    await page.click('.nav-dropdown-logout')

    await expect(page).toHaveURL('/')
    await expect(page.locator('.btn-login')).toBeVisible()
    await expect(page.locator('.user-menu-btn')).not.toBeVisible()
  })
})
