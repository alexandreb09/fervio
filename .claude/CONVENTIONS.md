# Conventions du projet Tennis Partner

## Règle absolue : aucun style inline dans les templates Vue

**Ne jamais utiliser l'attribut `style="..."` directement dans le DOM HTML.**

```vue
<!-- ❌ INTERDIT -->
<div style="display:flex; gap:12px; color:#6366F1;">...</div>
<button @mouseenter="$event.currentTarget.style.background='#4F46E5'">...</button>
<input @focus="$event.target.style.borderColor='#6366F1'" />

<!-- ✅ CORRECT -->
<div class="my-row">...</div>
<button class="btn-primary">...</button>
<input class="field-input" />
```

---

## Où définir les styles CSS

### 1. Classes globales → `frontend/src/style.css`

Pour les patterns utilisés dans plusieurs composants :

| Classe | Usage |
|---|---|
| `.field-input` | Input natif HTML standard (padding 9/11px, font 14px) |
| `.field-input--sm` | Input compact pour les panneaux de filtre |
| `.field-input--error` | État d'erreur sur un input |
| `.field-select` | Select natif HTML standard |
| `.field-select--sm` | Select compact |
| `.field-label` | Label de champ de formulaire |
| `.field-label--sm` | Label compact |
| `.field-error` | Message d'erreur sous un champ |
| `.section-label` | Titre de section en majuscules (Identité, Profil de jeu…) |
| `.error-banner` | Bannière d'erreur rouge |
| `.success-banner` | Bannière de succès verte |
| `.btn-primary` | Bouton action principale (violet #6366F1) |
| `.btn-secondary` | Bouton secondaire (blanc, bordure) |
| `.form-divider` | Séparateur horizontal fin |
| `.badge`, `.badge-*` | Badges colorés (green, amber, purple, gray, blue, pink, red) |
| `.badge--xs`, `.badge--sm`, `.badge--md` | Tailles de badge |
| `.fin-card` | Carte blanche avec bordure et hover |
| `.fin-label` | Label de section uppercase violet |
| `.page`, `.page-sm` | Conteneurs de page avec max-width |
| `.ml-auto` | `margin-left: auto` |

### 2. Classes locales → `<style scoped>` du composant

Pour les styles spécifiques à un seul composant.

---

## Focus et hover : utiliser les pseudo-classes CSS

Ne jamais utiliser des écouteurs JS pour simuler :hover ou :focus.

```vue
<!-- ❌ INTERDIT -->
<input
  @focus="$event.target.style.borderColor='#6366F1'"
  @blur="$event.target.style.borderColor='#E2E8F0'"
/>
<button
  @mouseenter="$event.currentTarget.style.background='#4F46E5'"
  @mouseleave="$event.currentTarget.style.background='#6366F1'"
>

<!-- ✅ CORRECT — le CSS global gère déjà le focus sur .field-input -->
<input class="field-input" />

<!-- ✅ CORRECT — le CSS gère le hover via .btn-primary:hover -->
<button class="btn-primary">
```

Les classes globales `.field-input`, `.field-select`, `.btn-primary`, `.btn-secondary` gèrent déjà automatiquement leurs états `:focus` et `:hover`.

---

## Styles dynamiques : uniquement pour les valeurs calculées

L'attribut `:style` (binding Vue) est accepté **uniquement** pour les valeurs vraiment dynamiques (calculées à l'exécution, non prévisibles statiquement).

```vue
<!-- ✅ OK : valeur calculée (% dynamique) -->
<div class="progress-fill" :style="{ width: fillPercent + '%' }" />

<!-- ❌ INTERDIT : valeur statique déguisée en dynamique -->
<div :style="{ color: '#6366F1' }" />
```

Pour les états conditionnels, utiliser `:class` avec des classes CSS :

```vue
<!-- ❌ INTERDIT -->
<div :style="`background: ${isActive ? '#6366F1' : '#E2E8F0'}`" />

<!-- ✅ CORRECT -->
<div :class="['bubble', isMine ? 'bubble--mine' : 'bubble--other']" />
```

---

## CityInput : utiliser `input-class` (pas `input-style`)

```vue
<!-- ❌ INTERDIT -->
<CityInput input-style="width:100%; padding:9px 11px; border:1px solid #E2E8F0; ..." />

<!-- ✅ CORRECT -->
<CityInput input-class="field-input" />
<!-- ou pour les filtres -->
<CityInput input-class="field-input field-input--sm" />
```

---

## Nomenclature des classes

- Préfixes par composant pour éviter les conflits : `.conv-`, `.proposal-`, `.profile-`, `.auth-`, `.nav-`, etc.
- Modificateurs BEM légers : `.btn--active`, `.input--error`, `.card--selected`
- Éviter les classes trop génériques dans `<style scoped>` (`.row`, `.title`) ; préférer `.proposal-row`, `.auth-title`
