<?php

/**
 * Pays et devises (Price Based on Country, gratuit).
 *
 * - Les pays proposés sont les « pays de vente » de WooCommerce (Réglages > Général) ; chaque pays prend la
 *   devise de sa zone tarifaire (WooCommerce > Réglages > Zones tarifaires), l'euro à défaut.
 * - Le pays se choisit dans le sélecteur « Langue et pays » ; à la première visite, il est deviné par l'adresse IP.
 * - Les taux de change des zones sont mis à jour chaque jour ici (fonction payante de l'extension) :
 *   francs CFA à leur parité fixe avec l'euro, les autres devises au cours du jour.
 */

namespace App;

/** Devises arrimées à l'euro : parité fixe, jamais remplacée par un cours du marché. */
const PARITES_FIXES = ['XAF' => 655.957, 'XOF' => 655.957];

/** Devises affichées sans centimes. */
const DEVISES_SANS_CENTIMES = ['XAF', 'XOF', 'CDF'];

/** Source des cours du jour (gratuite, sans clé). */
const SOURCE_TAUX = 'https://open.er-api.com/v6/latest/';

/**
 * Pays du sélecteur, triés par nom, avec leur devise.
 *
 * @return array<int, array{code: string, nom: string, devise: string, symbole: string, actif: bool}>
 */
function pays_et_devises(): array
{
    if (! function_exists('WC') || ! WC()->countries) {
        return [];
    }
    $base = get_option('woocommerce_currency');
    $courant = function_exists('wcpbc_get_woocommerce_country') ? wcpbc_get_woocommerce_country() : WC()->countries->get_base_country();
    $liste = [];
    foreach (WC()->countries->get_allowed_countries() as $code => $nom) {
        $zone = function_exists('wcpbc_get_zone_by_country') ? wcpbc_get_zone_by_country($code) : null;
        $devise = $zone ? $zone->get_currency() : $base;
        $liste[] = [
            'code' => $code,
            'nom' => html_entity_decode($nom, ENT_QUOTES, 'UTF-8'),
            'devise' => $devise,
            'symbole' => html_entity_decode(get_woocommerce_currency_symbol($devise), ENT_QUOTES, 'UTF-8'),
            'actif' => $code === $courant,
        ];
    }

    return count($liste) > 1 ? $liste : [];
}

/** Pays choisi (ou deviné), pour le libellé du sélecteur. */
function pays_actif(): ?array
{
    foreach (pays_et_devises() as $pays) {
        if ($pays['actif']) {
            return $pays;
        }
    }

    return null;
}

// Choix du pays dans le sélecteur : l'extension l'enregistre (champ « wcpbc-manual-country »), puis on recharge
// la page en GET pour qu'un retour arrière ne renvoie pas le formulaire.
add_action('template_redirect', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! empty($_POST['us_pays']) && ! empty($_POST['wcpbc-manual-country'])) {
        wp_safe_redirect(remove_query_arg('wcpbc-manual-country', home_url(add_query_arg([]))));
        exit;
    }
}, 1);

// Francs CFA et franc congolais : sans centimes, et symboles usuels.
add_filter('wc_get_price_decimals', function ($decimales) {
    return (! is_admin() || wp_doing_ajax()) && in_array(get_woocommerce_currency(), DEVISES_SANS_CENTIMES, true) ? 0 : $decimales;
});
add_filter('woocommerce_currency_symbol', function ($symbole, $devise) {
    return ['XAF' => 'FCFA', 'XOF' => 'FCFA', 'CDF' => 'FC'][$devise] ?? $symbole;
}, 10, 2);

/**
 * Met à jour le taux de change de chaque zone tarifaire. Renvoie [devise => taux] des zones mises à jour.
 * Sans réponse de la source, les taux en place sont gardés.
 */
function mettre_a_jour_taux(): array
{
    if (! class_exists('WCPBC_Pricing_Zones')) {
        return [];
    }
    $base = get_option('woocommerce_currency');
    $cours = [];
    $reponse = wp_remote_get(SOURCE_TAUX . rawurlencode($base), ['timeout' => 15]);
    if (! is_wp_error($reponse) && wp_remote_retrieve_response_code($reponse) === 200) {
        $donnees = json_decode(wp_remote_retrieve_body($reponse), true);
        if (($donnees['result'] ?? '') === 'success') {
            $cours = (array) ($donnees['rates'] ?? []);
        }
    }

    $faits = [];
    foreach (\WCPBC_Pricing_Zones::get_zones() as $zone) {
        $devise = $zone->get_currency();
        $taux = $base === 'EUR' && isset(PARITES_FIXES[$devise]) ? PARITES_FIXES[$devise] : ($cours[$devise] ?? null);
        if ($devise === $base || ! $taux || (float) $zone->get_exchange_rate() === (float) $taux) {
            continue;
        }
        $zone->set_exchange_rate((float) $taux);
        \WCPBC_Pricing_Zones::save($zone);
        $faits[$devise] = (float) $taux;
    }
    update_option('us_taux_mis_a_jour', current_time('mysql'), false);

    return $faits;
}

add_action('us_taux_de_change', __NAMESPACE__ . '\\mettre_a_jour_taux');
add_action('init', function () {
    if (class_exists('WCPBC_Pricing_Zones') && ! wp_next_scheduled('us_taux_de_change')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'us_taux_de_change');
    }
    if (! wp_next_scheduled('us_base_pays')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'us_base_pays');
    }
});

/*
|--------------------------------------------------------------------------
| Pays deviné par l'adresse IP
|--------------------------------------------------------------------------
| WooCommerce ne géolocalise qu'avec une clé MaxMind (compte à créer). Sans elle, on lit la base gratuite
| « IP to Country Lite » de DB-IP (licence CC BY 4.0, attribution dans le sélecteur), mise à jour chaque mois,
| avec le lecteur fourni par WooCommerce. Une clé MaxMind ou un en-tête pays de l'hébergeur passent avant.
*/

/** Chemin de la base DB-IP. */
function base_pays(): string
{
    return trailingslashit(wp_upload_dir(null, false)['basedir']) . 'woocommerce_uploads/us-dbip-country-lite.mmdb';
}

/** Télécharge la base du mois (gardée si elle date de moins de 25 jours). Renvoie vrai si elle est en place. */
function mettre_a_jour_base_pays(bool $forcer = false): bool
{
    $chemin = base_pays();
    if (! $forcer && is_file($chemin) && filemtime($chemin) > time() - 25 * DAY_IN_SECONDS) {
        return true;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    foreach ([gmdate('Y-m'), gmdate('Y-m', strtotime('first day of last month'))] as $mois) {
        $archive = download_url("https://download.db-ip.com/free/dbip-country-lite-$mois.mmdb.gz", 120);
        if (is_wp_error($archive)) {
            continue;
        }
        $contenu = gzdecode((string) file_get_contents($archive));
        wp_delete_file($archive);
        if ($contenu) {
            wp_mkdir_p(dirname($chemin));
            file_put_contents($chemin, $contenu);

            return true;
        }
    }

    return is_file($chemin);
}
add_action('us_base_pays', fn() => mettre_a_jour_base_pays());

add_filter('woocommerce_geolocate_ip', function ($pays, $ip) {
    $maxmind = get_option('woocommerce_maxmind_geolocation_settings', []);
    if ($pays !== false || ! empty($maxmind['license_key']) || ! empty($_SERVER['HTTP_CF_IPCOUNTRY']) || ! class_exists('MaxMind\Db\Reader') || ! is_file(base_pays())) {
        return $pays;
    }
    $ip = $ip ?: \WC_Geolocation::get_ip_address();
    if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $pays;
    }
    try {
        $lecteur = new \MaxMind\Db\Reader(base_pays());
        $code = $lecteur->get($ip)['country']['iso_code'] ?? '';
        $lecteur->close();
    } catch (\Throwable $e) {
        return $pays;
    }

    return $code ?: $pays;
}, 10, 2);
