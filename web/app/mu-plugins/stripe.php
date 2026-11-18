<?php

/**
 * Plugin Name: Stripe (.env)
 * Description: Donne à la passerelle Stripe de WooCommerce les clés du .env (STRIPE_PUBLISHABLE_KEY, STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET). Le mode test se déduit de la clé secrète (sk_test_…).
 */

use function Env\env;

if (! env('STRIPE_SECRET_KEY') || ! env('STRIPE_PUBLISHABLE_KEY')) {
    return;
}

// Les clés ne sont jamais rangées en base par le projet : elles sont posées sur les réglages à la lecture.
$cles = function ($reglages) {
    $reglages = is_array($reglages) ? $reglages : [];
    $secrete = (string) env('STRIPE_SECRET_KEY');
    $test = str_starts_with($secrete, 'sk_test_') || str_starts_with($secrete, 'rk_test_');
    $prefixe = $test ? 'test_' : '';

    return array_merge($reglages, [
        'testmode' => $test ? 'yes' : 'no',
        $prefixe . 'publishable_key' => (string) env('STRIPE_PUBLISHABLE_KEY'),
        $prefixe . 'secret_key' => $secrete,
        $prefixe . 'webhook_secret' => (string) env('STRIPE_WEBHOOK_SECRET'),
    ]);
};

add_filter('option_woocommerce_stripe_settings', $cles);
add_filter('default_option_woocommerce_stripe_settings', $cles);
