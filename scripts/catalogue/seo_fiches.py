"""Référencement des fiches produit : requête cible, titre et méta-description de chaque référence.

    python scripts/catalogue/seo_fiches.py            # écrit scripts/seo-fiches.json
    python scripts/catalogue/seo_fiches.py --liste    # affiche aussi chaque fiche

Entrées : catalogue.json (nom, rayon, famille olfactive, notes, formats) et seo-manuel.json (fiches rédigées à la main).
Sortie : scripts/seo-fiches.json, appliqué par scripts/seo.php (Yoast : titre SEO, méta-description, requête cible).

Règles :
- la requête cible ouvre le titre et figure dans la description ;
- titre ≤ 60 caractères, « | Univers Sensoriel » ajouté quand il tient ;
- description de 120 à 150 caractères, faite de faits (famille, notes, formats, livraison), sans prix ni promesse de santé ;
- aucune marque tierce : une description qui en cite une est refusée.
"""

import json
import os
import re
import sys

ICI = os.path.dirname(os.path.abspath(__file__))
MARQUE = ' | Univers Sensoriel'
LIVRAISON = ' Livraison offerte dès 49 €.'
INTERDITS = ('montale', 'orto parisi', 'megamare', 'chogan', 'dior', 'chanel', 'guerlain', 'lancôme', 'armani')
TITRE_MAX, DESC_MIN, DESC_MAX = 60, 120, 150


def droit(texte):
    """Apostrophes droites et espaces simples, comme dans seo.json."""
    return re.sub(r'\s+', ' ', texte.replace('’', "'").replace(' ', ' ')).strip()


def minuscule(texte):
    return texte[:1].lower() + texte[1:]


def liste(mots):
    mots = [minuscule(droit(m)) for m in mots]
    return mots[0] if len(mots) == 1 else ', '.join(mots[:-1]) + ' et ' + mots[-1]


def notes(p, combien=3):
    """« aux notes de jasmin, ambre et musc blanc » (rien si les notes du catalogue sont douteuses)."""
    propres = [n for n in p['notes'] if not re.search(r'notes|touches|sensuelles|fruits doux', n, re.I)][:combien]
    if len(propres) < 2:
        return ''
    de = "d'" if droit(propres[0])[0].lower() in 'aeéèiouyh' else 'de '
    return f' aux notes {de}{liste(propres)}'


def formats(p):
    """« en 15, 30 et 70 ml » à partir des déclinaisons."""
    ml = sorted({int(m.group(1)) for v in p['variations'] if (m := re.fullmatch(r'(\d+) ?ml', v['format']))})
    return f'en {liste([str(x) for x in ml])} ml' if ml else ''


def titre(base):
    base = droit(base)
    return base + MARQUE if len(base) + len(MARQUE) <= TITRE_MAX else base


def choisir(*variantes):
    """La variante la plus complète qui tient dans la limite ; la livraison comble une description trop courte."""
    for v in variantes:
        v = droit(v)
        if len(v) <= DESC_MAX:
            for ajout in (LIVRAISON, ' Paiement sécurisé.'):  # une description trop courte est comblée par un fait
                if len(v) < DESC_MIN and ajout.strip() not in v and len(v + ajout) <= DESC_MAX:
                    v += ajout
            return v
    return droit(variantes[-1])


def raccourcir(texte, limite=DESC_MAX):
    texte = droit(re.sub(r'<[^>]+>', ' ', texte))
    if len(texte) <= limite:
        return texte
    coupe = texte[:limite]
    fin = coupe.rfind('. ')
    if fin > limite * 0.6:
        return coupe[:fin + 1]
    coupe = coupe[:coupe.rfind(' ')]
    # Pas de fin en l'air (« … et les », « … qui ») : on recule jusqu'à un mot plein.
    coupe = re.sub(r"(\s+(et|ou|qui|que|pour|sur|dans|avec|sans|aux?|du|des?|les?|la|un|une|en|à|d'|l'))+$", '', coupe.rstrip(' ,;:'))
    return coupe.rstrip(' ,;:') + '…'


def parfum(p):
    genre = {'Femme': 'femme', 'Homme': 'homme'}.get(p['genre'], '')
    famille = droit(p['sous_titre'].split('·')[1]) if '·' in p['sous_titre'] else ''
    nom = droit(p['nom'])
    if famille:
        cle = f'parfum {genre} {famille}'
        tete = court = f'{nom}, extrait de parfum {genre} {famille}' + ('' if notes(p) else ' longue tenue')
    else:  # pas de famille olfactive au catalogue : le nom distingue la fiche, l'accroche la décrit
        cle = f'parfum {genre} {nom}'
        tete = f'{nom}, extrait de parfum {genre}' + (' de la collection Luxury' if p['collections'] else ' longue tenue')
        court = tete
        phrase = re.split(r'(?<=[.!?])\s', droit(p['accroche']))[0].rstrip('.')
        if '|' not in phrase and 'Saint-Valentin' not in phrase and not notes(p):
            tete = f'{nom}, extrait de parfum {genre} : {minuscule(phrase)}'
    concentre = ' Concentré à 30 %' if '30%' in p['description'].replace(' ', '').replace(' ', '') else ''
    f = formats(p)
    suite = (f'{concentre}, {f}.' if concentre else f' {f[0].upper()}{f[1:]}.') if f else (f'{concentre}.' if concentre else '')
    if not famille and len(f'{cle} – extrait de parfum') > TITRE_MAX:
        nom = ''
    return {
        'motcle': droit(cle),
        'titre': titre(f'{cle[0].upper()}{cle[1:]}') if not nom else titre(f'{cle[0].upper()}{cle[1:]} – {nom}' if famille else f'{cle[0].upper()}{cle[1:]} – extrait de parfum'),
        'description': choisir(
            f'{tete}{notes(p)}.{suite}{LIVRAISON}', f'{tete}{notes(p)}.{suite}', f'{tete}{notes(p, 2)}.{suite}',
            f'{tete}.{suite}{LIVRAISON}', f'{tete}.{suite}', f'{tete}.', f'{court}.{suite}{LIVRAISON}', f'{court}.{suite}',
        ),
    }


def parfume(p, type_):
    """Gels douche et crèmes corps assortis aux parfums : « Gel douche parfumé Black Code »."""
    senteur = droit(p['nom'][len(type_):])
    familles = ' '.join(p['familles']).lower()
    genre = {'Femme': ' pour femme', 'Homme': ' pour homme', 'Mixte': ' mixte'}.get(p['genre'], '')
    geste = ' Il nettoie en douceur et parfume la peau.' if type_.startswith('Gel') else ' Elle hydrate et parfume la peau.'
    tete = f'{type_} {senteur}{genre}'
    long = f'{type_} {senteur} – {familles}' if familles else ''
    return {
        'motcle': f'{type_.lower()} {senteur}',
        'titre': titre(long if long and len(long) <= TITRE_MAX else f'{type_} {senteur}'),
        'description': choisir(f'{tete}{notes(p)}.{geste}{LIVRAISON}', f'{tete}{notes(p)}.{geste}', f'{tete}.{geste}{LIVRAISON}'),
    }


def ambiance(p):
    nom = droit(p['nom'])
    familles = ' '.join(p['familles']).lower()
    f = formats(p)
    tete = f"Parfum d'ambiance {nom}" + (f', {familles}' if familles else '')
    long = f"Parfum d'ambiance {nom} – {familles}"
    return {
        'motcle': f"parfum d'ambiance {nom}",
        'titre': titre(long if familles and len(long) <= TITRE_MAX else f"Parfum d'ambiance {nom}"),
        'description': choisir(
            f'{tete}, pour le salon, la chambre ou la salle de bains. {f[0].upper()}{f[1:]}.{LIVRAISON}',
            f'{tete}, pour le salon, la chambre ou la salle de bains. {f[0].upper()}{f[1:]}.',
        ),
    }


def a_sous_titre(p, deja):
    """Entretien et compléments : le sous-titre dit ce qu'est le produit (« Nettoyant pour joints, parfum… »)."""
    nom, sous = droit(p['nom']), droit(p['sous_titre'])
    genre_ = sous.split(',')[0]
    if nom.startswith('Chiffon microfibre'):
        cle = f'chiffon microfibre {minuscule(genre_)}'
        base = f'Chiffon microfibre {minuscule(genre_)} {nom.split()[-1]}'
    elif nom.split()[0].lower() in ('tête', 'éponge', 'jus'):  # le nom dit déjà le type (« Tête de vaporisateur… »)
        cle, base = minuscule(nom), f'{nom} : {minuscule(sous)}'
    else:
        cle = minuscule(genre_)
        if cle in deja:  # plusieurs « complément alimentaire » ou « nettoyant pour sols » : le nom distingue
            cle = f'{minuscule(genre_)} {nom}'
        base = f'{genre_} {nom}'
    if len(base) > TITRE_MAX:  # « Complément alimentaire en sticks à diluer Dren Fast… » : on garde le type court
        court = ' '.join(genre_.split()[:2])
        cle, base = f'{minuscule(court)} {nom}', f'{court} {nom}'
    # La requête ouvre la description, suivie de l'accroche (déjà relue : des faits, pas de promesse de santé).
    accroche = droit(p['accroche'])
    return {'motcle': cle, 'titre': titre(base), 'description': choisir(f'{base}. {accroche}', raccourcir(f'{base}. {accroche}'))}


def requete_naturelle(cle, titre_):
    """« shampoing aloe vera » devient « shampoing à l'aloe vera » : la forme exacte du titre, que Yoast retrouve en tête."""
    if titre_.lower().startswith(cle.lower()):
        return cle
    voulus = set(re.findall(r'[\w%-]+', cle.lower()))
    for m in re.finditer(r'[\w%-]+', titre_):
        voulus.discard(m.group().lower())
        if not voulus:
            debut = titre_[:m.end()]
            return minuscule(debut) if len(debut.split()) <= len(cle.split()) + 3 else cle
    return cle


def main():
    catalogue = json.load(open(os.path.join(ICI, 'catalogue.json'), encoding='utf-8'))
    manuel = json.load(open(os.path.join(ICI, 'seo-manuel.json'), encoding='utf-8'))
    fiches, alertes, deja = {}, [], set()
    for p in catalogue:
        sku = p['sku']
        if sku in manuel:
            cle, t, d = manuel[sku]
            f = {'motcle': requete_naturelle(cle, t), 'titre': titre(t), 'description': choisir(d or raccourcir(p['accroche']))}
        elif p['rayon'] in ('Parfums femme', 'Parfums homme'):
            f = parfum(p)
        elif p['nom'].startswith('Gel douche parfumé'):
            f = parfume(p, 'Gel douche parfumé')
        elif p['nom'].startswith('Crème corps parfumée'):
            f = parfume(p, 'Crème corps parfumée')
        elif p['rayon'] == "Parfums d'ambiance":
            f = ambiance(p)
        elif p['sous_titre']:
            f = a_sous_titre(p, deja)
        else:
            alertes.append(f'{sku} {p["nom"]} : aucune règle, à ajouter dans seo-manuel.json')
            continue
        deja.add(f['motcle'])
        fiches[sku] = f
        if len(f['titre']) > TITRE_MAX:
            alertes.append(f'{sku} titre de {len(f["titre"])} caractères : {f["titre"]}')
        if not DESC_MIN - 25 <= len(f['description']) <= DESC_MAX:
            alertes.append(f'{sku} description de {len(f["description"])} caractères : {f["description"]}')
        if any(m in (f['titre'] + f['description']).lower() for m in INTERDITS):
            alertes.append(f'{sku} cite une marque tierce : {f["description"]}')
        if not f['titre'].lower().startswith(f['motcle'].lower()[:12]):
            alertes.append(f'{sku} le titre ne commence pas par la requête « {f["motcle"]} » : {f["titre"]}')
        if '--liste' in sys.argv:
            print(f'{sku} [{f["motcle"]}]\n   {f["titre"]}\n   {f["description"]}')
    sortie = os.path.join(os.path.dirname(ICI), 'seo-fiches.json')
    json.dump(fiches, open(sortie, 'w', encoding='utf-8', newline='\n'), ensure_ascii=False, indent=1)
    print(f'{len(fiches)} fiches sur {len(catalogue)} -> {os.path.relpath(sortie)}')
    cles = [f['motcle'] for f in fiches.values()]
    print(f'{len(set(cles))} requêtes cibles différentes')
    for a in alertes:
        print('  !', a)


if __name__ == '__main__':
    main()
