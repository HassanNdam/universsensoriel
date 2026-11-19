# Journal des versions

Les versions suivent `MAJEUR.MINEUR.CORRECTIF`. Le site n'est pas encore en ligne : la mise en ligne sera la 1.0.0.

## À venir

- Page d'attente « bientôt en ligne » : fond sapin, titre en or, arche où des photos de matières se relaient, inscription à la lettre pour être prévenu de l'ouverture. Visible par les visiteurs non connectés tant que `WP_ENV` n'est pas `production` ; une personne connectée voit le site (aperçu : `?maintenance`). `US_MAINTENANCE=true` ou `false` dans le `.env` pour forcer.

## 0.2.0 – 19 novembre 2026

Boutique prête à vendre en local : paiement, compte client, contenus, avis et référencement complet.

### Boutique et paiement

- Paiement par carte avec Stripe (passerelle officielle de WooCommerce), en mode test. Clés lues dans le `.env`, réglages dans `scripts/paiement.php`.
- Page « commande reçue » refaite : remerciement, avancement en trois étapes, résumé, articles avec vignettes, livraison et facturation.
- Compte client refait : commandes en cartes, détail d'une commande avec son avancement, adresses en deux cartes. L'écran de vérification de l'e-mail garde la mise en page du site.
- Pied de page : logos des moyens de paiement acceptés, affichés seulement s'ils sont activés dans Stripe.
- Belle Peau publiée : prix remis dans le bon sens (81,90 € la boîte, 163,80 € les trois), fiche réécrite. Les 375 fiches sont publiées.

### Contenus

- Le journal : huit articles repris de l'ancien blog et relus, quatre rubriques, page d'article, derniers articles sur l'accueil.
- Avis clients : huit avis de l'ancienne boutique reproduits tels quels (accueil, fiches, page « Avis clients »), « Achat vérifié » seulement quand c'est vrai. Les nouveaux avis sont réservés aux acheteurs, relus avant publication ; l'e-mail « commande terminée » y invite, et la colonne « Accueil » de Produits › Avis choisit ceux de l'accueil.
- Formulaires (Forminator) : formulaire de contact avec accusé de réception, inscription à la lettre d'information dans le pied de page.
- Pages légales complétées : mentions légales (hébergeur IONOS), CGV, confidentialité, livraison et retours, politique de cookies. Adresse et horaires de la boutique de Metz.
- Bandeau de cookies (Complianz) aux couleurs de la charte : mesure d'audience seulement avec accord.
- Textes raccourcis sur tout le site : surtitres et phrases d'introduction retirés quand ils n'apportaient rien.

### Rendu

- Accueil : « éveiller les sens » en or, avec un reflet, le ruban du logo dessiné dessous et l'étoile qui scintille.
- Avis de l'accueil sur fond vert sapin.
- Fond « lin clair » (#F1E8D8) pour les cartes, les encarts et les sections qui étaient blanches ; dernier lien des cartes aligné en bas.
- Corrections : bande vide en haut du journal, apostrophes des titres d'article.

### Référencement

- Titre, méta-description et requête cible Yoast pour les 375 fiches produit, les rayons, les pages et les articles ; image de partage 1200 × 630 par page ; notes Yoast affichées dans l'admin. `composer seo` réapplique le référencement seul.

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
