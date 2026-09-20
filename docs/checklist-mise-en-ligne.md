# Checklist de mise en ligne — Campus Connect

À dérouler dans l'ordre avant d'ouvrir campusconnect.fr au public.
Aucun point bloquant ne peut être reporté « pour plus tard ».

---

## 🔴 Points bloquants

### 1. Remplacer les numéros WhatsApp de développement

**Remplacer `CC_WHATSAPP_FR` et `CC_WHATSAPP_CM` dans
`wp-content/mu-plugins/cc-config.php` par les numéros professionnels de
Campus Connect.**

Les valeurs actuelles sont des numéros de test. Tant qu'elles sont en place,
chaque visiteur qui clique sur un bouton WhatsApp écrit à quelqu'un qui n'est
pas Campus Connect : les demandes sont perdues sans que personne ne s'en
aperçoive côté agence.

- Format attendu : indicatif + numéro, sans `+`, sans espace, sans zéro initial
  (ex. `33612345678`). C'est le format de `wa.me` ; un numéro mal formé
  n'échoue pas visiblement, il ouvre une page d'erreur WhatsApp.
- Une alerte rouge s'affiche dans le back-office tant que les numéros de dev
  sont actifs et que le site ne tourne pas en environnement `local`.
- Vérifier après remplacement : cliquer sur un bouton WhatsApp depuis un
  téléphone et confirmer que la conversation s'ouvre sur le bon compte.
- Ne jamais réécrire un numéro ailleurs que dans ce fichier : le thème et les
  pages Elementor passent tous par `cc_whatsapp_link()` ou `[cc_whatsapp]`.

### 2. Basculer la licence Elementor Pro sur le domaine de production

La licence est actuellement activée sur l'environnement de développement.

- Désactiver le site de dev depuis le compte Elementor
  (my.elementor.com → Licences → Gérer les sites).
- Activer la licence sur `campusconnect.fr`.
- Sans cette bascule : plus de mises à jour Pro ni de correctifs de sécurité,
  et les widgets Pro finissent par afficher un bandeau d'avertissement.

### 3. Remettre `blog_public` à 1

Le site est en « décourager les moteurs de recherche » pendant le
développement.

```bash
wp option update blog_public 1
```

Puis vérifier que `/robots.txt` ne contient plus `Disallow: /` et soumettre le
sitemap Rank Math à la Search Console. Oublier ce point rend tout le travail
SEO sans effet.

### 4. Retirer tout contenu `.cc-placeholder`

Aucune page publiée ne doit conserver d'élément portant la classe
`cc-placeholder` : ce sont les contenus fictifs (faux témoignages, chiffres
provisoires, textes de remplissage) posés pendant la maquette.

- Ils se repèrent à l'œil : fond rayé jaune, bordure orange pointillée,
  étiquette « CONTENU FICTIF ».
- Recherche en base pour ne rien manquer :

  ```bash
  wp db query "SELECT ID, post_title, post_type, post_status FROM cc_posts \
    WHERE post_content LIKE '%cc-placeholder%' AND post_status != 'trash';"
  ```

- Penser aux modèles Elementor (en-tête, pied de page, popups) et aux widgets,
  qui ne sortent pas tous de la requête ci-dessus.

### 5. Textes légaux validés par le juriste

Remplacer les versions de travail par les textes définitifs :

- mentions légales (éditeur, hébergeur, directeur de publication) ;
- politique de confidentialité et durée de conservation des données du
  formulaire ;
- conditions générales de service ;
- bandeau cookies Complianz configuré sur les traceurs réellement présents.

Rappel des règles de contenu du projet : aucun prix affiché, et aucune
promesse de résultat (« visa garanti », « admission garantie »,
« 100 % de réussite », « dossier infaillible »).

---

## Avant l'ouverture au public

### Contenus et conformité

- [ ] Relire chaque page publiée : aucune promesse de résultat, aucun tarif.
- [ ] Consentement RGPD jamais pré-coché sur le formulaire Fluent Forms.
- [ ] Destinations inactives (Canada, Allemagne) affichées en « bientôt » et
      non comme des offres disponibles.
- [ ] Mentions de sources et de dates sur les informations réglementaires
      (Campus France, procédures de visa) — elles changent chaque année.

### Technique

- [ ] `SITE_URL` et `home` / `siteurl` sur le domaine de production, en HTTPS.
- [ ] `wp search-replace` de l'URL de dev vers l'URL de prod effectué et vérifié.
- [ ] Certificat SSL actif, redirection HTTP → HTTPS.
- [ ] `WP_ENVIRONMENT_TYPE` à `production` et `WP_DEBUG` à `false`.
- [ ] `DISALLOW_FILE_EDIT` toujours à `true`.
- [ ] Elementor : régénérer le CSS (Elementor → Outils → Regénérer les fichiers).
- [ ] Vérifier qu'aucune requête ne part vers `fonts.googleapis.com` ni
      `fonts.gstatic.com` (onglet Réseau du navigateur) : les polices sont
      auto-hébergées, c'est une contrainte RGPD du projet.
- [ ] Comptes de développement supprimés ; mot de passe administrateur
      renouvelé ; téléversement SVG réservé aux administrateurs (déjà en place
      via `cc-svg-upload.php`).

### Formulaires et e-mails

- [ ] WP Mail SMTP configuré sur la boîte professionnelle (plus Mailpit).
- [ ] Envoi de test du formulaire « Je veux étudier en France » reçu et lisible.
- [ ] Notification et accusé de réception partant de l'adresse du domaine.
- [ ] Destinataires internes des notifications à jour.

### Mesure et sauvegarde

- [ ] Matomo relié au site de production, respect du refus de consentement.
- [ ] UpdraftPlus : sauvegarde planifiée vers un stockage distant, et une
      restauration testée au moins une fois.
- [ ] Wordfence actif, notifications envoyées à une adresse relevée.
- [ ] Rank Math : titres et métadescriptions renseignés sur les pages clés.

### Dernière vérification

- [ ] Parcours complet sur mobile : accueil → page destination → formulaire
      → confirmation, puis accueil → bouton WhatsApp.
- [ ] Logo et favicon corrects sur mobile et sur desktop.
- [ ] Aucune page 404 dans le menu ni dans le pied de page.
