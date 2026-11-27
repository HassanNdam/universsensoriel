<?php

/**
 * Import de fiches produit depuis un fichier préparé (scripts/catalogue/nouveautes.py -> produits.json) et ses photos.
 *
 * Sert en local (WP-CLI) et en ligne (app/mises-a-jour.php, par lots, depuis l'administration) : le serveur n'a ni
 * WP-CLI ni le dossier scripts/. Une fiche est retrouvée par sa source (méta _us_source) : relancer l'import la met
 * à jour sans la dupliquer, et une fiche déjà importée dont les données n'ont pas changé n'est pas réécrite.
 *
 * Déclinaisons : « Format » (pa_format), « Teinte » (pa_teinte) ou « Senteur » (pa_senteur). Une teinte garde sa
 * couleur et son code (métas de terme us_couleur, us_code) : la fiche les montre en pastilles (app/palette.php).
 */

namespace App;

/** Rayons créés par l'import s'ils manquent : slug => [nom, rayon parent (slug, '' = premier niveau), description]. */
const IMPORT_RAYONS = [
    'maquillage' => ['Maquillage', 'beaute', 'Lèvres, teint, yeux : rouges et encres à lèvres, crayons, sticks, fonds de teint et mascaras.'],
    'bougies-parfumees' => ['Bougies parfumées', 'art-de-vivre', 'Bougies en cire végétale, en 190 et 400 g : jusqu\'à 90 heures de parfum dans la pièce.'],
    'nouveautes' => ['Nouveautés', '', 'Les derniers produits arrivés dans la boutique : parfums, maquillage, soins, bougies et parfums d\'ambiance.'],
];

/** Attributs des déclinaisons : option de la fiche => [slug, libellé]. */
const IMPORT_OPTIONS = [
    'Format' => ['format', 'Format'],
    'Teinte' => ['teinte', 'Teinte'],
    'Senteur' => ['senteur', 'Senteur'],
];

/** Rayon (créé si besoin) : identifiant du terme. Un rayon créé ici se range après ses frères. */
function import_rayon(string $slug): int
{
    if ($terme = get_term_by('slug', $slug, 'product_cat')) {
        return (int) $terme->term_id;
    }
    if (! isset(IMPORT_RAYONS[$slug])) {
        return 0;
    }
    [$nom, $parent, $description] = IMPORT_RAYONS[$slug];
    $idParent = $parent ? import_rayon($parent) : 0;
    $r = wp_insert_term($nom, 'product_cat', ['slug' => $slug, 'parent' => $idParent, 'description' => $description]);
    if (is_wp_error($r)) {
        return 0;
    }
    // Ordre : après les rayons frères (le menu et les pastilles suivent la méta « order »).
    // « Nouveautés » n'en a pas : elle reste hors des quatre univers du menu, qui a sa propre entrée.
    if ($idParent) {
        $ordres = array_map(fn ($t) => (int) get_term_meta($t->term_id, 'order', true), get_terms(['taxonomy' => 'product_cat', 'parent' => $idParent, 'hide_empty' => false]));
        update_term_meta($r['term_id'], 'order', ($ordres ? max($ordres) : 0) + 1);
    }

    return (int) $r['term_id'];
}

/** Attribut global (créé si besoin) : nom de sa taxonomie (pa_…). */
function import_attribut(string $slug, string $libelle): string
{
    if (! wc_attribute_taxonomy_id_by_name($slug)) {
        wc_create_attribute(['name' => $libelle, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false]);
        delete_transient('wc_attribute_taxonomies');
    }
    $taxonomie = wc_attribute_taxonomy_name($slug);
    if (! taxonomy_exists($taxonomie)) {
        register_taxonomy($taxonomie, ['product'], ['hierarchical' => false, 'public' => false]);
    }

    return $taxonomie;
}

/** Terme d'un attribut (créé si besoin) : identifiant. */
function import_valeur(string $taxonomie, string $nom, array $metas = []): int
{
    $terme = get_term_by('slug', sanitize_title($nom), $taxonomie) ?: get_term_by('name', $nom, $taxonomie);
    $id = $terme ? (int) $terme->term_id : (int) (wp_insert_term($nom, $taxonomie, ['slug' => sanitize_title($nom)])['term_id'] ?? 0);
    foreach ($metas as $cle => $valeur) {
        if ($id && $valeur !== '') {
            update_term_meta($id, $cle, $valeur);
        }
    }

    return $id;
}

/**
 * Photo importée une seule fois dans la médiathèque, repérée comme par le seed (méta _us_source « chemin:taille ») :
 * une photo déjà en ligne (le flacon Luxury, par exemple) est réutilisée.
 */
function import_media(string $dossier, string $fichier, string $alt): int
{
    $chemin = $dossier . '/' . $fichier;
    if (! is_file($chemin)) {
        return 0;
    }
    $cle = $fichier . ':' . filesize($chemin);
    $connus = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_us_source', 'meta_value' => $cle]);
    if ($connus) {
        return (int) $connus[0];
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $provisoire = wp_tempnam(basename($fichier));
    copy($chemin, $provisoire);
    $id = media_handle_sideload(['name' => basename($fichier), 'tmp_name' => $provisoire], 0, $alt);
    if (is_wp_error($id)) {
        return 0;
    }
    update_post_meta($id, '_us_source', $cle);
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    update_post_meta($id, '_us_detoure', webp_transparent($chemin) ? '1' : '0');

    return (int) $id;
}

/** Vrai pour un WebP à couche de transparence (packshot détouré) : en-tête VP8X, drapeau « alpha ». */
function webp_transparent(string $chemin): bool
{
    $entete = (string) file_get_contents($chemin, false, null, 0, 21);

    return strlen($entete) === 21 && substr($entete, 12, 4) === 'VP8X' && (ord($entete[20]) & 0x10);
}

/** Une fiche : créée ou mise à jour. Renvoie son identifiant. */
function importer_fiche(array $p, string $dossier): int
{
    $existants = get_posts(['post_type' => 'product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_us_source', 'meta_value' => $p['source']]);
    $id = (int) ($existants[0] ?? 0);
    $empreinte = md5(wp_json_encode($p) . 'v3');
    if ($id && get_post_meta($id, '_us_empreinte', true) === $empreinte) {
        return $id;
    }
    $variable = count($p['variations']) > 0;
    $produit = $id ? ($variable ? new \WC_Product_Variable($id) : new \WC_Product_Simple($id)) : ($variable ? new \WC_Product_Variable() : new \WC_Product_Simple());
    if ($id) {
        wp_set_object_terms($id, $variable ? 'variable' : 'simple', 'product_type');
    }

    $produit->set_name($p['nom']);
    $produit->set_slug($p['slug']);
    $produit->set_status($p['publie'] ? 'publish' : 'draft');
    $produit->set_catalog_visibility('visible');
    $produit->set_short_description($p['accroche']);
    $produit->set_description($p['description']);
    $produit->set_reviews_allowed(true);
    // Date d'arrivée : l'ordre de vitrine ($p['rang'], 0 = la plus récente), d'où l'ordre du menu et de l'accueil.
    // Toujours dans le passé : une date à venir ferait passer la fiche en « programmée ».
    $produit->set_date_created(min(time(), strtotime('2026-11-26 12:00:00 UTC')) - 60 * (int) ($p['rang'] ?? 0));
    if (! $id && $p['sku'] && ! wc_get_product_id_by_sku($p['sku'])) {
        $produit->set_sku($p['sku']);
    }

    // Rayons, leurs univers, et la catégorie Nouveautés.
    $rayons = [];
    foreach ([...$p['rayons'], 'nouveautes'] as $slug) {
        if ($t = import_rayon($slug)) {
            $rayons[] = $t;
            $rayons = array_merge($rayons, get_ancestors($t, 'product_cat'));
        }
    }
    $produit->set_category_ids(array_values(array_unique($rayons)));

    // Attributs affichés (pour qui, gamme), puis l'option d'achat.
    $attributs = [];
    $position = 0;
    foreach (['genre' => [$p['genre']], 'famille-olfactive' => $p['familles'] ?? [], 'gamme' => [$p['gamme']]] as $slug => $valeurs) {
        $valeurs = array_filter($valeurs);
        if (! $valeurs || ! wc_attribute_taxonomy_id_by_name($slug)) {
            continue;
        }
        $a = new \WC_Product_Attribute();
        $a->set_id(wc_attribute_taxonomy_id_by_name($slug));
        $a->set_name(wc_attribute_taxonomy_name($slug));
        $a->set_options(array_map(fn ($valeur) => import_valeur(wc_attribute_taxonomy_name($slug), $valeur), $valeurs));
        $a->set_position($position++);
        $a->set_visible(true);
        $a->set_variation(false);
        $attributs[$a->get_name()] = $a;
    }
    $taxonomieOption = '';
    if ($variable) {
        [$slugOption, $libelleOption] = IMPORT_OPTIONS[$p['option']] ?? IMPORT_OPTIONS['Format'];
        $taxonomieOption = import_attribut($slugOption, $libelleOption);
        // « order » : rang de la teinte dans la palette (ordre de la page produit, par code), lu par WooCommerce.
        $valeurs = array_map(
            fn ($v, $rang) => import_valeur($taxonomieOption, $v['format'], ['us_couleur' => $v['couleur'] ?? '', 'us_code' => $v['code'] ?? '', 'order' => $slugOption === 'format' ? '' : $rang]),
            $p['variations'], array_keys($p['variations'])
        );
        $a = new \WC_Product_Attribute();
        $a->set_id(wc_attribute_taxonomy_id_by_name($slugOption));
        $a->set_name($taxonomieOption);
        $a->set_options($valeurs);
        $a->set_position($position);
        $a->set_visible(false);
        $a->set_variation(true);
        $attributs[$taxonomieOption] = $a;
    } else {
        $produit->set_regular_price($p['prix']);
        $produit->set_stock_status('instock');
    }
    $produit->set_attributes($attributs);

    // Photos : la première sert de visuel principal.
    $alt = trim($p['nom'] . ($p['sous_titre'] ? ' – ' . $p['sous_titre'] : '')) . ' | Univers Sensoriel';
    $photos = array_values(array_filter(array_map(fn ($i) => import_media($dossier, $i['fichier'], $alt), $p['images'])));
    $produit->set_image_id($photos[0] ?? 0);
    $produit->set_gallery_image_ids(array_slice($photos, 1));

    $id = $produit->save();
    update_post_meta($id, '_us_source', $p['source']);
    update_post_meta($id, '_us_sous_titre', $p['sous_titre']);
    update_post_meta($id, '_us_reference', $p['reference']);
    update_post_meta($id, '_us_notes', $p['notes']);
    update_post_meta($id, '_us_famille', '');

    if ($variable) {
        $connues = [];
        foreach ($produit->get_children() as $enfant) {
            $v = wc_get_product($enfant);
            $connues[(string) $v->get_meta('_us_format')] = $v;
        }
        foreach ($p['variations'] as $rang => $v) {
            $variation = $connues[$v['format']] ?? new \WC_Product_Variation();
            unset($connues[$v['format']]);
            $variation->set_parent_id($id);
            $variation->set_attributes([$taxonomieOption => sanitize_title($v['format'])]);
            $variation->set_regular_price($v['prix']);
            $variation->set_stock_status('instock');
            $variation->set_menu_order($rang);
            if (! empty($v['image']) && ($image = import_media($dossier, $v['image'], $p['nom'] . ' – ' . $v['format'] . ' | Univers Sensoriel'))) {
                $variation->set_image_id($image);
            }
            $variation->update_meta_data('_us_format', $v['format']);
            $variation->save();
        }
        foreach ($connues as $reste) {
            $reste->delete(true);
        }
        $produit = wc_get_product($id);
        $produit->set_default_attributes([$taxonomieOption => sanitize_title($p['variations'][0]['format'])]);
        $produit->save();
        \WC_Product_Variable::sync($id);
    }
    update_post_meta($id, '_us_empreinte', $empreinte);
    clean_post_cache($id);

    return $id;
}

/**
 * Importe les fiches de $donnees['produits'] (photos dans $dossier), puis range dans Nouveautés les fiches déjà en
 * vente ($donnees['deja_en_ligne'], par slug). $secondes > 0 : plus de nouvelle fiche commencée après ce délai
 * (un serveur mutualisé coupe les pages trop longues ; la suite passe au chargement suivant).
 * Renvoie le nombre de fiches qui restent à traiter.
 */
function importer_produits(array $donnees, string $dossier, int $secondes = 0): int
{
    if (! function_exists('wc_get_product')) {
        return -1;
    }
    $debut = microtime(true);
    $restantes = 0;
    foreach ($donnees['produits'] ?? [] as $p) {
        $existants = get_posts(['post_type' => 'product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_us_source', 'meta_value' => $p['source']]);
        $aJour = $existants && get_post_meta($existants[0], '_us_empreinte', true) === md5(wp_json_encode($p) . 'v3');
        if ($aJour) {
            continue;
        }
        if ($secondes > 0 && microtime(true) - $debut > $secondes) {
            $restantes++;
            continue;
        }
        importer_fiche($p, $dossier);
    }
    if ($restantes === 0 && ($nouveautes = import_rayon('nouveautes'))) {
        foreach ($donnees['deja_en_ligne'] ?? [] as $slug) {
            if ($page = get_page_by_path($slug, OBJECT, 'product')) {
                wp_set_object_terms($page->ID, [$nouveautes], 'product_cat', true);
            }
        }
        delete_transient('us_inspirations');
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }
    }

    return $restantes;
}
