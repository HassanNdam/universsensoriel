# Journal des versions

Les versions suivent `MAJEUR.MINEUR.CORRECTIF`. Le site n'est pas encore en ligne : la mise en ligne sera la 1.0.0.

## À venir

- Paiement par carte avec Stripe (passerelle officielle de WooCommerce), en mode test. Clés lues dans le `.env`, réglages dans `scripts/paiement.php`.
- Fonds teintés : nouveau ton « lin clair » (#F1E8D8) pour les cartes, les encarts et les sections qui étaient blanches.
- Cartes produit et cartes d'article : fond discret sous le visuel, dernier lien toujours aligné en bas ; apostrophes des titres d'article corrigées.

- Référencement complet : titre, méta-description et requête cible Yoast pour les 375 fiches produit (`scripts/seo-fiches.json`), les rayons, les pages et les articles ; image de partage 1200 × 630 pour chaque page (réseaux sociaux, WhatsApp) ; notes Yoast affichées dans l'admin ; l'éditeur Yoast analyse la page publiée. `composer seo` réapplique le référencement seul.

- Textes raccourcis sur tout le site : surtitres et phrases d'introduction retirés quand ils n'apportaient rien, page Contact allégée, rayons décrits en une phrase, pages de contenu resserrées.

- Formulaires (Forminator) : formulaire de contact avec accusé de réception, page Contact réorganisée en deux colonnes, inscription à la lettre d'information en tête du pied de page. Formulaires décrits dans `scripts/formulaires.php` et recréés par le seed.
- Le journal : huit articles repris de l'ancien blog et relus, quatre rubriques, page d'article, derniers articles sur l'accueil.
- Nouveaux avis : invitation dans l'e-mail « commande terminée », relecture dans Produits › Avis, choix des avis de l'accueil par la colonne « Accueil », compte client créé à la commande.
- Avis clients : huit avis de l'ancienne boutique reproduits tels quels (accueil, fiches, page « Avis clients »), mention « Achat vérifié » seulement quand elle est vraie, dépôt d'avis réservé aux acheteurs et relu avant publication.

- Belle Peau publiée : prix remis dans le bon sens (81,90 € la boîte, 163,80 € les trois), fiche réécrite.

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
