<?php

/**
 * Pont : corps des e-mails rédigés par le thème (table MAILS_REDIGES dans app/mails.php).
 * WooCommerce l'inclut à la place du gabarit de l'e-mail, avec les mêmes variables.
 * Les crochets de WooCommerce sont rejoués dans le même ordre que dans ses gabarits.
 *
 * @var \WC_Email $email
 * @var string $email_heading
 * @var string $additional_content
 * @var bool $sent_to_admin
 */
defined('ABSPATH') || exit;

$message = App\mail_message($email, get_defined_vars());
$commande = $message['commande'];
$boutique = ! empty($sent_to_admin);

do_action('woocommerce_email_header', $email_heading, $email);

echo \Roots\view('mails.message', $message + [
    'detail' => $commande
        ? App\sortie_crochet('woocommerce_email_order_details', $commande, $boutique, false, $email)
            . App\sortie_crochet('woocommerce_email_order_meta', $commande, $boutique, false, $email)
            . App\sortie_crochet('woocommerce_email_customer_details', $commande, $boutique, false, $email)
        : '',
    'complement' => ! empty($additional_content) ? wp_kses_post(wpautop(wptexturize($additional_content))) : '',
])->render();

do_action('woocommerce_email_footer', $email);
