/**
 * Captures de contrôle du styleguide.
 *
 *   cd scripts && npm install && node captures.js
 *
 * Pilote le Chrome déjà installé sur le poste via puppeteer-core : aucun
 * Chromium n'est téléchargé.
 *
 * La page Styleguide étant privée, le script la publie le temps des captures
 * puis la remet en privé. La remise en privé est dans un `finally` : même si
 * Chrome échoue, la page ne reste jamais publique.
 *
 * Conséquence à connaître : les captures sont prises en visiteur anonyme. Les
 * messages « rien à afficher » et les exemples rendus par [cc_exemple] ne s'y
 * trouvent donc pas, puisqu'ils ne s'affichent qu'aux personnes pouvant éditer.
 */

const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

const RACINE = path.resolve(__dirname, '..');
const SORTIE = path.join(RACINE, 'docs', 'captures');
const URL_BASE = process.env.CC_URL || 'http://localhost:8090';

const FORMATS = [
  { nom: 'styleguide-1280.png', width: 1280, height: 900 },
  { nom: 'styleguide-375.png', width: 375, height: 800 },
];

/** Emplacements habituels de Chrome et d'Edge sous Windows et macOS. */
const CANDIDATS = [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
];

function trouverNavigateur() {
  if (process.env.CC_CHROME) return process.env.CC_CHROME;

  const trouve = CANDIDATS.find((p) => fs.existsSync(p));
  if (!trouve) {
    throw new Error(
      'Aucun Chrome ni Edge trouvé. Renseigner le chemin dans la variable CC_CHROME.'
    );
  }
  return trouve;
}

/** Exécute une commande WP-CLI dans le conteneur. */
function wp(args) {
  return execFileSync(
    'docker',
    ['compose', 'run', '--rm', 'wpcli', ...args],
    { cwd: RACINE, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'], env: { ...process.env, MSYS_NO_PATHCONV: '1' } }
  ).trim();
}

(async () => {
  fs.mkdirSync(SORTIE, { recursive: true });

  const navigateur = trouverNavigateur();
  console.log(`Navigateur : ${navigateur}`);

  /*
   * L'identifiant de la page n'est pas figé : on le retrouve par son slug.
   * `wp post list --name=` ne remonte pas une page privée, d'où get_page_by_path.
   */
  const id = wp(['eval', '$p = get_page_by_path("styleguide", OBJECT, "page"); echo $p ? $p->ID : "";']);

  if (!id) {
    throw new Error('Page « styleguide » introuvable. Lancer d\'abord scripts/structure.php.');
  }

  let navigateurOuvert = null;

  try {
    wp(['post', 'update', id, '--post_status=publish']);

    navigateurOuvert = await puppeteer.launch({
      executablePath: navigateur,
      headless: 'new',
      args: ['--no-sandbox', '--disable-dev-shm-usage', '--hide-scrollbars'],
    });

    for (const format of FORMATS) {
      const page = await navigateurOuvert.newPage();
      await page.setViewport({ width: format.width, height: format.height, deviceScaleFactor: 1 });
      await page.goto(`${URL_BASE}/styleguide/`, { waitUntil: 'networkidle2', timeout: 60000 });

      // Déplie les accordéons, sinon les réponses de la FAQ n'apparaissent pas.
      await page.evaluate(() => {
        document.querySelectorAll('details').forEach((d) => { d.open = true; });
      });
      await new Promise((r) => setTimeout(r, 400));

      await page.screenshot({ path: path.join(SORTIE, format.nom), fullPage: true });

      const mesures = await page.evaluate(() => ({
        hauteur: document.documentElement.scrollHeight,
        debordement: document.documentElement.scrollWidth > window.innerWidth,
      }));

      console.log(
        `${format.nom}  ${format.width}x${mesures.hauteur}px  ` +
        `débordement horizontal : ${mesures.debordement ? 'OUI — à corriger' : 'non'}`
      );

      await page.close();
    }
  } finally {
    if (navigateurOuvert) await navigateurOuvert.close();
    wp(['post', 'update', id, '--post_status=private']);
    console.log('Styleguide remise en privé.');
  }
})().catch((e) => {
  console.error('Échec :', e.message);
  process.exit(1);
});
