<?php

/**
 * Référencement calculé à l'affichage (Yoast SEO) :
 * titres et descriptions des fiches produit et des familles olfactives, données structurées, plan du site.
 * Les pages et les rayons sont réglés dans scripts/seo.json ; une valeur saisie dans Yoast l'emporte toujours.
 */

namespace App;

if (! defined('WPSEO_VERSION')) {
    return;
}

/** Coupe un texte à la dernière phrase ou au dernier mot avant la limite. */
function raccourcir(string $texte, int $limite): string
{
    $texte = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($texte)));
    if (mb_strlen($texte) <= $limite) {
        return $texte;
    }
    $coupe = mb_substr($texte, 0, $limite);
    $phrase = mb_strrpos($coupe, '. ');
    if ($phrase !== false && $phrase > $limite * 0.6) {
        return mb_substr($coupe, 0, $phrase + 1);
    }

    return rtrim(mb_substr($coupe, 0, (int) mb_strrpos($coupe, ' ')), ' ,;:') . '…';
}

/** Vrai si Yoast n'a pas de valeur saisie pour la fiche (on calcule alors la nôtre). */
function sans_meta_yoast(string $meta): bool
{
    return is_singular('product') && get_post_meta(get_queried_object_id(), $meta, true) === '';
}

/**
 * Titre d'une fiche : « Absolutely – Parfum femme chypré fruité | Univers Sensoriel ».
 * Le mot-clé (type de produit) suit le nom ; la marque saute si le titre dépasse 60 caractères.
 */
function titre_fiche(\WC_Product $produit): string
{
    $infos = fiche($produit);
    $nom = $produit->get_name();
    $type = '';
    if ($infos['parfum'] && $infos['rayon']) {
        $type = match ($infos['rayon']->slug) {
            'parfums-femme' => 'Parfum femme',
            'parfums-homme' => 'Parfum homme',
            'parfums-enfant' => 'Parfum enfant',
            default => 'Extrait de parfum',
        };
        if ($infos['familles']) {
            $type .= ' ' . mb_strtolower(implode(' ', array_map(fn ($f) => $f->name, array_slice($infos['familles'], 0, 2))));
        }
    } elseif ($infos['rayon'] && ! str_contains(mb_strtolower($nom), mb_strtolower(explode(' ', $infos['rayon']->name)[0]))) {
        $type = $infos['rayon']->name;
    }
    $titre = $type ? "$nom – $type" : $nom;

    return mb_strlen($titre) <= 40 ? "$titre | Univers Sensoriel" : $titre;
}

function description_fiche(\WC_Product $produit): string
{
    $infos = fiche($produit);
    $texte = raccourcir($produit->get_short_description() ?: $produit->get_description(), 118);
    $suite = '';
    if ($produit->is_type('variable') && ($min = $produit->get_variation_price('min', true)) !== '') {
        $suite = ' Dès ' . html_entity_decode(wp_strip_all_tags(wc_price($min)), ENT_QUOTES, 'UTF-8') . '.';
    }

    return trim($texte . $suite);
}

$titreProduit = function ($titre) {
    if (sans_meta_yoast('_yoast_wpseo_title') && ($produit = wc_get_product(get_queried_object_id()))) {
        return titre_fiche($produit);
    }
    if (is_tax('pa_famille-olfactive')) {
        $terme = get_queried_object();

        return 'Parfums ' . famille_au_pluriel($terme->name) . ' : ' . $terme->count . ' extraits de parfum | Univers Sensoriel';
    }

    return $titre;
};
$descriptionProduit = function ($description) {
    if (sans_meta_yoast('_yoast_wpseo_metadesc') && ($produit = wc_get_product(get_queried_object_id()))) {
        return description_fiche($produit);
    }
    if (is_tax('pa_famille-olfactive') && ($terme = get_queried_object())) {
        return raccourcir($terme->description, 110) . ' Extraits longue tenue, livraison offerte dès ' . SEUIL_LIVRAISON . ' €.';
    }

    return $description;
};
foreach (['wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title'] as $filtre) {
    add_filter($filtre, $titreProduit, 20);
}
foreach (['wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description'] as $filtre) {
    add_filter($filtre, $descriptionProduit, 20);
}

/** Organization : coordonnées et boutiques de la Maison. */
add_filter('wpseo_schema_organization', function ($data) {
    $c = coordonnees();
    $data['email'] = $c['courriel'];
    $data['telephone'] = $c['telephone'];
    $data['slogan'] = 'Beauté · Bien-être · Art de vivre';
    $data['contactPoint'] = [
        '@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => $c['telephone'],
        'email' => $c['courriel'], 'availableLanguage' => ['French'],
    ];

    return $data;
});

/** Product : la marque est la gamme (Olfazeta, Aurodhea…), le vendeur Univers Sensoriel. */
add_filter('woocommerce_structured_data_product', function ($data, $produit) {
    $infos = fiche($produit);
    if ($infos['gamme']) {
        $data['brand'] = ['@type' => 'Brand', 'name' => $infos['gamme']];
    }
    if ($infos['rayon']) {
        $data['category'] = $infos['rayon']->name;
    }

    return $data;
}, 10, 2);

/** Plan du site : jamais de page technique, même hors production (Yoast n'y tient pas ses « indexables »). */
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
    foreach (['cart', 'checkout', 'myaccount'] as $page) {
        if (function_exists('wc_get_page_id') && ($id = wc_get_page_id($page)) > 0) {
            $ids[] = $id;
        }
    }

    return $ids;
});

/** Les archives d'attributs qui ne servent qu'aux filtres n'entrent pas dans le plan du site. */
add_filter('wpseo_sitemap_exclude_taxonomy', fn ($exclure, $taxonomie) => in_array($taxonomie, ['pa_genre', 'pa_gamme', 'pa_format', 'product_tag'], true) ?: $exclure, 10, 2);

/** Rayons vides ou réservés aux brouillons : pas d'indexation. */
add_filter('wpseo_robots', function ($robots) {
    if (is_tax('product_cat') && ($terme = get_queried_object()) && (int) $terme->count === 0) {
        return 'noindex, follow';
    }

    return $robots;
}, 20);
