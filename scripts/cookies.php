<?php

/**
 * Bandeau de cookies Complianz : réponses de l'assistant, pages légales reliées, textes et couleurs de la charte.
 *
 * Tout est décrit ici puis écrit dans Complianz ; une empreinte évite de réécrire une configuration inchangée.
 * US_SEED_FORCE=1 réécrit tout. La typographie et les boutons du bandeau sont dans le thème (resources/css/cookies.css).
 *
 * Mesure d'audience : renseigner GOOGLE_ANALYTICS_ID='G-XXXXXXXXXX' dans le .env. Complianz charge alors
 * Google Analytics, seulement après accord. Sans identifiant, rien n'est chargé.
 *
 * Lancer seul : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/cookies.php --user=admin
 */
if (! function_exists('cmplz_update_option')) {
    WP_CLI::warning('Complianz est inactif : bandeau de cookies non configuré.');

    return;
}

$coordonnees = function_exists('App\\coordonnees') ? App\coordonnees() : [];
$analytics = trim((string) (function_exists('Env\\env') ? Env\env('GOOGLE_ANALYTICS_ID') : getenv('GOOGLE_ANALYTICS_ID')));
$idPage = fn(string $slug) => (int) (get_page_by_path($slug, OBJECT, 'page')->ID ?? 0);

// Pages reliées au bandeau : la politique de cookies est écrite par Complianz, les deux autres sont celles du site.
$pageCookies = $idPage('politique-de-cookies');
if (! $pageCookies) {
    $pageCookies = (int) wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'politique-de-cookies',
        'post_title' => 'Politique de cookies',
        'post_content' => '<!-- wp:complianz/document {"title":"Politique de cookies","selectedDocument":"cookie-statement"} /-->',
    ]);
    WP_CLI::log('  page créée : Politique de cookies');
}
$pages = [
    'privacy-statement' => $idPage('politique-de-confidentialite'),
    'impressum' => $idPage('mentions-legales'),
];

/*
|--------------------------------------------------------------------------
| Réponses de l'assistant
|--------------------------------------------------------------------------
*/
$reponses = [
    'regions' => 'eu',
    'eu_consent_regions' => 'no',
    'wp_admin_access_users' => 'no',
    'cookie-statement' => 'generated',
    'privacy-statement' => $pages['privacy-statement'] ? 'custom' : 'none',
    'impressum' => $pages['impressum'] ? 'custom' : 'none',
    'disclaimer' => 'none',
    'organisation_name' => 'Univers Sensoriel by K.Elle',
    'address_company' => 'Adresse du siège : à compléter',
    'country_company' => 'FR',
    'email_company' => $coordonnees['courriel'] ?? get_option('admin_email'),
    'telephone_company' => $coordonnees['telephone'] ?? '',
    'purpose_personaldata' => ['contact', 'payments', 'register-account', 'newsletters', 'deliveries'],
    'records_of_consent' => 'no',
    'datarequest' => 'no',
    'respect_dnt' => 'no',
    // Mesure d'audience : toujours soumise à l'accord, même anonymisée.
    'compile_statistics' => $analytics ? 'google-analytics' : 'yes',
    'consent_for_anonymous_stats' => 'yes',
    'configuration_by_complianz' => 'yes',
    'ua_code' => $analytics,
    'uses_thirdparty_services' => 'no',
    'uses_social_media' => 'no',
    'uses_firstparty_marketing_cookies' => 'no',
    'uses_ad_cookies' => 'no',
    'uses_wordpress_comments' => 'no',
    'send_notifications_email' => false,
    'use_document_css' => false, // la politique de cookies prend la typographie du thème
    'enable_cookie_banner' => 'yes',
    'enable_cookie_blocker' => 'yes',
];

/*
|--------------------------------------------------------------------------
| Bandeau : textes courts, couleurs de la charte
|--------------------------------------------------------------------------
*/
$sapin = '#1f3124';
$ivoire = '#f7f0e4';
$blanc = '#fffdf9';
$lin = '#eadfcd';
$bronze = '#7b4c0f';
$mousse = '#4f5a4c';
$texte = fn(string $t, bool $visible = true) => ['text' => $t, 'show' => $visible];
$angles = ['top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'type' => 'px'];

$bandeau = [
    'title' => 'Univers Sensoriel',
    'position' => 'bottom-left',
    'animation' => 'fade',
    'banner_width' => 460,
    'checkbox_style' => 'slider',
    'use_categories' => 'view-preferences',
    'manage_consent_options' => 'hide-everywhere', // le pied de page porte le lien « Gérer les cookies »
    'legal_documents' => 1,
    'use_logo' => 'hide',
    'close_button' => 0, // un choix clair : accepter, refuser ou personnaliser
    'use_box_shadow' => 1,
    'soft_cookiewall' => 0,
    'dismiss_on_scroll' => 0,
    'dismiss_on_timeout' => 0,
    'font_size' => 15,
    'colorpalette_border_radius' => $angles,
    'buttons_border_radius' => $angles,
    'border_width' => ['top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1'],
    'colorpalette_background' => ['color' => $blanc, 'border' => $lin],
    'colorpalette_text' => ['color' => $sapin, 'hyperlink' => $bronze],
    'colorpalette_toggles' => ['background' => $sapin, 'bullet' => $blanc, 'inactive' => $mousse],
    'colorpalette_button_accept' => ['background' => $sapin, 'border' => $sapin, 'text' => $ivoire],
    'colorpalette_button_deny' => ['background' => $blanc, 'border' => $sapin, 'text' => $sapin],
    'colorpalette_button_settings' => ['background' => $blanc, 'border' => $lin, 'text' => $sapin],
    'header' => $texte('Vos cookies'),
    'message_optin' => 'Les cookies du panier et du compte sont toujours actifs. Avec votre accord, nous mesurons aussi la fréquentation du site.',
    'message_optout' => 'Les cookies du panier et du compte sont toujours actifs. Avec votre accord, nous mesurons aussi la fréquentation du site.',
    'accept' => 'Accepter',
    'accept_informational' => $texte('Accepter'),
    'dismiss' => $texte('Refuser'),
    'view_preferences' => 'Personnaliser',
    'save_preferences' => 'Enregistrer',
    'revoke' => 'Gérer les cookies',
    'category_functional' => 'Nécessaires',
    'functional_text' => $texte('Panier, compte client, paiement et mémorisation de votre choix. Sans eux, la boutique ne fonctionne pas.'),
    'category_prefs' => $texte('Préférences', false),
    'preferences_text' => $texte('Mémorisent vos réglages d\'affichage.', false),
    'category_stats' => $texte('Mesure d\'audience'),
    'statistics_text' => $texte('Comptent les visites et les pages vues, pour améliorer la boutique.'),
    'statistics_text_anonymous' => $texte('Comptent les visites et les pages vues, sans vous identifier.'),
    'category_all' => $texte('Publicité'),
    'marketing_text' => $texte('Servent à vous montrer nos offres sur d\'autres sites et sur les réseaux sociaux.'),
];

/*
|--------------------------------------------------------------------------
| Écriture
|--------------------------------------------------------------------------
*/
$empreinte = md5(wp_json_encode([$reponses, $bandeau, $pages, $pageCookies]));
$banniereActuelle = (int) cmplz_get_default_banner_id();
if (! getenv('US_SEED_FORCE') && $banniereActuelle && get_option('us_cookies_empreinte') === $empreinte) {
    return;
}

foreach ($pages as $type => $id) {
    update_option("cmplz_{$type}_custom_page", $id);
}
foreach ($reponses as $cle => $valeur) {
    if (cmplz_get_option($cle, false) !== $valeur) {
        cmplz_update_option($cle, $valeur);
    }
}
update_option('cmplz_wizard_completed_once', true);
update_option('cmplz_documents_update_date', time());

// Un seul bandeau : celui par défaut, créé s'il manque.
$banniere = new CMPLZ_COOKIEBANNER($banniereActuelle ?: false);
foreach ($bandeau as $cle => $valeur) {
    $banniere->{$cle} = $valeur;
}
$banniere->default = true;
$banniere->disable_cookiebanner = false;
$banniere->save();

// Les liens du bandeau et de la politique sont mis en cache par Complianz.
foreach (['cookie-statement', 'privacy-statement', 'impressum'] as $type) {
    cmplz_delete_transient("cmplz_shortcode_{$type}-eu");
}
update_option('us_cookies_empreinte', $empreinte, false);
WP_CLI::log('  bandeau de cookies à jour (' . ($analytics ? "Google Analytics $analytics après accord" : 'aucun outil de mesure branché') . ')');
