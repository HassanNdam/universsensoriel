"""Visuels du thème (accueil, univers, bandeaux) : recadrage, WebP, noms lisibles.

    python visuels_site.py <dossier scrape>

Sortie : web/app/themes/univers-sensoriel/resources/images/site/
Seules des photos fournisseur sont utilisées (aucune image générée de l'ancien site).
"""
import glob
import os
import sys

from PIL import Image

import images

ICI = os.path.dirname(os.path.abspath(__file__))
SORTIE = os.path.join(os.path.dirname(os.path.dirname(ICI)), 'web', 'app', 'themes', 'univers-sensoriel', 'resources', 'images', 'site')

# nom de sortie : (fichier source, ratio largeur/hauteur ou None, centre horizontal, centre vertical, largeur max)
PHOTOS = {
    'encens-bois-de-santal-parfum': ('dark-nectar-parfum-homme-70-ml-3.jpg', 4 / 5, 0.5, 0.5, 1080),
    'extrait-de-parfum-homme-notes-boisees': ('dark-nectar-parfum-homme-70-ml-2.jpg', 4 / 5, 0.52, 0.5, 1080),
    'coffret-soins-visage-peptilux': ('luxury-day-skin-care-set-2.png', 4 / 5, 0.42, 0.5, 1080),
    'huile-vegetale-amande-douce': ('huile-pure-damande-douce-100-ml-100-naturelle-2.png', 4 / 5, 0.5, 0.5, 1080),
    'huile-de-baobab-fruit': ('huile-pure-de-baobab-100-naturelle-10-ml-2.png', 3 / 2, 0.5, 0.5, 1248),
    'parfum-d-ambiance-diffuseur-salon': ('magnolia-bouquet-3000-ml-8.jpg', 4 / 5, 0.5, 0.5, 1080),
    'parfum-luxury-fleurs-et-epices': ('baccarat-rouge-540-maison-francis-kurkdjian-2.jpg', 3 / 2, 0.5, 0.5, 1200),
    'collection-mytologik-bustes': ('pack-complet-mytologik-embrace-your-inner-god-5.jpg', 4 / 3, 0.5, 0.5, 1200),
    'bois-de-cedre-matiere-premiere': ('zeta-morph-3.jpg', 1, 0.5, 0.5, 1080),
    'fleur-de-lys-matiere-premiere': ('zeta-morph-4.jpg', 1, 0.5, 0.5, 1080),
    'cacao-matiere-premiere': ('goddess-burberry-3.jpg', 1, 0.5, 0.5, 1080),
    'gingembre-matiere-premiere': ('goddess-burberry-4.jpg', 1, 0.5, 0.5, 1080),
    'aloe-vera-feuilles': ('masque-cheveux-a-l-aloe-vera-enrichi-en-huiles-d-avocat-de-macadamia-e-2.jpg', 1, 0.5, 0.5, 1080),
    'serum-visage-rituel': ('rituel-peau-acneique-grasse-et-mixte-5.jpg', 4 / 5, 0.5, 0.5, 1080),
    'brume-parfumee-fleurs': ('lush-cloud-white-petals-2.jpg', 4 / 5, 0.5, 0.5, 1080),
}
DETOURES = {
    'flacon-extrait-de-parfum-femme': 'absolutely-parfum-femme-010-1.jpg',
    'flacon-extrait-de-parfum-homme': 'dark-nectar-parfum-homme-70-ml-1.jpg',
    'flacon-parfum-collection-luxury': 'baccarat-rouge-540-maison-francis-kurkdjian-1.jpg',
}


def source(scrape, nom):
    trouves = glob.glob(os.path.join(scrape, '03-images', 'produits', '*', nom))
    if not trouves:
        raise SystemExit(f'Source introuvable : {nom}')
    return trouves[0]


def recadrer(im, ratio, cx, cy):
    if not ratio:
        return im
    l, h = im.size
    if l / h > ratio:
        nl = round(h * ratio)
        x = min(max(0, round(l * cx - nl / 2)), l - nl)
        return im.crop((x, 0, x + nl, h))
    nh = round(l / ratio)
    y = min(max(0, round(h * cy - nh / 2)), h - nh)
    return im.crop((0, y, l, y + nh))


def main():
    scrape = sys.argv[1]
    os.makedirs(SORTIE, exist_ok=True)
    for nom, (fichier, ratio, cx, cy, largeur) in PHOTOS.items():
        im = recadrer(Image.open(source(scrape, fichier)).convert('RGB'), ratio, cx, cy)
        if im.width > largeur:
            im = im.resize((largeur, round(im.height * largeur / im.width)), Image.LANCZOS)
        images.enregistrer(im, os.path.join(SORTIE, nom + '.webp'), 80)
        print(nom, im.size)
    for nom, fichier in DETOURES.items():
        cible = os.path.join(SORTIE, nom + '.webp')
        if os.path.exists(cible):
            continue
        coupe = images.detourer(Image.open(source(scrape, fichier)))
        images.enregistrer(coupe, cible, 88)
        print(nom, coupe.size)


if __name__ == '__main__':
    main()
