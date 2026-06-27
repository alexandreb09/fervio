import { test, expect } from '@playwright/test'
import { loginAs, gotoProtected, MOCK_BOB, MOCK_CONVERSATIONS, MOCK_MESSAGES } from './helpers.js'

// ── Boîte de réception (/messages) ───────────────────────────────────────────

test.describe('Boîte de réception (/messages)', () => {
  test("affiche l'état vide quand aucune conversation", async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/messages/conversations', (route) =>
      route.fulfill({ json: [] })
    )

    await gotoProtected(page, '/messages')
    await expect(page.locator('.empty-state-title')).toContainText('Aucun message pour le moment')
    await expect(page.locator('.empty-state-cta')).toContainText('Voir les joueurs')
  })

  test('affiche les conversations avec nom et aperçu du dernier message', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/messages/conversations', (route) =>
      route.fulfill({ json: MOCK_CONVERSATIONS })
    )

    await gotoProtected(page, '/messages')
    await expect(page.locator('.conv-row')).toBeVisible()
    await expect(page.locator('.conv-name')).toContainText('Bob Martin')
    await expect(page.locator('.conv-preview')).toContainText('Bonjour, je suis intéressé')
  })

  test('affiche le badge de messages non lus avec le bon compteur', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/messages/conversations', (route) =>
      route.fulfill({ json: MOCK_CONVERSATIONS })
    )

    await gotoProtected(page, '/messages')
    await expect(page.locator('.conv-unread-dot')).toContainText('1')
    await expect(page.locator('.conv-name--unread')).toBeVisible()
  })

  test('cliquer sur une conversation navigue vers /messages/:id', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/messages/conversations', (route) =>
      route.fulfill({ json: MOCK_CONVERSATIONS })
    )
    // Pré-mocker la conversation cible pour éviter une erreur au chargement
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))

    await gotoProtected(page, '/messages')
    await page.click('.conv-row')
    await expect(page).toHaveURL('/messages/2')
  })
})

// ── Conversation (/messages/2) ────────────────────────────────────────────────

test.describe('Conversation (/messages/2)', () => {
  test('affiche les messages reçus et envoyés avec les bonnes classes', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) =>
      route.fulfill({ json: MOCK_MESSAGES })
    )

    await gotoProtected(page, '/messages/2')
    await expect(page.locator('.msg-bubble--other').first()).toContainText(
      'Bonjour, je suis intéressé par votre annonce !'
    )
    await expect(page.locator('.msg-bubble--mine').first()).toContainText(
      'Bonjour Bob, avec plaisir !'
    )
  })

  test("l'état vide s'affiche quand aucun message dans la conversation", async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))

    await gotoProtected(page, '/messages/2')
    await expect(page.locator('.conv-empty-text')).toContainText('Démarrez la conversation !')
  })

  test('taper un message et cliquer Envoyer vide le champ de saisie', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))
    await page.route('**/api/messages', (route) => {
      if (route.request().method() !== 'POST') return route.fallback()
      return route.fulfill({ json: { id: 99 } })
    })

    await gotoProtected(page, '/messages/2')
    await page.fill('.conv-input', 'Salut Bob !')
    await page.click('.conv-send-btn')
    await expect(page.locator('.conv-input')).toHaveValue('')
  })

  test('le bouton Envoyer est désactivé si le champ est vide', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))

    await gotoProtected(page, '/messages/2')
    await expect(page.locator('.conv-send-btn')).toBeDisabled()
    await page.fill('.conv-input', 'Bonjour')
    await expect(page.locator('.conv-send-btn')).toBeEnabled()
  })

  test('le lien "Voir le profil" navigue vers /joueurs/:publicId', async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))

    await gotoProtected(page, '/messages/2')
    await page.click('.conv-header-profile')
    await expect(page).toHaveURL('/joueurs/player-def')
  })

  test("affiche le nom du partenaire dans l'en-tête de conversation", async ({ page }) => {
    await loginAs(page)
    await page.route('**/api/users/2', (route) => route.fulfill({ json: MOCK_BOB }))
    await page.route('**/api/messages/with/2', (route) => route.fulfill({ json: [] }))

    await gotoProtected(page, '/messages/2')
    await expect(page.locator('.conv-header-name')).toContainText('Bob Martin')
  })
})
