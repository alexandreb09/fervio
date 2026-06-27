<script setup>
import { ref } from 'vue'
import api from '@/api'

const props = defineProps({
  targetType: { type: String, required: true },
  targetId:   { type: Number, required: true },
})

const emit = defineEmits(['close'])

const CATEGORIES = [
  { value: 'harassment',            label: 'Harcèlement' },
  { value: 'sexism',                label: 'Sexisme / discriminations' },
  { value: 'fake_profile',          label: 'Profil frauduleux' },
  { value: 'spam',                  label: 'Spam' },
  { value: 'inappropriate_content', label: 'Contenu inapproprié' },
  { value: 'violence',              label: 'Comportement violent' },
  { value: 'other',                 label: 'Autre' },
]

const category = ref('')
const reason   = ref('')
const sending  = ref(false)
const sent     = ref(false)
const error    = ref('')

async function submit() {
  if (!category.value) { error.value = 'Veuillez choisir une catégorie.'; return }
  sending.value = true
  error.value   = ''
  try {
    await api.post('/reports', {
      targetType: props.targetType,
      targetId:   props.targetId,
      category:   category.value,
      reason:     reason.value.trim(),
    })
    sent.value = true
    setTimeout(() => emit('close'), 2000)
  } catch (e) {
    error.value = e.response?.data?.error || 'Une erreur est survenue.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="report-backdrop" @click.self="emit('close')">
    <div class="report-box">
      <div class="report-header">
        <span class="report-title">Signaler un contenu</span>
        <button class="report-close" @click="emit('close')">✕</button>
      </div>

      <div v-if="sent" class="report-success">
        <v-icon color="success" size="28">mdi-check-circle-outline</v-icon>
        <p>Signalement envoyé. Merci pour votre aide.</p>
      </div>

      <template v-else>
        <p class="report-hint">
          Votre signalement sera examiné par notre équipe de modération.
        </p>

        <label class="report-label">Catégorie <span class="required">*</span></label>
        <div class="category-list">
          <button
            v-for="c in CATEGORIES"
            :key="c.value"
            type="button"
            :class="['category-pill', category === c.value && 'category-pill--on']"
            @click="category = c.value"
          >{{ c.label }}</button>
        </div>

        <label class="report-label" style="margin-top:14px;">
          Précisions <span class="report-optional">(optionnel)</span>
        </label>
        <textarea
          v-model="reason"
          class="report-textarea"
          placeholder="Décrivez brièvement le problème…"
          rows="3"
          maxlength="1000"
        />

        <p v-if="error" class="report-error">{{ error }}</p>

        <div class="report-actions">
          <button class="btn-secondary" :disabled="sending" @click="emit('close')">Annuler</button>
          <button class="btn-danger" :disabled="sending || !category" @click="submit">
            <v-progress-circular v-if="sending" size="14" width="2" indeterminate color="white" />
            <template v-else>Envoyer le signalement</template>
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.report-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}
.report-box {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
}
.report-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.report-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--c-text);
}
.report-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  color: var(--c-text-sm);
  padding: 0;
  line-height: 1;
}
.report-hint {
  font-size: 13px;
  color: var(--c-text-md);
  margin: 0 0 16px;
  line-height: 1.5;
}
.report-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--c-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 8px;
}
.required { color: var(--c-error); }
.report-optional { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--c-text-sm); }
.category-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.category-pill {
  border: 1px solid var(--c-border);
  border-radius: 20px;
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 500;
  color: var(--c-text-muted);
  background: #fff;
  cursor: pointer;
  font-family: Inter, sans-serif;
  transition: all 0.1s;
}
.category-pill:hover { border-color: var(--c-error); color: var(--c-error); }
.category-pill--on { background: #FEF2F2; border-color: var(--c-error); color: #DC2626; font-weight: 600; }
.report-textarea {
  width: 100%;
  border: 1px solid var(--c-border);
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 13px;
  font-family: Inter, sans-serif;
  color: var(--c-text);
  resize: vertical;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.1s;
}
.report-textarea:focus { border-color: var(--c-primary); }
.report-error {
  font-size: 12px;
  color: #DC2626;
  margin: 8px 0 0;
}
.report-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 18px;
}
.report-success {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 24px 0 8px;
  text-align: center;
  color: #16A34A;
  font-size: 14px;
  font-weight: 600;
}
</style>
