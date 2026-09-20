# Captures de contrôle

Captures pleine page du **styleguide**, à regénérer après toute modification
du design system.

| Fichier | Largeur | Hauteur rendue |
|---|---|---|
| `styleguide-1280.png` | 1280 px | 8817 px |
| `styleguide-375.png` | 375 px | 12490 px |

## Comment elles sont produites

Chrome en mode headless piloté par `puppeteer-core`, avec le Chrome déjà
installé sur le poste — aucun Chromium n'est téléchargé.

La page Styleguide étant **privée**, elle est publiée le temps de la capture
puis remise en privé. Conséquence à connaître : les captures sont prises en
visiteur anonyme, et deux choses n'y figurent donc pas, car elles ne
s'affichent qu'aux personnes pouvant éditer :

- les messages « aucun contenu à afficher » des composants filtrés ;
- les exemples de mise en forme rendus par `[cc_exemple]`.

Le script ouvre tous les `<details>` avant la capture, pour que les réponses
de la FAQ soient visibles.

## Ce qu'il faut y regarder

- Aucun débordement horizontal (vérifié par le script à chaque capture).
- Le filet vert au-dessus de chaque titre de section.
- Les cartes de packs : trois par ligne à 1280, empilées à 375, boutons
  alignés en bas, **aucune zone de prix**.
- La frise du parcours : alternance gauche/droite à 1280, colonne unique à 375.
- Les destinations : France seule en vert « Ouverte », les deux autres grisées.
- Le fond rayé jaune sur tout le contenu de démonstration.
