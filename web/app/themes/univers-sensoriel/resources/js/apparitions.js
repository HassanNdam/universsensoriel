/*
 * Apparitions au défilement (styles : resources/css/apparitions.css).
 * Un seul observateur pour toute la page ; chaque bloc entre une fois, puis n'est plus surveillé.
 * Les blocs déjà à l'écran au chargement ne sont pas touchés : ils s'affichent tout de suite.
 */

/* Blocs qui montent en fondu. Ne pas mettre un bloc et son parent dans la liste : ils monteraient deux fois. */
const MONTENT = [
  '.section-tete',
  '.section-suite',
  '.grille-produits > *',
  '.univers-grille > *',
  '.familles-photos > *',
  '.familles-grille > .pastilles',
  '.journal-grille > *',
  '.carrousel-element',
  '.avis-note',
  '.maison-texte',
  '.honneur-image',
  '.honneur-texte',
  '.rendez-vous-grille > *',
  '.garanties li',
  '.rayon-note',
].join(',');

/* Photos dévoilées comme un rideau. */
const DEVOILENT = '.maison-image';

const ECART = 90; // ms entre deux cartes qui entrent ensemble
const ECARTS_MAX = 5;

export function apparitions() {
  if (!window.matchMedia('(prefers-reduced-motion: no-preference)').matches || !('IntersectionObserver' in window)) return;

  const finir = (bloc) => {
    bloc.classList.remove('apparition', 'devoile', 'est-visible');
    bloc.style.removeProperty('--delai');
  };

  const observateur = new IntersectionObserver((entrees) => {
    // Bloc déjà dépassé (saut par une ancre, défilement très rapide) : affiché tout de suite, sans animation.
    entrees
      .filter((entree) => !entree.isIntersecting && entree.boundingClientRect.bottom <= 0)
      .forEach((entree) => {
        observateur.unobserve(entree.target);
        finir(entree.target);
      });

    entrees
      .filter((entree) => entree.isIntersecting)
      // Les blocs qui entrent ensemble se suivent de haut en bas, de gauche à droite.
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top || a.boundingClientRect.left - b.boundingClientRect.left)
      .forEach((entree, rang) => {
        const bloc = entree.target;
        observateur.unobserve(bloc);
        const delai = Math.min(rang, ECARTS_MAX) * ECART;
        if (bloc.classList.contains('apparition')) bloc.style.setProperty('--delai', `${delai}ms`);
        bloc.classList.add('est-visible');
        // Une fois l'entrée jouée, le bloc retrouve ses propres transitions (survol des cartes…).
        setTimeout(() => finir(bloc), delai + 2100);
      });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

  const bas = window.innerHeight;
  const preparer = (bloc, classe) => {
    const cadre = bloc.getBoundingClientRect();
    // Visible au chargement, ou caché (onglet fermé) : on n'y touche pas.
    if (!cadre.height || cadre.top < bas) return;
    bloc.classList.add(classe);
    observateur.observe(bloc);
  };

  document.querySelectorAll(DEVOILENT).forEach((bloc) => preparer(bloc, 'devoile'));
  document.querySelectorAll(MONTENT).forEach((bloc) => preparer(bloc, 'apparition'));
}
