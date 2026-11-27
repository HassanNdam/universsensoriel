"""Nouveautés, deuxième lot (27/11/2026) : maquillage des lèvres, fichier « nouveautés produits chogan 2.xlsx ».

Même traitement que le premier lot (scripts/catalogue/nouveautes.py, dont on reprend les fonctions) :
- entrées (scripts/catalogue/nouveautes-2/) : excel.json (le fichier, prix client seulement) et fiches-chogan.json
  (les pages produits, relevées dans le navigateur) ;
- sortie : scripts/catalogue/nouveautes-2/produits.json et les photos dans docs/02-images-produits/nouveautes/.

    python scripts/catalogue/nouveautes2.py

Import : App\\importer_produits (seed en local ; en ligne, mise à jour « nouveautes-2026-11-b » depuis l'administration,
dossier uploads/import/nouveautes-2026-11-b/). Chaque teinte a son propre terme (« lipstick-glossy-cherry ») : plusieurs
noms existent déjà pour d'autres produits (Red Velvet, Watermelon) ou reviennent dans ce lot (Cherry, Magenta).
"""
import json
import os
import re
import sys

ICI = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, ICI)
import nouveautes as lot1  # noqa: E402

DONNEES = os.path.join(ICI, 'nouveautes-2')
FICHES = json.load(open(os.path.join(DONNEES, 'fiches-chogan.json'), encoding='utf-8'))
EXCEL = {l['ligne']: l for l in json.load(open(os.path.join(DONNEES, 'excel.json'), encoding='utf-8'))}
M = lot1.MAQUILLAGE
T = 'Teinte'

# Gamme : « Make-up », le nom de la ligne de maquillage du fabricant (catalogue « Make-up »).
PRODUITS = [
    dict(cle='lipstick-glossy', nom='Lipstick Glossy', sous='Rouge à lèvres brillant', gamme='Make-up', rayons=[M], source=6739, option=T,
         declinaisons=[('Azalea', 14, 6739), ('Dark Nude', 16, 6738), ('Magenta', 17, 6737), ('Strawberry', 18, 6736),
                       ('Cherry', 21, 6735), ('Coral', 20, 6734), ('Light Nude', 19, 6733), ('Candy Pink', 15, 6732)]),
    dict(cle='gloss-repulpant', nom='Gloss repulpant', sous='Gloss à lèvres effet volume', gamme='Make-up', rayons=[M], source=6801, option=T,
         declinaisons=[('Clear', 3, 6801), ('Cherry', 4, 6802), ('Nude', 5, 6804)]),
    dict(cle='rouge-liquide-mat', nom='Rouge à lèvres liquide mat', sous='Longue tenue', gamme='Make-up', rayons=[M], source=6775, option=T,
         declinaisons=[('Bold Pink', 6, 6775), ('Dark Plum', 7, 6776), ('Magenta', 8, 6777), ('Ruby', 9, 6778), ('Red Velvet', 10, 6779)]),
    dict(cle='lipstick-matte', nom='Lipstick Matte', sous='Rouge à lèvres mat', gamme='Make-up', rayons=[M], source=6742, option=T,
         declinaisons=[('Unique Rose', 12, 6742), ('Raspberry', 13, 6741), ('Watermelon', 13, 6740)]),
    dict(cle='spicy-gloss', nom='Spicy Gloss', sous='Gloss extra volume', gamme='Make-up', rayons=[M], source=7402, ligne=2),
    dict(cle='lip-balm', nom='Lip Balm', sous='Baume à lèvres hydratant', gamme='Make-up', rayons=[M], source=7421, ligne=1),
]
VITRINE = [p['cle'] for p in PRODUITS]
# Contenance que la lecture du fichier coupe à la virgule (« 4,5ml »).
FORMATS = {'lip-balm': '4,5 ml'}


def retouche(texte):
    """« Chogan Make-up » devient « Make-up » une fois la marque retirée : « Le Lipstick Glossy de Make-up est… »."""
    return re.sub(r'\s+(?:de\s+)?Make-up\b', '', texte)


if __name__ == '__main__':
    produits = lot1.construire(PRODUITS, FICHES, EXCEL, VITRINE, lot='nouveautes-2026-11-b', sku='US-N2',
                               arrivee='2026-11-27 12:00:00 UTC', termes_uniques=True)
    for p in produits:
        p['accroche'], p['description'] = retouche(p['accroche']), retouche(p['description'])
        cle = p['source'].rsplit('/', 1)[-1]
        if cle in FORMATS:
            p['format'] = FORMATS[cle]
    json.dump({'produits': produits, 'deja_en_ligne': [], 'ecartees': {}},
              open(os.path.join(DONNEES, 'produits.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print(len(produits), 'fiches ->', os.path.join(DONNEES, 'produits.json'))
