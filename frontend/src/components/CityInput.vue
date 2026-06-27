<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/api'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Paris…' },
  inputClass: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'search'])

const cities = ref([])
const open = ref(false)
const activeIndex = ref(-1)
const inputEl = ref(null)

onMounted(async () => {
  try {
    const res = await api.get('/users/cities')
    cities.value = res.data
  } catch { /* ignore */ }
  document.addEventListener('click', onOutsideClick, true)
})

onUnmounted(() => {
  document.removeEventListener('click', onOutsideClick, true)
})

const suggestions = computed(() => {
  const q = props.modelValue.trim().toLowerCase()
  if (!q) return []
  return cities.value.filter(c => c.toLowerCase().includes(q)).slice(0, 8)
})

function onInput(e) {
  emit('update:modelValue', e.target.value)
  open.value = true
  activeIndex.value = -1
}

function select(city) {
  emit('update:modelValue', city)
  open.value = false
  activeIndex.value = -1
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
    if (activeIndex.value >= 0) {
      select(suggestions.value[activeIndex.value])
    } else {
      open.value = false
      emit('search')
    }
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
</script>

<template>
  <div ref="inputEl" class="city-wrap">
    <input
      :value="modelValue"
      :placeholder="placeholder"
      :class="inputClass"
      autocomplete="off"
      @input="onInput"
      @focus="open = suggestions.length > 0"
      @keydown="onKeydown"
    />
    <ul v-if="open && suggestions.length" class="city-dropdown">
      <li
        v-for="(city, i) in suggestions"
        :key="city"
        :class="['city-option', { 'city-option--active': i === activeIndex }]"
        @mousedown.prevent="select(city)"
      >
        <v-icon size="12" color="text-subtle">mdi-map-marker-outline</v-icon>
        {{ city }}
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
</style>
