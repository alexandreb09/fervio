<script setup>
</script>

<template>
  <div class="page-sm about-page">
    <p class="fin-label">Notre mission</p>
    <h1 class="about-title">Pourquoi Fervio ?</h1>
    <p class="about-lead">
      Fervio est né d'un constat simple : trouver un partenaire de tennis en France reste plus compliqué qu'il ne devrait l'être.
      Entre les annuaires vieillissants, les groupes Facebook dispersés et les clubs saturés, beaucoup de joueurs passent plus de temps à chercher une partie qu'à la jouer.
    </p>

    <section class="about-section">
      <h2>Un partenaire de tennis, gratuitement</h2>
      <p>
        Fervio est 100 % gratuit : créer un profil, consulter l'annuaire des joueurs, publier ou rejoindre une annonce de partie et échanger par messagerie ne coûtent rien, sans abonnement caché ni publicité intrusive.
        Notre objectif est de rendre la recherche d'un partenaire raquette accessible à tous les joueurs, débutants comme confirmés.
      </p>
    </section>

    <section class="about-section">
      <h2>Une interface pensée pour aujourd'hui</h2>
      <p>
        Des annuaires historiques comme Partennis ou Tennis-Contact ont permis à des milliers de joueurs de se rencontrer, et on leur doit beaucoup.
        Mais leurs interfaces n'ont que peu évolué depuis leur création : peu adaptées au mobile, denses et parfois difficiles à prendre en main.
        Fervio a été conçu dès le départ pour être rapide, lisible et agréable à utiliser aussi bien sur smartphone que sur ordinateur.
      </p>
    </section>

    <section class="about-section">
      <h2>Des parties équilibrées grâce au classement FFT</h2>
      <p>
        Plutôt qu'un simple annuaire de contacts, Fervio permet de filtrer les joueurs par classement FFT, ville et type de partie (simple, double ou double mixte),
        pour organiser des rencontres réellement équilibrées et progresser avec un partenaire de tennis de son niveau.
      </p>
    </section>

    <section class="about-section">
      <h2>Une messagerie intégrée, pour plus de sérénité</h2>
      <p>
        Contactez un partenaire directement depuis Fervio, sans avoir à communiquer votre numéro de téléphone dès le premier échange.
        Vous gardez le contrôle sur ce que vous partagez et à quel moment.
      </p>
    </section>

    <section class="about-section">
      <h2>Une communauté modérée</h2>
      <p>
        Chaque profil et chaque annonce peut être signalé en un clic. Notre équipe modère activement la communauté pour que chercher un partenaire de tennis
        reste une expérience sûre et agréable, et le site continue d'évoluer au fil des retours des joueurs.
      </p>
    </section>

    <div class="about-cta">
      <router-link to="/inscription" class="btn-primary">Rejoindre Fervio gratuitement →</router-link>
    </div>
  </div>
</template>

<style scoped>
.about-page { padding-top: 48px; padding-bottom: 64px; }
.about-title {
  font-size: clamp(1.8rem, 4vw, 2.4rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--c-text);
  margin: 8px 0 20px;
}
.about-lead { font-size: 15px; color: var(--c-text-md); line-height: 1.7; margin: 0 0 36px; }
.about-section { margin-bottom: 28px; }
.about-section h2 {
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--c-text);
  margin: 0 0 8px;
}
.about-section p { font-size: 14px; color: var(--c-text-md); line-height: 1.7; margin: 0; }
.about-cta { text-align: center; margin-top: 40px; }
</style>
