<?php

/**
 * Fonctions communes aux scripts de contenu (seed.php, seo.php) : journal, import de média, pages, termes.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function us_log(string $message): void
{
    WP_CLI::log('  ' . $message);
}

/** Importe un fichier local dans la médiathèque (une seule fois, repéré par son chemin relatif). */
function us_media(string $path, string $alt = '', string $cle = ''): int
{
    if (! is_file($path)) {
        WP_CLI::warning("Média introuvable : $path");

        return 0;
    }
    $cle = ($cle ?: basename($path)) . ':' . filesize($path);
    $existing = get_posts([
        'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
        'meta_key' => '_us_source', 'meta_value' => $cle,
    ]);
    if ($existing) {
        return (int) $existing[0];
    }
    // Ancienne clé (sans la taille) : gardée si le fichier n'a pas changé.
    $anciens = get_posts([
        'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
        'meta_key' => '_us_source', 'meta_value' => substr($cle, 0, (int) strrpos($cle, ':')),
    ]);
    if ($anciens && is_file((string) get_attached_file($anciens[0])) && filesize(get_attached_file($anciens[0])) === filesize($path)) {
        update_post_meta($anciens[0], '_us_source', $cle);

        return (int) $anciens[0];
    }
    $name = basename($path);
    $tmp = wp_tempnam($name);
    copy($path, $tmp);
    $id = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], 0, $alt);
    if (is_wp_error($id)) {
        WP_CLI::warning("$name : " . $id->get_error_message());

        return 0;
    }
    update_post_meta($id, '_us_source', $cle);
    if ($alt) {
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
    }

    return (int) $id;
}

function us_page(string $slug, string $title, string $content = '', int $parent = 0): int
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        if ((getenv('US_SEED_FORCE') || getenv('US_SEED_PAGES')) && $content !== '' && $page->post_content !== $content) {
            wp_update_post(['ID' => $page->ID, 'post_content' => $content, 'post_title' => $title]);
            us_log("page mise à jour : $title");
        }

        return (int) $page->ID;
    }
    $id = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug,
        'post_title' => $title, 'post_content' => $content, 'post_parent' => $parent,
    ]);
    us_log("page créée : $title");

    return (int) $id;
}

/** Terme (créé si besoin) : renvoie son identifiant. */
function us_terme(string $nom, string $taxonomie, array $args = []): int
{
    $slug = $args['slug'] ?? sanitize_title($nom);
    $terme = get_term_by('slug', $slug, $taxonomie);
    if ($terme) {
        if (! empty($args['description']) && $terme->description !== $args['description']) {
            wp_update_term($terme->term_id, $taxonomie, ['description' => $args['description']]);
        }

        return (int) $terme->term_id;
    }
    $r = wp_insert_term($nom, $taxonomie, $args + ['slug' => $slug]);
    if (is_wp_error($r)) {
        WP_CLI::warning("$taxonomie / $nom : " . $r->get_error_message());

        return 0;
    }

    return (int) $r['term_id'];
}
