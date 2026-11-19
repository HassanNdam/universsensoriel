# Univers Sensoriel

WordPress sous [Bedrock](https://roots.io/bedrock/), thème sur mesure basé sur [Sage 11](https://roots.io/sage/) (Blade, Acorn, Vite, Tailwind CSS 4).

## Organisation

| Dossier | Rôle |
| --- | --- |
| `config/`, `.env` | Configuration WordPress (Bedrock) |
| `web/app/themes/univers-sensoriel/` | Thème Sage |
| `web/app/mu-plugins/` | Extensions obligatoires (dont `smtp.php` : e-mails via le SMTP du `.env`) |

## Installation locale (Laragon)

1. `composer install` à la racine, puis dans `web/app/themes/univers-sensoriel/`.
2. Copier `.env.example` en `.env` et renseigner la base de données et les clés (https://roots.io/salts.html).
3. Vhost Apache : `univers-sensoriel.localhost` → `web/` (fichier `C:/laragon/etc/apache2/sites-enabled/univers-sensoriel.localhost.conf`).
4. Thème : `npm install`, puis `npm run dev` (rechargement à chaud) ou `npm run build`.

URL utiles :

- Site : http://univers-sensoriel.localhost
- Administration : http://univers-sensoriel.localhost/wp/wp-admin
- phpMyAdmin : http://localhost/phpmyadmin
- E-mails de test (Mailpit) : http://localhost:8025

> Si `npm run dev` est interrompu brutalement, supprimer `web/app/themes/univers-sensoriel/public/hot`. Sinon le site continue de charger ses assets depuis un serveur Vite arrêté.

## WP-CLI

```bash
php vendor/wp-cli/wp-cli/php/boot-fs.php <commande>   # Git Bash
vendor\bin\wp.bat <commande>                           # PowerShell
```

Sous Git Bash, préfixer par `MSYS_NO_PATHCONV=1` les commandes dont un argument commence par `/` (ex. `wp rewrite structure '/%postname%/'`).

## Contenus de départ

```bash
composer seed
```

Relançable sans doublon. Le seed active WooCommerce et Yoast SEO, règle la boutique (euros, France, livraison offerte dès 49 €, retrait à Metz), crée les rayons et les attributs, importe le catalogue, les pages et le référencement. Une fiche n'est retraitée que si ses données ont changé ; `US_SEED_FORCE=1` réécrit tout, y compris les pages.

| Fichier | Rôle |
| --- | --- |
| `scripts/seed.php` | Réglages, rayons, catalogue, livraison, pages |
| `scripts/seo.json`, `scripts/seo.php` | Titres, descriptions, requêtes cibles et images de partage (Yoast) des pages, des rayons et des articles. `composer seo` les réapplique seuls |
| `scripts/seo-fiches.json` | Titre, description et requête cible de chaque fiche produit, fabriqués par `python scripts/catalogue/seo_fiches.py` (règles par rayon + `catalogue/seo-manuel.json` pour les fiches rédigées à la main) |
| `scripts/seo-scores.json` | Notes SEO et lisibilité de Yoast, relevées par `scripts/seo-export.php` puis `scripts/seo-scores.js` (mode d'emploi en tête du script) |
| `docs/03-images-site/partage/` | Images de partage 1200 × 630 des pages et des rayons. Celles des fiches sont fabriquées par le thème dans `uploads/partage/` |
| `scripts/pages/*.html` | Texte des pages |
| `scripts/journal.php`, `scripts/blog/` | Articles du journal (un fichier HTML par article, `articles.json` pour le reste) |
| `scripts/avis.json` | Avis clients repris de l'ancienne boutique (voir `docs/00-projet/avis-ancienne-boutique.md`) |
| `scripts/catalogue/catalogue.json` | Les 375 fiches, nettoyées |
| `scripts/catalogue/rayons.json` | Univers, rayons et leurs textes |
| `scripts/catalogue/selection.json` | Produits vedettes (accueil, tête de rayon) |

### Refaire le catalogue depuis l'export de l'ancienne boutique

```bash
cd scripts/catalogue
python preparer.py <dossier scrape>            # données seules
python preparer.py <dossier scrape> --images   # + détourage et WebP (Pillow, NumPy, rembg)
python visuels_site.py <dossier scrape>        # visuels du thème (accueil, univers)
```

Règles appliquées (audit de novembre 2026) : aucune marque tierce dans les fiches, pas de « Chogan » dans les titres, entretien de la maison et compléments alimentaires réécrits à la main (`reecritures.json`, sans allégation de santé). Les images écartées à la main sont dans `exclusions.txt`, les recadrages dans `recadrages.json`, les visuels à ne pas détourer dans `sans-detourage.txt`.

## Charte

`docs/00-projet/Audit-et-charte-Univers-Sensoriel.pdf` (page 3). Les couleurs et les polices sont déclarées dans `resources/css/charte.css` : sapin, ivoire, lin, or (jamais pour du texte sur fond clair), bronze. Titres en Marcellus, texte en Jost, hébergées dans le thème.

## Administration des textes (menu « Univers Sensoriel »)

Textes, coordonnées, boutiques, photos de l'accueil et page d'attente se modifient dans l'administration, menu **Univers Sensoriel** (extension Secure Custom Fields) : Coordonnées, Accueil, Textes du site, Page d'attente.

- Une seule description : `App\REGLAGES` (`app/reglages.php`). Elle fabrique les écrans et donne à chaque réglage sa valeur d'origine. Ajouter un champ = ajouter une ligne, puis le lire dans le gabarit avec `App	exte('cle')`, `App
eglage('cle')`, `App\lignes('cle')` (liste) ou `App\media()` (image).
- Valeur lue : jamais enregistrée → valeur d'origine ; texte vidé → rien ; image vide → image du thème.
- `composer reglages` (rejoué par `composer seed`) recopie les valeurs d'origine dans l'administration tant qu'elles n'y sont pas ; un réglage enregistré n'est jamais écrasé.
- Repères dans un texte : `{seuil}` (livraison offerte), `{annee}`, `{numero}` (commande). Dans un titre, les `*mots*` entre étoiles sont en or.
- Coordonnées dans le texte d'une page : codes courts `[us_contact champ="courriel"]`, `[us_contact champ="telephone"]`, `[us_whatsapp message="…"]Texte[/us_whatsapp]`, `[us_boutiques]`, `[us_editeur]`, `[us_hebergeur]` (`app/coordonnees.php`). Un numéro n'est écrit qu'à un seul endroit.
- L'adresse e-mail des coordonnées reçoit les messages du formulaire de contact : la changer met à jour les formulaires Forminator.
- Page d'attente : « Automatique » suit `WP_ENV` et `US_MAINTENANCE` du `.env` ; « Affichée » et « Masquée » passent avant.
- Restent dans le code : libellés d'interface (boutons du panier, filtres, tri, pagination), menus et liens du pied de page, logos.
- Le menu « SCF » n'apparaît qu'en développement.
- Photos de l'arche (accueil et page d'attente) : liste `ouverture_images` (Univers Sensoriel › Accueil › Ouverture), lue par `App\diapos()`. Elles se relaient en fondu, 7 s chacune, quel que soit leur nombre (`partials/diapos.blade.php` calcule le rythme). Poussière d'or : `partials/poussiere.blade.php`. Animations communes : `resources/css/scene.css`.

### Pages et articles en blocs (Gutenberg)

- Pages de texte et articles du journal sont en blocs : paragraphes, titres, listes, boutons, questions (bloc « Détails »). L'éditeur reprend polices, couleurs et mise en page du site (`resources/css/editor.css`, règles du texte en `:is(.prose, .is-root-container)`).
- Blocs du thème, catégorie « Univers Sensoriel » (`resources/blocks/<nom>/block.json`, rendus par `App
endu_bloc()` dans `app/blocs.php`) : Nos boutiques, Coordonnées, Bouton WhatsApp, Éditeur du site, Hébergeur. Leur contenu est lu dans Univers Sensoriel › Coordonnées.
- Dans un paragraphe : `[us_seuil]` et `[us_forfait]` (montants de livraison lus dans WooCommerce › Expédition), `[us_contact champ="courriel"]`, `[us_contact champ="telephone"]`.
- Blocs proposés : seulement ceux qui ont un style dans le thème (`App\BLOCS_AUTORISES`) ; ni couleur, ni police, ni taille libres (`theme.json`, Tailwind désactivé dans `vite.config.js`).
- Questions fréquentes : chaque bloc « Détails » d'une page devient une question des données Google (FAQPage).
- `composer blocs` passe en blocs un contenu encore en HTML (fichiers de `scripts/pages`, `scripts/blog` et contenus du site) ; un contenu déjà en blocs n'est pas touché.
- Le seed ne réécrit jamais une page modifiée dans l'éditeur depuis le dernier passage (empreinte `_us_contenu_seed`), même avec `US_SEED_FORCE=1`.

## Boutique

- Filtres : `?genre[]=femme&famille[]=floral&format[]=…&gamme[]=…&prix=20-40` (`app/woocommerce.php`).
- Familles olfactives : archives publiques `/famille-olfactive/<famille>/`.
- Seuil de livraison offerte : lu dans WooCommerce › Réglages › Expédition (`App\seuil_livraison()`), jamais écrit ailleurs. Dans un texte réglable, `{seuil}` le reprend.
- Commande reçue et compte client : la table `App\PONTS` (`app/commande.php`) remplace quatre gabarits de WooCommerce (`checkout/thankyou`, `myaccount/orders`, `myaccount/view-order`, `myaccount/my-address`) par des vues du thème (`resources/views/woocommerce/`), via les ponts de `app/ponts/`. Données préparées dans `app/commande.php` et `app/compte.php`, styles dans `commande.css` et `compte.css`. Aucun fichier de WooCommerce n'est copié : une mise à jour de l'extension ne défait rien.
- Compte client d'essai en local : identifiant `client-test`, mot de passe dans le `.env` (`US_CLIENT_TEST_PASSWORD`).

## E-mails

Tous les e-mails du site partent dans le même habillage, à la charte : en-tête sapin au logo, titre en Marcellus, bouton sapin, pied avec WhatsApp, e-mail et téléphone des Coordonnées.

- Code : `app/mails.php`, ponts `app/ponts/mail-*.php`, vues `resources/views/mails/`, styles `resources/css/mail.css` (lu tel quel, pas compilé : pas de variables CSS). Aucun gabarit de WooCommerce n'est copié.
- Commandes et compte (WooCommerce) : l'habillage, le détail de commande et les adresses remplacent les gabarits partagés (`App\PONTS_MAILS`), donc tout e-mail WooCommerce les reçoit, y compris ceux de Stripe. Le texte des quatorze e-mails courants est écrit dans `App\mail_message()` (`App\MAILS_REDIGES`).
- Formulaires et notifications de WordPress : enveloppés à l'envoi (filtre `wp_mail`). Un `<h1>` en tête du message devient le titre ; sinon c'est l'objet.
- Objet, titre et contenu additionnel de chaque e-mail : WooCommerce › Réglages › E-mails (aperçu compris). Valeurs d'origine dans `scripts/mails.php`, rejoué par `composer seed`.
- Destinataire des e-mails pour la boutique (nouvelle commande, annulée, paiement échoué) : l'adresse e-mail d'Univers Sensoriel › Coordonnées, sauf si le champ « Destinataire(s) » de l'e-mail est rempli dans WooCommerce.
- Expéditeur : `MAIL_FROM_ADDRESS` et `MAIL_FROM_NAME` du `.env`, pour WooCommerce comme pour le reste (`web/app/mu-plugins/smtp.php`).
- Relire tous les e-mails dans Mailpit : `php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/mails-essai.php [n° de commande] --user=admin`.
- Logo : `resources/images/mail/` en PNG (Outlook n'affiche pas le WebP). Les photos des produits restent en WebP : Outlook pour Windows ne les montre pas.

## Paiement (Stripe)

- Extension : passerelle Stripe officielle de WooCommerce. Les moyens de paiement proposés se choisissent dans le tableau de bord Stripe.
- Clés : `STRIPE_PUBLISHABLE_KEY` et `STRIPE_SECRET_KEY` dans le `.env`, jamais en base ni dans Git (`web/app/mu-plugins/stripe.php`). Une clé `sk_test_…` met la boutique en mode test.
- Réglages : `scripts/paiement.php`, rejoué par `composer seed`.
- Carte de test : `4242 4242 4242 4242`, date future, trois chiffres au choix.
- Webhooks en local : lancer `scripts/stripe-ecoute.ps1` et le laisser tourner pendant les essais (il lit la clé dans le `.env` et transmet les événements à `/?wc-api=wc_stripe`). Le `whsec_…` qu'il affiche va dans `STRIPE_WEBHOOK_SECRET` ; il ne change pas d'un lancement à l'autre.
- En ligne : créer le webhook dans le tableau de bord Stripe vers `https://<domaine>/?wc-api=wc_stripe` et mettre les clés `live` dans le `.env` du serveur.
- TVA : taxes WooCommerce désactivées tant que le statut de l'entreprise n'est pas fixé. Stripe Tax (`stripe-tax-for-woocommerce`) ne sert que si la boutique collecte la TVA.

## Branches et versions

Le dépôt suit Git flow (`main`, `develop`, `feature/*`, `release/*`, `hotfix/*`) : voir [CONTRIBUTING.md](CONTRIBUTING.md). Les versions sont décrites dans [CHANGELOG.md](CHANGELOG.md).

## Avis clients

1. À la commande, un compte est créé pour l'acheteur. Quand la commande passe à « Terminée », l'e-mail l'invite à donner son avis, avec un lien vers chaque fiche.
2. Sur la fiche, le formulaire n'apparaît qu'aux personnes connectées qui ont acheté le produit.
3. L'avis arrive en attente dans **Produits › Avis** ; la boutique reçoit un e-mail. On l'approuve ou on le met à la corbeille, sans jamais modifier son texte.
4. Approuvé, il s'affiche sur la fiche du produit et en tête de la page « Avis clients », avec la mention « Achat vérifié ».
5. Pour l'accueil, c'est un choix : colonne **Accueil** de Produits › Avis (ou la case « Page d'accueil » dans l'avis). L'accueil montre les six avis choisis les plus récents (`App\AVIS_ACCUEIL`).

Le code est dans `app/avis.php` (affichage) et `app/avis-gestion.php` (admin, e-mail).
