import { apparitions } from './apparitions.js';

import.meta.glob(['../images/**', '../fonts/**']);
import './langues.js';

document.documentElement.classList.add('js');

/* Tiroirs (menu mobile, filtres) et panneau de recherche : [data-ouvrir="id"] / [data-fermer="id"]. */
const ouverts = new Map();

function ouvrir(id, declencheur) {
  const cible = document.getElementById(id);
  if (!cible) return;
  cible.hidden = false;
  declencheur?.setAttribute('aria-expanded', 'true');
  ouverts.set(id, declencheur);
  if (cible.classList.contains('tiroir')) {
    document.body.classList.add('tiroir-ouvert');
  }
  (cible.querySelector('input[type="search"]') ?? cible.querySelector('[data-fermer]:not(.tiroir-voile)'))?.focus();
}

function fermer(id) {
  const cible = document.getElementById(id);
  if (!cible || cible.hidden) return;
  cible.hidden = true;
  const declencheur = ouverts.get(id);
  declencheur?.setAttribute('aria-expanded', 'false');
  declencheur?.focus();
  ouverts.delete(id);
  if (![...ouverts.keys()].some((cle) => document.getElementById(cle)?.classList.contains('tiroir'))) {
    document.body.classList.remove('tiroir-ouvert');
  }
}

document.addEventListener('click', (evenement) => {
  const bouton = evenement.target.closest('[data-ouvrir], [data-fermer]');
  if (!bouton) return;
  if (bouton.dataset.ouvrir) {
    const id = bouton.dataset.ouvrir;
    document.getElementById(id)?.hidden ? ouvrir(id, bouton) : fermer(id);
  } else {
    fermer(bouton.dataset.fermer);
  }
});

document.addEventListener('keydown', (evenement) => {
  if (evenement.key !== 'Escape') return;
  [...ouverts.keys()].forEach(fermer);
  document.querySelectorAll('[data-mega]').forEach((entree) => {
    entree.classList.add('est-fermee');
    entree.querySelector('a')?.blur();
  });
});

/* Méga-menu : Échap le ferme, le survol suivant le rouvre. */
document.querySelectorAll('[data-mega]').forEach((entree) => {
  entree.addEventListener('mouseenter', () => entree.classList.remove('est-fermee'));
  entree.addEventListener('focusin', () => entree.classList.remove('est-fermee'));
});

/* En-tête : ombre dès que la page défile. */
const entete = document.querySelector('[data-entete]');
if (entete) {
  const suivre = () => entete.classList.toggle('est-collee', window.scrollY > 40);
  suivre();
  window.addEventListener('scroll', suivre, { passive: true });
}

/* Onglets de la sélection (accueil), avec les flèches du clavier. */
document.querySelectorAll('[data-onglets]').forEach((bloc) => {
  const onglets = [...bloc.querySelectorAll('[role="tab"]')];
  const activer = (onglet, focus = false) => {
    onglets.forEach((autre) => {
      const actif = autre === onglet;
      autre.setAttribute('aria-selected', actif);
      autre.tabIndex = actif ? 0 : -1;
      document.getElementById(autre.getAttribute('aria-controls')).hidden = !actif;
    });
    if (focus) onglet.focus();
  };
  onglets.forEach((onglet, rang) => {
    onglet.addEventListener('click', () => activer(onglet));
    onglet.addEventListener('keydown', (evenement) => {
      const pas = { ArrowRight: 1, ArrowLeft: -1 }[evenement.key];
      if (pas) activer(onglets[(rang + pas + onglets.length) % onglets.length], true);
    });
  });
});

/* Tri de la boutique : envoi dès le choix. */
document.querySelectorAll('[data-envoi-auto]').forEach((champ) => {
  champ.addEventListener('change', () => champ.form.submit());
});

/* Galerie de la fiche produit. */
document.querySelectorAll('[data-galerie]').forEach((galerie) => {
  const vignettes = galerie.querySelectorAll('[data-image]');
  const visuels = galerie.querySelectorAll('[data-visuel]');
  vignettes.forEach((vignette) => {
    vignette.addEventListener('click', () => {
      vignettes.forEach((autre) => autre.toggleAttribute('aria-current', autre === vignette));
      vignette.setAttribute('aria-current', 'true');
      visuels.forEach((visuel) => {
        visuel.hidden = visuel.dataset.visuel !== vignette.dataset.image;
      });
    });
  });
});

/* Formats : le <select> de WooCommerce devient une rangée de pastilles, le <select> reste la source. */
document.querySelectorAll('.variations_form .variations select').forEach((liste) => {
  const rangee = document.createElement('div');
  rangee.className = 'formats';
  rangee.setAttribute('role', 'group');
  rangee.setAttribute('aria-label', liste.closest('tr')?.querySelector('label')?.textContent?.trim() || 'Format');

  const dessiner = () => {
    rangee.replaceChildren(
      ...[...liste.options]
        .filter((option) => option.value)
        .map((option) => {
          const bouton = document.createElement('button');
          bouton.type = 'button';
          bouton.textContent = option.textContent;
          bouton.disabled = option.disabled;
          bouton.setAttribute('aria-pressed', String(option.value === liste.value));
          bouton.addEventListener('click', () => {
            liste.value = option.value;
            liste.dispatchEvent(new Event('change', { bubbles: true }));
            window.jQuery?.(liste).trigger('change');
          });
          return bouton;
        }),
    );
  };

  liste.classList.add('est-remplace');
  liste.after(rangee);
  dessiner();
  liste.addEventListener('change', dessiner);
  // WooCommerce reconstruit les options après chaque choix : on redessine.
  window.jQuery?.(liste.form).on('woocommerce_update_variation_values', dessiner);
});

/* Carrousels d'avis : flèches et barre de progression, affichées seulement s'il y a de quoi défiler. */
document.querySelectorAll('[data-carrousel]').forEach((carrousel) => {
  const piste = carrousel.querySelector('[data-carrousel-piste]');
  const commandes = carrousel.querySelector('[data-carrousel-commandes]');
  const precedent = carrousel.querySelector('[data-carrousel-precedent]');
  const suivant = carrousel.querySelector('[data-carrousel-suivant]');
  const barre = carrousel.querySelector('[data-carrousel-barre]');
  if (!piste || !commandes) return;

  const pas = () => (piste.querySelector('.carrousel-element')?.getBoundingClientRect().width ?? piste.clientWidth) + parseFloat(getComputedStyle(piste).columnGap || 0);

  const mettreAJour = () => {
    const reste = piste.scrollWidth - piste.clientWidth;
    commandes.hidden = reste <= 2;
    precedent.disabled = piste.scrollLeft <= 2;
    suivant.disabled = piste.scrollLeft >= reste - 2;
    const visible = piste.clientWidth / piste.scrollWidth;
    barre.style.width = `${Math.max(visible, 0.08) * 100}%`;
    barre.style.transform = `translateX(${reste > 0 ? (piste.scrollLeft / reste) * ((1 - visible) / visible) * 100 : 0}%)`;
  };

  precedent.addEventListener('click', () => piste.scrollBy({ left: -pas(), behavior: 'smooth' }));
  suivant.addEventListener('click', () => piste.scrollBy({ left: pas(), behavior: 'smooth' }));
  piste.addEventListener('scroll', mettreAJour, { passive: true });
  piste.addEventListener('keydown', (evenement) => {
    const sens = { ArrowRight: 1, ArrowLeft: -1 }[evenement.key];
    if (!sens) return;
    evenement.preventDefault();
    piste.scrollBy({ left: sens * pas(), behavior: 'smooth' });
  });
  new ResizeObserver(mettreAJour).observe(piste);
  mettreAJour();
});

/* Avis coupés à cinq lignes : le bouton « Lire la suite » n'apparaît que si le texte dépasse. */
const avisLongs = () => document.querySelectorAll('[data-avis-suite]').forEach((bouton) => {
  const carte = bouton.closest('.avis');
  if (carte.classList.contains('est-deplie')) return;
  const texte = carte.querySelector('[data-avis-texte]');
  bouton.hidden = texte.scrollHeight <= texte.clientHeight + 2;
});
document.addEventListener('click', (evenement) => {
  const bouton = evenement.target.closest('[data-avis-suite]');
  if (!bouton) return;
  const deplie = bouton.closest('.avis').classList.toggle('est-deplie');
  bouton.textContent = deplie ? 'Réduire' : 'Lire la suite';
  bouton.setAttribute('aria-expanded', String(deplie));
});
document.fonts?.ready.then(avisLongs);
window.addEventListener('resize', avisLongs, { passive: true });
avisLongs();

/* Bandeau d'événement : compte à rebours jusqu'à la fin du dernier jour ; disparaît une fois l'échéance passée. */
document.querySelectorAll('[data-compte]').forEach((compte) => {
  const echeance = Date.parse(compte.dataset.compte);
  const cases = Object.fromEntries(['j', 'h', 'm', 's'].map((unite) => [unite, compte.querySelector(`[data-${unite}]`)]));
  const lu = compte.querySelector('.sr-only');
  const deux = (nombre) => String(nombre).padStart(2, '0');

  const battre = () => {
    const reste = Math.max(0, Math.floor((echeance - Date.now()) / 1000));
    if (!reste) {
      compte.hidden = true;
      clearInterval(minuterie);
      return;
    }
    const valeurs = {
      j: String(Math.floor(reste / 86400)),
      h: deux(Math.floor((reste % 86400) / 3600)),
      m: deux(Math.floor((reste % 3600) / 60)),
      s: deux(reste % 60),
    };
    Object.entries(valeurs).forEach(([unite, valeur]) => {
      const chiffre = cases[unite];
      if (!chiffre || chiffre.textContent === valeur) return;
      chiffre.textContent = valeur;
      // Le chiffre qui change tombe en place.
      chiffre.classList.remove('est-change');
      void chiffre.offsetWidth;
      chiffre.classList.add('est-change');
    });
    if (lu) lu.textContent = `${valeurs.j} jours et ${Number(valeurs.h)} heures`;
  };

  const minuterie = setInterval(battre, 1000);
  battre();
});

/* Apparitions au défilement (apparitions.js). */
apparitions();
