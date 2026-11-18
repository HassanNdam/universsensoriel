<?php

/**
 * WooCommerce : gabarits Blade, filtres de la boutique, prix, compteur du panier.
 */

namespace App;

if (! function_exists('WC')) {
    return;
}

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce', ['thumbnail_image_width' => 600, 'single_image_width' => 1200]);
});

/** Boutique et fiche produit en Blade (resources/views/woocommerce/*). */
add_filter('template_include', function ($template) {
    $view = match (true) {
        is_product() => 'single-product',
        is_shop(), is_product_taxonomy() => 'archive-product',
        default => null,
    };

    return $view ? get_theme_file_path("resources/views/woocommerce/{$view}.blade.php") : $template;
}, 99);

/** Styles de WooCommerce coupés : le thème applique la charte (les blocs panier et commande gardent les leurs). */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');
add_filter('woocommerce_product_tabs', '__return_empty_array', 98);
add_filter('woocommerce_price_trim_zeros', '__return_true'); // « 35 € », pas « 35,00 € »
add_filter('loop_shop_per_page', fn () => 24);
add_filter('woocommerce_variable_price_html', fn ($html, $produit) => prix_carte($produit), 10, 2);
add_filter('woocommerce_reset_variations_link', '__return_empty_string');
add_filter('woocommerce_product_add_to_cart_text', fn ($texte, $produit) => $produit->is_type('variable') ? 'Choisir' : 'Ajouter au panier', 10, 2);
add_filter('woocommerce_product_single_add_to_cart_text', fn () => 'Ajouter au panier');
add_filter('woocommerce_dropdown_variation_attribute_options_args', fn ($args) => ['show_option_none' => false] + $args);

/** Les archives de la famille olfactive s'appellent /famille-olfactive/floral/. */
add_filter('woocommerce_taxonomy_args_pa_famille-olfactive', function ($args) {
    $args['rewrite'] = ['slug' => 'famille-olfactive', 'with_front' => false];
    $args['public'] = true;

    return $args;
});

/*
|--------------------------------------------------------------------------
| Filtres de la boutique
|--------------------------------------------------------------------------
| Paramètres d'adresse : ?genre[]=femme&famille[]=floral&gamme[]=…&format[]=…&prix=20-40
| Un même filtre additionne ses valeurs (OU), deux filtres différents se croisent (ET).
*/

const FACETTES = [
    'genre' => ['pa_genre', 'Pour qui'],
    'famille' => ['pa_famille-olfactive', 'Famille olfactive'],
    'format' => ['pa_format', 'Format'],
    'gamme' => ['pa_gamme', 'Gamme'],
];

const TRANCHES_PRIX = ['0-20' => 'Moins de 20 €', '20-40' => 'De 20 à 40 €', '40-60' => 'De 40 à 60 €', '60-' => 'Plus de 60 €'];

/** Filtres actifs lus dans l'adresse : ['genre' => ['femme'], 'prix' => '20-40', …]. */
function filtres_actifs(): array
{
    $actifs = [];
    foreach (FACETTES as $cle => [$taxonomie]) {
        $valeurs = array_filter(array_map('sanitize_title', (array) ($_GET[$cle] ?? [])));
        if ($valeurs) {
            $actifs[$cle] = array_values($valeurs);
        }
    }
    $prix = sanitize_text_field((string) ($_GET['prix'] ?? ''));
    if (isset(TRANCHES_PRIX[$prix])) {
        $actifs['prix'] = $prix;
    }

    return $actifs;
}

add_action('woocommerce_product_query', function ($query) {
    $actifs = filtres_actifs();
    $taxQuery = (array) $query->get('tax_query');
    foreach (FACETTES as $cle => [$taxonomie]) {
        if (! empty($actifs[$cle])) {
            $taxQuery[] = ['taxonomy' => $taxonomie, 'field' => 'slug', 'terms' => $actifs[$cle], 'operator' => 'IN'];
        }
    }
    $query->set('tax_query', $taxQuery);
    if (! empty($actifs['prix'])) {
        [$min, $max] = array_pad(explode('-', $actifs['prix']), 2, '');
        $metaQuery = (array) $query->get('meta_query');
        $metaQuery[] = ['key' => '_price', 'value' => $max === '' ? (float) $min : [(float) $min, (float) $max - 0.01],
            'compare' => $max === '' ? '>=' : 'BETWEEN', 'type' => 'DECIMAL(10,2)'];
        $query->set('meta_query', $metaQuery);
    }
});

/** Une page filtrée ou triée n'est pas à indexer : seule la page du rayon l'est. */
add_filter('wpseo_robots', function ($robots) {
    return (filtres_actifs() || isset($_GET['orderby'])) && (is_shop() || is_product_taxonomy()) ? 'noindex, follow' : $robots;
});

/**
 * Facettes à afficher pour la page en cours, avec le nombre de produits par valeur.
 * Le compte porte sur le rayon affiché, sans tenir compte des autres filtres cochés.
 */
function facettes(): array
{
    $objet = get_queried_object();
    $args = ['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true];
    if ($objet instanceof \WP_Term) {
        $args['tax_query'] = [['taxonomy' => $objet->taxonomy, 'field' => 'term_id', 'terms' => $objet->term_id]];
    }
    if (is_search()) {
        $args['s'] = get_search_query();
    }
    $cle = 'us_facettes_' . md5(wp_json_encode($args));
    $resultat = get_transient($cle);
    if ($resultat === false) {
        $ids = get_posts($args);
        $resultat = ['total' => count($ids), 'facettes' => [], 'prix' => []];
        foreach (FACETTES as $nom => [$taxonomie, $libelle]) {
            if (! taxonomy_exists($taxonomie) || ($objet instanceof \WP_Term && $objet->taxonomy === $taxonomie)) {
                continue;
            }
            $comptes = [];
            foreach ($ids ? wp_get_object_terms($ids, $taxonomie, ['fields' => 'all_with_object_id']) : [] as $terme) {
                $comptes[$terme->slug] ??= ['slug' => $terme->slug, 'nom' => $terme->name, 'nombre' => 0];
                $comptes[$terme->slug]['nombre']++;
            }
            if (count($comptes) < 2) {
                continue;
            }
            $nom === 'format'
                ? uasort($comptes, fn ($a, $b) => (int) $a['nom'] <=> (int) $b['nom'] ?: strcmp($a['nom'], $b['nom']))
                : uasort($comptes, fn ($a, $b) => $b['nombre'] <=> $a['nombre'] ?: strcmp($a['nom'], $b['nom']));
            $resultat['facettes'][$nom] = ['libelle' => $libelle, 'valeurs' => array_values($comptes)];
        }
        foreach ($ids as $id) {
            $prix = (float) get_post_meta($id, '_price', true);
            foreach (array_keys(TRANCHES_PRIX) as $tranche) {
                [$min, $max] = array_pad(explode('-', $tranche), 2, '');
                if ($prix >= (float) $min && ($max === '' || $prix < (float) $max)) {
                    $resultat['prix'][$tranche] = ($resultat['prix'][$tranche] ?? 0) + 1;
                }
            }
        }
        set_transient($cle, $resultat, HOUR_IN_SECONDS);
    }

    return $resultat;
}

/** Le cache des facettes tombe dès qu'un produit change. */
add_action('save_post_product', function () {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_us\\_facettes\\_%' OR option_name LIKE '\\_transient\\_timeout\\_us\\_facettes\\_%'");
});

/** Tri : libellés courts, dans l'ordre utile. */
add_filter('woocommerce_catalog_orderby', fn () => [
    'menu_order' => 'Notre sélection',
    'date' => 'Nouveautés',
    'price' => 'Prix croissant',
    'price-desc' => 'Prix décroissant',
    'title' => 'De A à Z',
]);
add_filter('woocommerce_get_catalog_ordering_args', function ($args, $orderby) {
    return $orderby === 'title' ? ['orderby' => 'title', 'order' => 'ASC', 'meta_key' => ''] : $args;
}, 10, 2);

/*
|--------------------------------------------------------------------------
| Panier
|--------------------------------------------------------------------------
*/

/** Compteur du panier dans l'en-tête, mis à jour après un ajout en AJAX. */
function compteur_panier(): string
{
    $nombre = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    return sprintf('<span class="panier-nombre" data-nombre="%d">%s</span>', $nombre, $nombre ?: '');
}
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['span.panier-nombre'] = compteur_panier();

    return $fragments;
});

/** Somme qui manque pour la livraison offerte (0 si le seuil est atteint ou le panier vide). */
function reste_livraison_offerte(): float
{
    $total = WC()->cart ? (float) WC()->cart->get_displayed_subtotal() : 0;

    return $total > 0 ? max(0, SEUIL_LIVRAISON - $total) : 0;
}

/** Données structurées Product : une fiche en Blade ne passe pas par le crochet habituel. */
add_action('wp_footer', function () {
    if (is_product() && ($produit = wc_get_product(get_the_ID()))) {
        WC()->structured_data->generate_product_data($produit);
    }
}, 5);

/** Scripts de WooCommerce inutiles hors boutique. */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wc-blocks-style');
    if (is_cart() || is_checkout() || is_account_page()) {
        wp_enqueue_style('wc-blocks-style');
    }
}, 100);

/** Le compteur du panier se met à jour sans recharger la page (ajout depuis une carte produit). */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('wc-cart-fragments');
});
