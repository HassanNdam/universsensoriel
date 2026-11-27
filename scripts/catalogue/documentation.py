"""Documentation et certifications : les documents publiés par le fabricant, prêts à importer dans le site.

Source : https://www.chogangroupspa.com/documents (version française). Chaque lien « doc_view/<code> » mène à
dflip/page_flip.php?doc=<nom> ; le PDF est à https://www.chogangroup.com/documenti/<nom>.pdf.

Sortie :
- docs/04-documentation/pdf/*.pdf : les PDF, renommés (hors dépôt Git : 146 Mo, retéléchargés par ce script) ;
- docs/04-documentation/couvertures/*.webp : couvertures des catalogues (carrées, 800 px) ;
- scripts/catalogue/documentation/documents.json : la liste lue par l'import du thème (App\\importer_documents).

    python scripts/catalogue/documentation.py         (fichiers déjà téléchargés : non retéléchargés)

Règles de l'audit : pas de personne en couverture. Les couvertures du maquillage, de MoniAmori et d'Aurodhea
(visages, mains) sont remplacées par une page du catalogue où l'on ne voit que les produits.
Documents rangés mais masqués au départ (case « Afficher » dans l'administration) : le catalogue des compléments
alimentaires (non vendus en ligne), les conditions de vente et la politique de confidentialité des partenaires du
fabricant (elles ne s'appliquent pas aux clientes du site, qui ont les leurs).
"""
import io
import json
import os
import sys
import urllib.request

import numpy as np
import pymupdf
from PIL import Image

ICI = os.path.dirname(os.path.abspath(__file__))
RACINE = os.path.dirname(os.path.dirname(ICI))
SORTIE = os.path.join(RACINE, 'docs', '04-documentation')
JSON = os.path.join(ICI, 'documentation', 'documents.json')
BRUTS = os.environ.get('US_DOCUMENTATION_BRUTS', r'C:\Users\hndam\OneDrive\Bureau\Projets_Dev\k-ellesensorielle\scrape\documentation-2026-11')
PDF = 'https://www.chogangroup.com/documenti/{}_documents.pdf'
COUVERTURE = 'https://www.chogangroup.com/documenti/cover//{}_cover.jpg'

# fichier, titre, rubrique, précision, nom du PDF chez le fabricant, couverture (numéro chez le fabricant,
# ou page du PDF à reprendre, comptée à partir de 1), afficher.
DOCUMENTS = [
    dict(fichier='catalogue-olfazeta', titre='Olfazeta', rubrique='catalogues', precision='Extraits de parfum', pdf=1790688767, couverture=1791208211),
    dict(fichier='catalogue-mytologik', titre='Mytologik', rubrique='catalogues', precision='Parfums-sculptures', pdf=1758177649, couverture=1791208250),
    dict(fichier='catalogue-cooperativa-profumieri', titre='Cooperativa Profumieri', rubrique='catalogues', precision='Parfums d\'ambiance', pdf=1776171628, couverture=1791208259),
    dict(fichier='catalogue-peptilux', titre='Peptilux', rubrique='catalogues', precision='Soins du visage aux peptides', pdf=1740410027, couverture=1791208473),
    dict(fichier='catalogue-aurodhea', titre='Aurodhea', rubrique='catalogues', precision='Soins du corps, du visage et des cheveux', pdf=1792576601, page=12),
    dict(fichier='catalogue-lolum', titre='Lolûm', rubrique='catalogues', precision='Huiles végétales et essentielles', pdf=1774515759, couverture=1791208242),
    dict(fichier='catalogue-maquillage', titre='Maquillage', rubrique='catalogues', precision='Teint, yeux, lèvres', pdf=1761039811, page=21),
    dict(fichier='catalogue-moniamori', titre='MoniAmori', rubrique='catalogues', precision='Maquillage', pdf=1785326049, page=4),
    dict(fichier='catalogue-brilhome', titre='Brilhome', rubrique='catalogues', precision='Entretien de la maison', pdf=1768810497, couverture=1791208223),
    dict(fichier='catalogue-kleyes', titre='Kleyes', rubrique='catalogues', precision='Lunettes de soleil', pdf=1765987058, couverture=1791208187),
    dict(fichier='catalogue-supplefit', titre='SuppleFit', rubrique='catalogues', precision='Compléments alimentaires', pdf=1793778766, couverture=1791208278, afficher=False),
    dict(fichier='certificat-de-conformite', titre='Certificat de conformité', rubrique='certificats', precision='En italien et en anglais', pdf=1778154279),
    dict(fichier='modele-organisation-231', titre='Modèle d\'organisation, de gestion et de contrôle', rubrique='engagements', precision='Décret italien 231/2001, en italien', pdf=1781771301),
    dict(fichier='code-ethique-et-de-conduite', titre='Code d\'éthique et de conduite', rubrique='engagements', precision='En italien', pdf=1781771320),
    dict(fichier='procedure-signalement', titre='Procédure de signalement (whistleblowing)', rubrique='engagements', precision='En italien', pdf=1781521922),
    dict(fichier='conditions-generales-de-vente-fabricant', titre='Conditions générales de vente du fabricant', rubrique='engagements', precision='Pour ses partenaires', pdf=1769162600, afficher=False),
    dict(fichier='politique-confidentialite-partenaires', titre='Politique de confidentialité des partenaires', rubrique='engagements', precision='Pour les partenaires du fabricant', pdf=1743093140, afficher=False),
]


def telecharger(url, cible):
    if not os.path.isfile(cible):
        requete = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(requete, timeout=120) as reponse, open(cible, 'wb') as f:
            f.write(reponse.read())
    return cible


def sans_marge(im):
    """Retire la marge blanche d'une page (photo non imprimée à fond perdu) : lignes et colonnes presque toutes blanches."""
    gris = np.asarray(im.convert('L')) < 235
    lignes, colonnes = np.where(gris.mean(axis=1) > 0.5)[0], np.where(gris.mean(axis=0) > 0.5)[0]
    if not len(lignes) or not len(colonnes):
        return im
    return im.crop((colonnes[0], lignes[0], colonnes[-1] + 1, lignes[-1] + 1))


def carre(im, taille=800):
    """Centre carré, redimensionné."""
    cote = min(im.size)
    gauche, haut = (im.width - cote) // 2, (im.height - cote) // 2
    return im.crop((gauche, haut, gauche + cote, haut + cote)).resize((taille, taille), Image.LANCZOS)


def construire():
    os.makedirs(BRUTS, exist_ok=True)
    for dossier in ('pdf', 'couvertures'):
        os.makedirs(os.path.join(SORTIE, dossier), exist_ok=True)
    liste = []
    for d in DOCUMENTS:
        brut = telecharger(PDF.format(d['pdf']), os.path.join(BRUTS, f"{d['pdf']}_documents.pdf"))
        pdf = os.path.join(SORTIE, 'pdf', d['fichier'] + '.pdf')
        if not os.path.isfile(pdf) or os.path.getsize(pdf) != os.path.getsize(brut):
            with open(brut, 'rb') as source, open(pdf, 'wb') as cible:
                cible.write(source.read())
        document = pymupdf.open(pdf)
        pages = document.page_count
        couverture = ''
        if d.get('couverture') or d.get('page'):
            if d.get('couverture'):
                jpg = telecharger(COUVERTURE.format(d['couverture']), os.path.join(BRUTS, f"cover_{d['couverture']}.jpg"))
                im = Image.open(jpg).convert('RGB')
            else:
                pix = document[d['page'] - 1].get_pixmap(dpi=110)
                im = sans_marge(Image.open(io.BytesIO(pix.tobytes('png'))).convert('RGB'))
            couverture = d['fichier'] + '.webp'
            carre(im).save(os.path.join(SORTIE, 'couvertures', couverture), 'WEBP', quality=82, method=6)
        liste.append({
            'titre': d['titre'],
            'rubrique': d['rubrique'],
            'fichier': 'pdf/' + d['fichier'] + '.pdf',
            'couverture': 'couvertures/' + couverture if couverture else '',
            'description': f"{d['precision']} · {pages} pages",
            'afficher': d.get('afficher', True),
            'source': PDF.format(d['pdf']),
        })
        print(f"{d['fichier']}: {pages} p, {os.path.getsize(pdf) / 1e6:.1f} Mo{', couverture' if couverture else ''}")
    os.makedirs(os.path.dirname(JSON), exist_ok=True)
    with open(JSON, 'w', encoding='utf-8') as f:
        json.dump(liste, f, ensure_ascii=False, indent=1)
    print(f'{len(liste)} documents → {JSON}')


if __name__ == '__main__':
    sys.exit(construire())
