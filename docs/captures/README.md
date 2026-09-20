# Captures de contrôle

Captures pleine page du **styleguide**, à regénérer après toute modification
du design system.

Les PNG ne sont **pas versionnés** : ils pèsent environ 2 Mo à eux deux et
changent à chaque retouche de CSS. Ce dossier ne contient donc que ce fichier
tant que les captures n'ont pas été produites.

## Régénérer

```bash
cd scripts
npm install     # la première fois seulement
node captures.js
```

Le script pilote le Chrome (ou l'Edge) déjà installé sur le poste via
`puppeteer-core` : aucun Chromium n'est téléchargé. Si le navigateur est
ailleurs que dans les emplacements habituels, renseigner son chemin :

```bash
CC_CHROME="/chemin/vers/chrome" node captures.js
```

L'URL du site se règle par `CC_URL` (par défaut `http://localhost:8090`).

Sortie attendue :

```
styleguide-1280.png  1280x8817px  débordement horizontal : non
styleguide-375.png   375x12490px  débordement horizontal : non
Styleguide remise en privé.
```

## Ce que fait le script

La page Styleguide est **privée**. Le script la publie le temps des captures
puis la remet en privé — la remise en privé est dans un `finally`, donc elle a
lieu même si Chrome échoue. Vérifié en conditions d'échec.

Conséquence à connaître : les captures sont prises **en visiteur anonyme**.
Deux choses n'y figurent donc pas, car elles ne s'affichent qu'aux personnes
pouvant éditer :

- les messages « rien à afficher » des composants filtrés
  (témoignages sans accord écrit, partenaires sans convention) ;
- les exemples de mise en forme rendus par `[cc_exemple]`.

Les `<details>` sont ouverts avant la capture, pour que les réponses de la FAQ
soient visibles.

## Ce qu'il faut y regarder

- Aucun débordement horizontal — le script le signale à chaque capture.
- Le filet vert au-dessus de chaque titre de section.
- Les cartes de packs : trois par ligne à 1280, empilées à 375, boutons
  alignés en bas, **aucune zone de prix**.
- La frise du parcours : alternance gauche/droite à 1280, colonne unique à 375.
- Les destinations : France seule en vert « Ouverte », les deux autres grisées
  et non cliquables.
- Le fond rayé jaune sur tout le contenu de démonstration.
