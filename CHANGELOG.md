# Journal des versions

Les versions suivent `MAJEUR.MINEUR.CORRECTIF`. Le site n'est pas encore en ligne : la mise en ligne sera la 1.0.0.

## À venir

Une seule boutique : la boutique de Yaoundé a fermé, seule reste celle de Metz.

- Yaoundé retiré des boutiques, de l'accueil, de La Maison, de la page d'attente, du référencement et des traductions.
- Page « Nos boutiques » renommée « Notre boutique » (`/notre-boutique/`, l'ancienne adresse y renvoie). Les titres passent d'eux-mêmes au pluriel si une autre boutique est ajoutée dans Univers Sensoriel › Coordonnées.
- Boutique seule : carte pleine largeur (coordonnées à gauche, horaires à droite) ; adresse et téléphone dans le pied de page.
- `app/mises-a-jour.php` : mises à jour ponctuelles de la base en ligne, appliquées une fois au premier passage après l'envoi du thème, sans écraser un texte retouché dans l'administration.

Textes et coordonnées modifiables dans l'administration.

- Menu « Univers Sensoriel » (Secure Custom Fields) : Coordonnées (contact, boutiques et horaires, mentions légales), Accueil (textes, photos, rayons de chaque bloc), Textes du site (bandeau, garanties, pied de page, fiche produit, boutique, journal, contact, commande, page introuvable), Page d'attente (affichage, textes, titre et image de partage).
- Une seule description des réglages (`app/reglages.php`) : écrans de l'administration et valeurs d'origine. `composer reglages` les recopie dans l'administration.
- Un numéro ou une adresse n'est plus écrit qu'à un seul endroit : pages légales, « Nos boutiques » et « Devenir consultante » les lisent par codes courts ; les formulaires suivent l'adresse e-mail.
- Seuil de livraison offerte lu dans WooCommerce › Expédition (plus de constante à tenir à jour) ; `{seuil}` dans les textes.
- Page d'attente : interrupteur dans l'administration (Automatique, Affichée, Masquée).
- Pages de texte et articles en blocs Gutenberg (paragraphes, titres, listes, boutons, questions), éditeur aux couleurs et polices du site, sans couleur ni taille libre.
- Blocs du thème : Nos boutiques, Coordonnées, Bouton WhatsApp, Éditeur du site, Hébergeur, lus dans les réglages. Montants de livraison des pages lus dans WooCommerce (`[us_seuil]`, `[us_forfait]`).
- Questions fréquentes : données Google FAQPage tirées des blocs « Détails ».
- Le seed ne réécrit plus une page modifiée dans l'éditeur.
- Accueil : l'ouverture reprend la scène de la page d'attente. Photos qui se relaient dans l'arche, poussière d'or qui monte, texte et arche qui apparaissent en douceur, halo qui respire, flacons qui flottent. Photos de l'arche choisies dans Univers Sensoriel › Accueil › Ouverture (autant qu'on veut, dans l'ordre voulu), les mêmes sur la page d'attente.
- Au chargement, la première photo de l'arche est seule (la dernière ne s'efface plus par-dessus).
- E-mails à la charte : commandes, compte, formulaires et notifications partent dans le même habillage (logo sur sapin, Marcellus, bouton sapin, coordonnées dans le pied). Textes des e-mails de commande et de compte réécrits, courts ; objets et titres en français, modifiables dans WooCommerce › Réglages › E-mails. Même expéditeur pour tous. `scripts/mails-essai.php` les envoie tous dans Mailpit.
- Langues : français et anglais sous `/en/` (TranslatePress gratuit), traductions anglaises de départ dans `scripts/traductions/en_GB.json`, corrigeables dans l'éditeur visuel. Néerlandais, allemand, espagnol, italien et portugais en traduction automatique de Google, chargée seulement à la demande du visiteur.
- Pays et devises : sélecteur « Langue et pays » (en-tête, menu mobile, pied de page) avec les 14 pays de l'ancienne boutique ; prix et paiement en CHF, FCFA (XAF, XOF) ou CDF selon le pays (Price Based on Country gratuit). Taux du jour mis à jour chaque jour, pays deviné par l'adresse IP (base DB-IP gratuite). `composer langues`.

## 0.3.0 – 19 novembre 2026

Page d'attente avant la mise en ligne et avis en carrousel.

- Avis clients en carrousel horizontal (accueil, page « Avis clients », fiches) : flèches, barre de progression, défilement au doigt et au clavier, avis longs coupés à cinq lignes avec « Lire la suite ».
- Page d'attente « bientôt en ligne » : fond sapin, titre en or, arche où des photos de matières se relaient, inscription à la lettre pour être prévenu de l'ouverture. Visible par les visiteurs non connectés tant que `WP_ENV` n'est pas `production` ; une personne connectée voit le site (aperçu : `?maintenance`). `US_MAINTENANCE=true` ou `false` dans le `.env` pour forcer.
- Page d'attente : titre, méta-description et image de partage à elle (1200 × 630, `partage-bientot-en-ligne.jpg`) ; les robots d'aperçu des réseaux reçoivent 200 pour que le lien partagé ait son aperçu, les moteurs gardent le 503. L'analyse Yoast de l'admin et `scripts/seo-scores.js` lisent les pages avec un jeton signé qui passe la page d'attente (sinon Yoast notait la page d'attente).

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
