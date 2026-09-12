#!/usr/bin/env bash
# Installation initiale de WordPress + plugins. À lancer UNE fois :
#   docker compose run --rm wpcli bash /scripts/install.sh
# (les variables viennent du .env via docker compose)
set -euo pipefail

: "${SITE_URL:=http://localhost:8080}"
: "${SITE_TITLE:=Campus Connect}"
: "${ADMIN_USER:=admin_cc}"
: "${ADMIN_PASSWORD:?ADMIN_PASSWORD manquant dans .env}"
: "${ADMIN_EMAIL:=dev@example.com}"

cd /var/www/html

if ! wp core is-installed 2>/dev/null; then
  wp core install \
    --url="$SITE_URL" --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASSWORD" \
    --admin_email="$ADMIN_EMAIL" --skip-email
fi

# Langue et réglages de base
wp language core install fr_FR --activate
wp option update timezone_string "Europe/Paris"
wp option update date_format "j F Y"
wp option update permalink_structure "/%postname%/"
wp option update blog_public 0              # pas d'indexation en local
wp option update blogdescription "Votre passerelle de confiance vers l'international"

# Thème parent + thème enfant
wp theme install hello-elementor
wp theme activate campus-connect

# Plugins gratuits (wordpress.org)
wp plugin install --activate \
  elementor \
  fluentform \
  polylang \
  advanced-custom-fields \
  custom-post-type-ui \
  seo-by-rank-math \
  complianz-gdpr \
  wp-mail-smtp \
  updraftplus \
  wordfence \
  click-to-chat-for-whatsapp \
  matomo

# Nettoyage du contenu par défaut
wp post delete 1 2 --force || true
wp plugin delete hello akismet || true
wp theme delete twentytwentyfour twentytwentyfive twentytwentysix || true

echo
echo "OK. Reste à installer manuellement Elementor Pro (zip) via Extensions > Ajouter."
echo "Admin : $SITE_URL/wp-admin  |  user : $ADMIN_USER"
