<?php

/**
 * Paiement : passerelle Stripe de WooCommerce.
 *
 * Les clés viennent du .env (voir web/app/mu-plugins/stripe.php) ; ce script ne règle que le reste.
 * Les moyens de paiement proposés (carte, Apple Pay, Google Pay, Link…) se choisissent dans le
 * tableau de bord Stripe : la passerelle les reprend tels quels.
 *
 * Lancer seul : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/paiement.php --user=admin
 */
if (! class_exists('WC_Stripe')) {
    WP_CLI::warning('La passerelle Stripe est inactive : paiement non réglé.');

    return;
}

$reglages = [
    'enabled' => 'yes',
    'title' => 'Carte bancaire',
    'description' => 'Paiement sécurisé par Stripe.',
    'capture' => 'yes',
    'saved_cards' => 'yes',
    'statement_descriptor' => 'UNIVERS SENSORIEL',
    'short_statement_descriptor' => 'UNIVSENS',
    'logging' => 'no',
];

// Les clés posées à la lecture par le mu-plugin sont retirées, pour ne pas les recopier en base.
$actuels = get_option('woocommerce_stripe_settings', []);
$actuels = is_array($actuels) ? $actuels : [];
foreach (['testmode', 'test_publishable_key', 'test_secret_key', 'test_webhook_secret', 'publishable_key', 'secret_key', 'webhook_secret'] as $cle) {
    unset($actuels[$cle]);
}

update_option('woocommerce_stripe_settings', array_merge($actuels, $reglages));

// Les moyens de paiement hors ligne de WooCommerce restent fermés.
foreach (['bacs', 'cheque', 'cod'] as $moyen) {
    $option = "woocommerce_{$moyen}_settings";
    $valeur = (array) get_option($option, []);
    if (($valeur['enabled'] ?? 'no') !== 'no') {
        update_option($option, ['enabled' => 'no'] + $valeur);
    }
}

$cle = function_exists('Env\\env') ? (string) Env\env('STRIPE_SECRET_KEY') : '';
$mode = $cle === '' ? 'clés absentes du .env' : (str_contains($cle, '_test_') ? 'mode test' : 'mode réel');
$message = "Stripe : carte bancaire activée ($mode)";
function_exists('us_log') ? us_log($message) : WP_CLI::log($message);
