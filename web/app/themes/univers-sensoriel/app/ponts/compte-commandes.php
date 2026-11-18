<?php

/**
 * Pont : liste des commandes du compte (remplace myaccount/orders.php de WooCommerce).
 *
 * @var object $customer_orders
 * @var int $current_page
 * @var bool $has_orders
 */
defined('ABSPATH') || exit;

$page = (int) ($current_page ?? 1);
$pages = (int) ($customer_orders->max_num_pages ?? 1);

echo \Roots\view('woocommerce.compte-commandes', [
    'commandes' => App\liste_commandes((array) ($customer_orders->orders ?? [])),
    'precedente' => $page > 1 ? wc_get_endpoint_url('orders', $page - 1) : '',
    'suivante' => $page < $pages ? wc_get_endpoint_url('orders', $page + 1) : '',
    'avant' => App\sortie_crochet('woocommerce_before_account_orders', ! empty($has_orders)),
    'pagination' => App\sortie_crochet('woocommerce_before_account_orders_pagination'),
    'apres' => App\sortie_crochet('woocommerce_after_account_orders', ! empty($has_orders)),
])->render();
