<?php

/**
 * Promotions et nouveautés.
 *
 * - /promotions/ : la boutique limitée aux produits en promotion (prix barré dans WooCommerce), avec les mêmes
 *   filtres, tri et pagination. Aucune liste à tenir à jour : un produit mis en promotion y apparaît tout seul.
 * - Nouveautés : la catégorie « nouveautes » (/collection/nouveautes/), remplie par l'import (app/import-produits.php).
 *   Un produit y est ajouté ou retiré dans sa fiche, case « Nouveautés » des catégories.
 */

namespace App;

add_action('init', function () {
    add_rewrite_rule('^promotions/?$', 'index.php?post_type=product&us_offres=1', 'top');
    add_rewrite_rule('^promotions/page/([0-9]+)/?$', 'index.php?post_type=product&us_offres=1&paged=$matches[1]', 'top');
});

add_filter('query_vars', fn ($variables) => [...$variables, 'us_offres']);

/** Vrai sur la page Promotions. */
function page_offres(): bool
{
    return (bool) get_query_var('us_offres');
}

/** Produits publiés en promotion (les produits à déclinaisons comptent dès qu'une déclinaison l'est). */
function ids_offres(): array
{
    if (! function_exists('wc_get_product_ids_on_sale')) {
        return [];
    }
    $ids = array_filter(array_map('intval', wc_get_product_ids_on_sale()));

    return array_values(array_filter($ids, fn ($id) => get_post_type($id) === 'product' && get_post_status($id) === 'publish'));
}

/** Nombre de produits en promotion, pour le menu. */
function nombre_offres(): int
{
    static $nombre = null;

    return $nombre ??= count(ids_offres());
}

add_action('woocommerce_product_query', function ($query) {
    if (page_offres()) {
        $query->set('post__in', ids_offres() ?: [0]);
    }
});

/** Lien de la page Promotions. */
function lien_offres(): string
{
    return home_url('/promotions/');
}

/** Catégorie Nouveautés : [lien, nombre de produits], ou null si elle n'existe pas encore. */
function nouveautes(): ?array
{
    $terme = get_term_by('slug', 'nouveautes', 'product_cat');

    return $terme && $terme->count ? [get_term_link($terme), (int) $terme->count, $terme] : null;
}

/** Vrai si le produit est dans la catégorie Nouveautés (badge « Nouveau » de la carte). */
function est_nouveaute(\WC_Product $produit): bool
{
    return has_term('nouveautes', 'product_cat', $produit->get_parent_id() ?: $produit->get_id());
}

/** Derniers produits de la catégorie Nouveautés (publiés, avec photo), les plus récents d'abord. */
function produits_nouveautes(int $nombre): array
{
    if (! function_exists('wc_get_products')) {
        return [];
    }

    return array_values(array_filter(
        wc_get_products(['status' => 'publish', 'category' => ['nouveautes'], 'limit' => $nombre * 2, 'orderby' => 'date', 'order' => 'DESC']),
        fn ($p) => $p->get_image_id()
    ));
}

/** Référencement de la page Promotions : titre, description, adresse canonique. */
add_filter('wpseo_title', fn ($titre) => page_offres() ? texte('offres_titre') . ' | Univers Sensoriel' : $titre, 30);
add_filter('wpseo_opengraph_title', fn ($titre) => page_offres() ? texte('offres_titre') . ' | Univers Sensoriel' : $titre, 30);
add_filter('wpseo_metadesc', fn ($texte) => page_offres() ? texte('offres_description') : $texte, 30);
add_filter('wpseo_opengraph_desc', fn ($texte) => page_offres() ? texte('offres_description') : $texte, 30);
add_filter('wpseo_canonical', fn ($adresse) => page_offres() ? lien_offres() . (get_query_var('paged') > 1 ? 'page/' . (int) get_query_var('paged') . '/' : '') : $adresse, 30);
add_filter('wpseo_opengraph_url', fn ($adresse) => page_offres() ? lien_offres() : $adresse, 30);

/** Nouveautés : triées par date d'arrivée (l'ordre de vitrine de l'import), sauf tri choisi par le visiteur. */
add_filter('woocommerce_default_catalog_orderby', fn ($tri) => function_exists('is_product_category') && is_product_category('nouveautes') ? 'date' : $tri);
