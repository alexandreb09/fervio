import { createApp } from 'vue'
import './style.css'
import { createPinia } from 'pinia'
import { createVuetify } from 'vuetify'
import { createRouter, createWebHistory } from 'vue-router'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import App from './App.vue'
import { routes, setupRouterGuards } from './router'

const vuetify = createVuetify({
  components,
  directives,
  icons: { defaultSet: 'mdi', aliases, sets: { mdi } },
  defaults: {
    VCard:     { elevation: 0 },
    VTextField:  { variant: 'outlined', color: 'primary', density: 'comfortable' },
    VSelect:     { variant: 'outlined', color: 'primary', density: 'comfortable' },
    VTextarea:   { variant: 'outlined', color: 'primary', density: 'comfortable' },
    VBtn:        { elevation: 0 },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          // Brand
          primary:            '#C25228',
          'primary-darken-1': '#A33F18',
          secondary:          '#6B4A38',
          // Status
          error:              '#EF4444',
          warning:            '#F59E0B',
          info:               '#3B82F6',
          success:            '#16A34A',
          // Layout
          background:         '#FAF5EF',
          surface:            '#FFFFFF',
          'on-primary':       '#FFFFFF',
          'on-background':    '#1A0F08',
          'on-surface':       '#1A0F08',
          // Semantic (utilisables via color="xxx" sur les composants Vuetify)
          'primary-bg':       '#FEF0E6',
          'surface-hover':    '#F5EDE4',
          border:             '#E8D4C0',
          'border-light':     '#CDB8A8',
          'text-medium':      '#78604E',
          'text-subtle':      '#9A7B6A',
          'text-muted':       '#6B4A38',
          'text-dark':        '#3D2A20',
        },
      },
    },
  },
})

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

const pinia = createPinia()
const app = createApp(App)

app.use(pinia).use(router).use(vuetify)
setupRouterGuards(router)
app.mount('#app')