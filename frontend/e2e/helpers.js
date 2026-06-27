import {
  MOCK_USER,
  MOCK_PROPOSALS,
  MOCK_PLAYERS,
  MOCK_PROPOSAL_DETAIL,
  MOCK_BOB,
  MOCK_PROPOSAL_BY_BOB,
  MOCK_PROPOSAL_BY_BOB_JOINED,
  MOCK_PRIVATE_PROPOSAL,
  MOCK_CONVERSATIONS,
  MOCK_MESSAGES,
} from './fixtures.js'

/**
 * Mocks all public GET endpoints needed by the home, proposals, and players pages.
 * Each handler falls through on non-GET methods so POST routes can be set in individual tests.
 */
export async function mockPublicApis(page, options = {}) {
  const { proposals = [], players = [] } = options

  await page.route(/\/api\/proposals/, (route) => {
    if (route.request().method() !== 'GET') return route.fallback()
    const url = route.request().url()
    if (url.includes('/received-private')) return route.fulfill({ json: [] })
    if (/\/api\/proposals\/\d+$/.test(url)) return route.fulfill({ json: MOCK_PROPOSAL_DETAIL })
    return route.fulfill({ json: proposals })
  })

  await page.route(/\/api\/users/, (route) => {
    if (route.request().method() !== 'GET') return route.fallback()
    const url = route.request().url()
    if (url.includes('/cities')) return route.fulfill({ json: ['Paris', 'Lyon', 'Bordeaux', 'Marseille'] })
    return route.fulfill({ json: players })
  })
}

/**
 * Mocks the endpoints that are called for any authenticated session:
 * - GET /api/auth/me  (auth store initialization)
 * - GET /api/messages/unread-count  (App.vue polling)
 * - GET /api/partners  (partners store)
 */
export async function mockAuthApis(page, user = MOCK_USER) {
  await page.route('**/api/auth/me', (route) => route.fulfill({ json: user }))
  await page.route('**/api/messages/unread-count', (route) => route.fulfill({ json: { count: 0 } }))
  await page.route(/\/api\/partners/, (route) => {
    if (route.request().method() !== 'GET') return route.fallback()
    return route.fulfill({ json: [] })
  })
}

/**
 * Simulates an authenticated session by:
 * 1. Mocking all auth-related API endpoints.
 * 2. Injecting a fake JWT token into localStorage before the page scripts run.
 * 3. Adding a catch-all mock so intermediate navigations don't hit the real backend.
 *
 * Must be called BEFORE page.goto(). Specific route mocks registered AFTER this
 * call will take priority over the catch-all (Playwright matches in reverse order).
 */
export async function loginAs(page, user = MOCK_USER) {
  // Catch-all FIRST → lowest priority (LIFO: registered first = checked last).
  // Specific mocks registered after this (including mockAuthApis) take precedence.
  await page.route(/\/api\//, (route) => {
    if (route.request().method() !== 'GET') return route.fallback()
    return route.fulfill({ json: [] })
  })
  await mockAuthApis(page, user)
  await page.addInitScript(() => {
    localStorage.setItem('jwt_token', 'test-jwt-token')
  })
}

/**
 * Navigates to an auth-guarded page reliably despite the async auth timing issue.
 *
 * The router guard checks isLoggedIn synchronously, but fetchMe() is async.
 * Strategy: load a non-guarded page first → wait for auth → client-side push to target.
 */
export async function gotoProtected(page, targetUrl) {
  // Load a public page so Vue app + auth store initialize and fetchMe() can complete.
  await page.goto('/annonces')
  // Wait for the user menu to appear — proof that isLoggedIn is true.
  await page.locator('.user-menu-btn').waitFor({ timeout: 10_000 })
  // Client-side navigation via Vue Router (no full reload, guard passes with auth ready).
  await page.evaluate((url) => {
    const app = document.querySelector('#app')?.__vue_app__
    app?.config?.globalProperties?.$router?.push(url)
  }, targetUrl)
  // SPA navigation doesn't fire a 'load' event, so use expect().toHaveURL() instead of waitForURL().
  const { expect } = await import('@playwright/test')
  await expect(page).toHaveURL(targetUrl)
}

export {
  MOCK_USER,
  MOCK_PROPOSALS,
  MOCK_PLAYERS,
  MOCK_PROPOSAL_DETAIL,
  MOCK_BOB,
  MOCK_PROPOSAL_BY_BOB,
  MOCK_PROPOSAL_BY_BOB_JOINED,
  MOCK_PRIVATE_PROPOSAL,
  MOCK_CONVERSATIONS,
  MOCK_MESSAGES,
}
