<?php

/**
 * Pont : détail d'une commande dans le compte (remplace myaccount/view-order.php de WooCommerce).
 *
 * @var \WC_Order $order
 * @var int $order_id
 */
defined('ABSPATH') || exit;

if (! ($order ?? null) instanceof WC_Order) {
    return;
}

// Le tableau standard est retiré de ce crochet : la vue le remplace. Les autres extensions y restent.
remove_action('woocommerce_view_order', 'woocommerce_order_details_table', 10);

echo \Roots\view('woocommerce.compte-commande', [
    'commande' => App\confirmation_commande($order),
    'etat' => App\etat_commande($order),
    'notes' => App\notes_commande($order),
    'apres' => App\sortie_crochet('woocommerce_view_order', $order->get_id()),
    'retour' => wc_get_account_endpoint_url('orders'),
])->render();
