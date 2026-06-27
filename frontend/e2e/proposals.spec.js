import { test, expect } from '@playwright/test'
import {
  loginAs,
  MOCK_PROPOSAL_DETAIL,
  MOCK_PROPOSAL_BY_BOB,
  MOCK_PROPOSAL_BY_BOB_JOINED,
  MOCK_PRIVATE_PROPOSAL,
} from './helpers.js'

// ── Vue de l'auteur (/annonces/1) ─────────────────────────────────────────────

test.describe("Détail d'une annonce — vue de l'auteur", () => {
  test('affiche le titre, le statut et le type de partie', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/1$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_DETAIL })
    )

    await page.goto('/annonces/1')
    await expect(page.locator('.detail-title')).toContainText('Partie de simple samedi matin')
    await expect(page.locator('.badge-green')).toContainText('Disponible')
    await expect(page.locator('.badge-purple:has-text("Simple")')).toBeVisible()
  })

  test("n'affiche pas le bouton Rejoindre pour l'auteur de la partie", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/1$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_DETAIL })
    )

    await page.goto('/annonces/1')
    await expect(page.locator('.detail-title')).toBeVisible()
    await expect(page.locator('.btn-join')).not.toBeVisible()
    await expect(page.locator('.btn-contact')).not.toBeVisible()
  })

  test('affiche la description de la partie', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/1$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_DETAIL })
    )

    await page.goto('/annonces/1')
    await expect(page.locator('.detail-description-text')).toContainText(
      'Partie de simple en terre battue'
    )
  })

  test("affiche la surface et la ville dans la grille d'infos", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/1$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_DETAIL })
    )

    await page.goto('/annonces/1')
    await expect(page.locator('.detail-info-grid')).toContainText('Paris')
    await expect(page.locator('.detail-info-grid')).toContainText('Terre battue')
  })
})

// ── Rejoindre / Se désinscrire (/annonces/2) ─────────────────────────────────

test.describe("Rejoindre et se désinscrire d'une annonce publique", () => {
  test("affiche le bouton Rejoindre pour un utilisateur non-auteur", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )

    await page.goto('/annonces/2')
    await expect(page.locator('.btn-join--join')).toBeVisible()
    await expect(page.locator('.btn-join--join')).toContainText('Rejoindre')
  })

  test('rejoindre une annonce change le bouton en "Se désinscrire"', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )
    await page.route('**/api/proposals/2/join', (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB_JOINED })
    )

    await page.goto('/annonces/2')
    await page.locator('.btn-join--join').waitFor()
    await page.click('.btn-join--join')
    await expect(page.locator('.btn-join--leave')).toBeVisible()
    await expect(page.locator('.btn-join--leave')).toContainText('Se désinscrire')
  })

  test('se désinscrire remet le bouton "Rejoindre"', async ({ page }) => {
    await loginAs(page)
    // Démarrer avec Alice déjà inscrite
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB_JOINED })
    )
    await page.route('**/api/proposals/2/leave', (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )

    await page.goto('/annonces/2')
    await page.locator('.btn-join--leave').waitFor()
    await page.click('.btn-join--leave')
    await expect(page.locator('.btn-join--join')).toBeVisible()
  })

  test('le compteur de participants se met à jour après avoir rejoint', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )
    await page.route('**/api/proposals/2/join', (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB_JOINED })
    )

    await page.goto('/annonces/2')
    await expect(page.locator('.detail-progress-count')).toContainText('1 / 4')
    await page.click('.btn-join--join')
    await expect(page.locator('.detail-progress-count')).toContainText('2 / 4')
  })

  test("Alice apparaît dans la liste des participants après avoir rejoint", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )
    await page.route('**/api/proposals/2/join', (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB_JOINED })
    )

    await page.goto('/annonces/2')
    await page.click('.btn-join--join')
    await expect(page.locator('.participant-name:has-text("Alice Dupont")')).toBeVisible()
  })
})

// ── Contacter l'organisateur ──────────────────────────────────────────────────

test.describe("Contacter l'organisateur", () => {
  test("le bouton 'Contacter l'organisateur' ouvre le dialog de message", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )

    await page.goto('/annonces/2')
    await page.locator('.btn-contact').waitFor()
    await page.click('.btn-contact')
    await expect(page.locator('.dialog-box')).toBeVisible()
    await expect(page.locator('.dialog-title')).toContainText('Bob')
    await expect(page.locator('.dialog-textarea')).toBeVisible()
  })

  test("envoyer un message affiche l'état de succès", async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )
    await page.route('**/api/messages', (route) => {
      if (route.request().method() !== 'POST') return route.fallback()
      return route.fulfill({ json: { id: 1 } })
    })

    await page.goto('/annonces/2')
    await page.click('.btn-contact')
    await page.fill('.dialog-textarea', 'Bonjour Bob, je suis intéressé !')
    await page.click('.dialog-btn-send')
    await expect(page.locator('.dialog-success')).toBeVisible()
    await expect(page.locator('.dialog-success-text')).toContainText('Message envoyé !')
  })

  test('annuler le dialog le ferme sans envoyer', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/2$/, (route) =>
      route.fulfill({ json: MOCK_PROPOSAL_BY_BOB })
    )

    await page.goto('/annonces/2')
    await page.click('.btn-contact')
    await expect(page.locator('.dialog-box')).toBeVisible()
    await page.click('.dialog-btn-cancel')
    await expect(page.locator('.dialog-box')).not.toBeVisible()
  })
})

// ── Annonce privée (/annonces/3) ──────────────────────────────────────────────

test.describe('Annonce privée', () => {
  test('affiche le badge "Privée" et la notice du destinataire', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/3$/, (route) =>
      route.fulfill({ json: MOCK_PRIVATE_PROPOSAL })
    )

    await page.goto('/annonces/3')
    await expect(page.locator('.badge-purple:has-text("Privée")')).toBeVisible()
    await expect(page.locator('.private-target-notice')).toBeVisible()
    await expect(page.locator('.private-target-notice')).toContainText('Alice Dupont')
  })

  test('le bouton Rejoindre est visible pour le destinataire de la partie privée', async ({ page }) => {
    await loginAs(page)
    await page.route(/\/api\/proposals\/3$/, (route) =>
      route.fulfill({ json: MOCK_PRIVATE_PROPOSAL })
    )

    await page.goto('/annonces/3')
    await expect(page.locator('.btn-join--join')).toBeVisible()
  })

  test('rejoindre une partie privée fonctionne comme une partie publique', async ({ page }) => {
    await loginAs(page)
    const joined = {
      ...MOCK_PRIVATE_PROPOSAL,
      participantCount: 2,
      participants: [
        ...MOCK_PRIVATE_PROPOSAL.participants,
        { id: 1, firstName: 'Alice', lastName: 'Dupont', fftRanking: '15/2', avatar: null },
      ],
    }
    await page.route(/\/api\/proposals\/3$/, (route) =>
      route.fulfill({ json: MOCK_PRIVATE_PROPOSAL })
    )
    await page.route('**/api/proposals/3/join', (route) =>
      route.fulfill({ json: joined })
    )

    await page.goto('/annonces/3')
    await page.click('.btn-join--join')
    await expect(page.locator('.btn-join--leave')).toBeVisible()
  })
})
