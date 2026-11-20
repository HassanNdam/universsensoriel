<?php

/**
 * Langues et pays : TranslatePress (français + anglais) et Price Based on Country (devise selon le pays).
 * Le sélecteur « Langue et pays » et les autres langues (traduction automatique) sont dans le thème :
 * app/langues.php et app/devises.php.
 *
 *   composer langues            (relancé aussi par composer seed)
 *
 * Idempotent. Une traduction déjà saisie dans l'éditeur de TranslatePress n'est pas écrasée, ni une zone
 * tarifaire déjà créée, sauf avec US_SEED_FORCE=1.
 */
if (! defined('ABSPATH')) {
    exit("À lancer avec WP-CLI : wp eval-file scripts/langues.php\n");
}

require_once __DIR__ . '/fonctions.php';

$force = (bool) getenv('US_SEED_FORCE');

/*
|--------------------------------------------------------------------------
| Langues : français par défaut sans préfixe, anglais sous /en/
|--------------------------------------------------------------------------
*/
WP_CLI::log('Langues');
if (class_exists('TRP_Translate_Press')) {
    // Passés par la validation de TranslatePress : c'est elle qui crée les tables de traduction de chaque langue.
    $trpReglages = TRP_Translate_Press::get_trp_instance()->get_component('settings');
    $trp = (array) get_option('trp_settings', []);
    update_option('trp_settings', $trpReglages->sanitize_settings(array_merge($trp, [
        'default-language' => 'fr_FR',
        'translation-languages' => ['fr_FR', 'en_GB'],
        'publish-languages' => ['fr_FR', 'en_GB'],
        'url-slugs' => ['fr_FR' => 'fr', 'en_GB' => 'en'],
        'add-subdirectory-to-default-language' => 'no',
        'force-language-to-custom-links' => 'yes',
        'native_or_english_name' => 'native_name',
        'trp-ls-floater' => 'no',
        'trp-ls-show-poweredby' => 'no',
    ])));
    // TranslatePress 3 : le bouton flottant a ses propres réglages (le sélecteur est celui du thème).
    $switcher = (array) get_option('trp_language_switcher_settings', []);
    $switcher['floater'] = array_merge((array) ($switcher['floater'] ?? []), ['enabled' => false, 'showPoweredBy' => false]);
    update_option('trp_language_switcher_settings', $switcher);
    us_log('français (par défaut) + anglais sous /en/');

    // Traductions anglaises (scripts/traductions/en_GB.json : texte français → anglais, tel qu'il sort du HTML).
    $fichier = __DIR__ . '/traductions/en_GB.json';
    if (is_file($fichier)) {
        global $wpdb;
        $trpQuery = TRP_Translate_Press::get_trp_instance()->get_component('query');
        $table = $trpQuery->get_table_name('en_GB');
        $traductions = json_decode(file_get_contents($fichier), true) ?: [];
        // Chaque texte aussi sous son autre forme HTML (« l'huile » / « l&#039;huile », « & » / « &amp; ») :
        // TranslatePress compare le HTML tel quel, et les textes ne sortent pas tous échappés pareil.
        foreach ($traductions as $fr => $en) {
            $brut = html_entity_decode($fr, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            foreach ([$brut, esc_html($brut)] as $forme) {
                $traductions[$forme] ??= $en;
            }
        }
        $connus = array_flip($wpdb->get_col("SELECT original FROM `$table`"));
        $nouveaux = array_values(array_filter(array_keys($traductions), fn($t) => ! isset($connus[$t])));
        foreach (array_chunk($nouveaux, 500) as $lot) {
            $trpQuery->insert_strings($lot, 'en_GB');
        }
        $condition = $force ? '' : ' AND (translated IS NULL OR translated = "" OR status = 0)';
        $faites = 0;
        foreach ($traductions as $fr => $en) {
            $faites += (int) $wpdb->query($wpdb->prepare(
                "UPDATE `$table` SET translated = %s, status = %d WHERE original = %s$condition",
                $en,
                TRP_Query::HUMAN_REVIEWED,
                $fr,
            ));
        }
        us_log(sprintf('traductions anglaises : %d textes (%d mis à jour)', count($traductions), $faites));
    }
} else {
    WP_CLI::warning('TranslatePress est inactif : langues non réglées.');
}

/*
|--------------------------------------------------------------------------
| Pays et devises : pays de l'ancienne boutique, devise de chaque pays
|--------------------------------------------------------------------------
*/
WP_CLI::log('Pays et devises');
$pays = ['DE', 'BE', 'BJ', 'CM', 'CG', 'CD', 'CI', 'FR', 'GA', 'IT', 'LU', 'NL', 'CH', 'TG'];
update_option('woocommerce_allowed_countries', 'specific');
update_option('woocommerce_specific_allowed_countries', $pays);
// Pays deviné par l'adresse IP à la première visite (base gratuite db-ip, téléchargée par l'extension).
update_option('woocommerce_default_customer_address', 'geolocation');
us_log(count($pays) . ' pays de vente, pays deviné à la première visite');
if (function_exists('App\mettre_a_jour_base_pays')) {
    us_log(App\mettre_a_jour_base_pays() ? 'base DB-IP des pays en place' : 'base DB-IP des pays non téléchargée (nouvel essai chaque semaine)');
}

if (class_exists('WCPBC_Pricing_Zones')) {
    // Installation de l'extension d'abord : faite plus tard (premier passage dans l'admin), elle viderait les zones.
    if (class_exists('WCPBC_Install')) {
        WCPBC_Install::check_version();
    }
    update_option('wc_price_based_country_based_on', 'shipping');
    update_option('wc_price_based_country_shipping_exchange_rate', 'yes');

    // Zones hors euro (les pays de la zone euro gardent l'euro de la boutique). Taux posés par app/devises.php.
    $zones = [
        'suisse' => ['Suisse', ['CH'], 'CHF'],
        'afrique-centrale' => ['Afrique centrale', ['CM', 'CG', 'GA'], 'XAF'],
        'afrique-de-l-ouest' => ["Afrique de l'Ouest", ['BJ', 'CI', 'TG'], 'XOF'],
        'rd-congo' => ['RD Congo', ['CD'], 'CDF'],
    ];
    $existantes = WCPBC_Pricing_Zones::get_zones();
    $ordre = 1;
    foreach ($zones as $id => [$nom, $codes, $devise]) {
        if (isset($existantes[$id]) && ! $force) {
            $ordre++;
            continue;
        }
        $zone = $existantes[$id] ?? WCPBC_Pricing_Zones::create();
        $zone->set_id($id);
        $zone->set_name($nom);
        $zone->set_countries($codes);
        $zone->set_currency($devise);
        $zone->set_enabled('yes');
        $zone->set_order($ordre++);
        if (! (float) $zone->get_exchange_rate() || (float) $zone->get_exchange_rate() === 1.0) {
            $zone->set_exchange_rate(App\PARITES_FIXES[$devise] ?? 1);
        }
        WCPBC_Pricing_Zones::save($zone);
        us_log("zone $nom : " . implode(', ', $codes) . " en $devise");
    }
    if (function_exists('App\\mettre_a_jour_taux')) {
        foreach (App\mettre_a_jour_taux() as $devise => $taux) {
            us_log("taux : 1 € = $taux $devise");
        }
    }
} else {
    WP_CLI::warning('Price Based on Country est inactif : devises non réglées.');
}
