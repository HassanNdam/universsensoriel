"""Images produits : dédoublonnage, détourage des packshots sur fond blanc, conversion WebP, noms lisibles.

Un packshot (bords blancs) est détouré (rembg) puis recadré dans un carré transparent de 1200 px :
le thème le pose sur un fond lin ou sapin. Une photo d'ambiance est seulement redimensionnée (1600 px).
Les images écartées à la main sont listées dans exclusions.txt (nom du fichier source, une par ligne).
"""
import collections
import os

import numpy as np
from PIL import Image

from regles import slug

ICI = os.path.dirname(os.path.abspath(__file__))
MAX_PAR_PRODUIT = 5
_session = None


def empreinte(im):
    """Empreinte perceptuelle : deux fichiers de la même photo donnent la même clé."""
    g = np.asarray(im.convert('L').resize((12, 12), Image.LANCZOS), dtype=np.int16)
    ratio = round(im.width / im.height, 1)
    teinte = np.asarray(im.convert('RGB').resize((3, 3), Image.BOX), dtype=np.int16) // 20
    return f"{ratio}-" + ''.join('1' if v else '0' for v in (g > g.mean()).flatten()) + '-' + '.'.join(map(str, teinte.flatten()))


def est_packshot(im):
    """Vrai si le pourtour de l'image est blanc uni (photo studio à détourer)."""
    a = np.asarray(im.convert('RGB').resize((200, 200)), dtype=np.float32)
    bord = np.concatenate([a[:6].reshape(-1, 3), a[-6:].reshape(-1, 3), a[:, :6].reshape(-1, 3), a[:, -6:].reshape(-1, 3)])
    return bool(bord.mean() > 243 and bord.std() < 9)


def detourer(im):
    global _session
    from rembg import new_session, remove
    if _session is None:
        _session = new_session('isnet-general-use')
    im = im.convert('RGB')
    im.thumbnail((1400, 1400), Image.LANCZOS)
    coupe = remove(im, session=_session)
    alpha = np.asarray(coupe.split()[3])
    ys, xs = np.where(alpha > 12)
    if len(xs) < 400:  # détourage raté : on garde l'image telle quelle
        return None
    coupe = coupe.crop((xs.min(), ys.min(), xs.max() + 1, ys.max() + 1))
    cote = 1200
    marge = 0.86  # le sujet occupe 86 % du carré
    echelle = min(cote * marge / coupe.width, cote * marge / coupe.height)
    coupe = coupe.resize((max(1, round(coupe.width * echelle)), max(1, round(coupe.height * echelle))), Image.LANCZOS)
    carre = Image.new('RGBA', (cote, cote), (0, 0, 0, 0))
    carre.alpha_composite(coupe, ((cote - coupe.width) // 2, (cote - coupe.height) // 2))
    return carre


def analyser(catalogue):
    """Ajoute à chaque produit '_images' : liste de dicts {cle, source, packshot}."""
    exclus = set()
    chemin = os.path.join(ICI, 'exclusions.txt')
    if os.path.exists(chemin):
        exclus = {l.strip() for l in open(chemin, encoding='utf-8') if l.strip() and not l.startswith('#')}
    uniques = {}
    usages = collections.Counter()
    for p in catalogue:
        p['_images'], vus = [], set()
        for f in p['_fichiers']:
            if os.path.basename(f) in exclus:
                continue
            try:
                im = Image.open(f)
                im.load()
            except Exception:
                continue
            if min(im.size) < 300:
                continue
            cle = empreinte(im)
            if cle in vus:
                continue
            vus.add(cle)
            if cle not in uniques:
                uniques[cle] = {'cle': cle, 'source': f, 'packshot': est_packshot(im), 'taille': im.size}
            if os.path.basename(uniques[cle]['source']) in exclus:
                continue
            p['_images'].append(uniques[cle])
        # Le packshot d'abord, puis les ambiances.
        p['_images'].sort(key=lambda i: not i['packshot'])
        p['_images'] = p['_images'][:MAX_PAR_PRODUIT]
        for i in p['_images']:
            usages[i['cle']] += 1
    return uniques, usages


def traiter(catalogue, sortie):
    uniques, usages = analyser(catalogue)
    noms, pris = {}, set()
    total = sum(1 for c in usages)
    fait = 0
    for p in catalogue:
        p['images'] = []
        dossier = slug(p['rayon'])
        for rang, i in enumerate(p['_images'], 1):
            cle = i['cle']
            if cle not in noms:
                if usages[cle] > 1:  # visuel partagé par plusieurs fiches : nom du rayon et de la gamme
                    base = f"{slug(p['rayon'])}-{slug(p['gamme'])}-univers-sensoriel"
                else:
                    base = p['slug']
                nom, n = f'{base}-{rang}', rang
                while nom in pris:
                    n += 1
                    nom = f'{base}-{n}'
                pris.add(nom)
                rel = f'{dossier}/{nom}.webp'
                cible = os.path.join(sortie, rel)
                os.makedirs(os.path.dirname(cible), exist_ok=True)
                detoure = False
                if not os.path.exists(cible):
                    im = Image.open(i['source'])
                    coupe = detourer(im) if i['packshot'] else None
                    if coupe is not None:
                        coupe.save(cible, 'WEBP', quality=86, method=6)
                        detoure = True
                    else:
                        im = im.convert('RGB')
                        im.thumbnail((1600, 1600), Image.LANCZOS)
                        im.save(cible, 'WEBP', quality=82, method=6)
                else:
                    detoure = Image.open(cible).mode == 'RGBA'
                noms[cle] = {'fichier': rel, 'detoure': detoure, 'partage': usages[cle] > 1}
                fait += 1
                if fait % 25 == 0:
                    print(f'  images : {fait}/{total}', flush=True)
            p['images'].append(noms[cle])
        del p['_images']
    print(f'{fait} images uniques pour {len(catalogue)} produits')
