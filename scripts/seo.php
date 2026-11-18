<?php

/**
 * Référencement (Yoast SEO gratuit), appelé à la fin de scripts/seed.php.
 *
 * - réglages Yoast : séparateur, nom du site, logo, image de partage par défaut, archives inutiles coupées ;
 * - titre, description, mot-clé principal de chaque page et de chaque rayon (scripts/seo.json) ;
 * - les fiches produit et les familles olfactives sont titrées par le thème (app/seo.php) : rien n'est écrit ici pour elles.
 *
 * L'admin fait foi : une valeur n'est réécrite que si elle est vide ou si personne n'y a touché depuis le dernier seed
 * (la valeur écrite est gardée en « ombre » : méta _us_seo_auto…, options us_seo_auto et us_seo_auto_rayons).
 * US_SEED_FORCE=1 réapplique tout seo.json.
 */

if (! defined('WPSEO_VERSION')) {
    WP_CLI::warning('Yoast SEO inactif : référencement non appliqué.');

    return;
}

$seo = json_decode(file_get_contents(__DIR__ . '/seo.json'), true);
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
    'disable-author' => true, // pas de blog : archives auteur, date et format redirigées
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

// Pages.
$faits = [];
foreach ($seo['pages'] as $slug => $reglage) {
    $id = (int) (get_page_by_path($slug)?->ID ?? 0);
    if (! $id) {
        WP_CLI::warning("SEO : page « $slug » introuvable");
        continue;
    }
    foreach ([
        '_yoast_wpseo_title' => $reglage['titre'] ?? null,
        '_yoast_wpseo_metadesc' => $reglage['description'] ?? null,
        '_yoast_wpseo_focuskw' => $reglage['motcle'] ?? null,
        '_yoast_wpseo_meta-robots-noindex' => ! empty($reglage['noindex']) ? '1' : null,
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
    ]);
    $actuel = array_intersect_key((array) WPSEO_Taxonomy_Meta::get_term_meta($terme, 'product_cat'), $valeurs);
    $ombre = $autoRayons[$slug] ?? null;
    if (! $forcer && $ombre !== null && array_filter($actuel) && $actuel != array_intersect_key($ombre, $actuel)) {
        $gardes[] = "rayon {$terme->name}";
        continue;
    }
    WPSEO_Taxonomy_Meta::set_values($terme->term_id, 'product_cat', $valeurs);
    $autoRayons[$slug] = $valeurs;
    $rayonsFaits++;
}
update_option('us_seo_auto_rayons', $autoRayons, false);

// Yoast garde une copie des métas (table des « indexables ») : on la reconstruit pour les pages modifiées.
$construire = YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$depot = YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
foreach ($faits as $id) {
    $construire->build_for_id_and_type($id, 'post', $depot->find_by_id_and_type($id, 'post', false));
}

us_log(sprintf('référencement : %d pages, %d rayons', count($faits), $rayonsFaits));
if ($gardes) {
    us_log('modifié dans l\'admin, conservé (US_SEED_FORCE=1 pour écraser) : ' . implode(', ', $gardes));
}
