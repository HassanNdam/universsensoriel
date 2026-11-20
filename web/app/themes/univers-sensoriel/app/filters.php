<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * WordPress enregistre « Corps &amp; bain » : hors admin, le thème reçoit le nom décodé
 * et Blade l'échappe lui-même ({{ }}), sans double encodage.
 */
if (! is_admin()) {
    $decoder = function ($terme) {
        if ($terme instanceof \WP_Term) {
            $terme->name = wp_specialchars_decode($terme->name, ENT_QUOTES);
        }

        return $terme;
    };
    add_filter('get_term', $decoder);
    add_filter('get_terms', fn ($termes) => is_array($termes) ? array_map($decoder, $termes) : $termes);
}

/** Pages renommées (ancien chemin => nouveau) : l'ancienne adresse renvoie vers la nouvelle (301). */
const PAGES_RENOMMEES = ['nos-boutiques' => 'notre-boutique'];

add_action('template_redirect', function () {
    if (! is_404()) {
        return;
    }
    $ancien = basename(trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
    if (isset(PAGES_RENOMMEES[$ancien]) && ($page = get_page_by_path(PAGES_RENOMMEES[$ancien]))) {
        wp_safe_redirect(get_permalink($page), 301);
        exit;
    }
});
