"""Règles de tri et de nettoyage du catalogue repris de l'ancienne boutique Shopify."""
import html
import re
import unicodedata

# Marques tierces à ne jamais citer (audit : risque de contrefaçon sur 110 fiches).
MARQUES_TIERCES = [
    'Chanel', 'Dior', 'Armani', 'Giorgio Armani', 'Tom Ford', 'Yves Saint Laurent', 'YSL', 'Lancôme', 'Lancome',
    'Guerlain', 'Hermès', 'Hermes', 'Gucci', 'Prada', 'Versace', 'Burberry', 'Hugo Boss', 'Paco Rabanne',
    'Jean Paul Gaultier', 'Gaultier', 'Dolce & Gabbana', 'Dolce Gabbana', 'Dolce&Gabbana', 'Givenchy', 'Bvlgari',
    'Bulgari', 'Cartier', 'Creed', 'Maison Francis Kurkdjian', 'Kurkdjian', 'Louis Vuitton', 'Issey Miyake',
    'Calvin Klein', 'Carolina Herrera', 'Narciso Rodriguez', 'Thierry Mugler', 'Mugler', 'Azzaro', 'Kenzo', 'Valentino',
    'Viktor & Rolf', 'Viktor&Rolf', 'Chloé', 'Balmain', 'Pierre Balmain', 'Xerjoff', 'Kilian', 'Byredo',
    'Le Labo', 'Montblanc', 'Mont Blanc', 'Lacoste', 'Davidoff', 'Rochas', 'Nina Ricci', 'Cacharel',
    'Trussardi', 'Ferragamo', 'Jimmy Choo', 'Escada', 'Elie Saab', 'Parfums de Marly', 'Initio',
    'Matière Première', 'Matiere Premiere', 'Zarkoperfume', 'Sospiro', 'Tiziana Terenzi', 'Nasomatto',
    'Juliette Has a Gun', 'Yves Rocher', 'Lolita Lempicka', 'Moschino', 'Shiseido', 'Estée Lauder', 'Estee Lauder',
    'Clinique', 'Joop', 'Dsquared', 'Bottega Veneta', 'Chopard', 'Acqua di Parma', 'Penhaligon',
    'Baccarat Rouge', 'Coco Mademoiselle', 'La Vie est Belle', 'Black Opium', 'Miss Dior', "J'adore",
    'Fahrenheit', 'Invictus', 'One Million', 'Aventus', 'Acqua di Gio',
]
RE_MARQUES = re.compile(
    r'(?<![\w])(' + '|'.join(re.escape(m) for m in sorted(MARQUES_TIERCES, key=len, reverse=True)) + r')(?![\w])', re.I)

FAMILLES = {  # mot repéré dans « Famille olfactive : … » -> famille du filtre
    'floral': 'Floral', 'fleuri': 'Floral', 'fruit': 'Fruité', 'bois': 'Boisé', 'ambr': 'Ambré', 'oriental': 'Ambré',
    'chypr': 'Chypré', 'foug': 'Fougère', 'aromati': 'Aromatique', 'hesp': 'Hespéridé', 'agrum': 'Hespéridé',
    'citrus': 'Hespéridé', 'gourmand': 'Gourmand', 'vanill': 'Gourmand', 'aquati': 'Aquatique', 'marin': 'Aquatique',
    'ozoni': 'Aquatique', 'cuir': 'Cuiré', 'épic': 'Épicé', 'epic': 'Épicé', 'musc': 'Musqué', 'poudr': 'Poudré',
    'vert': 'Vert',
}

MYTOLOGIK = {'AFRODITE', 'ARTEMIDE', 'MEDUSA', 'APOLLO', 'ARES', 'ERMES', 'ZEUS'}


def slug(texte):
    t = unicodedata.normalize('NFKD', texte).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', t.lower()).strip('-')


def texte_brut(h):
    return re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', h or ''))).strip()


def casse_titre(t):
    """LUSH CLOUD WHITE PETALS -> Lush Cloud White Petals (les titres déjà en minuscules sont gardés)."""
    if t.upper() != t:
        return t
    petits = {'de', 'du', 'des', 'la', 'le', 'les', 'et', 'à', 'au', 'aux', 'en', 'pour'}
    mots = t.lower().split()
    return ' '.join(m if (i and m in petits) else m[:1].upper() + m[1:] for i, m in enumerate(mots))


def categorie(p):
    """Renvoie (univers, rayon, publié)."""
    t = (p['title'] + ' ' + p['handle']).lower()
    ty = (p.get('product_type') or '').lower()

    def a(*mots):
        return any(m in t for m in mots)

    # Parfums
    if a('mytologik', 'mψtologik') or p['title'].strip().upper() in MYTOLOGIK:
        return 'Parfums', 'Collection Mytologik', True
    if ty == 'parfum enfant':
        return 'Parfums', 'Parfums enfant', True
    if 'brume' in ty:
        return 'Parfums', 'Brumes parfumées', True
    if a('étui à parfum'):
        return 'Parfums', 'Accessoires', True
    if a('scented love'):
        return 'Parfums', 'Parfums femme', True
    if 'essence de parfums homme' in ty or 'parfum homme' in ty:
        return 'Parfums', 'Parfums homme', True
    if ty in {'essence de parfums femme', 'extrait de parfum', "l'eau d'issey issey miyake",
              'miss dior cherie dior'} or 'parfum femme' in ty:
        return 'Parfums', 'Parfums femme', True
    # Corps parfumé
    if 'gel douche' in ty or 'crème corps' in ty:
        return 'Beauté', 'Corps & bain', True
    if a('bébé', 'bebe', 'baby', 'enfant'):
        return 'Beauté', 'Bébé & enfant', True
    # Maison
    if ty in {'lessive', 'adoucissant parfumé longue durée'} or a('pure black', 'pure white', 'pure color'):
        return 'Art de vivre', 'Soin du linge', True
    if a('chiffon', 'bril', 'saver', 'détergent', 'detergent', 'floor cleaner', 'dégraissant', 'degraissant',
         'déboucheur', 'anti-odeur', 'anticalcaire', 'anti-poussi', 'polissant', 'shinyfridge', 'frostaway',
         'wonderiron', 'fire cleanse', 'eponge', 'powerdish', 'greendish', 'tête de vaporisateur',
         'tete-de-vaporisateur', 'joints nettoyant', 'sanipaw'):
        return 'Art de vivre', 'Entretien de la maison', False
    if a("sprays d'ambiance", 'sprays-dambiance') or (
            re.search(r'-(100|200|3000)-ml', p['handle']) and 'huile' not in t and 'ml' not in p['title'].lower()):
        return 'Art de vivre', "Parfums d'ambiance", True
    # Bien-être
    if a('zent', 'huile essentielle', 'huiles essentielles', 'lolum', 'lolûm', 'compte-gouttes'):
        return 'Bien-être', 'Huiles essentielles', True
    if a('huile pure', 'huiles végétales', 'huiles-vegetales', 'huile de rose', 'beurres'):
        return 'Bien-être', 'Huiles végétales', True
    if ty == 'bien-être' or a('-cps-', 'stick-pack', 'myvera', 'gelules', 'gélules', "jus d'aloe", 'beauty drink',
                             'collagen-poudre', 'skinail'):
        return 'Bien-être', 'Compléments alimentaires', False
    # Beauté
    if a('rituel', 'luxury skin care', 'luxury day skin', 'combo'):
        return 'Beauté', 'Rituels & coffrets', True
    if a('shampoing', 'cheveux', 'capillaire'):
        return 'Beauté', 'Cheveux', True
    if a('dentifrice', 'bain de bouche', 'brosse à dents', 'intime', 'savon', 'déo', 'deo-', 'deodorant'):
        return 'Beauté', 'Hygiène', True
    if a('visage', 'contour des yeux', 'sérum', 'serum', 'pour les yeux'):
        return 'Beauté', 'Soin du visage', True
    if a('pieds', 'mains', 'corps', 'gel douche', 'aloe vera', 'premiers secours'):
        return 'Beauté', 'Corps & bain', True
    return 'Beauté', 'Soin du visage', True
