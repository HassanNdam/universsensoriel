<?php

/**
 * Pont : détail de la commande dans un e-mail (remplace emails/email-order-details.php de WooCommerce).
 * Les crochets placés avant et après le tableau sont rejoués : les moyens de paiement y écrivent leurs consignes.
 *
 * @var \WC_Order $order
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var \WC_Email|string $email
 */
defined('ABSPATH') || exit;

if (! ($order ?? null) instanceof WC_Order) {
    return;
}

$boutique = ! empty($sent_to_admin);
$email = $email ?? '';

echo \Roots\view('mails.commande', [
    'commande' => App\mail_commande($order, $boutique),
    'avant' => App\sortie_crochet('woocommerce_email_before_order_table', $order, $boutique, false, $email),
    'apres' => App\sortie_crochet('woocommerce_email_after_order_table', $order, $boutique, false, $email),
])->render();
