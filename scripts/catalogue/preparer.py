"""Prépare le catalogue Univers Sensoriel à partir de l'export de l'ancienne boutique.

    python preparer.py <dossier scrape>            # catalogue.json + index des images
    python preparer.py <dossier scrape> --images   # en plus : détourage et conversion WebP

Sorties : scripts/catalogue/catalogue.json et docs/02-images-produits/<rayon>/<nom>.webp
Règles (audit de novembre 2026) : aucune marque tierce, aucun « Chogan » dans les titres,
entretien de la maison et compléments alimentaires importés en brouillon.
"""
import collections
import glob
import html
import json
import os
import re
import sys

from regles import FAMILLES, MYTOLOGIK, RE_MARQUES, casse_titre, categorie, slug, texte_brut

ICI = os.path.dirname(os.path.abspath(__file__))
RACINE = os.path.dirname(os.path.dirname(ICI))
SORTIE_IMAGES = os.path.join(RACINE, 'docs', '02-images-produits')

GAMMES = {
    'Parfums femme': 'Olfazeta', 'Parfums homme': 'Olfazeta', 'Parfums enfant': 'Olfazeta', 'Accessoires': 'Olfazeta',
    'Collection Mytologik': 'Mytologik', 'Brumes parfumées': 'Aurodhea', "Parfums d'ambiance": 'Cooperativa Profumieri',
    'Huiles essentielles': 'Lolûm', 'Huiles végétales': 'Lolûm', 'Compléments alimentaires': 'Supplefit',
    'Entretien de la maison': 'Brilhome', 'Soin du linge': 'Brilhome',
}


def sans_chogan(t):
    t = re.sub(r"\s*\b(by\s+)?chogan\b", '', t, flags=re.I)
    return re.sub(r'\s{2,}', ' ', t).strip(' -–|')


def nettoyer_html(h, titre):
    """Description sans attributs, sans coches, sans marque tierce."""
    h = re.sub(r'<(script|style)[^>]*>.*?</\1>', '', h or '', flags=re.S | re.I)
    h = re.sub(r'<(/?)(\w+)[^>]*?>', lambda m: f'<{m.group(1)}{m.group(2).lower()}>', h)
    h = re.sub(r'</?(span|div|font|meta|img|a|u|section|article)>', ' ', h)
    h = h.replace('✅', '').replace('✔', '').replace('☑', '').replace('•', '')
    h = re.sub(r'<h1>', '<h2>', h).replace('</h1>', '</h2>')

    def bloc(m):
        balise, contenu = m.group(1), m.group(2)
        brut = texte_brut(contenu)
        if not brut:
            return ''
        if RE_MARQUES.search(brut):
            if balise.startswith('h'):
                return ''
            phrases = [p for p in re.split(r'(?<=[.!?])\s+', brut) if not RE_MARQUES.search(p)]
            if not phrases:
                return ''
            contenu = html.escape(' '.join(phrases), quote=False)
        contenu = re.sub(r'\s*<br>\s*', '<br>', contenu.strip())
        return f'<{balise}>{sans_chogan_html(contenu)}</{balise}>'

    h = re.sub(r'<(p|li|h2|h3|h4)>(.*?)</\1>', bloc, h, flags=re.S)
    h = re.sub(r'<(ul|ol)>\s*</\1>', '', h)
    # Premier titre identique au nom du produit : doublon du H1.
    m = re.match(r'\s*<h2>(.*?)</h2>', h, flags=re.S)
    if m and slug(texte_brut(m.group(1)))[:12] == slug(titre)[:12]:
        h = h[m.end():]
    h = re.sub(r'<(strong|b|em)>\s*</\1>', '', h)
    # Parfums d'ambiance : « Pièce / zone » et « Famille olfactive » hors paragraphe.
    motif = r'<strong>\s*(Pièce / zone|Famille olfactive)\s*:?\s*</strong>\s*:?\s*([^<]+)'
    libres = re.findall(motif, h)
    if libres:
        h = re.sub(motif, '', h)
        h += '<ul>' + ''.join(f'<li>{k.replace(" / zone", "")} : {v.strip()}</li>' for k, v in libres) + '</ul>'
    return re.sub(r'\n\s*\n+', '\n', re.sub(r'[ \t]+', ' ', h)).strip()


def sans_chogan_html(t):
    return re.sub(r'\s{2,}', ' ', re.sub(r"\s*\bchogan\b", '', t, flags=re.I))


def nom_produit(p, rayon):
    """Renvoie (nom, sous-titre, référence)."""
    t = re.sub(r'\s+', ' ', p['title']).strip()
    ref = ''
    if rayon in ('Parfums femme', 'Parfums homme'):
        m = re.search(r'(\d{2,3})\s*$', t)
        ref = m.group(1).zfill(3) if m else ''
        nom = re.split(r'\s*-\s*-?\s*parfum\b', t, flags=re.I)[0]
        nom = re.split(r'\s[–|]\s', nom)[0]
        nom = sans_chogan(casse_titre(nom))
        return nom, '', ref
    if rayon == 'Parfums enfant':
        m = re.search(r'(\d{3})', t)
        qui = 'Bébé' if 'béb' in t.lower() else ('Fille' if 'fille' in t.lower() else 'Garçon')
        return f'Eau de parfum {qui}', 'Hypoallergénique, 30 ml', m.group(1) if m else ''
    m = re.search(r'-(bsf|crf)(\d{3})', p['handle'])
    if m or 'gel douche' in t.lower() or 'gels douche' in t.lower() or 'crème corps parfum' in t.lower():
        ref = m.group(2) if m else ''
        senteur = sans_chogan(t.split(' - ')[-1]) if ' - ' in t else ''
        if 'gel' in t.lower():
            base = 'Gel douche parfumé'
        else:
            base = 'Crème corps parfumée'
        if not senteur or RE_MARQUES.search(senteur):
            senteur = f'n° {ref}'
        return f'{base} {senteur[:1].upper() + senteur[1:]}', '', ref
    parties = re.split(r'\s[–|]\s|\s-\s(?=[A-ZÉ])', sans_chogan(t), maxsplit=1)
    nom = casse_titre(parties[0].strip(' -'))
    sous = parties[1].strip() if len(parties) > 1 else ''
    nom = re.sub(r'\s*-\s*$', '', nom.replace('™', ''))
    nom = re.sub(r'\s+Aurodhea\b', '', nom).replace('MΨTOLOGIK', 'Mytologik')
    nom = re.sub(r'^Rituel\s*-\s*', 'Rituel ', nom)
    nom = re.sub(r'\s*[-–]?\s*\d+\s?(ml|g)\s*$', '', nom, flags=re.I).strip(' -,')
    sous = re.sub(r'\s+Aurodhea\b', '', sous)
    if sous.upper() == sous:
        sous = sous.capitalize()
    return nom, sous, ref


def familles_notes(brut):
    fams, notes = [], []
    m = re.search(r'Famille olfactive\s*:\s*(.{3,45}?)(?=\s+Essence|\s+Notes?\s*:|\s+Pièce|\s+Sillage|$)', brut)
    libelle = m.group(1).strip(' .') if m else ''
    for cle, fam in FAMILLES.items():
        if cle in libelle.lower() and fam not in fams:
            fams.append(fam)
    if 'oud' in libelle.lower() and 'Boisé' not in fams:
        fams.append('Boisé')
    listes = re.findall(r"(?:aux |ses |Ses )?notes? (?:de |d['’])([^.;:]{4,90}?)(?=\s+(?:incarne|crée|créent|cree|déclenche|évoque|révèle|pour |se mêl|offre|dessine|compose|capture|enveloppe|séduisent|séduit|apporte|signe|ouvre|laisse|sublime|font|fait|est |sont |dans |—|–)|[.;:]|$)", brut)
    listes += re.findall(r'(?:Notes|Fond|Tête|Cœur|Coeur)\s*:\s*([^.;:]{4,90}?)(?=\s+(?:Fond|Tête|Cœur|Coeur|Nettoie|Hydrate|Essence|Sillage|\d)|$)', brut)
    for liste in listes:
        for n in re.split(r',| et | & ', liste):
            n = re.sub(r"^(de |d['’]|du |des |la |le |les |l['’])", '', n.strip(' .'), flags=re.I).strip()
            if 2 < len(n) < 24 and not re.search(r'\d', n) and len(n.split()) <= 3:
                n = n[:1].upper() + n[1:].lower()
                if n not in notes:
                    notes.append(n)
    return libelle, fams, notes[:7]


def format_propre(v):
    v = re.sub(r'\s+', ' ', v).strip()
    v = re.sub(r'(\d)\s*m[lL]\b', r'\1 ml', v)
    return v[:1].upper() + v[1:]


def construire(scrape):
    produits = json.load(open(os.path.join(scrape, '01-donnees', 'produits.json'), encoding='utf-8'))
    index = collections.defaultdict(list)
    for f in glob.glob(os.path.join(scrape, '03-images', 'produits', '*', '*')):
        m = re.match(r'(.+)-(\d+)\.(jpe?g|png|webp)$', os.path.basename(f), flags=re.I)
        if m:
            index[m.group(1)].append((int(m.group(2)), f))
    sortie, slugs = [], set()
    for p in produits:
        univers, rayon, publie = categorie(p)
        nom, sous, ref = nom_produit(p, rayon)
        brut = re.sub(r'\s+', ' ', re.sub('[✅✔☑•]', ' ', texte_brut(p['body_html'])))
        libelle_famille, fams, notes = familles_notes(brut) if univers in ('Parfums', 'Art de vivre') or 'parfum' in nom.lower() else ('', [], [])
        genre = ''
        if rayon == 'Parfums femme':
            genre = 'Femme'
        elif rayon == 'Parfums homme':
            genre = 'Homme'
        elif rayon == 'Parfums enfant':
            genre = 'Enfant'
        elif rayon == 'Collection Mytologik':
            genre = {'AFRODITE': 'Femme', 'ARTEMIDE': 'Femme', 'MEDUSA': 'Femme'}.get(p['title'].strip().upper(), 'Homme' if p['title'].strip().upper() in MYTOLOGIK else 'Mixte')
        elif 'mixte' in (p.get('product_type') or '').lower():
            genre = 'Mixte'
        elif re.search(r'pour femme\b', brut, re.I) or 'femme' in (p.get('product_type') or '').lower():
            genre = 'Femme'
        elif re.search(r'pour homme\b', brut, re.I):
            genre = 'Homme'
        collections_sup = []
        if univers == 'Parfums' and (nom.lower().startswith('luxury') or nom.upper() in ('IMPERATRIX', 'NADIR')):
            collections_sup.append('Collection Luxury')
        gamme = GAMMES.get(rayon, '')
        if not gamme:
            bas = (p['title'] + ' ' + brut[:400] + ' ' + p['handle']).lower()
            gamme = next((g for cle, g in (('peptilux', 'Peptilux'), ('tq-10', 'Peptilux'), ('anti-rides', 'Peptilux'), ('antirides', 'Peptilux'), ('olfazeta', 'Olfazeta'), ('parfum', 'Olfazeta')) if cle in bas), 'Aurodhea')
        if rayon in ('Parfums femme', 'Parfums homme'):
            sous = 'Extrait de parfum' + (f' · {libelle_famille.lower()}' if libelle_famille else '')
            base_slug = f"{slug(nom)}-parfum-{genre.lower()}" + (f'-{ref}' if ref else '')
        else:
            base_slug = slug(nom)[:70].strip('-')
        s, i = base_slug, 2
        while s in slugs:
            s, i = f'{base_slug}-{i}', i + 1
        slugs.add(s)
        description = nettoyer_html(p['body_html'], nom)
        paragraphes = [texte_brut(x) for x in re.findall(r'<p>(.*?)</p>', description, flags=re.S)]
        accroche = next((x for x in paragraphes if len(x) > 60 and x.upper() != x), '') or texte_brut(description)[:240]
        if len(accroche) > 320:
            accroche = re.sub(r'\s+\S*$', '', accroche[:300]) + '…'
        nom_option = (p.get('options') or [{}])[0].get('name', 'Format')
        variations = []
        for v in p['variants']:
            if v['title'] == 'Default Title':
                continue
            variations.append({'format': format_propre(v['title']), 'prix': v['price'],
                               'ancien_prix': v.get('compare_at_price'), 'dispo': bool(v.get('available', True))})
        prix = p['variants'][0]['price']
        if len(variations) == 1:  # une seule déclinaison : produit simple
            sous = sous or variations[0]['format']
            variations = []
        prefixe = {'Parfums': 'P', 'Beauté': 'B', 'Bien-être': 'E', 'Art de vivre': 'V'}[univers]
        sortie.append({
            'source': p['handle'], 'slug': s, 'nom': nom, 'sous_titre': sous, 'reference': ref,
            'sku': f"US-{prefixe}{len(sortie) + 1:03d}",
            'univers': univers, 'rayon': rayon, 'collections': collections_sup, 'publie': publie,
            'genre': genre, 'gamme': gamme, 'famille': libelle_famille, 'familles': fams, 'notes': notes,
            'accroche': accroche, 'description': description,
            'option': 'Format' if nom_option.lower() in ('format', 'taille', 'size', 'title') else nom_option,
            'prix': prix, 'ancien_prix': p['variants'][0].get('compare_at_price'), 'variations': variations,
            'dispo': any(v.get('available', True) for v in p['variants']),
            '_fichiers': [f for _, f in sorted(index.get(p['handle'][:70], []))],
        })
    return sortie


def main():
    scrape = sys.argv[1]
    catalogue = construire(scrape)
    if '--images' in sys.argv:
        import images
        images.traiter(catalogue, SORTIE_IMAGES)
    elif os.path.exists(os.path.join(ICI, 'catalogue.json')):
        anciens = {p['source']: p.get('images', []) for p in json.load(open(os.path.join(ICI, 'catalogue.json'), encoding='utf-8'))}
        for p in catalogue:
            p['images'] = anciens.get(p['source'], [])
    for p in catalogue:
        p.pop('_fichiers', None)
        p.setdefault('images', [])
    json.dump(catalogue, open(os.path.join(ICI, 'catalogue.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    c = collections.Counter((p['univers'], p['rayon'], p['publie']) for p in catalogue)
    for k in sorted(c):
        print(f'{c[k]:4d}  {k[0]} › {k[1]}' + ('' if k[2] else '  (brouillon)'))
    print(len(catalogue), 'produits ->', os.path.join(ICI, 'catalogue.json'))


if __name__ == '__main__':
    sys.stdout.reconfigure(encoding='utf-8')
    main()
