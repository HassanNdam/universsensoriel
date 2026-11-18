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
| `scripts/seo.json`, `scripts/seo.php` | Titres, descriptions et mots-clés (Yoast) des pages et des rayons |
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

## Boutique

- Filtres : `?genre[]=femme&famille[]=floral&format[]=…&gamme[]=…&prix=20-40` (`app/woocommerce.php`).
- Familles olfactives : archives publiques `/famille-olfactive/<famille>/`.
- Seuil de livraison offerte : constante `App\SEUIL_LIVRAISON` (`app/helpers.php`), à garder égale au réglage WooCommerce › Expédition.
- Coordonnées et boutiques : `App\coordonnees()` (`app/helpers.php`).

## Branches et versions

Le dépôt suit Git flow (`main`, `develop`, `feature/*`, `release/*`, `hotfix/*`) : voir [CONTRIBUTING.md](CONTRIBUTING.md). Les versions sont décrites dans [CHANGELOG.md](CHANGELOG.md).
