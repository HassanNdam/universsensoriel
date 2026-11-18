<?php

/**
 * Commande reçue et compte client : les gabarits de WooCommerce sont remplacés par des vues Blade du thème.
 *
 * Rien n'est copié depuis l'extension : les vues ne lisent les commandes que par les méthodes publiques
 * de WC_Order et rejouent les crochets de WooCommerce. Une mise à jour de WooCommerce, de la
 * passerelle de paiement ou de WordPress ne touche donc ni ces fichiers, ni les vues
 * (resources/views/woocommerce/), ni les styles (resources/css/commande.css et compte.css).
 *
 * Ce fichier : la table des gabarits remplacés et la page « commande reçue ». Le compte client est dans app/compte.php.
 */

namespace App;

if (! function_exists('WC')) {
    return;
}

/** Gabarit de WooCommerce => pont du thème (app/ponts/<nom>.php), qui rend la vue Blade du même nom. */
const PONTS = [
    'checkout/thankyou.php' => 'commande-recue',
    'myaccount/orders.php' => 'compte-commandes',
    'myaccount/view-order.php' => 'compte-commande',
    'myaccount/my-address.php' => 'compte-adresses',
];

add_filter('wc_get_template', function ($fichier, $nom) {
    return isset(PONTS[$nom]) ? get_theme_file_path('app/ponts/' . PONTS[$nom] . '.php') : $fichier;
}, 10, 2);

/** Vrai sur la page affichée après le paiement. */
function est_confirmation_commande(): bool
{
    return function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received');
}

/** Tout ce que la vue affiche, lu sur la commande. */
function confirmation_commande(\WC_Order $commande): array
{
    $articles = [];
    foreach ($commande->get_items() as $ligne) {
        $produit = $ligne->get_product();
        $visible = $produit && $produit->is_visible();
        $articles[] = [
            'nom' => $ligne->get_name(),
            'url' => $visible ? $produit->get_permalink() : '',
            'image' => $produit ? image_produit($produit, 'thumbnail') : '',
            'options' => wc_display_item_meta($ligne, ['echo' => false, 'before' => '', 'after' => '', 'separator' => ' · ', 'label_before' => '', 'label_after' => ' : ', 'autop' => false]),
            'quantite' => $ligne->get_quantity(),
            'total' => $commande->get_formatted_line_subtotal($ligne),
        ];
    }

    // Sous-total, livraison, remises, taxes, total : WooCommerce fournit les lignes déjà formatées.
    $totaux = [];
    foreach ($commande->get_order_item_totals() as $cle => $ligne) {
        if ($cle !== 'payment_method') {
            $totaux[] = ['cle' => $cle, 'libelle' => rtrim(trim(wp_strip_all_tags($ligne['label'])), ' :'), 'valeur' => $ligne['value']];
        }
    }

    $retrait = false;
    foreach ($commande->get_shipping_methods() as $mode) {
        $retrait = $retrait || in_array($mode->get_method_id(), ['local_pickup', 'pickup_location'], true);
    }

    $client = $commande->get_user_id() === get_current_user_id();
    $date = $commande->get_date_created();

    return [
        'numero' => $commande->get_order_number(),
        'date' => $date ? date_i18n('j F Y', $date->getOffsetTimestamp()) : '',
        'prenom' => $commande->get_billing_first_name(),
        'total' => $commande->get_formatted_order_total(),
        'paiement' => wp_strip_all_tags($commande->get_payment_method_title()),
        'echec' => $commande->has_status('failed'),
        'url_paiement' => $commande->get_checkout_payment_url(),
        'articles' => $articles,
        'totaux' => $totaux,
        'retrait' => $retrait,
        'livraison' => $commande->get_shipping_method(),
        // Adresses et e-mail : seulement pour la personne qui a passé la commande, comme le fait WooCommerce.
        'client' => $client,
        'courriel' => $client ? $commande->get_billing_email() : '',
        'telephone' => $client ? $commande->get_billing_phone() : '',
        'adresse_livraison' => $client && ! $retrait && $commande->needs_shipping_address() ? $commande->get_formatted_shipping_address() : '',
        'adresse_facturation' => $client ? $commande->get_formatted_billing_address() : '',
        'url_suivi' => $client && is_user_logged_in() ? $commande->get_view_order_url() : '',
        'note' => $commande->get_customer_note(),
    ];
}

/**
 * Crochets de WooCommerce rejoués autour de la vue : les extensions (paiement, suivi, e-mails)
 * y accrochent leur traitement et parfois un message. Le tableau standard est retiré, la vue le remplace.
 */
function crochets_confirmation(\WC_Order $commande): array
{
    remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);

    ob_start();
    do_action('woocommerce_before_thankyou', $commande->get_id());
    $avant = trim(ob_get_clean());

    ob_start();
    do_action('woocommerce_thankyou_' . $commande->get_payment_method(), $commande->get_id());
    do_action('woocommerce_thankyou', $commande->get_id());
    $apres = trim(ob_get_clean());

    return ['avant' => $avant, 'apres' => $apres];
}
