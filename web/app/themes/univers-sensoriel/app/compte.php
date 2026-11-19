<?php

/**
 * Compte client : liste des commandes, détail d'une commande, adresses.
 * Les gabarits de WooCommerce sont remplacés par des vues du thème (table PONTS dans app/commande.php).
 */

namespace App;

if (! function_exists('WC')) {
    return;
}

/**
 * État d'une commande pour l'affichage : libellé de WooCommerce, ton de la pastille,
 * et étape atteinte (0 reçue, 1 préparation, 2 expédiée ou prête) ou null si elle est arrêtée.
 */
function etat_commande(\WC_Order $commande): array
{
    $statut = $commande->get_status();

    return [
        'libelle' => wc_get_order_status_name($statut),
        'ton' => match ($statut) {
            'completed' => 'terminee',
            'cancelled', 'failed', 'refunded' => 'arretee',
            default => 'en-cours',
        },
        'etape' => match ($statut) {
            'pending', 'on-hold' => 0,
            'processing' => 1,
            'completed' => 2,
            default => null,
        },
    ];
}

/** Les trois étapes d'une commande, selon qu'elle est livrée ou retirée en boutique. */
function etapes_commande(bool $retrait): array
{
    return ['Commande reçue', 'Préparation', $retrait ? 'Prête en boutique' : 'Expédition'];
}

/** Commandes du compte, prêtes pour la vue : une carte par commande. */
function liste_commandes(array $commandes): array
{
    $liste = [];
    foreach ($commandes as $commande) {
        $commande = wc_get_order($commande);
        if (! $commande) {
            continue;
        }
        $vignettes = [];
        foreach ($commande->get_items() as $ligne) {
            if ($produit = $ligne->get_product()) {
                $vignettes[] = image_produit($produit, 'thumbnail');
            }
        }
        $nombre = $commande->get_item_count();
        $actions = [];
        foreach (wc_get_account_orders_actions($commande) as $cle => $action) {
            $actions[] = ['cle' => $cle, 'url' => $action['url'], 'nom' => $action['name']];
        }
        $date = $commande->get_date_created();
        $liste[] = [
            'numero' => $commande->get_order_number(),
            'url' => $commande->get_view_order_url(),
            'date' => $date ? date_i18n('j F Y', $date->getOffsetTimestamp()) : '',
            'etat' => etat_commande($commande),
            'total' => $commande->get_formatted_order_total(),
            'articles' => $nombre . ' ' . ($nombre > 1 ? 'articles' : 'article'),
            'vignettes' => array_slice($vignettes, 0, 4),
            'reste' => max(0, count($vignettes) - 4),
            'actions' => $actions,
        ];
    }

    return $liste;
}

/** Notes que la boutique a adressées au client sur une commande. */
function notes_commande(\WC_Order $commande): array
{
    $notes = [];
    foreach ($commande->get_customer_order_notes() as $note) {
        $notes[] = [
            'date' => date_i18n('j F Y, H\hi', strtotime($note->comment_date)),
            'texte' => wpautop(wptexturize($note->comment_content)),
        ];
    }

    return $notes;
}

/** Adresses du compte : facturation, et livraison si la boutique livre. */
function adresses_compte(): array
{
    $types = ['billing' => 'Facturation'];
    if (! wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
        $types['shipping'] = 'Livraison';
    }
    // Le filtre de WooCommerce peut ajouter ou retirer des adresses : on garde nos libellés courts pour les deux connues.
    $declarees = apply_filters('woocommerce_my_account_get_addresses', $types, get_current_user_id());

    $adresses = [];
    foreach ($declarees as $type => $titre) {
        ob_start();
        do_action('woocommerce_my_account_after_my_address', $type);
        $adresses[] = [
            'type' => $type,
            'titre' => $types[$type] ?? $titre,
            'icone' => $type === 'shipping' ? 'livraison' : 'courriel',
            'adresse' => wc_get_account_formatted_address($type),
            'url' => wc_get_endpoint_url('edit-address', $type),
            'complement' => trim(ob_get_clean()),
        ];
    }

    return $adresses;
}

/** Sortie d'un crochet de WooCommerce, pour la placer où la vue le décide. */
function sortie_crochet(string $crochet, ...$arguments): string
{
    ob_start();
    do_action($crochet, ...$arguments);

    return trim(ob_get_clean());
}
