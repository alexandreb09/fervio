import { test, expect } from '@playwright/test'
import { mockAuthApis, mockPublicApis, MOCK_USER } from './helpers.js'

// ── Connexion ─────────────────────────────────────────────────────────────────

test.describe('Connexion (/connexion)', () => {
  test('connexion réussie redirige vers /profil', async ({ page }) => {
    // Mock login endpoint then auth/me (called by auth.login() → fetchMe())
    await page.route('**/api/auth/login', (route) =>
      route.fulfill({ json: { token: 'fake-jwt-token' } })
    )
    await mockAuthApis(page)
    await mockPublicApis(page, { proposals: [], players: [] })
    // profile page also loads proposals and partners
    await page.route(/\/api\/proposals/, (route) => route.fulfill({ json: [] }))

    await page.goto('/connexion')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.fill('input[placeholder="••••••••"]', 'password123')
    await page.click('button[type="submit"]')

    await expect(page).toHaveURL('/profil')
    await expect(page.locator('.profile-name')).toContainText('Alice Dupont')
  })

  test('connexion avec mauvais identifiants affiche un message d\'erreur', async ({ page }) => {
    await page.route('**/api/auth/login', (route) =>
      route.fulfill({
        status: 401,
        json: { error: 'Email ou mot de passe incorrect.' },
      })
    )

    await page.goto('/connexion')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.fill('input[placeholder="••••••••"]', 'mauvais-mdp')
    await page.click('button[type="submit"]')

    await expect(page.locator('.error-banner')).toContainText('Email ou mot de passe incorrect.')
    await expect(page).toHaveURL('/connexion')
  })

  test('le bouton œil bascule la visibilité du mot de passe', async ({ page }) => {
    await page.goto('/connexion')
    const input = page.locator('input[placeholder="••••••••"]')
    await expect(input).toHaveAttribute('type', 'password')
    await page.click('.pwd-toggle')
    await expect(input).toHaveAttribute('type', 'text')
    await page.click('.pwd-toggle')
    await expect(input).toHaveAttribute('type', 'password')
  })

  test('le lien "Mot de passe oublié ?" navigue correctement', async ({ page }) => {
    await page.goto('/connexion')
    await page.click('text=Mot de passe oublié')
    await expect(page).toHaveURL('/mot-de-passe-oublie')
  })

  test('le lien "Créer un compte" navigue vers /inscription', async ({ page }) => {
    await page.goto('/connexion')
    await page.click('text=Créer un compte')
    await expect(page).toHaveURL('/inscription')
  })
})

// ── Inscription ───────────────────────────────────────────────────────────────

test.describe('Inscription (/inscription)', () => {
  test('les erreurs de validation 422 s\'affichent sur les champs', async ({ page }) => {
    await page.route('**/api/auth/register', (route) =>
      route.fulfill({
        status: 422,
        json: { errors: { email: 'Cette adresse email est déjà utilisée.' } },
      })
    )

    await page.goto('/inscription')
    await page.fill('input[placeholder="Alice"]', 'Alice')
    await page.fill('input[placeholder="Martin"]', 'Dupont')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.fill('input[type="password"]', 'password123')
    await page.click('button[type="submit"]')

    await expect(page.locator('.field-error')).toContainText('Cette adresse email est déjà utilisée.')
    await expect(page).toHaveURL('/inscription')
  })

  test('une erreur serveur affiche la bannière d\'erreur globale', async ({ page }) => {
    await page.route('**/api/auth/register', (route) =>
      route.fulfill({
        status: 500,
        json: { error: 'Une erreur est survenue.' },
      })
    )

    await page.goto('/inscription')
    await page.fill('input[placeholder="Alice"]', 'Alice')
    await page.fill('input[placeholder="Martin"]', 'Dupont')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.fill('input[type="password"]', 'password123')
    await page.click('button[type="submit"]')

    await expect(page.locator('.error-banner')).toBeVisible()
    await expect(page).toHaveURL('/inscription')
  })

  test('le formulaire requiert les champs obligatoires', async ({ page }) => {
    await page.goto('/inscription')
    // Soumettre sans remplir
    await page.click('button[type="submit"]')
    // Le navigateur valide les champs required nativement — on reste sur la page
    await expect(page).toHaveURL('/inscription')
  })

  test('le lien "Se connecter" navigue vers /connexion', async ({ page }) => {
    await page.goto('/inscription')
    await page.click('text=Se connecter')
    await expect(page).toHaveURL('/connexion')
  })
})

// ── Mot de passe oublié ───────────────────────────────────────────────────────

test.describe('Mot de passe oublié (/mot-de-passe-oublie)', () => {
  test('la soumission d\'un email valide affiche l\'écran de confirmation', async ({ page }) => {
    await page.route('**/api/auth/forgot-password', (route) =>
      route.fulfill({ status: 200, json: {} })
    )

    await page.goto('/mot-de-passe-oublie')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.click('button[type="submit"]')

    await expect(page.locator('text=Vérifiez vos emails')).toBeVisible()
    await expect(page.locator('text=alice@example.com')).toBeVisible()
  })

  test('une erreur API affiche la bannière d\'erreur', async ({ page }) => {
    await page.route('**/api/auth/forgot-password', (route) =>
      route.fulfill({ status: 500, json: {} })
    )

    await page.goto('/mot-de-passe-oublie')
    await page.fill('input[type="email"]', 'alice@example.com')
    await page.click('button[type="submit"]')

    await expect(page.locator('.error-banner')).toBeVisible()
  })

  test('le lien "Retour à la connexion" navigue vers /connexion', async ({ page }) => {
    await page.goto('/mot-de-passe-oublie')
    await page.click('a:has-text("Retour à la connexion")')
    await expect(page).toHaveURL('/connexion')
  })
})

