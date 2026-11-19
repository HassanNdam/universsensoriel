<?php

/**
 * Pont : adresses de la commande dans un e-mail (remplace emails/email-addresses.php de WooCommerce).
 *
 * @var \WC_Order $order
 * @var bool $sent_to_admin
 */
defined('ABSPATH') || exit;

if (! ($order ?? null) instanceof WC_Order) {
    return;
}

echo \Roots\view('mails.adresses', ['adresses' => App\mail_adresses($order, ! empty($sent_to_admin))])->render();
