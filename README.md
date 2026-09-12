# Campus Connect – environnement de dev

## Démarrage
```bash
cp .env.example .env        # puis éditer les mots de passe
docker compose up -d
docker compose run --rm wpcli bash /scripts/install.sh
```

- Site : http://localhost:8080  (admin : /wp-admin)
- phpMyAdmin : http://localhost:8081
- Mailpit (tous les mails sortants) : http://localhost:8025

## Après install.sh
1. Installer **Elementor Pro** (zip depuis le compte Elementor) et activer la licence.
2. Extensions > Elementor > Réglages > Fonctionnalités : activer Flexbox Container, désactiver ce qui n'est pas utilisé.
3. Mettre à jour Elementor : Outils > Regénérer CSS.

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
