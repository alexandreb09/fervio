<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Paris…' },
  inputClass: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'city-selected', 'search'])

const suggestions = ref([])
const open = ref(false)
const activeIndex = ref(-1)
const loading = ref(false)
const inputEl = ref(null)

let debounceTimer = null
// Track whether the current value was confirmed by selecting a suggestion.
// pendingWatchSkips prevents our own emits from being treated as external updates.
let pendingWatchSkips = 0
const confirmed = ref(!!props.modelValue)
const lastConfirmed = ref(props.modelValue || '')

// When the parent resets the form externally, treat the new value as confirmed.
watch(() => props.modelValue, (val) => {
  if (pendingWatchSkips > 0) { pendingWatchSkips--; return }
  lastConfirmed.value = val || ''
  confirmed.value = true
}, { flush: 'sync' })

async function fetchSuggestions(q) {
  if (!q || q.length < 2) { suggestions.value = []; return }
  loading.value = true
  try {
    const res = await fetch(
      `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(q)}&type=municipality&limit=8&autocomplete=1`
    )
    const data = await res.json()
    suggestions.value = (data.features ?? []).map(f => ({
      name: f.properties.city || f.properties.name,
      postalCode: f.properties.postcode,
      label: `${f.properties.city || f.properties.name} (${f.properties.postcode})`,
    }))
  } catch {
    suggestions.value = []
  } finally {
    loading.value = false
  }
}

function onInput(e) {
  pendingWatchSkips++
  emit('update:modelValue', e.target.value)
  confirmed.value = false
  open.value = true
  activeIndex.value = -1
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchSuggestions(e.target.value), 300)
}

function select(item) {
  pendingWatchSkips++
  emit('update:modelValue', item.name)
  emit('city-selected', { name: item.name, postalCode: item.postalCode })
  confirmed.value = true
  lastConfirmed.value = item.name
  open.value = false
  activeIndex.value = -1
  suggestions.value = []
}

function onBlur(e) {
  const currentVal = e.target.value
  // Delay to let mousedown.prevent on suggestions fire select() first.
  setTimeout(() => {
    if (confirmed.value) return
    if (currentVal === '') {
      // User explicitly cleared the field — accept it.
      lastConfirmed.value = ''
      confirmed.value = true
      pendingWatchSkips++
      emit('update:modelValue', '')
      emit('city-selected', { name: '', postalCode: null })
    } else {
      // Free-typed text not from a suggestion — revert.
      pendingWatchSkips++
      emit('update:modelValue', lastConfirmed.value)
    }
    open.value = false
    activeIndex.value = -1
    suggestions.value = []
  }, 200)
}

function onKeydown(e) {
  if (!open.value || suggestions.value.length === 0) {
    if (e.key === 'Enter') emit('search')
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeIndex.value = Math.min(activeIndex.value + 1, suggestions.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeIndex.value = Math.max(activeIndex.value - 1, -1)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (activeIndex.value >= 0) select(suggestions.value[activeIndex.value])
    else { open.value = false; emit('search') }
  } else if (e.key === 'Escape') {
    open.value = false
    activeIndex.value = -1
  }
}

function onOutsideClick(e) {
  if (inputEl.value && !inputEl.value.contains(e.target)) {
    open.value = false
    activeIndex.value = -1
  }
}

document.addEventListener('click', onOutsideClick, true)
onUnmounted(() => {
  document.removeEventListener('click', onOutsideClick, true)
  clearTimeout(debounceTimer)
})
</script>

<template>
  <div ref="inputEl" class="city-wrap">
    <input
      :value="modelValue"
      :placeholder="placeholder"
      :class="inputClass"
      autocomplete="off"
      @input="onInput"
      @keydown="onKeydown"
      @blur="onBlur"
      @focus="open = suggestions.length > 0"
    />
    <ul v-if="open && suggestions.length" class="city-dropdown">
      <li
        v-for="(item, i) in suggestions"
        :key="item.postalCode + item.name"
        :class="['city-option', { 'city-option--active': i === activeIndex }]"
        @mousedown.prevent="select(item)"
      >
        <v-icon size="12" color="text-subtle">mdi-map-marker-outline</v-icon>
        {{ item.name }}
        <span class="city-postal">{{ item.postalCode }}</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.city-wrap { position: relative; }

.city-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
  list-style: none;
  margin: 0;
  padding: 4px;
  z-index: 100;
  max-height: 220px;
  overflow-y: auto;
}

.city-option {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 7px 10px;
  font-size: 13px;
  color: var(--c-text);
  border-radius: 5px;
  cursor: pointer;
  font-family: Inter, sans-serif;
  transition: background 0.1s;
}
.city-option:hover,
.city-option--active { background: var(--c-primary-bg); color: var(--c-primary); }

.city-postal {
  margin-left: auto;
  font-size: 11px;
  color: var(--c-text-sm);
  font-variant-numeric: tabular-nums;
}
.city-option--active .city-postal,
.city-option:hover .city-postal { color: var(--c-primary); opacity: 0.7; }
</style>
