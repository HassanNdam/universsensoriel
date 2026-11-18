<?php

/**
 * Pont : adresses du compte (remplace myaccount/my-address.php de WooCommerce).
 */
defined('ABSPATH') || exit;

echo \Roots\view('woocommerce.compte-adresses', ['adresses' => App\adresses_compte()])->render();
