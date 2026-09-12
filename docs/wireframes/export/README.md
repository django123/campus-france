# Export PDF des wireframes

`Campus-Connect-wireframes-v1.pdf` est le document à transmettre au client.
Les PDF numérotés sont les vues unitaires qui le composent.

## Contenu

| Ordre | Vue | Largeur |
|---|---|---|
| 1–2 | Accueil | 1280 px / 375 px |
| 3–4 | Accompagnements (packs), onglet « pas encore d'admission » | 1280 px / 375 px |
| 5–6 | Accompagnements (packs), onglet « déjà une admission » | 1280 px / 375 px |
| 7–8 | Comment ça marche | 1280 px / 375 px |
| 9–10 | Je veux étudier en France | 1280 px / 375 px |
| 11–12 | Partenaires | 1280 px / 375 px |

Les vues 5–6 ne figuraient pas dans la commande initiale. Elles ont été
ajoutées parce que la page packs masque la moitié de l'offre derrière son
second onglet : sans elles, trois packs sur six seraient absents du PDF.

Chaque vue est **une seule page PDF à hauteur réelle**, pas un découpage en
feuilles A4 : une maquette web se lit d'un défilement, la pagination couperait
les blocs en deux.

## Régénérer

L'export n'a pas de dépendance versionnée dans ce dépôt — il est produit à la
demande, hors arborescence, pour ne pas y installer de `node_modules`.

Prérequis : Node 18+, et Google Chrome installé.

```bash
mkdir /tmp/wf-export && cd /tmp/wf-export
npm init -y
npm install puppeteer-core pdf-lib
# puis le script d'export, qui pilote le Chrome local :
#   - viewport 1280 et 375, media type "screen"
#   - page.pdf({ width, height: scrollHeight, printBackground: true })
#   - assemblage avec pdf-lib dans l'ordre du tableau ci-dessus
node export.mjs
```

Le chemin de Chrome est à adapter :
`C:/Program Files/Google/Chrome/Application/chrome.exe` sous Windows.

## Annotations

Les 56 pastilles rouges sont incluses dans le PDF. Elles sont numérotées par
page, à partir de 1, et se désignent par « page + numéro » (« Accueil n° 6 »).
Les annotations n° 1 et les deux dernières de chaque page portent sur le menu
et le footer : elles sont communes à toutes les pages.
