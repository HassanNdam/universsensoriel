<?php

/**
 * Journal (blog) et avis clients, appelé par scripts/seed.php avant le référencement.
 *
 * - articles : scripts/blog/articles.json + <slug>.html, images dans docs/03-images-site/journal ;
 * - avis : scripts/avis.json, importés comme avis WooCommerce (commentaires), reproduits tels quels.
 *
 * Un article ou un avis retouché dans l'admin n'est pas réécrit, sauf avec US_SEED_FORCE=1.
 */

WP_CLI::log('Journal');

$blog = json_decode(file_get_contents(__DIR__ . '/blog/articles.json'), true);
$imagesJournal = dirname(__DIR__) . '/docs/03-images-site/journal';
$forcer = (bool) getenv('US_SEED_FORCE');

$pageJournal = us_page('journal', 'Le journal');
update_option('page_for_posts', $pageJournal);
update_option('posts_per_page', 9);
update_option('category_base', 'journal');

$rubriques = [];
foreach ($blog['rubriques'] as $slug => $nom) {
    $rubriques[$slug] = us_terme($nom, 'category', ['slug' => $slug]);
}
$auteur = (int) (get_user_by('login', 'admin')->ID ?? 1);
$crees = $maj = 0;
foreach ($blog['articles'] as $article) {
    $contenu = trim((string) @file_get_contents(__DIR__ . "/blog/{$article['slug']}.html"));
    if ($contenu === '') {
        WP_CLI::warning("Article sans texte : {$article['slug']}");
        continue;
    }
    $empreinte = md5(wp_json_encode($article) . $contenu);
    $existant = get_page_by_path($article['slug'], OBJECT, 'post');
    if ($existant && ! $forcer && get_post_meta($existant->ID, '_us_empreinte', true) === $empreinte) {
        continue;
    }
    // Retouché dans l'admin depuis le dernier seed : on n'y touche pas.
    if ($existant && ! $forcer && get_post_meta($existant->ID, '_us_contenu_seed', true) !== md5($existant->post_content)) {
        us_log("article modifié dans l'admin, conservé : {$article['titre']}");
        continue;
    }
    $donnees = [
        'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $article['slug'], 'post_author' => $auteur,
        'post_title' => $article['titre'], 'post_content' => $contenu, 'post_excerpt' => $article['extrait'],
        'post_date' => $article['date'] . ' 09:00:00', 'post_date_gmt' => get_gmt_from_date($article['date'] . ' 09:00:00'),
        'post_category' => [$rubriques[$article['rubrique']]], 'comment_status' => 'closed', 'ping_status' => 'closed',
    ];
    $id = $existant ? wp_update_post(['ID' => $existant->ID] + $donnees) : wp_insert_post($donnees);
    if ($image = us_media("$imagesJournal/{$article['image']}", $article['alt'], 'journal/' . $article['image'])) {
        set_post_thumbnail($id, $image);
    }
    update_post_meta($id, '_us_empreinte', $empreinte);
    update_post_meta($id, '_us_contenu_seed', md5(get_post_field('post_content', $id)));
    update_post_meta($id, '_us_ancienne_adresse', $article['ancien']);
    $existant ? $maj++ : $crees++;
}
us_log("$crees articles créés, $maj mis à jour");

/*
|--------------------------------------------------------------------------
| Avis clients
|--------------------------------------------------------------------------
| Les avis de produits sont de vrais avis WooCommerce : ils apparaissent dans Produits › Avis.
| L'avis sur la boutique est rattaché à la page « Avis clients ».
*/
WP_CLI::log('Avis clients');

foreach ([
    'woocommerce_enable_reviews' => 'yes',
    'woocommerce_enable_review_rating' => 'yes',
    'woocommerce_review_rating_required' => 'yes',
    'woocommerce_review_rating_verification_label' => 'yes',
    'woocommerce_review_rating_verification_required' => 'yes', // seuls les acheteurs peuvent donner un avis
    'comment_moderation' => '1', // chaque nouvel avis est relu avant d'être publié
    'comment_registration' => '1',
] as $cle => $valeur) {
    update_option($cle, $valeur);
}
global $wpdb;
$wpdb->query("UPDATE {$wpdb->posts} SET comment_status = 'open' WHERE post_type = 'product'");

$pageAvis = us_page('avis-clients', 'Avis clients', is_file(__DIR__ . '/pages/avis-clients.html') ? trim(file_get_contents(__DIR__ . '/pages/avis-clients.html')) : '');
$avis = json_decode(file_get_contents(__DIR__ . '/avis.json'), true)['avis'];
$importes = 0;
$produitsTouches = [];
foreach ($avis as $a) {
    if (get_comments(['meta_key' => '_us_avis', 'meta_value' => $a['id'], 'count' => true, 'status' => 'all', 'type' => 'review'])) {
        continue;
    }
    $cible = $pageAvis;
    if ($a['produit'] !== '') {
        $fiches = get_posts(['post_type' => 'product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_us_source', 'meta_value' => $a['produit']]);
        if (! $fiches) {
            WP_CLI::warning("Avis {$a['id']} : fiche « {$a['produit']} » introuvable");
            continue;
        }
        $cible = $fiches[0];
        $produitsTouches[] = $cible;
    }
    $id = wp_insert_comment([
        'comment_post_ID' => $cible, 'comment_author' => $a['auteur'], 'comment_author_email' => '', 'comment_content' => $a['texte'],
        'comment_type' => 'review', 'comment_approved' => 1,
        'comment_date' => $a['date'] . ' 12:00:00', 'comment_date_gmt' => get_gmt_from_date($a['date'] . ' 12:00:00'),
    ]);
    update_comment_meta($id, '_us_avis', $a['id']);
    update_comment_meta($id, 'rating', (int) $a['note']);
    update_comment_meta($id, 'verified', $a['verifie'] ? 1 : 0);
    update_comment_meta($id, '_us_extrait', $a['extrait'] ? 1 : 0);
    update_comment_meta($id, '_us_accueil', $a['accueil'] ? 1 : 0);
    update_comment_meta($id, '_us_origine', 'ancienne-boutique');
    $importes++;
}
foreach (array_unique($produitsTouches) as $produit) {
    WC_Comments::clear_transients($produit);
}
us_log("$importes avis importés, " . (count($avis) - $importes) . ' déjà en place');
