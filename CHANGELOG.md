# Journal des versions

Les versions suivent `MAJEUR.MINEUR.CORRECTIF`. Le site n'est pas encore en ligne : la mise en ligne sera la 1.0.0.

## 0.1.0 – 18 novembre 2026

Première version complète en local.

### Site

- Thème sur mesure à la charte Univers Sensoriel : vert sapin, ivoire et or, titres en Marcellus, texte en Jost, polices hébergées dans le thème.
- Accueil : ouverture sombre, sélection à onglets, quatre univers, bloc Maison, familles olfactives, collection Luxury, boutiques.
- En-tête collant avec méga-menu par univers, recherche, menu mobile en tiroir.
- Boutique : pastilles de sous-rayons, tri, filtres en tiroir (pour qui, famille olfactive, format, gamme, prix), pagination.
- Fiche produit : galerie, formats en pastilles, profil olfactif, accordéons, « Complétez le rituel », produits du même rayon.
- Pages : La Maison, Contact, Nos boutiques, Livraison et retours, Questions fréquentes, Guide des familles olfactives, Devenir consultante, pages légales.

### Catalogue

- 375 fiches reprises de l'ancienne boutique, nettoyées : 374 publiées, 1 en brouillon (prix à vérifier).
- Aucune marque tierce ni « Chogan » dans les fiches.
- Entretien de la maison et compléments alimentaires réécrits : ton sobre, sans allégation de santé, mention réglementaire sur chaque complément.
- 370 images en WebP : packshots détourés, photos d'ambiance triées à la main, aucune image générée de l'ancien site.

### Référencement

- Yoast SEO : titres, descriptions et mots-clés de 15 pages et 23 rayons dans `scripts/seo.json`.
- Titres et descriptions calculés pour chaque fiche produit et chaque famille olfactive.
- Une page par famille olfactive, plan du site, image de partage, données structurées Organization et Product.

### Outillage

- Bedrock, Sage 11 (Acorn, Vite, Tailwind CSS 4), WooCommerce.
- `composer seed` : réglages, rayons, catalogue, livraison, pages et référencement, relançable sans doublon.
- Scripts Python de préparation du catalogue et des images.
- Git flow : voir `CONTRIBUTING.md`.
