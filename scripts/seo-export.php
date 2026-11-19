<?php

/**
 * Relevé des notes Yoast, étape 1 : liste de tout ce qui est à noter (pages, articles, rayons, fiches produit)
 * avec le titre SEO, la méta-description et la requête cible enregistrés dans Yoast.
 *
 *   php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/seo-export.php
 *
 * Écrit web/app/uploads/seo-export.json, lu par scripts/seo-scores.js dans l'éditeur (étape 2).
 * Le fichier est à supprimer une fois les notes relevées.
 */

if (! defined('WPSEO_VERSION')) {
    exit("Yoast SEO inactif.\n");
}

$seo = json_decode(file_get_contents(__DIR__ . '/seo.json'), true);
$fiches = json_decode(file_get_contents(__DIR__ . '/seo-fiches.json'), true);
$liste = [];

$article = function (string $groupe, string $cle, int $id) use (&$liste): void {
    $motcle = (string) get_post_meta($id, '_yoast_wpseo_focuskw', true);
    if ($motcle === '') {
        return;
    }
    $liste[] = [
        'groupe' => $groupe, 'cle' => $cle, 'type' => get_post_type($id), 'url' => get_permalink($id),
        'titre' => get_post_meta($id, '_yoast_wpseo_title', true) ?: get_the_title($id) . ' | Univers Sensoriel',
        'description' => (string) get_post_meta($id, '_yoast_wpseo_metadesc', true),
        'motcle' => $motcle, 'slug' => get_post_field('post_name', $id), 'nom' => get_the_title($id),
    ];
};

foreach (['pages' => 'page', 'articles' => 'post'] as $groupe => $type) {
    foreach ($seo[$groupe] ?? [] as $slug => $reglage) {
        if (empty($reglage['noindex']) && ($p = get_page_by_path($slug, OBJECT, $type))) {
            $article($groupe, $slug, $p->ID);
        }
    }
}
foreach ($seo['rayons'] as $slug => $reglage) {
    $terme = get_term_by('slug', $slug, 'product_cat');
    if (! $terme || ! empty($reglage['noindex']) || empty($reglage['motcle'])) {
        continue;
    }
    $liste[] = [
        'groupe' => 'rayons', 'cle' => $slug, 'type' => 'term', 'url' => get_term_link($terme),
        'titre' => $reglage['titre'] ?? $terme->name . ' | Univers Sensoriel', 'description' => $reglage['description'] ?? '',
        'motcle' => $reglage['motcle'], 'slug' => $slug, 'nom' => $terme->name,
    ];
}
foreach (array_keys($fiches) as $sku) {
    if (($id = (int) wc_get_product_id_by_sku($sku)) && get_post_status($id) === 'publish') {
        $article('fiches', $sku, $id);
    }
}

$fichier = wp_upload_dir()['basedir'] . '/seo-export.json';
file_put_contents($fichier, wp_json_encode($liste, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
WP_CLI::success(count($liste) . ' éléments à noter : ' . $fichier);
