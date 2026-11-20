/*
 * Langues automatiques (app/langues.php) : le choix du visiteur est gardé dans le cookie « us_langue ».
 * La traduction de Google n'est chargée que pour lui, sur les pages en français ou en anglais, jamais sur le
 * panier ni la commande (blocs React que la traduction casserait : body.sans-traduction-auto).
 */
const COOKIE = 'us_langue';
const UN_AN = 365 * 24 * 3600;

const lire = (nom) => document.cookie.split('; ').find((c) => c.startsWith(`${nom}=`))?.slice(nom.length + 1) ?? '';
const ecrire = (nom, valeur, duree) => {
  document.cookie = `${nom}=${valeur}; path=/; max-age=${duree}; SameSite=Lax`;
};

/* Cookie de Google, posé par lui sur l'hôte et sur le domaine : effacé partout. */
function oublierGoogle() {
  const hote = location.hostname;
  const domaines = ['', hote, `.${hote}`, `.${hote.split('.').slice(-2).join('.')}`];
  domaines.forEach((domaine) => {
    document.cookie = `googtrans=; path=/; max-age=0${domaine ? `; domain=${domaine}` : ''}`;
  });
}

document.addEventListener('click', (evenement) => {
  const lien = evenement.target.closest('[data-langue]');
  if (!lien) return;
  if ('auto' in lien.dataset) {
    evenement.preventDefault();
    ecrire(COOKIE, lien.dataset.langue, UN_AN);
    oublierGoogle();
    location.href = lien.href;
  } else if (lire(COOKIE)) {
    ecrire(COOKIE, '', 0);
    oublierGoogle();
    if (lien.getAttribute('aria-current')) {
      evenement.preventDefault();
      location.reload();
    }
  }
});

const choisie = lire(COOKIE);
const lienChoisi = choisie && document.querySelector(`[data-langue="${CSS.escape(choisie)}"][data-auto]`);

if (lienChoisi && !document.body.classList.contains('sans-traduction-auto')) {
  document.querySelectorAll('[data-langue]').forEach((lien) => {
    if (lien.dataset.langue === choisie) lien.setAttribute('aria-current', 'true');
    else lien.removeAttribute('aria-current');
  });
  document.querySelectorAll('[data-langue-code]').forEach((el) => {
    el.textContent = choisie.toUpperCase();
  });
  document.querySelectorAll('[data-langue-nom]').forEach((el) => {
    el.textContent = lienChoisi.textContent.trim();
  });

  const source = (document.documentElement.lang || 'fr').slice(0, 2);
  document.cookie = `googtrans=/${source}/${choisie}; path=/`;
  window.usTraduction = () => {
    // eslint-disable-next-line no-undef
    new google.translate.TranslateElement({ pageLanguage: source, autoDisplay: false }, 'us-traduction');
  };
  const conteneur = document.createElement('div');
  conteneur.id = 'us-traduction';
  conteneur.hidden = true;
  document.body.append(conteneur);
  const script = document.createElement('script');
  script.src = 'https://translate.google.com/translate_a/element.js?cb=usTraduction';
  script.async = true;
  document.head.append(script);
} else if (!choisie && document.cookie.includes('googtrans=')) {
  oublierGoogle();
}
