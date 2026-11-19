<?php

/**
 * E-mails de WooCommerce : objet et titre de chaque e-mail, couleurs de la charte.
 *
 * L'habillage et le texte des e-mails sont dans le thème (app/mails.php). Ce script ne pose que ce qui reste
 * modifiable dans WooCommerce › Réglages › E-mails : objet, titre, contenu additionnel (vidé : le thème écrit déjà
 * le texte). Une valeur déjà enregistrée n'est jamais écrasée, sauf avec US_SEED_FORCE=1.
 *
 * Repères utilisables : {order_number}, {order_date}, {site_title}.
 *
 * Lancer seul : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/mails.php --user=admin
 */
if (! function_exists('WC')) {
    WP_CLI::warning('WooCommerce est inactif : e-mails non réglés.');

    return;
}

$textes = [
    // Pour la boutique
    'new_order' => ['subject' => 'Nouvelle commande n° {order_number}', 'heading' => 'Nouvelle commande'],
    'cancelled_order' => ['subject' => 'Commande n° {order_number} annulée', 'heading' => 'Commande annulée'],
    'failed_order' => ['subject' => 'Paiement échoué · commande n° {order_number}', 'heading' => 'Paiement échoué'],

    // Pour la cliente ou le client
    'customer_on_hold_order' => ['subject' => 'Votre commande n° {order_number} est enregistrée', 'heading' => 'Commande enregistrée'],
    'customer_processing_order' => ['subject' => 'Votre commande n° {order_number} est confirmée', 'heading' => 'Merci pour votre commande'],
    'customer_completed_order' => ['subject' => 'Votre commande n° {order_number} est prête', 'heading' => 'Votre commande est prête'],
    'customer_refunded_order' => [
        'subject_full' => 'Votre commande n° {order_number} est remboursée', 'heading_full' => 'Commande remboursée',
        'subject_partial' => 'Remboursement partiel de votre commande n° {order_number}', 'heading_partial' => 'Remboursement partiel',
    ],
    'customer_cancelled_order' => ['subject' => 'Votre commande n° {order_number} est annulée', 'heading' => 'Commande annulée'],
    'customer_failed_order' => ['subject' => "Le paiement de votre commande n° {order_number} n'a pas abouti", 'heading' => 'Paiement non abouti'],
    'customer_invoice' => [
        'subject' => 'Votre commande n° {order_number} attend son règlement', 'heading' => 'Règlement de votre commande',
        'subject_paid' => 'Le détail de votre commande n° {order_number}', 'heading_paid' => 'Votre commande',
    ],
    'customer_note' => ['subject' => 'Un message sur votre commande n° {order_number}', 'heading' => 'Un message pour vous'],
    'customer_new_account' => ['subject' => 'Votre compte {site_title}', 'heading' => 'Bienvenue'],
    'customer_reset_password' => ['subject' => 'Votre nouveau mot de passe {site_title}', 'heading' => 'Nouveau mot de passe'],
    'customer_verify_email' => ['subject' => 'Confirmez votre adresse e-mail', 'heading' => 'Votre adresse e-mail'],
];

$forcer = (bool) getenv('US_SEED_FORCE');
$poses = 0;

foreach (WC()->mailer()->get_emails() as $mail) {
    if (! isset($textes[$mail->id])) {
        continue;
    }
    $cle = "woocommerce_{$mail->id}_settings";
    $avant = (array) get_option($cle, []);
    $apres = $avant;
    foreach ($textes[$mail->id] + ['additional_content' => ''] as $champ => $valeur) {
        if (isset($mail->form_fields[$champ]) && ($forcer || ! array_key_exists($champ, $avant))) {
            $apres[$champ] = $valeur;
        }
    }
    if ($apres !== $avant) {
        update_option($cle, $apres);
        $poses++;
    }
}

// Couleurs de la charte : elles ne servent qu'aux éléments que WooCommerce dessine lui-même (bouton de ses gabarits).
foreach ([
    'woocommerce_email_base_color' => '#1F3124',
    'woocommerce_email_background_color' => '#F7F0E4',
    'woocommerce_email_body_background_color' => '#FFFDF9',
    'woocommerce_email_text_color' => '#1F3124',
    'woocommerce_email_footer_text_color' => '#4F5A4C',
] as $option => $couleur) {
    update_option($option, $couleur);
}
if (! get_option('woocommerce_email_from_name')) {
    update_option('woocommerce_email_from_name', get_bloginfo('name'));
}

WP_CLI::log($poses ? "  e-mails : objets et titres posés ($poses)" : '  e-mails : rien à changer');
