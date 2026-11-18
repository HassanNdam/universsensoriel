import.meta.glob(['../images/**', '../fonts/**']);

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
