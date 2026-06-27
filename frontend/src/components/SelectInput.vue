<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  options: { type: Array, required: true }, // [{ value, label }]
  placeholder: { type: String, default: 'Tous' },
})
const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)
const wrapEl = ref(null)

const selectedLabel = computed(() => {
  if (!props.modelValue) return null
  return props.options.find(o => o.value === props.modelValue)?.label ?? null
})

function select(value) {
  emit('update:modelValue', value)
  emit('change', value)
  open.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape') open.value = false
  if ((e.key === 'Enter' || e.key === ' ') && !open.value) {
    e.preventDefault()
    open.value = true
  }
}

function onOutsideClick(e) {
  if (wrapEl.value && !wrapEl.value.contains(e.target)) open.value = false
}

onMounted(() => document.addEventListener('click', onOutsideClick, true))
onUnmounted(() => document.removeEventListener('click', onOutsideClick, true))
</script>

<template>
  <div ref="wrapEl" class="select-wrap">
    <button
      type="button"
      class="select-trigger"
      :class="{ 'select-trigger--open': open, 'select-trigger--filled': !!modelValue }"
      @click="open = !open"
      @keydown="onKeydown"
    >
      <span :class="modelValue ? 'select-value' : 'select-placeholder'">
        {{ selectedLabel ?? placeholder }}
      </span>
      <v-icon size="14" class="select-chevron" :class="{ 'select-chevron--up': open }">
        mdi-chevron-down
      </v-icon>
    </button>

    <ul v-if="open" class="select-dropdown">
      <li
        class="select-option"
        :class="{ 'select-option--active': modelValue === '' }"
        @mousedown.prevent="select('')"
      >
        {{ placeholder }}
      </li>
      <li
        v-for="opt in options"
        :key="opt.value"
        class="select-option"
        :class="{ 'select-option--active': modelValue === opt.value }"
        @mousedown.prevent="select(opt.value)"
      >
        {{ opt.label }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.select-wrap { position: relative; }

.select-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  padding: 0 10px;
  height: 34px;
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 8px;
  cursor: pointer;
  font-family: Inter, sans-serif;
  font-size: 13px;
  text-align: left;
  transition: border-color 0.15s;
}
.select-trigger:hover { border-color: var(--c-border-lt); }
.select-trigger--open { border-color: var(--c-primary); }

.select-placeholder { color: var(--c-text-sm); }
.select-value { color: var(--c-text); font-weight: 500; }

.select-chevron { color: var(--c-text-sm); transition: transform 0.15s; flex-shrink: 0; }
.select-chevron--up { transform: rotate(180deg); }

.select-dropdown {
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

.select-option {
  padding: 7px 10px;
  font-size: 13px;
  color: var(--c-text);
  border-radius: 5px;
  cursor: pointer;
  font-family: Inter, sans-serif;
  transition: background 0.1s;
}
.select-option:hover { background: var(--c-primary-bg); color: var(--c-primary); }
.select-option--active { background: var(--c-primary-bg); color: var(--c-primary); font-weight: 600; }
</style>
