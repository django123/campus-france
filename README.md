# Campus Connect – environnement de dev

## Démarrage
```bash
cp .env.example .env        # puis éditer les mots de passe
docker compose up -d
docker compose run --rm --entrypoint bash wpcli /scripts/install.sh
```

`--entrypoint bash` est nécessaire : l'entrypoint du service `wpcli` est `wp`,
pour que `docker compose run --rm wpcli <commande>` fonctionne (voir WP-CLI plus bas).

Sous Git Bash (Windows), préfixer par `MSYS_NO_PATHCONV=1` sinon `/scripts/install.sh`
est réécrit en chemin Windows et le script est introuvable.

- Site : http://localhost:8080  (admin : /wp-admin)
- phpMyAdmin : http://localhost:8081
- Mailpit (tous les mails sortants) : http://localhost:8025

Ces ports sont ceux de `.env.example` (`WP_PORT`, `PMA_PORT`, `MAIL_UI_PORT`) ; adaptez-les
si l'un est déjà pris. `WP_PORT` doit rester cohérent avec `SITE_URL` : après l'install,
changer de port impose aussi un `wp search-replace 'http://localhost:ancien' 'http://localhost:nouveau'`,
l'URL étant stockée en base.

## Après install.sh
1. Installer **Elementor Pro** (zip depuis le compte Elementor) et activer la licence.
2. Extensions > Elementor > Réglages > Fonctionnalités : activer Flexbox Container, désactiver ce qui n'est pas utilisé.
3. Appliquer la charte à Elementor :
   ```bash
   docker compose run --rm wpcli eval-file /scripts/configure-elementor.php
   ```
   Écrit les couleurs et polices globales à partir des jetons de
   `wp-content/themes/campus-connect/assets/brand/brand-tokens.css`, désactive les
   Google Fonts et régénère le CSS. Idempotent : à relancer après toute
   modification des jetons.
4. Mettre à jour Elementor : Outils > Regénérer CSS.

## Charte et contenus

- Les couleurs et les polices ont une source unique :
  `wp-content/themes/campus-connect/assets/brand/brand-tokens.css`.
- Poppins et Inter sont **auto-hébergées** (`assets/fonts/`) : aucun appel à
  Google Fonts, c'est une contrainte RGPD du projet.
- Tout contenu fictif doit porter la classe `.cc-placeholder`, qui le rend
  visuellement impossible à manquer. Le retrait de ces contenus est un point
  bloquant de `docs/checklist-mise-en-ligne.md`.
- Les coordonnées (WhatsApp) ne s'écrivent jamais en dur : voir
  `wp-content/mu-plugins/cc-config.php`, `cc_whatsapp_link()` et le shortcode
  `[cc_whatsapp pays="fr"]`, utilisable dans Elementor.
- **Avant toute mise en ligne** : dérouler `docs/checklist-mise-en-ligne.md`.
  Les numéros WhatsApp actuels sont des numéros de développement.

## WP-CLI
```bash
docker compose run --rm wpcli plugin list
docker compose run --rm wpcli db export /var/www/html/wp-content/backup.sql
```

## Ce qui est versionné
- `wp-content/themes/campus-connect/` : thème enfant (Hello Elementor)
- `wp-content/mu-plugins/` : CPT, taxonomies, helpers (étape 3)
- `scripts/` : installation et migrations

Le core, les plugins et les uploads ne sont PAS versionnés (volumes Docker).
Pour partager l'état du site (pages Elementor, réglages) : export DB + `wp search-replace` lors du déploiement.

## Reset complet
```bash
docker compose down -v
```
