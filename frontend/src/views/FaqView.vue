<script setup>
import { FAQ_ITEMS } from '@/data/faq'
</script>

<template>
  <div class="page-sm faq-page">
    <p class="fin-label">Questions fréquentes</p>
    <h1 class="faq-title">Tout savoir sur Fervio</h1>

    <div class="faq-list">
      <details v-for="item in FAQ_ITEMS" :key="item.question" class="faq-item">
        <summary class="faq-question">{{ item.question }}</summary>
        <p class="faq-answer">{{ item.answer }}</p>
      </details>
    </div>
  </div>
</template>

<style scoped>
.faq-page {
  padding-top: 48px;
  padding-bottom: 64px;
}

.faq-title {
  font-size: clamp(1.8rem, 4vw, 2.4rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 8px 0 28px;
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.faq-item {
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: 10px;
  padding: 14px 18px;
}

.faq-question {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text);
  cursor: pointer;
  list-style: none;
}

.faq-question::-webkit-details-marker {
  display: none;
}

.faq-answer {
  font-size: 13px;
  color: var(--c-text-md);
  line-height: 1.6;
  margin: 10px 0 0;
}
</style>
