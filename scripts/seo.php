<?php

/**
 * Référencement (Yoast SEO gratuit), appelé à la fin de scripts/seed.php.
 *
 * - réglages Yoast : séparateur, nom du site, logo, image de partage par défaut, archives inutiles coupées ;
 * - titre, description, requête cible et image de partage de chaque page, rayon et article (scripts/seo.json) ;
 * - titre, description et requête cible de chaque fiche produit (scripts/seo-fiches.json, fabriqué par catalogue/seo_fiches.py) ;
 * - notes SEO et lisibilité de Yoast (scripts/seo-scores.json, relevé par scripts/seo-scores.js dans l'admin) ;
 * - images de partage des fiches et des rayons fabriquées d'avance (thème : App\image_partage).
 * Les familles olfactives et les fiches créées plus tard dans l'admin sont titrées par le thème (app/seo.php).
 *
 * Seul : composer seo
 *
 * L'admin fait foi : une valeur n'est réécrite que si elle est vide ou si personne n'y a touché depuis le dernier seed
 * (la valeur écrite est gardée en « ombre » : méta _us_seo_auto…, options us_seo_auto et us_seo_auto_rayons).
 * US_SEED_FORCE=1 réapplique tout seo.json.
 */

if (! defined('ABSPATH')) {
    exit("À lancer avec WP-CLI : composer seo
");
}
require_once __DIR__ . '/fonctions.php';

if (! defined('WPSEO_VERSION')) {
    WP_CLI::warning('Yoast SEO inactif : référencement non appliqué.');

    return;
}

$lire = fn(string $fichier): array => is_file(__DIR__ . "/$fichier") ? (array) json_decode(file_get_contents(__DIR__ . "/$fichier"), true) : [];
$seo = $lire('seo.json');
$fiches = $lire('seo-fiches.json');
$scores = $lire('seo-scores.json');
$racine = dirname(__DIR__);

WP_CLI::log('Référencement (Yoast SEO)');

$forcer = (bool) getenv('US_SEED_FORCE');
$gardes = [];

/** Écrit une méta sauf si elle a été modifiée dans l'admin depuis le dernier seed (null = supprimer). */
$ecrireMeta = function (int $id, string $meta, ?string $valeur) use ($forcer, &$gardes): void {
    $ombre = '_us_seo_auto' . $meta;
    $actuel = (string) get_post_meta($id, $meta, true);
    if (! $forcer && $actuel !== '' && metadata_exists('post', $id, $ombre)
        && $actuel !== (string) get_post_meta($id, $ombre, true)) {
        $gardes[] = get_the_title($id) . " ($meta)";

        return;
    }
    $valeur === null ? delete_post_meta($id, $meta) : update_post_meta($id, $meta, $valeur);
    update_post_meta($id, $ombre, (string) $valeur);
};

$auto = (array) get_option('us_seo_auto', []);
$ecrireOption = function (string $cle, mixed $valeur) use ($forcer, &$auto, &$gardes): void {
    if (! $forcer && array_key_exists($cle, $auto) && WPSEO_Options::get($cle) != $auto[$cle]) {
        $gardes[] = "réglage Yoast $cle";

        return;
    }
    WPSEO_Options::set($cle, $valeur);
    $auto[$cle] = $valeur;
};

// Réglages généraux.
$defautId = us_media($racine . '/docs/03-images-site/partage/' . $seo['partage']['defaut'], $seo['partage']['alt']);
$logoId = us_media($racine . '/docs/01-logo/web/logo-univers-sensoriel.png', 'Logo Univers Sensoriel by K.Elle');

$titres = [
    'separator' => 'sc-pipe',
    'website_name' => 'Univers Sensoriel',
    'alternate_website_name' => 'Univers Sensoriel by K.Elle',
    'company_or_person' => 'company',
    'company_name' => 'Univers Sensoriel',
    'company_alternate_name' => 'Univers Sensoriel by K.Elle',
    'title-home-wpseo' => '%%sitename%% %%sep%% %%sitedesc%%',
    'title-page' => '%%title%% %%sep%% %%sitename%%',
    'title-product' => '%%title%% %%sep%% %%sitename%%',
    'metadesc-product' => '%%excerpt%%',
    'title-tax-product_cat' => '%%term_title%% %%sep%% %%sitename%%',
    'metadesc-tax-product_cat' => '%%term_description%%',
    'title-tax-pa_famille-olfactive' => 'Parfums %%term_title%% %%sep%% %%sitename%%',
    'title-search-wpseo' => 'Recherche : %%searchphrase%% %%sep%% %%sitename%%',
    'title-404-wpseo' => 'Page introuvable %%sep%% %%sitename%%',
    'breadcrumbs-enable' => false, // le thème a son propre fil d'Ariane
    'title-post' => '%%title%% %%sep%% %%sitename%%',
    'title-tax-category' => 'Journal : %%term_title%% %%sep%% %%sitename%%',
    'noindex-tax-post_tag' => true,
    'disable-author' => true, // un seul auteur : archives auteur, date et format redirigées
    'disable-date' => true,
    'disable-post_format' => true,
    'disable-attachment' => true,
    'noindex-tax-product_tag' => true,
    'noindex-tax-product_shipping_class' => true,
    'noindex-tax-pa_genre' => true,
    'noindex-tax-pa_gamme' => true,
    'noindex-tax-pa_format' => true,
    'opengraph' => true,
    'twitter' => true,
    'twitter_card_type' => 'summary_large_image',
    'tracking' => false,
];
if ($logoId) {
    $titres += ['company_logo_id' => $logoId, 'company_logo' => (string) wp_get_attachment_url($logoId)];
}
if ($defautId) {
    $titres += ['og_default_image_id' => $defautId, 'og_default_image' => (string) wp_get_attachment_url($defautId)];
}
foreach ($titres as $cle => $valeur) {
    $ecrireOption($cle, $valeur);
}
update_option('us_seo_auto', $auto, false);

/** Image de partage d'une page ou d'un rayon : [identifiant, adresse] du fichier de docs/03-images-site/partage. */
$imagePartage = function (array $reglage) use ($racine): array {
    $id = empty($reglage['image']) ? 0 : us_media($racine . '/docs/03-images-site/partage/' . $reglage['image'], $reglage['alt'] ?? '');

    return $id ? [(string) $id, (string) wp_get_attachment_url($id)] : [null, null];
};
/** Notes Yoast relevées dans l'éditeur : [SEO, lisibilité] sur 100. */
$notes = fn(string $groupe, string $cle): array => array_map(
    fn($n) => $n === null ? null : (string) $n,
    array_pad($scores[$groupe][$cle] ?? [], 2, null),
);

// Pages et articles.
$faits = [];
foreach ($seo['pages'] + ($seo['articles'] ?? []) as $slug => $reglage) {
    $id = (int) ((get_page_by_path($slug) ?: get_page_by_path($slug, OBJECT, 'post'))?->ID ?? 0);
    if (! $id) {
        WP_CLI::warning("SEO : page « $slug » introuvable");
        continue;
    }
    foreach ([
        '_yoast_wpseo_title' => $reglage['titre'] ?? null,
        '_yoast_wpseo_metadesc' => $reglage['description'] ?? null,
        '_yoast_wpseo_focuskw' => $reglage['motcle'] ?? null,
        '_yoast_wpseo_meta-robots-noindex' => ! empty($reglage['noindex']) ? '1' : null,
        '_yoast_wpseo_opengraph-image-id' => $imagePartage($reglage)[0],
        '_yoast_wpseo_opengraph-image' => $imagePartage($reglage)[1],
        '_yoast_wpseo_linkdex' => $notes(isset($seo['pages'][$slug]) ? 'pages' : 'articles', $slug)[0],
        '_yoast_wpseo_content_score' => $notes(isset($seo['pages'][$slug]) ? 'pages' : 'articles', $slug)[1],
    ] as $meta => $valeur) {
        $ecrireMeta($id, $meta, $valeur);
    }
    $faits[] = $id;
}

// Rayons (catégories de produits) : Yoast range ces valeurs dans l'option wpseo_taxonomy_meta.
$autoRayons = (array) get_option('us_seo_auto_rayons', []);
$rayonsFaits = 0;
foreach ($seo['rayons'] as $slug => $reglage) {
    $terme = get_term_by('slug', $slug, 'product_cat');
    if (! $terme) {
        continue;
    }
    $valeurs = array_filter([
        'wpseo_title' => $reglage['titre'] ?? '',
        'wpseo_desc' => $reglage['description'] ?? '',
        'wpseo_focuskw' => $reglage['motcle'] ?? '',
        'wpseo_noindex' => ! empty($reglage['noindex']) ? 'noindex' : '',
        'wpseo_opengraph-image-id' => (string) $imagePartage($reglage)[0],
        'wpseo_opengraph-image' => (string) $imagePartage($reglage)[1],
        'wpseo_linkdex' => (string) $notes('rayons', $slug)[0],
        'wpseo_content_score' => (string) $notes('rayons', $slug)[1],
    ]);
    // Retouché dans l'admin = une valeur non vide qui n'est pas celle du dernier passage (une clé nouvelle ici ne compte pas).
    $actuel = array_filter(array_intersect_key((array) WPSEO_Taxonomy_Meta::get_term_meta($terme, 'product_cat'), $valeurs));
    $ombre = $autoRayons[$slug] ?? null;
    if (! $forcer && $ombre !== null && array_diff_assoc($actuel, $ombre)) {
        $gardes[] = "rayon {$terme->name}";
        continue;
    }
    WPSEO_Taxonomy_Meta::set_values($terme->term_id, 'product_cat', $valeurs);
    $autoRayons[$slug] = $valeurs;
    $rayonsFaits++;
}
update_option('us_seo_auto_rayons', $autoRayons, false);

// Fiches produit, repérées par leur référence.
$fichesFaites = 0;
$partages = 0;
foreach ($fiches as $sku => $reglage) {
    $id = (int) wc_get_product_id_by_sku($sku);
    if (! $id) {
        continue;
    }
    foreach ([
        '_yoast_wpseo_title' => $reglage['titre'] ?? null,
        '_yoast_wpseo_metadesc' => $reglage['description'] ?? null,
        '_yoast_wpseo_focuskw' => $reglage['motcle'] ?? null,
        '_yoast_wpseo_linkdex' => $notes('fiches', $sku)[0],
        '_yoast_wpseo_content_score' => $notes('fiches', $sku)[1],
    ] as $meta => $valeur) {
        $ecrireMeta($id, $meta, $valeur);
    }
    // L'image de partage (1200 × 630) est fabriquée d'avance : le premier partage n'attend pas.
    if (function_exists('App\image_partage') && App\image_partage((int) get_post_thumbnail_id($id))) {
        $partages++;
    }
    $faits[] = $id;
    $fichesFaites++;
}

// Yoast garde une copie des métas (table des « indexables ») : on la reconstruit pour les pages modifiées.
$construire = YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$depot = YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
foreach ($faits as $id) {
    $construire->build_for_id_and_type($id, 'post', $depot->find_by_id_and_type($id, 'post', false));
}

us_log(sprintf(
    'référencement : %d pages et articles, %d rayons, %d fiches, %d images de partage de fiche',
    count($faits) - $fichesFaites,
    $rayonsFaits,
    $fichesFaites,
    $partages,
));
$gardes = array_unique($gardes);
if ($gardes) {
    us_log('modifié dans l\'admin, conservé (US_SEED_FORCE=1 pour écraser) : ' . implode(', ', $gardes));
}
