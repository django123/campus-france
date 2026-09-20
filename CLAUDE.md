# Campus Connect – site WordPress (campusconnect.fr)

Site vitrine WordPress pour une agence d'accompagnement aux études en France.
Objectif : conversion vers le formulaire "Je veux étudier en France" et WhatsApp.

## Stack
- WordPress 6 / PHP 8.3 en Docker (voir docker-compose.yml, README.md)
- Thème : Hello Elementor + thème enfant `wp-content/themes/campus-connect`
- Elementor Pro (installé manuellement), Fluent Forms, Polylang, ACF, CPT UI,
  Rank Math, Complianz, WP Mail SMTP, UpdraftPlus, Wordfence, Click to Chat, Matomo
- CPT et taxonomies dans `wp-content/mu-plugins/` (pas dans le thème)
- WP-CLI : `docker compose run --rm wpcli <commande>`

## Règles
- Aucun prix affiché sur le site (tarifs internes uniquement).
- Interdit dans tout contenu : "visa garanti", "admission garantie", "100% de réussite",
  "dossier infaillible" ou toute promesse de résultat.
- Français, mobile-first, RGPD (consentement jamais pré-coché).
- Anticiper Canada / Allemagne : CPT `destination` avec statut actif / bientôt.
- Ne jamais inventer de clés d'option ou de slugs de plugins tiers : vérifier via WP-CLI.