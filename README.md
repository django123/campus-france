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
4. Poser la structure du site (langue, pages, menus, termes) :
   ```bash
   docker compose run --rm wpcli eval-file /scripts/structure.php
   ```
5. Poser le contenu de démonstration, facultatif mais utile pour maquetter :
   ```bash
   docker compose run --rm wpcli eval-file /scripts/seed.php
   docker compose run --rm wpcli eval-file /scripts/seed.php supprimer   # retrait
   ```
6. Mettre à jour Elementor : Outils > Regénérer CSS.

Les trois scripts sont idempotents : les relancer ne crée pas de doublon.
`structure.php` ne touche jamais au contenu rédigé dans les pages, il ne
reconstruit que les menus.

## Design system et composants

Tout élément répété sur plusieurs pages est codé une fois et exposé en
shortcode. Elementor sert à la mise en page, pas à dupliquer des composants.

| Shortcode | Rend |
|---|---|
| `[cc_atouts]` | Les 4 points forts (Réglages → Campus Connect) |
| `[cc_parcours compact="1"]` | La frise des 8 étapes |
| `[cc_packs situation="pre\|post"]` | La grille des packs, sans aucun tarif |
| `[cc_temoignages limite="3"]` | Uniquement ceux avec accord écrit |
| `[cc_partenaires type="..."]` | Uniquement les partenariats formalisés |
| `[cc_destinations]` | France cliquable, Canada et Allemagne grisées |
| `[cc_faq]` | Accordéon natif `details`/`summary` |
| `[cc_cta_final]` | Le rappel des deux CTA |

- CSS : le socle est dans `assets/css/components.css` (importé par `style.css`),
  les styles propres à un composant dans `assets/css/composants/` et **mis en
  file seulement quand le shortcode est utilisé**.
- Gabarits surchargeables : `template-parts/cc-*.php` dans le thème enfant.
- Les filtres sur l'accord écrit et le partenariat formalisé sont des **règles**,
  pas des options : aucun attribut de shortcode ne les contourne.
- Le bouton WhatsApp flottant est intégré au thème (Click to Chat est désactivé).
- Page de contrôle visuel : **/styleguide/** (privée, connexion requise).
  Captures pleine page en 1280 et 375 : `cd scripts && npm install && node captures.js`.
  Les PNG ne sont pas versionnés, voir `docs/captures/README.md`.
- Ce qui reste à construire à la main dans Elementor :
  `docs/gabarits-elementor.md`.

Contrastes vérifiés WCAG AA sur toutes les combinaisons texte/fond. Deux
conséquences : le vert ne porte jamais de texte courant, et le bouton WhatsApp
a un libellé en encre et non en blanc (blanc sur le vert WhatsApp = 1,98:1).

## Contenus

| Type | Taxonomie | Champs ACF |
|---|---|---|
| `pack` | `situation` (pré/post-admission) | sous-titre, prestations, ordre — **aucun prix** |
| `temoignage` | — | prénom, établissement, ville, citation, accord écrit |
| `partenaire` | `type_partenaire` | logo, site web, partenariat formalisé |
| `formation` | `domaine` | — |
| `destination` | — | statut (active / bientôt), texte d'accroche |

CPT, taxonomies et groupes de champs vivent dans `wp-content/mu-plugins/` :
ils doivent survivre à un changement de thème. Les groupes ACF sont en JSON
versionné (`mu-plugins/acf-json/`), jamais saisis dans l'interface.

ACF est installé en version **gratuite** : pas de répéteur, pas de page
d'options. Les prestations d'un pack sont donc une zone de texte, une par
ligne, lue par `cc_pack_prestations()` ; les réglages globaux passent par
Réglages → Campus Connect (`mu-plugins/cc-reglages.php`). Passer en ACF Pro
ne demanderait de retoucher que ces deux points, pas les gabarits.

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
