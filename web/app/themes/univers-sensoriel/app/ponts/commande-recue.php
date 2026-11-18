<?php

/**
 * Pont entre WooCommerce et la vue Blade de la page « commande reçue ».
 * Inclus par WooCommerce à la place de son gabarit checkout/thankyou.php (voir app/commande.php).
 *
 * @var \WC_Order|false $order
 */
defined('ABSPATH') || exit;

$commande = ($order ?? false) instanceof WC_Order ? $order : null;

echo \Roots\view('woocommerce.commande-recue', [
    'commande' => $commande ? App\confirmation_commande($commande) : null,
    'crochets' => $commande ? App\crochets_confirmation($commande) : ['avant' => '', 'apres' => ''],
])->render();
