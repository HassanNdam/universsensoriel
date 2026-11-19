<?php

/**
 * Page d'attente « bientôt en ligne ». Tant que le site n'est pas en production, les visiteurs non connectés
 * ne voient que resources/views/maintenance.blade.php ; une personne connectée navigue normalement.
 *
 * Interrupteur : constante US_MAINTENANCE (config/application.php), vraie dès que WP_ENV n'est pas « production ».
 * Pour forcer : US_MAINTENANCE=true ou US_MAINTENANCE=false dans le .env.
 *
 * Restent ouverts : la connexion (/wp/wp-login.php), l'administration, l'API REST, admin-ajax (formulaires Forminator)
 * et les retours de paiement (wc-api, wc-ajax). Les pages légales liées depuis la page d'attente restent lisibles,
 * dans le même habillage : ni en-tête, ni menu, ni pied de page du site.
 */

namespace App;

/** Pages lisibles pendant l'attente (liées depuis la page d'attente et le bandeau de cookies). */
const MAINTENANCE_PAGES_OUVERTES = ['mentions-legales', 'politique-de-confidentialite', 'politique-de-cookies'];

function maintenance_active(): bool
{
    return defined('US_MAINTENANCE') ? (bool) US_MAINTENANCE : (defined('WP_ENV') && WP_ENV !== 'production');
}

/** Vrai si la requête en cours doit recevoir la page d'attente. */
function maintenance_affichee(): bool
{
    if (! maintenance_active()) {
        return false;
    }
    // Aperçu pour une personne connectée : ajouter ?maintenance à n'importe quelle adresse.
    if (is_user_logged_in()) {
        return isset($_GET['maintenance']);
    }

    return ! (is_robots() || is_favicon());
}

// Priorité 1 : après les appels wc-ajax de WooCommerce (priorité 0), avant tout le reste.
add_action('template_redirect', function () {
    if (! maintenance_affichee()) {
        return;
    }

    $legale = is_page(MAINTENANCE_PAGES_OUVERTES);
    add_filter('body_class', fn () => ['attente-page', $legale ? 'attente-page-legale' : 'attente-page-accueil']);
    nocache_headers();

    // Page légale : son texte seul (réponse 200, titre et description d'origine).
    if (! $legale) {
        $titre = fn () => 'Bientôt en ligne - ' . get_bloginfo('name');
        add_filter('pre_get_document_title', $titre, 99);
        add_filter('wpseo_title', $titre, 99);
        add_filter('wpseo_robots', fn () => 'noindex, nofollow', 99);
        add_filter('wpseo_metadesc', '__return_empty_string', 99);
        add_filter('wpseo_canonical', '__return_false', 99);
        add_filter('wpseo_json_ld_output', '__return_false', 99);

        // 503 + Retry-After : les moteurs comprennent que c'est provisoire et ne retiennent pas cette page.
        status_header(503);
        header('Retry-After: 3600');
    }

    echo \Roots\view('maintenance', ['legale' => $legale])->render();
    exit;
}, 1);

// Rappel dans la barre d'administration : le site est caché du public, avec un lien d'aperçu.
add_action('admin_bar_menu', function (\WP_Admin_Bar $barre) {
    if (! maintenance_active() || ! current_user_can('edit_pages')) {
        return;
    }
    $barre->add_node([
        'id' => 'us-maintenance',
        'title' => 'Site caché : page d’attente active',
        'href' => add_query_arg('maintenance', '1', home_url('/')),
        'meta' => ['title' => 'Voir la page que reçoivent les visiteurs non connectés'],
    ]);
}, 90);
