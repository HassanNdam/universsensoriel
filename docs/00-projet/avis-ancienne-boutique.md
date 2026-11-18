# Avis de l'ancienne boutique

Relevé du 18 novembre 2026 sur maisondubien-etrek-ellesensorielle.com (application Judge.me, 30 avis renvoyés).

## Ce qui est repris sur le site (8 avis, 5 personnes)

Ils sont dans `scripts/avis.json` et importés comme avis WooCommerce.

| Date | Auteur | Produit | Achat vérifié (Judge.me) | Remarque |
| --- | --- | --- | --- | --- |
| 17/07/2026 | Ann | Lush Cloud Pacific Aura, Brazilian Sunset, Golden Hour | oui (Shop) | Trois avis identiques, un par produit acheté. Un seul est montré sur l'accueil |
| 28/06/2026 | Anonyme | The Muse | non | Texte long et spontané, repris tel quel, fautes comprises |
| 21/06/2026 | Léa | Divine Aura | non | Repris tel quel |
| 10/06/2026 | Jean-Marc D. | Gel douche parfumé Obscuro | oui | Extrait : la suite comparait le produit à celui d'une marque de luxe |
| 10/04/2026 | Olivia | La boutique | non | Repris tel quel |
| 10/04/2026 | Olivia | Armozent | non | Extrait : le début parlait d'un effet sur le sommeil. Affiché sur la fiche seulement |

## Ce qui n'est pas repris (22 avis)

Aucun ne porte l'indication « acheteur vérifié ». Ils ont été saisis par lots, plusieurs le même jour, avec un titre qui reprend le type de produit et une phrase courte du même style. Rien ne permet de les rattacher à une commande : tant que leur origine n'est pas confirmée, ils ne sont pas affichés.

| Date de saisie | Nombre | Auteurs | Produits |
| --- | --- | --- | --- |
| 27/02/2026 | 2 | Salomé P, Inès K. | Déo-crème, huiles végétales |
| 01/03 au 10/03/2026 | 4 | Jade L., Mélanie S., Chloé R, Élodie N. | Huile de neem, huiles végétales, déo-crème |
| 14/03 et 17/03/2026 | 5 | Jerôme, Thomas.L, Kone, Jonathan, swchevencia | Parfums 101 et 066, Brilgrease, adoucissant, Bril WC |
| 21/03 et 24/03/2026 | 6 | Nadia A., Laurencia.P, Astride, Feby, François.Z, Merveille | Huile de rose musquée, Brilgrill, parfum 010, lessive, Briltotal, GreenDish |
| 29/03/2026 | 4 | Bernadette, Sylvestre, Denis, Karine.S | Saverdish, adoucissant, Bril WC, Brilglass |
| 07/04/2026 | 1 | Ngono | Pure Gold |

Si certains viennent de vraies clientes (messages reçus par WhatsApp puis recopiés, par exemple), ils peuvent être ajoutés à `scripts/avis.json` avec `"verifie": false`, à condition de garder la preuve de leur origine.

## Règles d'affichage retenues

- Le texte n'est jamais modifié. Un avis coupé porte la mention « Extrait ».
- La mention « Achat vérifié » reprend l'indication de Judge.me, puis celle de WooCommerce pour les avis déposés sur le nouveau site.
- Pas de note moyenne ni de compteur sur la vitrine : avec huit avis, le chiffre ne voudrait rien dire.
- Sur le nouveau site, seuls les acheteurs connectés peuvent donner un avis, et chaque avis est relu avant publication.
