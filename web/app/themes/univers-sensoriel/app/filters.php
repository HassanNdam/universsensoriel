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
