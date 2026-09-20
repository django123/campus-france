# Gabarits à construire dans Elementor

Ce qui suit se construit **à la main**, dans
**Modèles → Theme Builder**. Ces gabarits ne sont pas générés par code :
Elementor les stocke en base sous forme de JSON imbriqué, et un modèle produit
par script serait illisible et impossible à maintenir depuis l'interface.

Tout ce qui se répète d'une page à l'autre est déjà codé et exposé en
shortcode. Le travail dans Elementor consiste à **poser ces shortcodes et à
les mettre en page**, jamais à reconstruire un composant bloc par bloc.

> Pour poser un shortcode : widget **Shortcode** (rechercher « shortcode » dans
> le panneau des widgets), puis coller le code entre crochets.

Une page de contrôle existe pour vérifier le rendu de tous les composants
d'un coup : **/styleguide/** (page privée, il faut être connecté).

---

## 1. Header

**Modèles → Theme Builder → En-tête → Ajouter**

Nom suggéré : `Header — principal`

### Conditions d'affichage
| Réglage | Valeur |
|---|---|
| Inclure | **Tout le site** |

### Contenu
Une seule section, disposition en conteneur flex horizontal, alignement
vertical centré, largeur du contenu 1200px.

1. **Logo du site** — widget *Site Logo*.
   Lien vers l'accueil. Le logo est déjà réglé dans
   Apparence → Personnaliser (SVG de la charte), ne pas le téléverser à nouveau.
2. **Menu** — widget *Nav Menu*, menu **Menu principal**.
   - Menu mobile : activé, point de rupture **tablette**.
   - L'entrée « À propos » porte la classe `cc-menu-sans-lien` et pointe sur
     `#` : c'est un simple regroupement, ne pas lui chercher une page.
3. **Bouton de conversion** — widget *Button*.
   - Texte : `Je veux étudier en France`
   - Lien : la page **Je veux étudier en France**
   - Classe CSS : `cc-btn cc-btn--primaire`

> Le bouton WhatsApp flottant **ne se pose pas ici** : il est injecté sur
> toutes les pages par le thème. L'extension Click to Chat a été désactivée,
> ne pas la réactiver — elle ferait doublon.

### Header sticky
Si un header collant est souhaité : Réglages de la section → Avancé →
Effets de défilement → Sticky = Haut. Penser à vérifier que le bouton flottant
WhatsApp ne se superpose pas au menu mobile ouvert.

---

## 2. Footer

**Modèles → Theme Builder → Pied de page → Ajouter**

Nom suggéré : `Footer — principal`

### Conditions d'affichage
| Réglage | Valeur |
|---|---|
| Inclure | **Tout le site** |

### Contenu
Section avec la classe CSS `cc-section--nuit` (fond bleu nuit, texte clair —
contraste vérifié à 9,41:1). Quatre colonnes sur desktop, empilées sur mobile.

1. **Colonne 1 — identité**
   - Logo blanc : `assets/brand/logo-campus-connect-blanc.svg`
     (à téléverser dans la médiathèque, c'est la version prévue pour fond sombre).
   - Une phrase de présentation.
2. **Colonne 2 — navigation**
   - Widget *Nav Menu*, menu **Menu pied de page** (Blog et Contact).
3. **Colonne 3 — implantations**
   - France et Cameroun. Les coordonnées se saisissent dans
     **Réglages → Campus Connect**, pas en dur dans le widget.
4. **Colonne 4 — légal**
   - Liens vers Mentions légales, Politique de confidentialité, CGV.
   - Ces pages ne sont pas encore créées : les ajouter avant la mise en ligne
     (point bloquant n° 5 de la checklist).

Bandeau bas : mention de copyright et lien de gestion des cookies (Complianz
fournit un shortcode pour rouvrir la bannière).

---

## 3. Modèle de page standard

**Modèles → Theme Builder → Single → Ajouter → Page**

Nom suggéré : `Page — standard`

### Conditions d'affichage
| Réglage | Valeur |
|---|---|
| Inclure | **Pages** |
| Exclure | **Page d'accueil** |
| Exclure | La page **Je veux étudier en France** |

Les deux exclusions comptent : l'accueil et la page de conversion ont chacune
une mise en page propre, elles ne doivent pas hériter du gabarit générique.

### Contenu
1. En-tête de page : titre dynamique (*Page Title*), avec la classe
   `cc-titre-section` si le filet vert est souhaité.
2. Widget *Post Content* — c'est lui qui rend le contenu de chaque page.
3. Widget *Shortcode* : `[cc_cta_final]` dans une section
   `cc-section cc-section--bleue`.

---

## 4. Où poser quel shortcode

Les pages sont créées et vides. Voici ce que chacune attend.

### Accueil
Construire directement dans la page (pas de gabarit Theme Builder).

| Ordre | Bloc | Shortcode |
|---|---|---|
| 1 | Hero : titre, phrase, deux CTA | *(à composer dans Elementor)* |
| 2 | Ce qui nous distingue | `[cc_atouts titre="Ce qui nous distingue"]` |
| 3 | Réassurance (ancienneté, implantations) | *(texte Elementor)* |
| 4 | Double entrée pré/post-admission | *(deux boutons Elementor)* |
| 5 | Comment ça marche | `[cc_parcours compact="1" titre="Comment ça marche"]` |
| 6 | Ils nous ont fait confiance | `[cc_temoignages limite="3"]` |
| 7 | Nos partenaires | `[cc_partenaires]` |
| 8 | Rappel des CTA | `[cc_cta_final]` |

L'ancienneté se lit avec `cc_annees_experience()` : ne pas écrire « 7 ans » en
dur dans Elementor, ce serait faux au prochain anniversaire.

### Nos accompagnements
Deux onglets (widget *Tabs*), le premier ouvert par défaut :

| Onglet | Shortcode |
|---|---|
| Vous n'avez pas encore d'admission | `[cc_packs situation="pre"]` |
| Vous avez déjà une admission | `[cc_packs situation="post"]` |

**Aucune zone de prix**, et aucun emplacement réservé pour en accueillir un.

### Étudier en France
`[cc_parcours titre="Les étapes"]` puis `[cc_destinations]`.

### Nos formations
Liste des formations par domaine. Pas de shortcode dédié à ce jour :
utiliser un widget *Posts* filtré sur le type **Formations**, ou demander un
`[cc_formations]` si un rendu propre est nécessaire.

### Partenaires
Une section par type, avec son propre titre :

```
[cc_partenaires type="etablissement-france" titre="Établissements en France"]
[cc_partenaires type="etablissement-etranger" titre="Établissements à l'étranger"]
[cc_partenaires type="assurance-avi" titre="Assurance AVI"]
[cc_partenaires type="logement" titre="Logement"]
```

Une bande vide ne s'affiche pas : seuls les partenariats formalisés
apparaissent, et la grille s'adapte au nombre réel.

### FAQ
`[cc_faq titre="Questions fréquentes"]`

### Qui sommes-nous
Contenu rédigé, puis `[cc_cta_final]`.

### Je veux étudier en France
Formulaire Fluent Forms. Consentement RGPD **jamais pré-coché**.
Ne pas poser `[cc_cta_final]` : on est déjà au bout du tunnel.

---

## 5. Réglages Elementor à ne pas défaire

- **Couleurs et polices globales** : déjà réglées depuis les jetons de la
  charte par `scripts/configure-elementor.php`. Les modifier dans l'interface
  désynchronise le site de `brand-tokens.css`. Pour changer une couleur :
  modifier le jeton, puis relancer le script.
- **Google Fonts désactivées** (`elementor_google_font` à 0). C'est une
  contrainte RGPD : Poppins et Inter sont auto-hébergées. Ne pas réactiver.
- Après toute modification de gabarit : **Elementor → Outils → Regénérer les
  fichiers CSS**.

---

## 6. Ce qu'il ne faut pas faire dans Elementor

- **Recopier un composant** d'une page à l'autre. S'il apparaît deux fois,
  c'est un shortcode — sinon il faudra le corriger à deux endroits.
- **Écrire une couleur en dur**. Utiliser les couleurs globales, qui viennent
  des jetons de la charte.
- **Poser un prix**, où que ce soit. Règle du projet.
- **Écrire un numéro WhatsApp en dur**. Utiliser `[cc_whatsapp]`, ou
  `[cc_whatsapp url_seule="1"]` pour alimenter le champ lien d'un bouton.
- **Promettre un résultat** : « visa garanti », « admission garantie »,
  « 100 % de réussite ». Interdit, y compris dans un témoignage.
