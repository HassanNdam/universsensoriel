# Journal des versions

Les versions suivent `MAJEUR.MINEUR.CORRECTIF`. Le site est en ligne depuis la 1.0.0 : `main` est ce qui tourne sur https://universsensoriel.com.

## À venir

Nouveautés, deuxième lot (fichier « nouveautés produits chogan 2.xlsx »). Fichiers à envoyer : `mise-en-ligne/mise-a-jour-2026-11-27-lot2/` (hors Git, avec sa notice) ; import depuis l'administration (mise à jour `nouveautes-2026-11-b`).

- 6 produits de maquillage des lèvres, au prix client : Lipstick Glossy (8 teintes, 15 €), Gloss repulpant (3 teintes, 22 €), Rouge à lèvres liquide mat (5 teintes, 19 €), Lipstick Matte (3 teintes, 15 €), Spicy Gloss (20 €), Lip Balm (18 €). Rayon Maquillage et catégorie Nouveautés, en tête des derniers arrivés.
- Chaque teinte du lot a son propre terme (« lipstick-glossy-cherry ») : Red Velvet, Watermelon, Cherry et Magenta existent pour plusieurs produits, chacun garde sa couleur et son code dans la palette.
- Préparation : `scripts/catalogue/nouveautes2.py` (reprend les fonctions de `nouveautes.py`, dont le premier lot sort inchangé).

## 1.2.0 – 27 novembre 2026

Nouveautés, promotions et documentation (demandes de la promotrice du 27 novembre 2026). Fichiers envoyés : `mise-en-ligne/mise-a-jour-2026-11-27/` (hors Git) ; produits et documents importés depuis l'administration (`app/mises-a-jour.php`), dossier `uploads/import/` à supprimer du serveur ensuite.

- Menu : la catégorie Nouveautés n'est pas comptée parmi les univers (WooCommerce lui donne un ordre quand elle est créée depuis l'administration, elle apparaissait deux fois) ; étiquette « New » seulement quand elle existe.

- 57 nouveaux produits (fichier « nouveautés produits chogan.xlsx »), au prix client : parfums Luxury Amphera, Volt et Scarlet, Blooming Skin (163), Unfold (164), maquillage MoniAmori, bougies parfumées, soins du visage et des cheveux, sprays d'ambiance, gel douche Luxury. Compléments alimentaires en brouillon (allégations santé à relire). Deux rayons nouveaux : Maquillage (Beauté) et Bougies parfumées (Art de vivre).
- Catégorie Nouveautés : entrée du menu avec étiquette « New » animée (à la place du journal, resté dans le pied de page), bloc sur l'accueil après les essentiels, badge « Nouveau » sur les cartes, liens dans le pied de page.
- Teintes en palette sur la fiche : pastilles de couleur avec code et nom (rouges et encres à lèvres, crayons, fonds de teint, sticks), photo de la teinte au choix. Couleur et code modifiables dans Produits › Attributs (`app/palette.php`).
- Page Promotions (`/promotions/`) : les produits à prix barré, avec filtres et tri ; « Voir les offres » du Black Friday y mène.
- Page Documentation et certifications (pied de page, colonne Aide) : les 17 documents publiés par le fabricant (chogangroupspa.com/documents), hébergés sur le site. 11 catalogues en vignettes (Olfazeta, Mytologik, Cooperativa Profumieri, Peptilux, Aurodhea, Lolûm, Maquillage, MoniAmori, Brilhome, Kleyes, SuppleFit), certificat de conformité, engagements du fabricant (modèle 231, code d'éthique, procédure de signalement). Masqués au départ : catalogue SuppleFit (compléments non vendus), conditions de vente et confidentialité des partenaires du fabricant. Couvertures sans personne (pages produits pour le maquillage, MoniAmori et Aurodhea).
- Univers Sensoriel › Documentation : par document, titre, rubrique, fichier, couverture, précision et case « Afficher sur le site » ; onglet Rubriques pour les titres, le texte des certificats, le lien « Consulter » et le texte sans document. Préparation : `scripts/catalogue/documentation.py` (PDF dans `docs/04-documentation/pdf`, hors Git) ; import en ligne depuis l'administration (`uploads/import/documentation-2026-11/`).
- Textes du site › Nouveautés et Promotions : étiquette « New », titre « Derniers arrivés », badge « Nouveau », nombre de produits et lien « Voir les offres » du menu. Le nom « Nouveautés » (menu, pied de page, pastilles) est celui de la catégorie.
- Le bloc Journal quitte l'accueil.
- Import des fiches dans le thème (`app/import-produits.php`) : en local par le seed, en ligne par lots depuis l'administration (`app/mises-a-jour.php`, dossier `uploads/import/nouveautes-2026-11/`). Préparation : `scripts/catalogue/nouveautes.py`.
- Recherche par parfum de référence : Scarlet (170U) ajouté.

## 1.1.0 – 25 novembre 2026

Retours de la promotrice après les premiers tests. Fichiers à envoyer : `mise-en-ligne/mise-a-jour-2026-11-25/` (hors Git) ; la base en ligne se met à jour d'elle-même au premier chargement (`app/mises-a-jour.php`).

- Recherche par parfum de référence : « Dior », « J'adore », « Baccarat Rouge »… renvoient aux extraits Olfazeta correspondants, avec le gel douche et la crème de même senteur (`app/inspirations.php`). Les maisons ne sont affichées nulle part. « Chogan » est un mot-clé caché : retiré de la recherche, seul il affiche toute la boutique.
- E-mails : raison de chaque e-mail refusé et bouton « Envoyer un e-mail d'essai » dans l'admin (`mu-plugins/mails-echecs.php`).
- Livraison : point relais ou consigne 8 €, offert dès 79 € (au lieu de 49 €) ; à domicile 12,90 € ; Europe (Allemagne, Belgique, Italie, Luxembourg, Pays-Bas, Suisse) 14,90 € en point relais, 19,90 € à domicile. Délais : 72 h en France, 3 à 7 jours ouvrés en Europe.
- Retrait chez les partenaires de Metz (Borny, gare, 8 h – 17 h, sous 24 h) au lieu de « la boutique de Metz », avec le numéro de la Maison ; cartes, pied de page, accueil, contact, e-mails et suivi de commande.
- Code court `[us_forfait type="relais|domicile" zone="France|Europe"]`.
- Engagement qualité : produits fabriqués en Italie, cosmétiques notifiés sur le portail CPNP.
- « Livraison offerte dès 79 € » dans les descriptions Google (fiches, rayons) et leurs traductions.

## 1.0.0 – 20 novembre 2026

Mise en ligne sur https://universsensoriel.com (hébergement IONOS), page d'attente active jusqu'à l'ouverture, paiement Stripe en mode test.

Partage sur les réseaux : description sur toutes les adresses (458 relevées, toutes avec image 1200 × 630, titre et description).

- Descriptions ajoutées : rubriques du journal (Bien-être, Cheveux, Parfum, Soin de la peau), politique de cookies, panier, commande, compte, rayon Accessoires parfum. En anglais aussi.
- `scripts/seo.json` : nouvelle section `rubriques` (catégories d'articles), appliquée par `scripts/seo.php` comme les rayons.

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
