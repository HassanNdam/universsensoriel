<?php

/**
 * Vie d'un avis après son dépôt :
 * - l'acheteur est invité à donner son avis dans l'e-mail « commande terminée » ;
 * - l'avis arrive en attente dans Produits › Avis, et la boutique reçoit un e-mail ;
 * - une fois approuvé, il s'affiche sur la fiche du produit et sur la page « Avis clients » ;
 * - pour l'accueil, c'est un choix : colonne « Accueil » de Produits › Avis (ou case à cocher dans l'avis).
 */

namespace App;

/** Nombre d'avis montrés sur l'accueil, les plus récents d'abord. */
const AVIS_ACCUEIL = 6;

/** Vrai si l'avis est choisi pour l'accueil. */
function avis_sur_accueil(int $avis): bool
{
    return get_comment_meta($avis, '_us_accueil', true) === '1';
}

/*
|--------------------------------------------------------------------------
| Admin : choisir les avis de l'accueil
|--------------------------------------------------------------------------
*/

/** Adresse qui bascule un avis sur l'accueil (ou l'en retire). */
function lien_bascule_accueil(int $avis): string
{
    return wp_nonce_url(admin_url("admin-post.php?action=us_avis_accueil&avis={$avis}"), "us_avis_accueil_{$avis}");
}

add_action('admin_post_us_avis_accueil', function () {
    $avis = (int) ($_GET['avis'] ?? 0);
    if (! $avis || ! current_user_can('moderate_comments')) {
        wp_die('Action non autorisée.');
    }
    check_admin_referer("us_avis_accueil_{$avis}");
    update_comment_meta($avis, '_us_accueil', avis_sur_accueil($avis) ? '0' : '1');
    wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=product&page=product-reviews'));
    exit;
});

/** Colonne « Accueil » dans Produits › Avis. */
add_filter('woocommerce_product_reviews_table_columns', function ($colonnes) {
    $colonnes['us_accueil'] = 'Accueil';

    return $colonnes;
});
add_action('woocommerce_product_reviews_table_column_us_accueil', function ($avis) {
    if ($avis->comment_type !== 'review') {
        return;
    }
    $choisi = avis_sur_accueil((int) $avis->comment_ID);
    if ($avis->comment_approved !== '1') {
        echo '<span style="color:#787c82">À approuver d\'abord</span>';

        return;
    }
    printf(
        '<a href="%s" title="%s" style="text-decoration:none">%s</a>',
        esc_url(lien_bascule_accueil((int) $avis->comment_ID)),
        $choisi ? 'Retirer de l\'accueil' : 'Afficher sur l\'accueil',
        $choisi ? '<span style="color:#AA742C;font-size:18px">★</span> Affiché' : '<span style="color:#c3c4c7;font-size:18px">☆</span> Afficher'
    );
});

/** La même action sous chaque avis, au survol de la ligne. */
add_filter('comment_row_actions', function ($actions, $avis) {
    if ($avis->comment_type === 'review' && $avis->comment_approved === '1' && current_user_can('moderate_comments')) {
        $actions['us_accueil'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(lien_bascule_accueil((int) $avis->comment_ID)),
            avis_sur_accueil((int) $avis->comment_ID) ? 'Retirer de l\'accueil' : 'Afficher sur l\'accueil'
        );
    }

    return $actions;
}, 10, 2);

/** Et une case à cocher dans l'écran de modification de l'avis. */
add_action('add_meta_boxes_comment', function ($avis) {
    if ($avis->comment_type !== 'review') {
        return;
    }
    add_meta_box('us-avis-accueil', 'Page d\'accueil', function ($avis) {
        wp_nonce_field('us_avis_accueil_boite', 'us_avis_accueil_nonce');
        printf(
            '<label><input type="checkbox" name="us_avis_accueil" value="1" %s> Afficher cet avis sur la page d\'accueil</label><p class="description">L\'accueil montre les %d avis choisis les plus récents. Le texte de l\'avis n\'est jamais modifié.</p>',
            checked(avis_sur_accueil((int) $avis->comment_ID), true, false), AVIS_ACCUEIL
        );
    }, 'comment', 'normal');
});
add_action('edit_comment', function ($avis) {
    if (isset($_POST['us_avis_accueil_nonce']) && wp_verify_nonce($_POST['us_avis_accueil_nonce'], 'us_avis_accueil_boite')) {
        update_comment_meta($avis, '_us_accueil', empty($_POST['us_avis_accueil']) ? '0' : '1');
    }
});

/*
|--------------------------------------------------------------------------
| E-mail « commande terminée » : invitation à donner son avis
|--------------------------------------------------------------------------
*/
add_action('woocommerce_email_after_order_table', function ($commande, $pourAdmin, $texteBrut, $email) {
    if ($pourAdmin || ! $email || $email->id !== 'customer_completed_order') {
        return;
    }
    $liens = [];
    foreach ($commande->get_items() as $ligne) {
        $produit = wc_get_product($ligne->get_product_id());
        if ($produit && $produit->get_status() === 'publish') {
            $liens[$produit->get_id()] = [$produit->get_name(), $produit->get_permalink() . '#avis'];
        }
    }
    if (! $liens) {
        return;
    }
    $intro = 'Quand vous aurez essayé vos produits, dites-nous ce que vous en pensez : quelques lignes suffisent, et elles aident vraiment les autres à choisir. Connectez-vous à votre compte, puis ouvrez la fiche du produit :';
    if ($texteBrut) {
        echo "\nVotre avis compte\n" . $intro . "\n";
        foreach ($liens as [$nom, $url]) {
            echo "- {$nom} : {$url}\n";
        }

        return;
    }
    echo '<h2>Votre avis compte</h2><p>' . esc_html($intro) . '</p><ul>';
    foreach ($liens as [$nom, $url]) {
        printf('<li><a href="%s">%s</a></li>', esc_url($url), esc_html($nom));
    }
    echo '</ul>';
}, 20, 4);

/*
|--------------------------------------------------------------------------
| Après l'envoi d'un avis : retour sur la fiche, avec un message
|--------------------------------------------------------------------------
*/
add_filter('comment_post_redirect', function ($adresse, $avis) {
    if ($avis->comment_type === 'review') {
        return add_query_arg('avis', 'recu', get_permalink($avis->comment_post_ID)) . '#avis';
    }

    return $adresse;
}, 10, 2);
