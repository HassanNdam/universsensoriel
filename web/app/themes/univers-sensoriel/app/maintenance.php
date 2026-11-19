<?php

/**
 * Page d'attente « bientôt en ligne ». Tant que le site n'est pas en production, les visiteurs non connectés
 * ne voient que resources/views/maintenance.blade.php ; une personne connectée navigue normalement.
 *
 * Interrupteur : constante US_MAINTENANCE (config/application.php), vraie dès que WP_ENV n'est pas « production ».
 * Pour forcer : US_MAINTENANCE=true ou US_MAINTENANCE=false dans le .env.
 *
 * Titre, description et image de partage : MAINTENANCE_SEO ci-dessous.
 *
 * Restent ouverts : l'analyse Yoast de l'admin (jeton signé ?us-analyse=…), la connexion (/wp/wp-login.php), l'administration, l'API REST, admin-ajax (formulaires Forminator)
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

    // Lecture de la page par l'analyse Yoast (app/seo.php, scripts/seo-scores.js) : elle doit voir le vrai contenu.
    if (jeton_analyse_valide($_GET['us-analyse'] ?? '')) {
        return false;
    }

    return ! (is_robots() || is_favicon());
}

/**
 * Jeton de l'analyse Yoast : l'éditeur lit la page publiée sans cookie (en visiteur, comme Google), donc la page d'attente
 * lui répondrait. Le jeton, signé avec les clés du site et valable un jour, la laisse passer, elle seule.
 */
function jeton_analyse(): string
{
    $heure = (string) time();

    return $heure . '.' . substr(hash_hmac('sha256', 'us-analyse|' . $heure, wp_salt('auth')), 0, 24);
}

function jeton_analyse_valide(string $jeton): bool
{
    [$heure, $signature] = array_pad(explode('.', $jeton, 2), 2, '');

    return ctype_digit($heure) && abs(time() - (int) $heure) < DAY_IN_SECONDS
        && hash_equals(substr(hash_hmac('sha256', 'us-analyse|' . $heure, wp_salt('auth')), 0, 24), $signature);
}

/**
 * Titre, description et aperçu de partage de la page d'attente (Yoast) : les mêmes pour toutes les adresses,
 * et non ceux de la page d'accueil. Requête cible « Univers Sensoriel », en tête du titre et de la description.
 * Image de partage : resources/images/partage-bientot-en-ligne.jpg (1200 × 630, source dans docs/03-images-site/partage).
 */
const MAINTENANCE_SEO = [
    'titre' => 'Univers Sensoriel : la boutique en ligne ouvre bientôt',
    'description' => 'Univers Sensoriel ouvre bientôt sa boutique en ligne : extraits de parfum, soins et senteurs pour la maison. Boutiques ouvertes à Metz et Yaoundé.',
    'image' => 'partage-bientot-en-ligne.jpg',
    'alt' => 'Univers Sensoriel : la boutique en ligne ouvre bientôt, extraits de parfum devant un bol d’encens',
];

function maintenance_referencement(): void
{
    $seo = MAINTENANCE_SEO;
    $image = image($seo['image']);
    $valeurs = [
        'pre_get_document_title' => $seo['titre'],
        'wpseo_title' => $seo['titre'],
        'wpseo_metadesc' => $seo['description'],
        'wpseo_opengraph_title' => $seo['titre'],
        'wpseo_opengraph_desc' => $seo['description'],
        'wpseo_opengraph_url' => home_url('/'),
        'wpseo_opengraph_type' => 'website',
        'wpseo_opengraph_image' => $image,
        'wpseo_opengraph_image_width' => 1200,
        'wpseo_opengraph_image_height' => 630,
        'wpseo_opengraph_image_type' => 'image/jpeg',
        'wpseo_twitter_title' => $seo['titre'],
        'wpseo_twitter_description' => $seo['description'],
        'wpseo_twitter_image' => $image,
        // Pas encore ouvert : rien à indexer (la réponse 503 suffit déjà aux moteurs).
        'wpseo_robots' => 'noindex, follow',
    ];
    foreach ($valeurs as $filtre => $valeur) {
        add_filter($filtre, fn () => $valeur, 99);
    }
    add_filter('wpseo_canonical', '__return_false', 99);
    add_filter('wpseo_json_ld_output', '__return_false', 99);
    add_action('wpseo_head', function () use ($seo) {
        printf("\t<meta property=\"og:image:alt\" content=\"%s\" />\n\t<meta name=\"twitter:image:alt\" content=\"%1\$s\" />\n", esc_attr($seo['alt']));
    }, 99);
}

/** Robot qui fabrique l'aperçu d'un lien partagé (pas un moteur de recherche). */
function robot_de_partage(): bool
{
    return (bool) preg_match(
        '/facebookexternalhit|facebookcatalog|Facebot|Twitterbot|LinkedInBot|WhatsApp|Slackbot|TelegramBot|Discordbot|Pinterest|SkypeUriPreview|redditbot|vkShare|Embedly/i',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    );
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
        maintenance_referencement();

        // 503 + Retry-After : les moteurs comprennent que c'est provisoire et ne retiennent pas cette page.
        // Les robots d'aperçu des réseaux (Facebook, WhatsApp, LinkedIn…) reçoivent 200 : sinon le lien partagé reste sans aperçu.
        if (! robot_de_partage()) {
            status_header(503);
            header('Retry-After: 3600');
        }
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
