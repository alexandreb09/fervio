// Villes mises en avant pour les pages locales /partenaire-tennis/:ville.
// Les noms reprennent exactement la casse utilisée par l'API (voir UserController::cities)
// pour garantir une correspondance avec le filtre `city` (LIKE) côté backend.
export const CITIES = [
  { slug: 'paris', name: 'Paris' },
  { slug: 'lyon', name: 'Lyon' },
  { slug: 'marseille', name: 'Marseille' },
  { slug: 'toulouse', name: 'Toulouse' },
  { slug: 'bordeaux', name: 'Bordeaux' },
  { slug: 'nantes', name: 'Nantes' },
  { slug: 'lille', name: 'Lille' },
  { slug: 'strasbourg', name: 'Strasbourg' },
  { slug: 'nice', name: 'Nice' },
  { slug: 'montpellier', name: 'Montpellier' },
  { slug: 'rennes', name: 'Rennes' },
  { slug: 'grenoble', name: 'Grenoble' },
]

export function findCityBySlug(slug) {
  return CITIES.find((c) => c.slug === slug)
}
