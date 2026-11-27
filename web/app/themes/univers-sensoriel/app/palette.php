<?php

/**
 * Palette de teintes sur la fiche produit : rouges et encres à lèvres, crayons, fonds de teint, sticks…
 *
 * Chaque teinte (pa_teinte) ou senteur (pa_senteur) garde sa couleur et son code (métas de terme us_couleur, us_code,
 * posées par l'import, modifiables dans Produits › Attributs). Le <select> de WooCommerce reçoit la palette en
 * data-palette : le script de la fiche (resources/js/app.js) en fait des pastilles rondes, avec le nom de la teinte
 * choisie au-dessus, et montre sa photo.
 */

namespace App;

/** Attributs dont les valeurs peuvent avoir une couleur. */
const ATTRIBUTS_PALETTE = ['pa_teinte', 'pa_senteur'];

add_filter('woocommerce_dropdown_variation_attribute_options_html', function ($html, $args) {
    $taxonomie = $args['attribute'] ?? '';
    $produit = $args['product'] ?? null;
    if (! in_array($taxonomie, ATTRIBUTS_PALETTE, true) || ! $produit instanceof \WC_Product) {
        return $html;
    }
    $palette = [];
    foreach (wc_get_product_terms($produit->get_id(), $taxonomie, ['fields' => 'all']) as $terme) {
        $palette[$terme->slug] = [
            'couleur' => (string) get_term_meta($terme->term_id, 'us_couleur', true),
            'code' => (string) get_term_meta($terme->term_id, 'us_code', true),
            'nom' => $terme->name,
        ];
    }
    // Sans aucune couleur (senteurs d'un sérum…) : les boutons texte habituels.
    if (! array_filter(array_column($palette, 'couleur'))) {
        return $html;
    }

    return preg_replace('/<select\b/', '<select data-palette="' . esc_attr(wp_json_encode($palette)) . '"', $html, 1);
}, 10, 2);

/** Couleur d'une teinte dans l'écran des attributs (Produits › Attributs › Teinte) : champ « Couleur » et « Code ». */
foreach (ATTRIBUTS_PALETTE as $taxonomie) {
    add_action("{$taxonomie}_edit_form_fields", function ($terme) {
        foreach (['us_couleur' => ['Couleur', 'Exemple : #B85C5C. Vide : bouton texte.'], 'us_code' => ['Code', 'Référence de la teinte, exemple : MM070.']] as $cle => [$libelle, $aide]) {
            printf(
                '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td><input name="%1$s" id="%1$s" type="text" value="%3$s"><p class="description">%4$s</p></td></tr>',
                esc_attr($cle), esc_html($libelle), esc_attr((string) get_term_meta($terme->term_id, $cle, true)), esc_html($aide)
            );
        }
    });
    add_action("edited_{$taxonomie}", function ($id) {
        if (! current_user_can('manage_product_terms') || ! isset($_POST['us_couleur'])) {
            return;
        }
        $couleur = sanitize_hex_color(wp_unslash($_POST['us_couleur'])) ?: '';
        update_term_meta($id, 'us_couleur', $couleur);
        update_term_meta($id, 'us_code', sanitize_text_field(wp_unslash($_POST['us_code'] ?? '')));
    });
}
