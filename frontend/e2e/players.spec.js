import { test, expect } from '@playwright/test'
import { loginAs, MOCK_PLAYERS, MOCK_BOB } from './helpers.js'

// Route mock réutilisable pour la liste des joueurs avec filtre par paramètre
async function mockUsersRoute(page, filterFn) {
  await page.route(/\/api\/users(?!\/)/, (route) => {
    if (route.request().method() !== 'GET') return route.fallback()
    return route.fulfill({ json: filterFn(route.request().url()) })
  })
}

// ── Recherche et filtres (/joueurs) ───────────────────────────────────────────

test.describe('Recherche de joueurs (/joueurs)', () => {
  test('affiche tous les joueurs au chargement initial', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => MOCK_PLAYERS)

    await page.goto('/joueurs')
    await expect(page.locator('.list-count')).toContainText('2 joueurs')
    await expect(page.locator('.player-card-name').first()).toBeVisible()
  })

  test('filtrer par ville restreint les résultats', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, (url) =>
      url.includes('city=Paris') ? [MOCK_PLAYERS[0]] : MOCK_PLAYERS
    )
    await page.route('**/api/users/cities', (route) =>
      route.fulfill({ json: ['Paris', 'Lyon', 'Bordeaux'] })
    )

    await page.goto('/joueurs')
    await page.locator('.list-count').waitFor()
    await page.fill('.field-input--sm', 'Paris')
    await page.click('button.btn-primary:has-text("Rechercher")')

    await expect(page.locator('.list-count')).toContainText('1 joueur')
    await expect(page.locator('.player-card-name')).toContainText('Alice Dupont')
    await expect(page.locator('.player-card-name')).not.toContainText('Bob Martin')
  })

  test('filtrer par genre affiche uniquement les joueurs correspondants', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, (url) =>
      url.includes('gender=M') ? [MOCK_PLAYERS[1]] : MOCK_PLAYERS
    )

    await page.goto('/joueurs')
    await page.locator('.list-count').waitFor()

    const genreFilter = page.locator('.filter-field').filter({ hasText: 'Genre' })
    await genreFilter.locator('.select-trigger').click()
    await page.locator('.select-option:has-text("Homme")').click()

    await expect(page.locator('.list-count')).toContainText('1 joueur')
    await expect(page.locator('.player-card-name')).toContainText('Bob Martin')
    await expect(page.locator('.player-card-name')).not.toContainText('Alice Dupont')
  })

  test('filtrer par classement FFT affiche les joueurs correspondants', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, (url) =>
      url.includes('fftRanking=') ? [MOCK_PLAYERS[0]] : MOCK_PLAYERS
    )

    await page.goto('/joueurs')
    await page.locator('.list-count').waitFor()

    const rankFilter = page.locator('.filter-field').filter({ hasText: 'Classement FFT' })
    await rankFilter.locator('.select-trigger').click()
    await page.locator('.select-option:has-text("15/2")').click()

    await expect(page.locator('.list-count')).toContainText('1 joueur')
    await expect(page.locator('.player-card-name')).toContainText('Alice Dupont')
  })

  test('réinitialiser les filtres recharge tous les joueurs', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, (url) =>
      url.includes('gender=M') ? [MOCK_PLAYERS[1]] : MOCK_PLAYERS
    )

    await page.goto('/joueurs')
    await page.locator('.list-count').waitFor()

    const genreFilter = page.locator('.filter-field').filter({ hasText: 'Genre' })
    await genreFilter.locator('.select-trigger').click()
    await page.locator('.select-option:has-text("Homme")').click()
    await expect(page.locator('.list-count')).toContainText('1 joueur')

    await page.click('.btn-ghost:has-text("Réinitialiser")')
    await expect(page.locator('.list-count')).toContainText('2 joueurs')
  })

  test("l'état vide s'affiche quand aucun joueur ne correspond", async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => [])

    await page.goto('/joueurs')
    await expect(page.locator('.empty-state-text')).toContainText('Aucun joueur trouvé')
  })

  test("un clic sur une carte joueur navigue vers son profil", async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => MOCK_PLAYERS)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))

    await page.goto('/joueurs')
    await page.locator('.player-card').filter({ hasText: 'Bob Martin' }).click()
    await expect(page).toHaveURL('/joueurs/player-def')
  })
})

// ── Favoris / PartnerBtn ──────────────────────────────────────────────────────

test.describe('Favoris (PartnerBtn sur la liste des joueurs)', () => {
  test('le bouton partenaire est inactif par défaut (aucun partenaire)', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => MOCK_PLAYERS)

    await page.goto('/joueurs')
    // Bob's card (id=2 ≠ Alice's id=1) has a PartnerBtn
    const bobCard = page.locator('.player-card').filter({ hasText: 'Bob Martin' })
    await bobCard.locator('.partner-btn').waitFor()
    await expect(bobCard.locator('.partner-btn')).not.toHaveClass(/partner-btn--active/)
  })

  test("cliquer le bouton ajoute le joueur aux partenaires", async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => MOCK_PLAYERS)
    await page.route('**/api/partners/2', (route) => {
      if (route.request().method() === 'POST') return route.fulfill({ json: {} })
      return route.fallback()
    })

    await page.goto('/joueurs')
    const bobCard = page.locator('.player-card').filter({ hasText: 'Bob Martin' })
    await bobCard.locator('.partner-btn').click()
    await expect(bobCard.locator('.partner-btn--active')).toBeVisible()
  })

  test('si déjà partenaire, cliquer retire le joueur des favoris', async ({ page }) => {
    await loginAs(page)
    await mockUsersRoute(page, () => MOCK_PLAYERS)
    // Remplacer le mock GET /api/partners pour retourner Bob déjà en favori
    await page.route(/\/api\/partners(?!\/)/, (route) => {
      if (route.request().method() !== 'GET') return route.fallback()
      return route.fulfill({ json: [MOCK_BOB] })
    })
    await page.route('**/api/partners/2', (route) => {
      if (route.request().method() === 'DELETE') return route.fulfill({ status: 204, body: '' })
      return route.fallback()
    })

    await page.goto('/joueurs')
    const bobCard = page.locator('.player-card').filter({ hasText: 'Bob Martin' })
    await bobCard.locator('.partner-btn--active').waitFor()
    await bobCard.locator('.partner-btn--active').click()
    await expect(bobCard.locator('.partner-btn--active')).not.toBeVisible()
  })
})

// ── Profil joueur (/joueurs/player-def) ──────────────────────────────────────

test.describe('Profil joueur (/joueurs/player-def)', () => {
  test('affiche le nom, la ville et les badges du joueur', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))

    await page.goto('/joueurs/player-def')
    await expect(page.locator('.profile-name')).toContainText('Bob Martin')
    await expect(page.locator('.badge-blue')).toContainText('Homme')
    await expect(page.locator('.badge-purple')).toContainText('30')
    await expect(page.locator('.badge-gray:has-text("Lyon")')).toBeVisible()
  })

  test('le bouton "Proposer une partie privée" ouvre le dialog', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))

    await page.goto('/joueurs/player-def')
    await page.locator('.private-btn').waitFor()
    await page.click('.private-btn')

    await expect(page.locator('.dialog-box')).toBeVisible()
    await expect(page.locator('.dialog-player-name')).toContainText('Bob Martin')
    await expect(page.locator('.dialog-subtitle')).toContainText('Partie privée')
  })

  test("la soumission d'une invitation privée réussie affiche le succès", async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route(/\/api\/proposals$/, (route) => {
      if (route.request().method() !== 'POST') return route.fallback()
      return route.fulfill({ status: 201, json: { id: 10 } })
    })

    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)

    await page.goto('/joueurs/player-def')
    await page.click('.private-btn')
    await page.fill('input[placeholder="Partie simple amicale…"]', 'Partie amicale dimanche')
    await page.fill('input[type="datetime-local"]', tomorrow.toISOString().slice(0, 16))
    await page.click('.btn-send')

    await expect(page.locator('.dialog-success-text')).toContainText('Invitation envoyée !')
  })

  test('la validation affiche une erreur si les champs requis sont absents', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))

    await page.goto('/joueurs/player-def')
    await page.click('.private-btn')
    // Soumettre sans titre ni date (city est pré-remplie avec Lyon)
    await page.click('.btn-send')
    await expect(page.locator('.error-banner')).toContainText('Le titre, la ville et la date sont requis.')
  })

  test('le bouton "Envoyer un message" ouvre le dialog de message', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))

    await page.goto('/joueurs/player-def')
    await page.locator('.contact-btn').waitFor()
    await page.click('.contact-btn')

    await expect(page.locator('.dialog-box')).toBeVisible()
    await expect(page.locator('.dialog-subtitle')).toContainText('Nouveau message')
  })

  test("envoyer un message depuis le profil affiche le succès", async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages', (route) => {
      if (route.request().method() !== 'POST') return route.fallback()
      return route.fulfill({ json: { id: 1 } })
    })

    await page.goto('/joueurs/player-def')
    await page.click('.contact-btn')
    await page.fill('.dialog-textarea', 'Bonjour Bob !')
    await page.click('.btn-send')
    await expect(page.locator('.dialog-success-text')).toContainText('Message envoyé !')
  })

  test('le bouton "Ajouter aux partenaires" (labelé) ajoute Bob aux favoris', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/player-def', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/partners/2', (route) => {
      if (route.request().method() === 'POST') return route.fulfill({ json: {} })
      return route.fallback()
    })

    await page.goto('/joueurs/player-def')
    await page.locator('.partner-btn--labeled').waitFor()
    await expect(page.locator('.partner-btn--labeled')).toContainText('Ajouter aux partenaires')
    await page.click('.partner-btn--labeled')
    await expect(page.locator('.partner-btn--labeled')).toContainText('Partenaire enregistré')
  })
})
