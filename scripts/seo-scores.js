/**
 * Relevé des notes Yoast, étape 2 : à coller dans la console du navigateur, sur l'écran de modification
 * d'une fiche produit publiée (Yoast y est chargé avec son moteur d'analyse).
 *
 * Yoast ne calcule ses notes que dans l'éditeur, page par page, à l'enregistrement : tant qu'une page n'a pas été
 * ouverte puis enregistrée, la liste de l'admin affiche « Non disponible ». Ce script fait le même calcul en série,
 * avec le moteur de Yoast lui-même (YoastSEO.analysis.worker) et le même contenu que l'éditeur du thème
 * (le <main> de la page publiée, voir app/seo.php) : la note relevée est celle que l'éditeur affiche.
 *
 *   1. php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/seo-export.php
 *   2. coller ce script dans la console ; quand il a fini, il copie le résultat dans le presse-papiers
 *   3. coller le résultat dans scripts/seo-scores.json, puis : composer seo
 *   4. supprimer web/app/uploads/seo-export.json
 *
 * À refaire après une retouche des textes, des titres ou des requêtes cibles (compter 3 minutes pour 400 pages).
 */
(async () => {
  const base = YoastSEO.analysis.collectData()._attributes;
  const liste = await fetch('/app/uploads/seo-export.json?' + Date.now()).then((r) => r.json());
  const mesure = document.createElement('canvas').getContext('2d');
  mesure.font = '20px Arial'; // la police avec laquelle Yoast mesure la largeur d'un titre
  const scores = {};
  const erreurs = [];

  for (const e of liste) {
    try {
      const html = await fetch(e.url + (e.url.includes('?') ? '&' : '?') + 'us-analyse=' + Date.now(), { credentials: 'omit' }).then((r) => r.text());
      const principal = new DOMParser().parseFromString(html, 'text/html').querySelector('main');
      if (!principal) {
        erreurs.push(e.cle + ' : pas de <main>');
        continue;
      }
      principal.querySelectorAll('script, style, noscript, template, form, svg').forEach((x) => x.remove());
      const attributs = Object.assign({}, base, {
        textTitle: e.nom,
        keyword: e.motcle,
        synonyms: '',
        description: e.description,
        title: e.titre,
        slug: e.slug,
        permalink: e.url.replace(/\/$/, ''),
        titleWidth: Math.round(mesure.measureText(e.titre).width),
        isFrontPage: e.cle === 'accueil',
        wpBlocks: [],
      });
      const r = await YoastSEO.analysis.worker.analyze(new yoast.analysis.Paper(principal.innerHTML, attributs));
      (scores[e.groupe] = scores[e.groupe] || {})[e.cle] = [r.result.seo[''].score, r.result.readability.score];
    } catch (erreur) {
      erreurs.push(e.cle + ' : ' + erreur);
    }
  }

  console.log(erreurs.length ? erreurs : 'Aucune erreur', scores);
  window.usScores = scores;
  try {
    await navigator.clipboard.writeText(JSON.stringify(scores));
    console.log('Notes copiées dans le presse-papiers.');
  } catch (e) {
    console.log('Copie refusée : copy(JSON.stringify(window.usScores))');
  }
})();
