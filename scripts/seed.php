<?php
/**
 * Contenus de départ Univers Sensoriel (idempotent : relançable sans doublon).
 *
 *   composer seed
 *
 * Sources : scripts/catalogue/catalogue.json (produit par scripts/catalogue/preparer.py),
 * images dans docs/02-images-produits, textes des pages dans scripts/pages/*.html.
 * US_SEED_FORCE=1 réécrit aussi les fiches et les pages déjà créées.
 */

if (! defined('ABSPATH')) {
    exit("À lancer avec WP-CLI : wp eval-file scripts/seed.php\n");
}

require_once __DIR__ . '/fonctions.php';

$root = dirname(__DIR__);
$force = (bool) getenv('US_SEED_FORCE');

/*
|--------------------------------------------------------------------------
| Réglages
|--------------------------------------------------------------------------
*/
WP_CLI::log('Réglages');
$options = [
    'blogname' => 'Univers Sensoriel',
    'blogdescription' => 'Beauté · Bien-être · Art de vivre',
    'woocommerce_currency' => 'EUR',
    'woocommerce_currency_pos' => 'right_space',
    'woocommerce_price_thousand_sep' => ' ',
    'woocommerce_price_decimal_sep' => ',',
    'woocommerce_price_num_decimals' => '2',
    'woocommerce_default_country' => 'FR',
    'woocommerce_allowed_countries' => 'all',
    'woocommerce_calc_taxes' => 'no',
    'woocommerce_weight_unit' => 'g',
    'woocommerce_dimension_unit' => 'cm',
    'woocommerce_manage_stock' => 'no',
    'woocommerce_catalog_columns' => 4,
    'woocommerce_catalog_rows' => 6,
    'woocommerce_default_catalog_orderby' => 'menu_order',
    'woocommerce_feature_order_attribution_enabled' => 'no',
    'woocommerce_coming_soon' => 'no',
    'woocommerce_allow_tracking' => 'no',
    'woocommerce_show_marketplace_suggestions' => 'no',
    'woocommerce_task_list_hidden' => 'yes',
    'woocommerce_cart_redirect_after_add' => 'no',
    'woocommerce_enable_ajax_add_to_cart' => 'yes',
    'woocommerce_permalinks' => [
        'product_base' => 'produit',
        'category_base' => 'collection',
        'tag_base' => 'etiquette-produit',
        'attribute_base' => '',
        'use_verbose_page_rules' => false,
    ],
    'default_comment_status' => 'closed',
    'default_ping_status' => 'closed',
    'thumbnail_size_w' => 300, 'thumbnail_size_h' => 300,
    'medium_size_w' => 600, 'medium_size_h' => 600,
    'large_size_w' => 1200, 'large_size_h' => 1200,
];
foreach ($options as $cle => $valeur) {
    update_option($cle, $valeur);
}

/*
|--------------------------------------------------------------------------
| Attributs (filtres de la boutique)
|--------------------------------------------------------------------------
*/
WP_CLI::log('Attributs');
$attributs = [
    'genre' => ['Pour qui', false],
    'famille-olfactive' => ['Famille olfactive', true], // archives publiques : /famille-olfactive/floral/
    'gamme' => ['Gamme', false],
    'format' => ['Format', false],
];
foreach ($attributs as $slug => [$label, $archives]) {
    if (! wc_attribute_taxonomy_id_by_name($slug)) {
        wc_create_attribute(['name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => $archives]);
        us_log("attribut créé : $label");
    }
    $taxonomie = wc_attribute_taxonomy_name($slug);
    if (! taxonomy_exists($taxonomie)) {
        register_taxonomy($taxonomie, ['product'], ['hierarchical' => false, 'public' => $archives, 'rewrite' => ['slug' => $slug]]);
    }
}
delete_transient('wc_attribute_taxonomies');

$familles = [
    'Floral' => 'Rose, jasmin, pivoine, fleur d\'oranger : les parfums floraux déclinent le bouquet, du plus frais au plus opulent.',
    'Fruité' => 'Pêche, poire, cassis ou fruits rouges : des parfums fruités lumineux, gourmands sans être sucrés.',
    'Boisé' => 'Cèdre, santal, vétiver, oud : des parfums boisés au sillage chaud, sec et élégant.',
    'Ambré' => 'Vanille, ambre, résines et épices douces : la famille ambrée, autrefois dite orientale, enveloppe et réchauffe.',
    'Chypré' => 'Bergamote, patchouli et mousse : la structure chyprée, sophistiquée et pleine de caractère.',
    'Fougère' => 'Lavande, géranium, fève tonka : l\'accord fougère, classique de la parfumerie masculine.',
    'Aromatique' => 'Lavande, sauge, romarin, menthe : des parfums aromatiques nets et toniques.',
    'Hespéridé' => 'Citron, bergamote, mandarine, néroli : la fraîcheur des agrumes, vive et pétillante.',
    'Gourmand' => 'Vanille, praliné, caramel, cacao : des parfums gourmands, ronds et réconfortants.',
    'Aquatique' => 'Notes marines et ozoniques : des parfums aquatiques frais, transparents, comme une brise.',
    'Épicé' => 'Poivre, cardamome, cannelle, safran : des parfums épicés vibrants et chaleureux.',
    'Cuiré' => 'Cuir, daim, tabac : des parfums cuirés profonds, au caractère affirmé.',
    'Musqué' => 'Muscs blancs et notes de peau : des parfums musqués doux, propres et enveloppants.',
    'Poudré' => 'Iris, violette, héliotrope : des parfums poudrés au toucher velouté.',
    'Vert' => 'Feuilles froissées, galbanum, thé : des parfums verts, frais et naturels.',
];
foreach ($familles as $nom => $description) {
    us_terme($nom, 'pa_famille-olfactive', ['description' => $description]);
}

/*
|--------------------------------------------------------------------------
| Rayons (catégories de produits)
|--------------------------------------------------------------------------
*/
WP_CLI::log('Rayons');
$rayons = json_decode(file_get_contents(__DIR__ . '/catalogue/rayons.json'), true);
$idsRayons = [];
$ordre = 0;
foreach ($rayons as $univers) {
    $parent = us_terme($univers['nom'], 'product_cat', ['slug' => $univers['slug'], 'description' => $univers['description']]);
    $idsRayons[$univers['nom']] = $parent;
    update_term_meta($parent, 'order', ++$ordre);
    update_term_meta($parent, '_us_accroche', $univers['accroche']);
    foreach ($univers['rayons'] as $rayon) {
        $id = us_terme($rayon['nom'], 'product_cat', ['slug' => $rayon['slug'], 'parent' => $parent, 'description' => $rayon['description']]);
        $idsRayons[$rayon['nom']] = $id;
        update_term_meta($id, 'order', ++$ordre);
    }
}
// La catégorie par défaut « Non classé » ne sert pas.
$defaut = get_term_by('slug', 'non-classe', 'product_cat') ?: get_term_by('slug', 'uncategorized', 'product_cat');

/*
|--------------------------------------------------------------------------
| Catalogue
|--------------------------------------------------------------------------
*/
WP_CLI::log('Catalogue');
$catalogue = json_decode(file_get_contents(__DIR__ . '/catalogue/catalogue.json'), true);
$images = $root . '/docs/02-images-produits';
$existants = [];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
    if ($source = get_post_meta($id, '_us_source', true)) {
        $existants[$source] = $id;
    }
}
$selection = json_decode(file_get_contents(__DIR__ . '/catalogue/selection.json'), true);
$crees = $maj = 0;
$progression = \WP_CLI\Utils\make_progress_bar('  fiches', count($catalogue));

// Ordre d'affichage : rayon par rayon (parfums d'abord), les produits vedettes en tête de leur rayon.
$ordreRayons = array_flip(array_keys($idsRayons));

foreach ($catalogue as $rang => $p) {
    $progression->tick();
    $id = $existants[$p['source']] ?? 0;
    $vedette = in_array($p['nom'], $selection['vedettes'], true);
    $ordre = ($ordreRayons[$p['rayon']] ?? 99) * 1000 + ($vedette ? 0 : 500) + ($rang % 500);
    $empreinte = md5(wp_json_encode($p) . $ordre . 'v2');
    if ($id && ! $force && get_post_meta($id, '_us_empreinte', true) === $empreinte) {
        continue;
    }
    $variable = count($p['variations']) > 1;
    $produit = $id ? wc_get_product($id) : ($variable ? new WC_Product_Variable() : new WC_Product_Simple());
    if ($id && $variable !== $produit->is_type('variable')) {
        wp_set_object_terms($id, $variable ? 'variable' : 'simple', 'product_type');
        $produit = $variable ? new WC_Product_Variable($id) : new WC_Product_Simple($id);
    }

    $produit->set_name($p['nom']);
    $produit->set_slug($p['slug']);
    $produit->set_status($p['publie'] ? 'publish' : 'draft');
    $produit->set_catalog_visibility('visible');
    $produit->set_short_description($p['accroche']);
    $produit->set_description($p['description']);
    $produit->set_sku($p['sku']);
    $produit->set_menu_order($ordre);
    $produit->set_reviews_allowed(true); // réservés aux acheteurs (voir scripts/journal.php)
    $produit->set_featured($vedette);

    $categories = array_filter(array_map(fn ($nom) => $idsRayons[$nom] ?? 0, array_merge([$p['univers'], $p['rayon']], $p['collections'])));
    $produit->set_category_ids($categories);

    // Attributs : pour qui, famille olfactive, gamme, puis l'option d'achat (format le plus souvent).
    $attributsProduit = [];
    $position = 0;
    $globaux = ['genre' => $p['genre'] ? [$p['genre']] : [], 'famille-olfactive' => $p['familles'], 'gamme' => $p['gamme'] ? [$p['gamme']] : []];
    $optionGlobale = $p['option'] === 'Format';
    if ($variable && $optionGlobale) {
        $globaux['format'] = array_column($p['variations'], 'format');
    }
    foreach ($globaux as $slug => $valeurs) {
        if (! $valeurs) {
            continue;
        }
        $taxonomie = wc_attribute_taxonomy_name($slug);
        $ids = array_filter(array_map(fn ($v) => us_terme($v, $taxonomie), $valeurs));
        $a = new WC_Product_Attribute();
        $a->set_id(wc_attribute_taxonomy_id_by_name($slug));
        $a->set_name($taxonomie);
        $a->set_options($ids);
        $a->set_position($position++);
        $a->set_visible($slug !== 'format');
        $a->set_variation($slug === 'format');
        $attributsProduit[$taxonomie] = $a;
    }
    if ($variable && ! $optionGlobale) {
        $a = new WC_Product_Attribute();
        $a->set_name($p['option']);
        $a->set_options(array_column($p['variations'], 'format'));
        $a->set_position($position++);
        $a->set_visible(false);
        $a->set_variation(true);
        $attributsProduit[sanitize_title($p['option'])] = $a;
    }
    $produit->set_attributes($attributsProduit);

    if (! $variable) {
        $prix = $p['prix'];
        $ancien = $p['ancien_prix'] && (float) $p['ancien_prix'] > (float) $prix ? $p['ancien_prix'] : '';
        $produit->set_regular_price($ancien ?: $prix);
        $produit->set_sale_price($ancien ? $prix : '');
        $produit->set_stock_status($p['dispo'] ? 'instock' : 'outofstock');
    }

    // Images : la première sert de visuel principal.
    $alt = trim($p['nom'] . ($p['sous_titre'] ? ' – ' . $p['sous_titre'] : '')) . ' | Univers Sensoriel';
    $ids = [];
    foreach ($p['images'] as $image) {
        $altImage = $image['partage'] ? $p['rayon'] . ' ' . $p['gamme'] . ' – Univers Sensoriel' : $alt;
        if ($media = us_media($images . '/' . $image['fichier'], $altImage, $image['fichier'])) {
            update_post_meta($media, '_us_detoure', $image['detoure'] ? '1' : '0');
            $ids[] = $media;
        }
    }
    $produit->set_image_id($ids[0] ?? 0);
    $produit->set_gallery_image_ids(array_slice($ids, 1));

    $id = $produit->save();
    update_post_meta($id, '_us_source', $p['source']);
    update_post_meta($id, '_us_sous_titre', $p['sous_titre']);
    update_post_meta($id, '_us_reference', $p['reference']);
    update_post_meta($id, '_us_notes', $p['notes']);
    update_post_meta($id, '_us_famille', $p['famille']);
    if ($defaut) {
        wp_remove_object_terms($id, $defaut->term_id, 'product_cat');
    }

    if ($variable) {
        $cleAttribut = $optionGlobale ? 'pa_format' : sanitize_title($p['option']);
        $connues = [];
        foreach ($produit->get_children() as $enfant) {
            $v = wc_get_product($enfant);
            $connues[$v->get_meta('_us_format')] = $v;
        }
        // Les déclinaisons disparues ou renommées partent d'abord : leur référence (UGS) est réutilisée.
        foreach (array_diff_key($connues, array_flip(array_column($p['variations'], 'format'))) as $format => $ancienne) {
            $ancienne->delete(true);
            unset($connues[$format]);
        }
        foreach ($p['variations'] as $n => $v) {
            $variation = $connues[$v['format']] ?? new WC_Product_Variation();
            $variation->set_parent_id($id);
            $valeur = $optionGlobale ? sanitize_title($v['format']) : $v['format'];
            $variation->set_attributes([$cleAttribut => $valeur]);
            $ancien = $v['ancien_prix'] && (float) $v['ancien_prix'] > (float) $v['prix'] ? $v['ancien_prix'] : '';
            $variation->set_regular_price($ancien ?: $v['prix']);
            $variation->set_sale_price($ancien ? $v['prix'] : '');
            $variation->set_stock_status($v['dispo'] ? 'instock' : 'outofstock');
            $variation->set_menu_order($n);
            $variation->set_sku($p['sku'] . '-' . ($n + 1));
            $variation->update_meta_data('_us_format', $v['format']);
            $variation->save();
            unset($connues[$v['format']]);
        }
        foreach ($connues as $reste) {
            $reste->delete(true);
        }
        // Format proposé par défaut : le plus grand (le premier de la liste d'origine).
        $premier = $p['variations'][0]['format'];
        $produit = wc_get_product($id);
        $produit->set_default_attributes([$cleAttribut => $optionGlobale ? sanitize_title($premier) : $premier]);
        $produit->save();
        WC_Product_Variable::sync($id);
    }
    update_post_meta($id, '_us_empreinte', $empreinte);
    isset($existants[$p['source']]) ? $maj++ : $crees++;
}
$progression->finish();
$utilises = [];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
    $utilises[] = (int) get_post_thumbnail_id($id);
    $utilises = array_merge($utilises, array_map('intval', array_filter(explode(',', (string) get_post_meta($id, '_product_image_gallery', true)))));
}
$orphelins = array_diff(get_posts([
    'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids',
    'meta_query' => [['key' => '_us_detoure', 'compare' => 'EXISTS']],
]), $utilises);
foreach ($orphelins as $orphelin) {
    wp_delete_attachment($orphelin, true);
}
if ($orphelins) {
    us_log(count($orphelins) . ' images produit devenues inutiles supprimées');
}
us_log("$crees fiches créées, $maj mises à jour, " . (count($catalogue) - $crees - $maj) . ' déjà en place');

/*
|--------------------------------------------------------------------------
| Livraison : France, offerte dès 49 € (charte), retrait à la boutique de Metz
|--------------------------------------------------------------------------
*/
WP_CLI::log('Livraison');
if (! get_option('us_livraison_creee')) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('France');
    $zone->add_location('FR', 'country');
    $zone->save();
    $forfait = $zone->add_shipping_method('flat_rate');
    update_option("woocommerce_flat_rate_{$forfait}_settings", ['title' => 'Livraison à domicile', 'tax_status' => 'none', 'cost' => '4.90']);
    $gratuit = $zone->add_shipping_method('free_shipping');
    update_option("woocommerce_free_shipping_{$gratuit}_settings", ['title' => 'Livraison offerte', 'requires' => 'min_amount', 'min_amount' => '49']);
    $retrait = $zone->add_shipping_method('local_pickup');
    update_option("woocommerce_local_pickup_{$retrait}_settings", ['title' => 'Retrait à la boutique de Metz', 'tax_status' => 'none', 'cost' => '']);
    update_option('us_livraison_creee', 1);
    us_log('zone France : forfait, offerte dès 49 €, retrait à Metz');
}

// Paiement par carte (Stripe).
WP_CLI::log('Paiement');
require __DIR__ . '/paiement.php';

// Langues (français, anglais) et pays (devise de chaque pays).
require __DIR__ . '/langues.php';

// E-mails : objets et titres en français, couleurs de la charte.
WP_CLI::log('E-mails');
require __DIR__ . '/mails.php';

/*
|--------------------------------------------------------------------------
| Pages
|--------------------------------------------------------------------------
*/
WP_CLI::log('Pages');
$contenu = fn (string $fichier) => is_file(__DIR__ . "/pages/$fichier.html") ? trim(file_get_contents(__DIR__ . "/pages/$fichier.html")) : '';
$accueil = us_page('accueil', 'Univers Sensoriel');
us_page('la-maison', 'La Maison', $contenu('la-maison'));
// Contact : la page n'appelle que le shortcode du formulaire, le reste est dans page-contact.blade.php.
require __DIR__ . '/formulaires.php';
$formulaireContact = (int) (get_option('us_formulaires', [])['contact'] ?? 0);
$codeContact = $formulaireContact ? '[forminator_form id="' . $formulaireContact . '"]' : '';
$contact = us_page('contact', 'Contact', $codeContact);
if ($codeContact && ! str_contains((string) get_post_field('post_content', $contact), '[forminator_form')) {
    wp_update_post(['ID' => $contact, 'post_content' => $codeContact]);
    us_log('page Contact : shortcode du formulaire en place');
}
us_page('nos-boutiques', 'Nos boutiques', $contenu('nos-boutiques'));
us_page('livraison-et-retours', 'Livraison et retours', $contenu('livraison-et-retours'));
us_page('questions-frequentes', 'Questions fréquentes', $contenu('questions-frequentes'));
us_page('guide-des-familles-olfactives', 'Guide des familles olfactives', $contenu('guide-des-familles-olfactives'));
us_page('devenir-consultante', 'Devenir consultante', $contenu('devenir-consultante'));
us_page('mentions-legales', 'Mentions légales', $contenu('mentions-legales'));
us_page('conditions-generales-de-vente', 'Conditions générales de vente', $contenu('conditions-generales-de-vente'));
$confidentialite = us_page('politique-de-confidentialite', 'Politique de confidentialité', $contenu('politique-de-confidentialite'));

update_option('show_on_front', 'page');
update_option('page_on_front', $accueil);
update_option('wp_page_for_privacy_policy', $confidentialite);
update_option('woocommerce_terms_page_id', get_page_by_path('conditions-generales-de-vente')->ID);

// Bandeau de cookies : après les pages légales, auxquelles il renvoie.
require __DIR__ . '/cookies.php';

// Réglages du site (menu « Univers Sensoriel ») : valeurs d'origine recopiées dans l'administration.
WP_CLI::log('Réglages');
require __DIR__ . '/reglages.php';

// Pages WooCommerce (boutique, panier, commande, compte) si elles manquent, puis slugs en français.
WC_Install::create_pages();
foreach (['shop' => ['boutique', 'Boutique'], 'cart' => ['panier', 'Panier'], 'checkout' => ['commande', 'Commande'], 'myaccount' => ['mon-compte', 'Mon compte']] as $cle => [$slug, $titre]) {
    $id = wc_get_page_id($cle);
    if ($id > 0 && get_post_field('post_name', $id) !== $slug) {
        wp_update_post(['ID' => $id, 'post_name' => $slug, 'post_title' => $titre]);
    }
}
// Contenus d'exemple de WordPress.
foreach (['hello-world' => 'post', 'bonjour-tout-le-monde' => 'post', 'sample-page' => 'page', 'page-d-exemple' => 'page'] as $slug => $type) {
    if ($exemple = get_page_by_path($slug, OBJECT, $type)) {
        wp_delete_post($exemple->ID, true);
    }
}

wc_delete_product_transients();
delete_transient('wc_term_counts');
flush_rewrite_rules();

require __DIR__ . '/journal.php';

if (is_file(__DIR__ . '/seo.php')) {
    require __DIR__ . '/seo.php';
}

WP_CLI::success('Seed terminé.');
