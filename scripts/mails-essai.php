<?php

/**
 * Envoie un exemplaire de chaque e-mail du site, pour le relire dans Mailpit (http://localhost:8025).
 *
 * À ne lancer qu'en local, où le SMTP du .env est Mailpit : les e-mails partent vers l'adresse de la commande
 * et celle de la boutique. Rien n'est modifié : ni l'état de la commande, ni le compte.
 *
 * php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/mails-essai.php [n° de commande] --user=admin
 * Sans numéro : la dernière commande. Un seul e-mail : variable US_MAIL=customer_processing_order (ou wordpress).
 */
if (wp_get_environment_type() === 'production') {
    WP_CLI::error("Pas d'envoi d'essai en production.");
}

$commande = ! empty($args[0]) ? wc_get_order((int) $args[0]) : (wc_get_orders(['limit' => 1, 'type' => 'shop_order'])[0] ?? null);
if (! $commande) {
    WP_CLI::error('Aucune commande : en passer une sur le site, puis relancer.');
}
$id = $commande->get_id();
$compte = get_user_by('id', $commande->get_customer_id()) ?: wp_get_current_user();
$seul = getenv('US_MAIL') ?: '';

// WooCommerce n'envoie « nouvelle commande » qu'une fois par commande : l'essai le renvoie.
add_filter('woocommerce_new_order_email_allows_resend', '__return_true');

$envois = [
    'new_order' => fn($m) => $m->trigger($id, $commande),
    'cancelled_order' => fn($m) => $m->trigger($id, $commande),
    'failed_order' => fn($m) => $m->trigger($id, $commande),
    'customer_on_hold_order' => fn($m) => $m->trigger($id, $commande),
    'customer_processing_order' => fn($m) => $m->trigger($id, $commande),
    'customer_completed_order' => fn($m) => $m->trigger($id, $commande),
    'customer_refunded_order' => fn($m) => $m->trigger($id, false),
    'customer_cancelled_order' => fn($m) => $m->trigger($id, $commande),
    'customer_failed_order' => fn($m) => $m->trigger($id, $commande),
    'customer_invoice' => fn($m) => $m->trigger($id, $commande),
    'customer_note' => fn($m) => $m->trigger(['order_id' => $id, 'customer_note' => 'Votre colis part demain matin, avec un échantillon offert.']),
    'customer_new_account' => fn($m) => $m->trigger($compte->ID, '', true),
    'customer_reset_password' => fn($m) => $m->trigger($compte->user_login, 'cle-d-essai'),
    'customer_verify_email' => fn($m) => $m->trigger($compte->ID, add_query_arg('verify', 'essai', wc_get_page_permalink('myaccount'))),
];

foreach (WC()->mailer()->get_emails() as $mail) {
    if (! isset($envois[$mail->id]) || ($seul && $seul !== $mail->id)) {
        continue;
    }
    $etat = $mail->enabled;
    $mail->enabled = 'yes'; // le temps de l'essai, sans rien enregistrer
    $envois[$mail->id]($mail);
    $mail->enabled = $etat;
    WP_CLI::log("  envoyé : {$mail->id}");
}

if (! $seul || $seul === 'wordpress') {
    // Un e-mail en texte brut, comme ceux de WordPress, et un morceau de HTML, comme ceux des formulaires.
    wp_mail(get_option('admin_email'), '[' . get_bloginfo('name') . '] Notification en texte brut', "Bonjour,\n\nCeci est un e-mail en texte brut, comme WordPress en envoie.\n\nUn lien : " . home_url('/') . "\n");
    wp_mail(get_option('admin_email'), 'Message au format des formulaires', 'Nouveau message depuis la page Contact :<br/><br/><ol><li><b>Nom et prénom</b><br/>Camille Essai</li><li><b>Votre message</b><br/>Bonjour, avez-vous ce parfum en 100 ml ?</li></ol>', ['Content-Type: text/html; charset=UTF-8']);
    WP_CLI::log('  envoyé : wordpress (texte brut et HTML)');
}

WP_CLI::success('À relire dans Mailpit : http://localhost:8025');
