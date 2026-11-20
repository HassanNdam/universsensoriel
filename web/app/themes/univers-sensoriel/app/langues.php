<?php

/**
 * Langues du site.
 *
 * - Français (par défaut) et anglais sous /en/ : TranslatePress gratuit. Vraies pages indexées par Google,
 *   traductions saisies dans l'éditeur visuel (« Traduire le site » dans la barre d'administration),
 *   alimentées au départ par scripts/traductions/en_GB.json (scripts/langues.php).
 * - Autres langues (LANGUES_AUTO) : traduction automatique de Google dans le navigateur du visiteur, chargée
 *   seulement quand il choisit l'une d'elles. Rien à saisir, rien d'indexé. Une langue ajoutée plus tard dans
 *   TranslatePress (extension « Multiple Languages ») quitte d'elle-même cette liste et devient une vraie langue.
 *
 * La version gratuite de TranslatePress ne traduit ni le texte alternatif des images, ni le titre de l'onglet,
 * ni la description pour les moteurs de recherche : on les traduit ici avec le même dictionnaire.
 */

namespace App;

/** Langues proposées en traduction automatique (code ISO => nom dans la langue). */
const LANGUES_AUTO = [
    'nl' => 'Nederlands',
    'de' => 'Deutsch',
    'es' => 'Español',
    'it' => 'Italiano',
    'pt' => 'Português',
];

/** Noms affichés dans le sélecteur, dans leur propre langue. */
const NOMS_LANGUES = ['fr' => 'Français', 'en' => 'English'] + LANGUES_AUTO;

/** Cookie du choix d'une langue automatique (lu aussi par resources/js/langues.js). */
const COOKIE_LANGUE = 'us_langue';

/** Langue affichée par TranslatePress (ex. en_GB), ou null sur la langue par défaut. */
function langue_traduite(): ?string
{
    $langue = $GLOBALS['TRP_LANGUAGE'] ?? null;
    if (! $langue || ! class_exists('TRP_Translate_Press')) {
        return null;
    }
    $reglages = get_option('trp_settings', []);

    return $langue === ($reglages['default-language'] ?? 'fr_FR') ? null : $langue;
}

/** Traduction d'un texte français depuis le dictionnaire TranslatePress (le texte d'origine s'il n'y en a pas). */
function traduire(string $texte): string
{
    $langue = langue_traduite();
    $texte = trim($texte);
    if (! $langue || $texte === '') {
        return $texte;
    }
    static $cache = [];
    if (! isset($cache[$langue][$texte])) {
        $query = \TRP_Translate_Press::get_trp_instance()->get_component('query');
        $formes = array_unique([$texte, esc_html($texte), htmlentities($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8')]);
        $trouves = $query->get_existing_translations($formes, $langue);
        $traduction = $texte;
        foreach ($formes as $forme) {
            if (! empty($trouves[$forme]->translated)) {
                $traduction = html_entity_decode($trouves[$forme]->translated, ENT_QUOTES, 'UTF-8');
                break;
            }
        }
        // La recherche en base ignore la casse : on garde la majuscule initiale du texte d'origine.
        $initiale = mb_substr($texte, 0, 1);
        if ($initiale !== mb_strtolower($initiale) && $initiale === mb_strtoupper($initiale)) {
            $traduction = mb_strtoupper(mb_substr($traduction, 0, 1)) . mb_substr($traduction, 1);
        }
        $cache[$langue][$texte] = $traduction;
    }

    return $cache[$langue][$texte];
}

/** Titre « Partie - Site » ou « Partie | Site » : chaque partie est traduite séparément. */
function traduire_titre(string $titre): string
{
    if (! langue_traduite()) {
        return $titre;
    }
    $morceaux = preg_split('/(\s+[|\-–·]\s+)/u', $titre, -1, PREG_SPLIT_DELIM_CAPTURE);

    return implode('', array_map(fn($m, $i) => $i % 2 ? $m : traduire($m), $morceaux, array_keys($morceaux)));
}

/** Description : le prix final « Dès 11,90 €. » change avec la devise, il est traduit à part. */
function traduire_description(string $texte): string
{
    if (! langue_traduite()) {
        return $texte;
    }
    if (preg_match('/^(.*?)\s*Dès ([\d  ,.]+\s*\S+)\.$/us', trim($texte), $m)) {
        return traduire($m[1]) . ' ' . traduire('Dès') . ' ' . $m[2] . '.';
    }

    return traduire($texte);
}

/** Panier et commande : blocs React de WooCommerce, que la traduction de Google casserait. */
function sans_traduction_auto(): bool
{
    return function_exists('is_cart') && (is_cart() || (is_checkout() && ! is_wc_endpoint_url()));
}

/**
 * Langues du sélecteur : celles de TranslatePress (vraies pages), puis les langues automatiques, qui partent de
 * la version par défaut de la page.
 *
 * @return array<int, array{code: string, nom: string, url: string, auto: bool, active: bool}>
 */
function langues(): array
{
    static $liste = null;
    if ($liste !== null) {
        return $liste;
    }
    $liste = [];
    $courante = substr($GLOBALS['TRP_LANGUAGE'] ?? get_locale(), 0, 2);
    $urlParDefaut = home_url(add_query_arg([]));

    if (function_exists('trp_custom_language_switcher')) {
        $defaut = (get_option('trp_settings', [])['default-language'] ?? 'fr_FR');
        foreach (trp_custom_language_switcher() as $locale => $langue) {
            $code = substr($locale, 0, 2);
            if ($locale === $defaut) {
                $urlParDefaut = $langue['current_page_url'];
            }
            $liste[] = [
                'code' => $code,
                'nom' => NOMS_LANGUES[$code] ?? $langue['language_name'],
                'url' => $langue['current_page_url'],
                'auto' => false,
                'active' => $code === $courante,
            ];
        }
    }
    if (! $liste) {
        $liste[] = ['code' => 'fr', 'nom' => 'Français', 'url' => $urlParDefaut, 'auto' => false, 'active' => true];
    }

    $reelles = array_column($liste, 'code');
    foreach (apply_filters('us_langues_auto', LANGUES_AUTO) as $code => $nom) {
        if (! in_array($code, $reelles, true)) {
            $liste[] = ['code' => $code, 'nom' => $nom, 'url' => $urlParDefaut, 'auto' => true, 'active' => false];
        }
    }

    return $liste;
}

/** Langue affichée côté serveur (la langue automatique éventuelle est posée par le navigateur). */
function langue_active(): array
{
    foreach (langues() as $langue) {
        if ($langue['active']) {
            return $langue;
        }
    }

    return langues()[0];
}

// Visiteur en langue automatique qui arrive au panier ou à la commande en français : version anglaise,
// plus lisible pour lui que le français, Google ne pouvant pas traduire ces deux pages.
add_action('template_redirect', function () {
    $choix = sanitize_key($_COOKIE[COOKIE_LANGUE] ?? '');
    if (! $choix || ! isset(LANGUES_AUTO[$choix]) || langue_traduite() || ! sans_traduction_auto() || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    foreach (langues() as $langue) {
        if ($langue['code'] === 'en' && ! $langue['auto']) {
            wp_safe_redirect($langue['url']);
            exit;
        }
    }
});

add_filter('body_class', function (array $classes) {
    if (sans_traduction_auto()) {
        $classes[] = 'sans-traduction-auto';
    }

    return $classes;
});

/*
|--------------------------------------------------------------------------
| Compléments à TranslatePress gratuit
|--------------------------------------------------------------------------
*/

// Textes alternatifs des images : collectés et traduits comme le reste de la page.
add_filter('trp_node_accessors', function (array $accessors) {
    $accessors['image_alt'] = ['selector' => 'img[alt]', 'accessor' => 'alt', 'attribute' => true];

    return $accessors;
});

// Titre de l'onglet, titres et descriptions de partage (Yoast SEO, puis WordPress à défaut).
// Après le thème (app/seo.php, priorité 20), qui calcule ceux des fiches sans réglage Yoast.
foreach (['wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title'] as $filtre) {
    add_filter($filtre, fn($titre) => traduire_titre((string) $titre), 99);
}
foreach (['wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description'] as $filtre) {
    add_filter($filtre, fn($texte) => traduire_description((string) $texte), 99);
}
add_filter('document_title_parts', fn($parts) => array_map(fn($p) => is_string($p) ? traduire($p) : $p, $parts));

// Libellés que le panier et la commande (blocs React) reçoivent par l'API de WooCommerce, hors de portée de
// TranslatePress gratuit : modes de livraison, moyen de paiement.
add_filter('woocommerce_shipping_rate_label', fn($libelle) => traduire((string) $libelle));
add_filter('woocommerce_gateway_title', fn($titre) => is_admin() ? $titre : traduire((string) $titre));
add_filter('woocommerce_gateway_description', fn($texte) => is_admin() ? $texte : traduire((string) $texte));

// Dates (« 9 novembre 2026 ») : TranslatePress change la langue des e-mails sans recharger les noms de mois de
// WordPress ; en anglais, la date est reformatée avec les noms anglais.
add_filter('wp_date', function ($date, $format, $timestamp, $fuseau) {
    $langue = langue_traduite();
    if (! $langue || ! str_starts_with($langue, 'en')) {
        return $date;
    }

    return (new \DateTimeImmutable('@' . $timestamp))->setTimezone($fuseau ?: wp_timezone())->format($format);
}, 10, 4);
add_filter('date_i18n', function ($date, $format, $timestamp, $gmt) {
    $langue = langue_traduite();
    if (! $langue || ! str_starts_with($langue, 'en') || preg_match('/(?<!\\\\)[eTOPpZ]/', $format)) {
        return $date;
    }

    return (new \DateTimeImmutable('@' . $timestamp))->format($format);
}, 10, 4);
