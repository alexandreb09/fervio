export const STORAGE_BASE = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace(/\/api$/, '')
  : 'http://localhost:8000'

export function avatarUrl(u, size = 80) {
  if (!u) return ''
  if (u.avatar) {
    return u.avatar.startsWith('http') ? u.avatar : `${STORAGE_BASE}${u.avatar}`
  }
  return `https://ui-avatars.com/api/?name=${u.firstName}+${u.lastName}&background=FEF0E6&color=C25228&bold=true&size=${size}`
}
