"""Nouveautés de novembre 2026 : fiches prêtes à importer, à partir du fichier de la promotrice et des pages produits.

Entrées (scripts/catalogue/nouveautes/) :
- excel.json : le fichier « nouveautés produits chogan.xlsx » (docs/Mise à jour 27 11 2026), ligne par ligne ;
- fiches-chogan.json : pour chaque lien du fichier, la page produit (nom, contenance, description, photos, pastilles de teinte),
  relevée dans le navigateur (le site est derrière Cloudflare).

Sortie :
- scripts/catalogue/nouveautes/produits.json : fiches au format de l'import du thème (App\\importer_produits) ;
- docs/02-images-produits/nouveautes/*.webp : photos détourées (packshots) ou redimensionnées (ambiance).
  Les photos d'origine sont gardées dans ../k-ellesensorielle/scrape/nouveautes-2026-11 (hors dépôt).

    python scripts/catalogue/nouveautes.py            (photos déjà téléchargées : non retéléchargées)

Prix : seulement le « prix client » du fichier. Les teintes d'un même produit deviennent des déclinaisons,
avec la couleur de la pastille (palette sur la fiche).
"""
import io
import json
import os
import re
import sys
import unicodedata
import urllib.request

from PIL import Image

ICI = os.path.dirname(os.path.abspath(__file__))
RACINE = os.path.dirname(os.path.dirname(ICI))
DONNEES = os.path.join(ICI, 'nouveautes')
IMAGES = os.path.join(RACINE, 'docs', '02-images-produits', 'nouveautes')
BRUTES = os.environ.get('US_NOUVEAUTES_BRUTES', r'C:\Users\hndam\OneDrive\Bureau\Projets_Dev\k-ellesensorielle\scrape\nouveautes-2026-11')
CDN = 'https://cdn.chogangroupspa.com/images/prodotti/big/'
MAX_PHOTOS = 4

sys.path.insert(0, ICI)
from images import detourer, enregistrer, est_packshot  # noqa: E402

FICHES = json.load(open(os.path.join(DONNEES, 'fiches-chogan.json'), encoding='utf-8'))
# Photos écartées à la main (règle de l'audit : ni avant/après, ni personnes) : nom de la photo produite, sans extension.
EXCLUSIONS = {l.strip() for l in open(os.path.join(DONNEES, 'exclusions.txt'), encoding='utf-8') if l.strip() and not l.startswith('#')}
EXCEL = {l['ligne']: l for l in json.load(open(os.path.join(DONNEES, 'excel.json'), encoding='utf-8'))}

# Rayons (slug) : ceux du site, plus deux nouveaux créés par l'import (Maquillage, Bougies parfumées).
P_FEMME, P_HOMME, LUXURY = 'parfums-femme', 'parfums-homme', 'collection-luxury'
VISAGE, CHEVEUX, HYGIENE, CORPS = 'soin-du-visage', 'cheveux', 'hygiene', 'corps-et-bain'
MAQUILLAGE, BOUGIES, AMBIANCE, COMPLEMENTS = 'maquillage', 'bougies-parfumees', 'parfums-d-ambiance', 'complements-alimentaires'

# Une ligne : clé, nom affiché, sous-titre, gamme, rayons, genre, numéro de parfum, page source (photos et texte),
# option (« Format », « Teinte », « Senteur »), déclinaisons [(libellé, ligne du fichier, page de la déclinaison)], publié.
# Les déclinaisons « Format » prennent leurs prix dans l'ordre de la ligne du fichier.
F = 'Format'
PRODUITS = [
    # Parfums
    dict(cle='amphera', nom='Luxury Amphera', sous='Extrait de parfum · floral ambré gourmand', gamme='Olfazeta', rayons=[P_FEMME, LUXURY], genre='Femme', source=16201, ligne=2, option=F, familles=['Floral', 'Ambré', 'Gourmand'], photos=[1, 3, 4], recadrer='droite'),
    dict(cle='volt', nom='Luxury Volt', sous='Extrait de parfum · aromatique épicé', gamme='Olfazeta', rayons=[P_HOMME, LUXURY], genre='Homme', source=16196, ligne=3, option=F, familles=['Aromatique', 'Épicé'], photos=[1, 3, 4], recadrer='droite'),
    dict(cle='scarlet', nom='Luxury Scarlet', sous='Extrait de parfum · floral ambré boisé', gamme='Olfazeta', rayons=[P_FEMME, P_HOMME, LUXURY], genre='Mixte', reference='170', source=16151, ligne=4, option=F, familles=['Floral', 'Ambré', 'Boisé'],
         photos_site=['parfums-femme/parfums-femme-olfazeta-univers-sensoriel-15.webp']),
    dict(cle='blooming-skin', nom='Blooming Skin', sous='Extrait de parfum · floral fruité gourmand', gamme='Olfazeta', rayons=[P_FEMME], genre='Femme', reference='163', source=13686, ligne=5, option=F, familles=['Floral', 'Fruité', 'Gourmand']),
    dict(cle='unfold', nom='Unfold', sous='Extrait de parfum · boisé aromatique', gamme='Olfazeta', rayons=[P_HOMME], genre='Homme', reference='164', source=13706, ligne=6, option=F, familles=['Boisé', 'Aromatique']),
    dict(cle='gel-douche-luxury', nom='Gel douche parfumé Luxury', sous='Édition limitée', gamme='Olfazeta', rayons=[CORPS], genre='Mixte', source=15986, ligne=7),
    # Compléments alimentaires : en brouillon, comme les autres (allégations santé à relire avant publication)
    dict(cle='vigoractive', nom='Vigoractive', sous='Complément alimentaire en gel, 30 sticks', gamme='Supplefit', rayons=[COMPLEMENTS], source=15616, ligne=8, publie=False),
    dict(cle='florcor', nom='Florcor', sous='Complément alimentaire, 30 gélules', gamme='Supplefit', rayons=[COMPLEMENTS], source=10023, ligne=64, publie=False),
    dict(cle='powergain', nom='Powergain', sous='Créatine monohydrate, 90 portions', gamme='Supplefit', rayons=[COMPLEMENTS], source=15311, ligne=23, publie=False),
    dict(cle='ultrablast', nom='Ultrablast', sous='Pré-entraînement, 20 portions', gamme='Supplefit', rayons=[COMPLEMENTS], source=15306, ligne=24, publie=False),
    dict(cle='recoverypro', nom='RecoveryPro', sous='BCAA 4:1:1, 45 portions', gamme='Supplefit', rayons=[COMPLEMENTS], source=15301, ligne=25, publie=False),
    # Bougies parfumées
    *[dict(cle=f'bougie-{c}', nom=f'Bougie parfumée {n}', sous='Édition limitée · cire végétale', gamme='Cooperativa Profumieri', rayons=[BOUGIES], source=s, ligne=l, option=F)
      for c, n, s, l in [('noire', 'Noiré', 15596, 9), ('melia', 'Mélia', 15581, 10), ('lavea', 'Lavéa', 15576, 11), ('muske', 'Muské', 15561, 12), ('rosea', 'Roséa', 15546, 13)]],
    # Soin du visage
    dict(cle='creme-nuit-aloe-vera', nom="Crème de nuit à l'aloe vera", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15521, ligne=14),
    dict(cle='serum-tenseur-acide-hyaluronique', nom="Sérum gel tenseur à l'acide hyaluronique", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15496, ligne=16),
    dict(cle='creme-huile-de-neem', nom="Crème visage à l'huile de neem", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15491, ligne=17),
    dict(cle='creme-acide-hyaluronique', nom="Crème visage à l'acide hyaluronique", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15481, ligne=18),
    dict(cle='creme-jour-lait-d-anesse', nom="Crème de jour au lait d'ânesse", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15471, ligne=19),
    dict(cle='creme-nuit-lait-d-anesse', nom="Crème de nuit au lait d'ânesse", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15456, ligne=20),
    dict(cle='creme-huile-d-argan', nom="Crème visage à l'huile d'argan", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15451, ligne=21),
    dict(cle='creme-bave-d-escargot', nom="Crème visage à la bave d'escargot", sous='Visage', gamme='Aurodhea', rayons=[VISAGE], source=15441, ligne=22),
    # Maquillage
    *[dict(cle=f'mascara-{c}', nom=n, sous=s, gamme='MoniAmori', rayons=[MAQUILLAGE], source=src, ligne=l)
      for c, n, s, src, l in [('extra-volume', 'Mascara Extra Volume', 'Volumateur', 15261, 26), ('high-definition', 'Mascara High Definition', 'Haute définition', 15256, 27),
                              ('super-intense', 'Mascara Super Intense', 'Longue tenue', 15251, 28), ('waterproof', 'Mascara Waterproof', "Résistant à l'eau", 15246, 29),
                              ('curly-lashes', 'Mascara Curly Lashes', 'Recourbant', 15241, 30)]],
    dict(cle='mylip-secret', nom='MyLip Secret', sous='Encre à lèvres peel-off', gamme='MoniAmori', rayons=[MAQUILLAGE], source=14461, option='Teinte',
         declinaisons=[('Blackberry Delight', 45, 14436), ('Cherry Tart', 44, 14441), ('Red Velvet', 43, 14446), ('Strawberry Soufflé', 42, 14451), ('Biscuit Rose', 41, 14456), ('Caramel Toffee', 40, 14461)]),
    dict(cle='juicy-oil', nom='Juicy Oil', sous='Huile lèvres hydratante', gamme='MoniAmori', rayons=[MAQUILLAGE], source=14431, option='Senteur',
         declinaisons=[('Pastèque', 46, 14431), ('Coco salé', 47, 14426), ('Litchi', 48, 14421), ('Cerise noire', 49, 14416), ('Raisin', 50, 14411)]),
    dict(cle='extra-lipstay', nom='Extra-LipStay', sous='Crayon à lèvres rétractable', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9770, option='Teinte',
         declinaisons=[('Royal Mauve', 78, 9715), ('Purple Mocha', 77, 9720), ('Berry Kiss', 76, 9725), ('Dark Cocoa', 80, 9730), ('Chili Love', 79, 9735), ('Chic Peach', 75, 9740),
                       ('Pinky Doll', 74, 9745), ('Ruby Flame', 73, 9750), ('Toffee Nude', 72, 9755), ('Blush Sand', 71, 9760), ('Dusty Rose', 70, 9765), ('Velvet Taupe', 69, 9770)]),
    dict(cle='extra-eyestay', nom='Extra-EyeStay', sous='Crayon yeux à tailler', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9680, option='Teinte',
         declinaisons=[('Wild Magenta', 81, 9710), ('Green Jungle', 88, 9675), ('Midnight Blue', 87, 9680), ('Dark Truffle', 85, 9690), ('Soft Butter', 86, 9685),
                       ('Silver Moon', 84, 9695), ('Crystal Blue', 83, 9700), ('Bold Orchid', 82, 9705)]),
    dict(cle='blush-and-kiss', nom='Blush & Kiss', sous='Stick multi-usage joues et lèvres', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9484, option='Teinte',
         declinaisons=[('Watermelon', 89, 9484), ('Peach', 90, 9479), ('Berry', 91, 9474)]),
    dict(cle='sculpt-and-lift', nom='Sculpt & Lift', sous='Stick contour', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9469, option='Teinte',
         declinaisons=[('Stone', 94, 9459), ('Clay', 93, 9464), ('Ebony', 92, 9469)]),
    dict(cle='light-and-go', nom='Light & Go', sous='Stick illuminateur', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9449, option='Teinte',
         declinaisons=[('Champagne', 97, 9444), ('Rose Gold', 96, 9449), ('Bronze', 95, 9454)]),
    dict(cle='solar-defence', nom='Solar Defence', sous='Fond de teint compact', gamme='MoniAmori', rayons=[MAQUILLAGE], source=9218, option='Teinte',
         declinaisons=[('Meringue', 102, 9208), ('Amaretto', 101, 9213), ('Cinnamon Roll', 100, 9218), ('Crème Caramel', 99, 9223), ('Tiramisù', 98, 9228)]),
    dict(cle='supreme-lip-treatment', nom='Supreme Lip Treatment', sous='Soin lèvres teinté', gamme='MoniAmori', rayons=[MAQUILLAGE], source=8997, option='Senteur',
         declinaisons=[('Vanille', 111, 8995), ('Fraise', 110, 8996), ('Framboise', 109, 8997)]),
    dict(cle='lollilip', nom='Lollilip', sous='Masque lèvres nourrissant', gamme='MoniAmori', rayons=[MAQUILLAGE], source=11686, option='Senteur',
         declinaisons=[('Spiced Cookie', 62, 11686), ('Salty Caramel', 63, 11691)]),
    # Parfums d'ambiance (spray 500 ml, échantillon 3 ml)
    *[dict(cle=f'spray-{c}', nom=n, sous="Spray d'ambiance", gamme='Cooperativa Profumieri', rayons=[AMBIANCE], source=s, ligne=l, option=F)
      for c, n, s, l in [('whispering-woods', 'Whispering Woods', 13821, 51), ('sweet-symphonies', 'Sweet Symphonies', 13811, 52), ('musk-ritual', 'Musk Ritual', 13801, 53),
                         ('soft-hypnose', 'Soft Hypnose', 13791, 54), ('fruity-delight', 'Fruity Delight', 13781, 55), ('velvet-citrus', 'Velvet Citrus', 13771, 56),
                         ('meadow-spirit', 'Meadow Spirit', 13756, 57), ('petal-infusion', 'Petal Infusion', 13746, 58), ('cotton-veil', 'Cotton Veil', 13741, 59),
                         ('sun-mirage', 'Sun Mirage', 13731, 60)]],
    # Cheveux
    dict(cle='serum-capillaire-argan', nom="Sérum capillaire réparateur à l'huile d'argan", sous='Cheveux', gamme='Aurodhea', rayons=[CHEVEUX], source=10018, ligne=65),
    dict(cle='ampoules-anti-chute', nom='Ampoules anti-chute', sous='Soin intensif', gamme='Aurodhea', rayons=[CHEVEUX], source=7660, ligne=66),
    dict(cle='lotion-anti-chute-undaria', nom="Lotion anti-chute à l'undaria pinnatifida", sous='Cheveux', gamme='Aurodhea', rayons=[CHEVEUX], source=7694, ligne=67),
    dict(cle='shampoing-anti-chute', nom='Shampoing anti-chute', sous='Cheveux', gamme='Aurodhea', rayons=[CHEVEUX], source=7888, ligne=68),
    dict(cle='lotion-menthol-camphre', nom='Lotion capillaire au menthol et au camphre', sous='Rafraîchissante', gamme='Aurodhea', rayons=[CHEVEUX], source=9177, ligne=103),
    dict(cle='light-drops', nom='Light Drops', sous='Sérum cheveux', gamme='Aurodhea', rayons=[CHEVEUX], source=9118, option='Senteur',
         declinaisons=[('Floral Sweetness', 106, 9118), ('Honey & Camomile', 107, 9117), ('Chocolate Obsession', 108, 9116)]),
    # Hygiène
    dict(cle='pommade-ichtyol', nom="Pommade à l'ichtyol 9,8 %", sous='Soin de la peau', gamme='Aurodhea', rayons=[HYGIENE], source=9178, ligne=104),
    dict(cle='buzzoff', nom='Buzzoff', sous='Lotion anti-moustiques', gamme='Aurodhea', rayons=[HYGIENE], source=9140, ligne=105),
]

# Ordre de vitrine (menu, accueil, tri « Nouveautés ») : les gammes alternent, les plus parlantes d'abord.
# Une fiche absente de la liste vient ensuite, dans l'ordre de PRODUITS.
VITRINE = ['amphera', 'mylip-secret', 'bougie-noire', 'volt', 'solar-defence', 'spray-velvet-citrus', 'blooming-skin',
           'extra-lipstay', 'creme-jour-lait-d-anesse', 'unfold', 'juicy-oil', 'bougie-rosea', 'scarlet', 'light-and-go',
           'serum-tenseur-acide-hyaluronique', 'blush-and-kiss', 'spray-whispering-woods', 'extra-eyestay', 'gel-douche-luxury']

# Produits du fichier déjà en vente sur le site : ajoutés à la catégorie Nouveautés, sans rien changer d'autre.
DEJA_EN_LIGNE = ['luxury-seduction-parfum-femme', 'luxury-mystere-edition-limitee-parfum-homme', 'creme-de-jour-visage-aloe-vera', 'lolum-ensemble-complet']

# Lignes du fichier laissées de côté (signalées à la promotrice).
ECARTEES = {
    15: "Crème de jour à l'aloe vera : déjà en vente (34,90 €, le fichier dit 39,90 €)",
    31: 'Luxury Mystère : déjà en vente (50 ml, 60 €)',
    32: 'Luxury Séduction : déjà en vente (50 ml, 60 €)',
    33: 'Kit Lolûm huiles essentielles et végétales : déjà en vente (« Lolûm ensemble complet », 419,22 €)',
    34: 'Kit Lolûm huiles essentielles et végétales : doublon de la ligne 33 (version allemande)',
    35: 'Kit Lolûm huiles végétales : aucune photo sur la page produit',
    36: 'Kit Lolûm huiles végétales : doublon de la ligne 35',
    37: 'Kit Lolûm huiles essentielles : aucune photo sur la page produit',
    38: 'Kit Lolûm huiles essentielles : doublon de la ligne 37',
    39: 'Kit de crayons Extra-Stay : aucune photo sur la page produit',
    61: 'Élastique porte-crayons : pas de prix client dans le fichier',
    112: 'Pochette « Change your life » : le lien mène au Supreme Lip Treatment vanille',
}

CORRECTIONS = {'P\ufffdrou': 'Pérou', 'Cr\ufffdme': 'Crème', 'Pralin\ufffd': 'Praliné', '\ufffd': 'é'}


def propre(texte):
    """Lettres en italique Unicode (𝘚𝘶𝘯) remises en lettres simples, caractères abîmés réparés, sans « Chogan »."""
    texte = unicodedata.normalize('NFKC', texte or '')
    for faux, juste in CORRECTIONS.items():
        texte = texte.replace(faux, juste)
    texte = re.sub(r'\s*\bChogan\b', '', texte, flags=re.I)
    return texte.replace('’', "'").strip()


def nombres(texte):
    return [float(n.replace(',', '.')) for n in re.findall(r'\d+(?:[.,]\d+)?', texte or '')]


def formats(texte):
    """« 3ml,15ml,50ml » -> ['3 ml', '15 ml', '50 ml'] ; « 190gr, 400gr » -> ['190 g', '400 g']."""
    sortie = []
    for morceau in re.split(r'[,;]', texte or ''):
        m = re.match(r'\s*(\d+(?:[.,]\d+)?)\s*(ml|gr?|g)\b', morceau.strip(), re.I)
        if m:
            sortie.append(f"{m.group(1).replace('.', ',')} {'ml' if m.group(2).lower() == 'ml' else 'g'}")
    return sortie


def prix(valeur):
    return f'{valeur:.2f}'


def sections(desc):
    """Texte de la page : introduction, notes (pyramide), mode d'emploi, durée, en blocs séparés par une ligne vide."""
    blocs = [b.strip() for b in re.split(r'\n\s*\n', propre(desc)) if b.strip()]
    intro, notes, usage, faits = [], [], '', []
    mode = 'intro'
    for bloc in blocs:
        bas = bloc.lower()
        if re.match(r'^(cpnp|teinte|couleur|parfum)\s*:', bas):
            continue
        if bas.startswith('pyramide olfactive'):
            mode = 'notes'
            continue
        if re.match(r"^(comment l'(appliquer|utiliser)|mode d'emploi|utilisation|conseils d'utilisation)", bas):
            mode = 'usage'
            continue
        if bas.startswith('durée indicative'):
            mode = 'duree'
            continue
        if bloc.isupper() or (len(bloc) < 45 and not bloc.endswith('.')):
            mode = 'autre'  # EFFICACITÉ TESTÉE, INGRÉDIENTS PRINCIPAUX… : non repris (texte plus court)
            continue
        if mode == 'intro':
            intro.append(bloc)
        elif mode == 'notes':
            notes += [l.strip() for l in bloc.split('\n') if ':' in l]
        elif mode == 'usage' and not usage:
            usage = bloc.replace('\n', ' ')
        elif mode == 'duree':
            faits += [l.strip() for l in bloc.split('\n') if l.strip() and not l.startswith('*')]
    return intro, notes, usage, faits


def phrases(texte, limite):
    """Premières phrases entières tenant dans « limite » caractères."""
    sortie = ''
    for p in re.split(r'(?<=[.!?])\s+', texte):
        if sortie and len(sortie) + len(p) + 1 > limite:
            break
        sortie = (sortie + ' ' + p).strip()
    return sortie


def description(fiche):
    intro, notes, usage, faits = sections(fiche['desc'])
    html = ''.join(f'<p>{p}</p>' for p in intro[:2])
    points = [n.replace('Notes de tête', 'Tête').replace('Notes de cœur', 'Cœur').replace('Notes de fond', 'Fond').replace(':', ' :', 1).replace('  ', ' ') for n in notes]
    points += faits
    if points:
        html += '<ul>' + ''.join(f'<li>{p}</li>' for p in points) + '</ul>'
    if usage:
        html += f'<p><strong>Utilisation.</strong> {usage}</p>'
    accroche = phrases(' '.join(intro), 230) if intro else ''
    principales = []
    for n in notes:
        principales += [x.strip() for x in n.split(':', 1)[1].split(',')][:1]
    return html, accroche, principales


def telecharger(fichier):
    os.makedirs(BRUTES, exist_ok=True)
    chemin = os.path.join(BRUTES, fichier)
    if not os.path.exists(chemin):
        requete = urllib.request.Request(CDN + fichier, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(requete, timeout=60) as r:
            open(chemin, 'wb').write(r.read())
    return chemin


def droite(carre):
    """Garde le sujet le plus à droite d'un packshot détouré (le flacon posé à côté de son coffret), recentré."""
    import numpy as np
    alpha = np.asarray(carre.split()[3])
    colonnes = np.where(alpha.max(axis=0) > 12)[0]
    trous = np.where(np.diff(colonnes) > 8)[0]
    if not len(trous):
        return carre
    gauche = colonnes[trous[-1] + 1]
    sujet = carre.crop((gauche, 0, carre.width, carre.height))
    lignes = np.where(np.asarray(sujet.split()[3]).max(axis=1) > 12)[0]
    sujet = sujet.crop((0, lignes.min(), sujet.width, lignes.max() + 1))
    sujet = sujet.crop(sujet.getbbox())
    cote = carre.width
    echelle = min(cote * 0.86 / sujet.width, cote * 0.86 / sujet.height)
    sujet = sujet.resize((round(sujet.width * echelle), round(sujet.height * echelle)), Image.LANCZOS)
    neuf = Image.new('RGBA', (cote, cote), (0, 0, 0, 0))
    neuf.alpha_composite(sujet, ((cote - sujet.width) // 2, (cote - sujet.height) // 2))
    return neuf


def photo(fichier, nom_sortie, recadrer=''):
    """Photo traitée comme le catalogue : packshot détouré en carré transparent 1200 px, ambiance en 1600 px."""
    os.makedirs(IMAGES, exist_ok=True)
    cible = os.path.join(IMAGES, nom_sortie + '.webp')
    if os.path.exists(cible):
        return os.path.relpath(cible, os.path.dirname(IMAGES)).replace('\\', '/'), Image.open(cible).mode == 'RGBA'
    im = Image.open(io.BytesIO(open(telecharger(fichier), 'rb').read()))
    im.load()
    detoure = False
    if est_packshot(im):
        carre = detourer(im)
        if carre is not None:
            im, detoure = (droite(carre) if recadrer == 'droite' else carre), True
    if not detoure:
        im = im.convert('RGB')
        im.thumbnail((1600, 1600), Image.LANCZOS)
    enregistrer(im, cible, 84)
    return os.path.relpath(cible, os.path.dirname(IMAGES)).replace('\\', '/'), detoure


def couleur(url):
    """Couleur d'une pastille : médiane du centre de la petite image."""
    if not url:
        return ''
    nom = 'pastille-' + url.rsplit('/', 1)[-1]
    chemin = os.path.join(BRUTES, nom)
    if not os.path.exists(chemin):
        os.makedirs(BRUTES, exist_ok=True)
        requete = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(requete, timeout=60) as r:
            open(chemin, 'wb').write(r.read())
    im = Image.open(chemin).convert('RGB')
    l, h = im.size
    centre = im.crop((l // 4, h // 4, 3 * l // 4, 3 * h // 4))
    pixels = sorted(list(centre.getdata()), key=lambda p: sum(p))
    r, g, b = pixels[len(pixels) // 2]
    return f'#{r:02X}{g:02X}{b:02X}'


def slug(texte):
    texte = unicodedata.normalize('NFKD', texte.lower()).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', texte).strip('-')


def construire():
    sortie = []
    for n, p in enumerate(PRODUITS, 1):
        fiche = FICHES[str(p['source'])]
        html, accroche, principales = description(fiche)
        base = slug(p['nom'])
        # Photos du produit : celles de la page source (la première sert de visuel principal).
        images = []
        # « photos » : numéros des photos gardées (les autres sont écartées) ; « recadrer » : réglage de la 1re.
        for rang, f in enumerate(fiche['images'][:MAX_PHOTOS], 1):
            if ('photos' in p and rang not in p['photos']) or f'{base}-{rang}' in EXCLUSIONS:
                continue
            fichier, detoure = photo(f, f'{base}-{rang}', p.get('recadrer', '') if rang == 1 else '')
            images.append({'fichier': fichier, 'detoure': detoure})
        for f in p.get('photos_site', []):
            images.append({'fichier': f, 'detoure': True})
        produit = {
            'source': f"nouveautes-2026-11/{p['cle']}", 'slug': base, 'nom': p['nom'], 'sous_titre': p['sous'],
            'sku': f"US-N{n:03d}", 'gamme': p['gamme'], 'rayons': p['rayons'], 'genre': p.get('genre', ''),
            'reference': p.get('reference', ''), 'familles': p.get('familles', []), 'notes': principales, 'accroche': accroche, 'description': html,
            'publie': p.get('publie', True), 'option': p.get('option', F), 'images': images, 'variations': [],
            'chogan': fiche.get('code', ''),
            'rang': VITRINE.index(p['cle']) if p['cle'] in VITRINE else len(VITRINE) + n,
        }
        if 'declinaisons' in p:
            for libelle, ligne, page in p['declinaisons']:
                f = FICHES[str(page)]
                prix_ligne = nombres(EXCEL[ligne]['prix_client'])
                v = {'format': libelle, 'prix': prix(prix_ligne[-1]), 'code': f.get('code', ''), 'couleur': couleur(f.get('pastille', ''))}
                if f.get('images'):
                    v['image'] = photo(f['images'][0], f'{base}-{slug(libelle)}')[0]
                produit['variations'].append(v)
        else:
            ligne = EXCEL[p['ligne']]
            montants, tailles = nombres(ligne['prix_client']), formats(ligne['contenance'])
            if len(montants) > 1 and len(montants) == len(tailles):
                # Du plus grand au plus petit, comme les parfums du site (le plus grand proposé d'abord).
                for taille, montant in sorted(zip(tailles, montants), key=lambda x: -x[1]):
                    produit['variations'].append({'format': taille, 'prix': prix(montant)})
            else:
                produit['prix'] = prix(montants[-1])
                produit['format'] = (formats(ligne['contenance']) or [''])[0] or ligne['contenance']
        sortie.append(produit)
        print(f"{n:>2}. {p['nom']} : {len(images)} photos, {len(produit['variations'])} déclinaisons")
    return sortie


if __name__ == '__main__':
    produits = construire()
    json.dump({'produits': produits, 'deja_en_ligne': DEJA_EN_LIGNE, 'ecartees': ECARTEES},
              open(os.path.join(DONNEES, 'produits.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print(len(produits), 'fiches ->', os.path.join(DONNEES, 'produits.json'))
