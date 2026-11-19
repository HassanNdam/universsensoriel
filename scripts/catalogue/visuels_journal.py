"""Images des articles du journal : recadrage 3:2, WebP, noms lisibles.

    python visuels_journal.py <dossier scrape>

Sortie : docs/03-images-site/journal/ (importées dans la médiathèque par le seed).
"""
import os
import sys

from PIL import Image

import images
from visuels_site import recadrer, source

ICI = os.path.dirname(os.path.abspath(__file__))
SORTIE = os.path.join(os.path.dirname(os.path.dirname(ICI)), 'docs', '03-images-site', 'journal')

# nom de sortie : (fichier source, centre horizontal, centre vertical)
PHOTOS = {
    'parfum-flacon-notes-boisees': ('dark-nectar-parfum-homme-70-ml-2.jpg', 0.5, 0.45),
    'soin-visage-serum': ('rituel-peau-mature-6.jpg', 0.5, 0.5),
    'huile-de-baobab': ('huile-pure-de-baobab-100-naturelle-10-ml-2.png', 0.5, 0.5),
    'coffret-soins-peptilux': ('luxury-day-skin-care-set-2.png', 0.5, 0.5),
    'nettoyant-visage': ('rituel-peau-acneique-grasse-et-mixte-5.jpg', 0.5, 0.55),
    'collagene-a-boire': ('skinail-collagen-poudre-2.jpg', 0.5, 0.5),
    'encens-et-huiles-essentielles': ('dark-nectar-parfum-homme-70-ml-3.jpg', 0.5, 0.6),
    'aloe-vera': ('masque-cheveux-a-l-aloe-vera-enrichi-en-huiles-d-avocat-de-macadamia-e-2.jpg', 0.5, 0.5),
}


def main():
    scrape = sys.argv[1]
    os.makedirs(SORTIE, exist_ok=True)
    for nom, (fichier, cx, cy) in PHOTOS.items():
        im = recadrer(Image.open(source(scrape, fichier)).convert('RGB'), 3 / 2, cx, cy)
        if im.width > 1200:
            im = im.resize((1200, 800), Image.LANCZOS)
        images.enregistrer(im, os.path.join(SORTIE, nom + '.webp'), 80)
        print(nom, im.size)


if __name__ == '__main__':
    main()
