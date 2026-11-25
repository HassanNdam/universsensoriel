<?php

/**
 * Plugin Name: E-mails non partis
 * Description: Garde la trace des e-mails refusés par le serveur d'envoi (raison comprise) et l'affiche aux administrateurs ; bouton « Envoyer un e-mail d'essai ».
 *
 * Sans cela, un e-mail refusé (mot de passe SMTP faux, boîte inexistante…) disparaît sans un mot :
 * ni la cliente ni la boutique ne reçoivent rien, et rien ne le signale.
 */

/** Chaque refus : date, destinataire, objet et raison donnée par le serveur (les 20 derniers). */
add_action('wp_mail_failed', function (WP_Error $erreur) {
    $donnees = (array) $erreur->get_error_data();
    $echecs = (array) get_option('us_mails_echecs', []);
    array_unshift($echecs, [
        'quand' => time(),
        'a' => implode(', ', (array) ($donnees['to'] ?? [])),
        'objet' => (string) ($donnees['subject'] ?? ''),
        'raison' => $erreur->get_error_message(),
    ]);
    update_option('us_mails_echecs', array_slice($echecs, 0, 20), false);
    error_log('E-mail non parti (' . ($donnees['subject'] ?? '') . ') : ' . $erreur->get_error_message());
});

/** Envoi d'essai vers l'adresse de l'administrateur connecté, puis retour sur la page avec le résultat. */
add_action('admin_post_us_mail_essai', function () {
    if (! current_user_can('manage_options') || ! check_admin_referer('us_mail_essai')) {
        wp_die('Action non autorisée.');
    }
    $a = wp_get_current_user()->user_email;
    $ok = wp_mail($a, 'E-mail d\'essai du site', 'Si vous lisez ce message, les e-mails du site partent bien.');
    wp_safe_redirect(add_query_arg('us-mail-essai', $ok ? 'ok' : 'echec', wp_get_referer() ?: admin_url()));
    exit;
});

add_action('admin_notices', function () {
    if (! current_user_can('manage_options')) {
        return;
    }
    $bouton = sprintf(
        '<a class="button" href="%s">Envoyer un e-mail d\'essai à %s</a>',
        esc_url(wp_nonce_url(admin_url('admin-post.php?action=us_mail_essai'), 'us_mail_essai')),
        esc_html(wp_get_current_user()->user_email)
    );
    $essai = sanitize_key($_GET['us-mail-essai'] ?? '');
    if ($essai === 'ok') {
        echo '<div class="notice notice-success is-dismissible"><p>E-mail d\'essai envoyé : vérifiez la boîte de réception (et les indésirables).</p></div>';
    }
    $echecs = array_filter((array) get_option('us_mails_echecs', []), fn ($e) => ($e['quand'] ?? 0) > time() - 7 * DAY_IN_SECONDS);
    if (! $echecs) {
        // Sans échec récent, le bouton d'essai reste sur le tableau de bord seulement.
        if (get_current_screen()?->id === 'dashboard' && $essai !== 'ok') {
            echo '<div class="notice notice-info"><p>E-mails du site : aucun refus ces 7 derniers jours. ' . $bouton . '</p></div>';
        }

        return;
    }
    $dernier = reset($echecs);
    printf(
        '<div class="notice notice-error"><p><strong>%s ces 7 derniers jours : les clientes et la boutique ne les ont pas reçus.</strong><br>Dernier : « %s » à %s, le %s.<br>Raison donnée par le serveur d\'envoi : <code>%s</code></p><p>Les réglages d\'envoi (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD…) sont dans le fichier .env du serveur. %s</p></div>',
        esc_html(sprintf(_n('%d e-mail non parti', '%d e-mails non partis', count($echecs)), count($echecs))),
        esc_html($dernier['objet']),
        esc_html($dernier['a']),
        esc_html(wp_date('d/m/Y à H:i', $dernier['quand'])),
        esc_html($dernier['raison']),
        $bouton
    );
});
