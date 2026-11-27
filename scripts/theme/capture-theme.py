"""Image de présentation du thème (Apparence › Thèmes) : web/app/themes/univers-sensoriel/screenshot.png, 1200 × 900.

Reprend le visuel d'ouverture de l'accueil avec les fichiers du thème : fond vert sapin, logo, titre en Marcellus,
arche (photo de lys) et les deux flacons détourés. Aucune image générée.

    python scripts/theme/capture-theme.py
"""
import os

from PIL import Image, ImageChops, ImageDraw, ImageFilter, ImageFont

ICI = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.join(ICI, '..', '..', 'web', 'app', 'themes', 'univers-sensoriel')
IMAGES = os.path.join(THEME, 'resources', 'images')
POLICES = os.path.join(THEME, 'resources', 'fonts')
L, H = 1200, 900
SAPIN, SAPIN_PROFOND = (31, 49, 36), (22, 36, 26)
IVOIRE, OR_CLAIR, OR = (247, 240, 228), (227, 181, 111), (170, 116, 44)


def police(nom, taille, graisse=None):
    f = ImageFont.truetype(os.path.join(POLICES, nom), taille)
    if graisse:
        f.set_variation_by_axes([graisse])
    return f


def fond():
    """Dégradé vertical sapin, halo doré en haut à droite, halo vert en bas à gauche (comme .ouverture)."""
    im = Image.new('RGB', (L, H))
    d = ImageDraw.Draw(im)
    for y in range(H):
        t = y / (H - 1)
        d.line([(0, y), (L, y)], fill=tuple(round(a + (b - a) * t) for a, b in zip(SAPIN, SAPIN_PROFOND)))
    for (cx, cy, rx, ry, couleur, force) in [(0.78, 0.3, 560, 420, OR_CLAIR, 0.16), (0.08, 1.0, 420, 320, (79, 90, 76), 0.4)]:
        halo = Image.new('L', (L, H), 0)
        ImageDraw.Draw(halo).ellipse([cx * L - rx, cy * H - ry, cx * L + rx, cy * H + ry], fill=round(255 * force))
        halo = halo.filter(ImageFilter.GaussianBlur(160))
        im = Image.composite(Image.new('RGB', (L, H), couleur), im, halo)
    return im


def arche(largeur, hauteur):
    """Masque en arche : rectangle au sommet en demi-cercle."""
    m = Image.new('L', (largeur * 4, hauteur * 4), 0)
    d = ImageDraw.Draw(m)
    r = largeur * 2
    d.ellipse([0, 0, largeur * 4, r * 2], fill=255)
    d.rectangle([0, r, largeur * 4, hauteur * 4], fill=255)
    return m.resize((largeur, hauteur), Image.LANCZOS)


def ombre(im, flou=26, decalage=(0, 34), opacite=0.45):
    alpha = im.split()[3].point(lambda a: round(a * opacite))
    calque = Image.new('RGBA', (im.width + 200, im.height + 200), (0, 0, 0, 0))
    noir = Image.new('RGBA', im.size, (10, 18, 12, 255))
    noir.putalpha(alpha)
    calque.alpha_composite(noir, (100 + decalage[0], 100 + decalage[1]))
    return calque.filter(ImageFilter.GaussianBlur(flou))


def detoure(nom, hauteur):
    im = Image.open(os.path.join(IMAGES, 'site', nom)).convert('RGBA')
    im = im.crop(im.getbbox())
    return im.resize((round(im.width * hauteur / im.height), hauteur), Image.LANCZOS)


def construire():
    im = fond().convert('RGBA')
    d = ImageDraw.Draw(im)

    # Arche : photo de lys, filet d'or décalé.
    aw, ah, ax, ay = 400, 560, 700, 150
    photo = Image.open(os.path.join(IMAGES, 'site', 'fleur-de-lys-matiere-premiere.webp')).convert('RGB')
    cote = max(aw, ah)
    photo = photo.resize((round(photo.width * cote / min(photo.size)), round(photo.height * cote / min(photo.size))), Image.LANCZOS)
    photo = photo.crop(((photo.width - aw) // 2, (photo.height - ah) // 2, (photo.width - aw) // 2 + aw, (photo.height - ah) // 2 + ah))
    masque = arche(aw, ah)
    contour = arche(aw + 28, ah + 22)
    trait = Image.new('L', contour.size, 0)
    trait.paste(contour, (0, 0))
    interieur = Image.new('L', contour.size, 0)
    interieur.paste(arche(aw + 26, ah + 20), (1, 1))
    trait = ImageChops.subtract(trait, interieur).point(lambda a: round(a * 0.55))
    or_ = Image.new('RGBA', contour.size, OR_CLAIR + (255,))
    or_.putalpha(trait)
    im.alpha_composite(or_, (ax - 14, ay - 8))
    im.paste(photo, (ax, ay), masque)

    # Flacons détourés devant l'arche, avec leur ombre.
    for nom, hauteur, (x, y) in [('flacon-extrait-de-parfum-femme.webp', 300, (620, 500)), ('flacon-extrait-de-parfum-homme.webp', 380, (930, 400))]:
        flacon = detoure(nom, hauteur)
        im.alpha_composite(ombre(flacon), (x - 100, y - 100))
        im.alpha_composite(flacon, (x, y))

    # Étoile du logo, petite, en haut de l'arche.
    ex, ey = 1130, 120
    d.polygon([(ex, ey - 16), (ex + 4, ey - 4), (ex + 16, ey), (ex + 4, ey + 4), (ex, ey + 16), (ex - 4, ey + 4), (ex - 16, ey), (ex - 4, ey - 4)], outline=OR_CLAIR)

    # Logo et textes.
    logo = Image.open(os.path.join(IMAGES, 'logo-univers-sensoriel-clair.webp')).convert('RGBA')
    logo = logo.resize((300, round(logo.height * 300 / logo.width)), Image.LANCZOS)
    im.alpha_composite(logo, (80, 70))
    titre = police('marcellus.woff2', 66)
    lignes = [('Parfums, soins', IVOIRE), ('et rituels pour', IVOIRE), ('éveiller les sens', OR_CLAIR)]
    y = 330
    for texte, couleur in lignes:
        d.text((80, y), texte, font=titre, fill=couleur)
        y += 84
    # Ruban d'or sous « éveiller les sens ».
    largeur = d.textlength('éveiller les sens', font=titre)
    d.arc([84, y - 20, 84 + largeur, y + 14], start=200, end=340, fill=OR, width=3)
    texte = police('jost.woff2', 22, 300)
    d.text((82, y + 40), 'Extraits de parfum longue tenue, soins', font=texte, fill=(247, 240, 228, 210))
    d.text((82, y + 72), 'et senteurs pour la maison.', font=texte, fill=(247, 240, 228, 210))
    pied = police('jost.woff2', 17, 500)
    d.text((82, H - 92), 'U N I V E R S S E N S O R I E L . C O M', font=pied, fill=OR_CLAIR)
    d.line([(82, H - 104), (150, H - 104)], fill=OR, width=1)

    cible = os.path.join(THEME, 'screenshot.png')
    im.convert('RGB').save(cible, optimize=True)
    print(cible, os.path.getsize(cible) // 1024, 'Ko')


if __name__ == '__main__':
    construire()
