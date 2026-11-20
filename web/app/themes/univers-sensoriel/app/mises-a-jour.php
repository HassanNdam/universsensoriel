<?php

/**
 * Mises à jour ponctuelles de la base, appliquées d'elles-mêmes au premier passage après l'envoi du thème.
 *
 * La base en ligne fait foi (commandes, clients, réglages saisis dans l'administration) : on n'y réimporte jamais
 * la base locale. Quand une évolution du site touche aussi des contenus enregistrés (réglages, pages, SEO,
 * traductions), elle est décrite ici. Chaque mise à jour est notée dans l'option us_mises_a_jour et ne repasse plus.
 * Un texte n'est remplacé que s'il vaut encore sa valeur d'origine : ce qui a été retouché dans l'administration
 * n'est pas écrasé.
 */

namespace App;

/** Nom de la mise à jour => fonction qui l'applique (idempotente). */
const MISES_A_JOUR = [
    'boutique-unique' => __NAMESPACE__ . '\\maj_boutique_unique',
];

add_action('init', function (): void {
    $faites = (array) get_option('us_mises_a_jour', []);
    $restantes = array_diff_key(MISES_A_JOUR, array_flip($faites));
    if (! $restantes || ! function_exists('update_field')) {
        return;
    }
    foreach ($restantes as $nom => $fonction) {
        $fonction();
        $faites[] = $nom;
    }
    update_option('us_mises_a_jour', array_values(array_unique($faites)), false);
}, 99);

/** Remplace un texte enregistré seulement s'il vaut encore l'ancienne valeur. */
function maj_option(string $option, string $ancien, string $nouveau): void
{
    if (get_option($option) === $ancien) {
        update_option($option, $nouveau);
    }
}

/** Méta Yoast d'une page : remplacée si elle vaut l'ancienne valeur ; l'ombre du seed (scripts/seo.php) suit. */
function maj_meta(int $id, string $meta, string $ancien, string $nouveau): void
{
    if ((string) get_post_meta($id, $meta, true) === $ancien) {
        update_post_meta($id, $meta, $nouveau);
        update_post_meta($id, '_us_seo_auto' . $meta, $nouveau);
    }
}

/**
 * 20/11/2026 : la boutique de Yaoundé (Style Mondial, Cameroun) a fermé, seule reste celle de Metz.
 * Retire Yaoundé des boutiques et des textes, renomme la page « Nos boutiques » en « Notre boutique »
 * (/notre-boutique/, l'ancienne adresse y renvoie) et ajoute les traductions anglaises des nouveaux textes.
 */
function maj_boutique_unique(): void
{
    // Boutiques (Univers Sensoriel › Coordonnées) : la ligne de Yaoundé disparaît, les autres restent telles quelles.
    $boutiques = (array) get_field('boutiques', 'option');
    $gardees = array_values(array_filter($boutiques, fn ($b) => stripos(remove_accents((string) ($b['ville'] ?? '')), 'yaounde') === false));
    if (count($gardees) !== count($boutiques)) {
        update_field('field_us_boutiques', $gardees, 'option');
    }

    // Textes des réglages.
    maj_option('options_conseil_lien_boutiques', 'Nos boutiques', 'Notre boutique');
    maj_option(
        'options_attente_texte',
        'Extraits de parfum, soins et senteurs pour la maison. En attendant, nos boutiques de Metz et Yaoundé vous accueillent.',
        'Extraits de parfum, soins et senteurs pour la maison. En attendant, notre boutique de Metz vous accueille.'
    );
    maj_option(
        'options_attente_seo_description',
        'Univers Sensoriel ouvre bientôt sa boutique en ligne : extraits de parfum, soins et senteurs pour la maison. Boutiques ouvertes à Metz et Yaoundé.',
        'Univers Sensoriel ouvre bientôt sa boutique en ligne : extraits de parfum, soins et senteurs pour la maison. Boutique ouverte à Metz.'
    );
    for ($i = 0, $n = (int) get_option('options_maison_points', 0); $i < $n; $i++) {
        maj_option("options_maison_points_{$i}_texte", 'Deux boutiques : Metz et Yaoundé', 'Notre boutique à Metz');
    }

    // Page « Nos boutiques » → « Notre boutique ». Les métas d'abord : l'enregistrement de la page met Yoast à jour.
    $page = get_page_by_path('nos-boutiques') ?: get_page_by_path('notre-boutique');
    if ($page) {
        maj_meta($page->ID, '_yoast_wpseo_title', 'Nos boutiques à Metz et à Yaoundé | Univers Sensoriel', 'Notre boutique à Metz | Univers Sensoriel');
        maj_meta(
            $page->ID,
            '_yoast_wpseo_metadesc',
            'Venez sentir les parfums et demander conseil dans nos boutiques de Metz (France) et de Yaoundé (Cameroun). Retrait gratuit des commandes à Metz.',
            'Venez sentir les parfums et demander conseil dans notre boutique de Metz, 57 rue des Allemands. Retrait gratuit des commandes passées sur le site.'
        );
        maj_meta($page->ID, '_yoast_wpseo_focuskw', 'boutiques', 'boutique Metz');
        wp_update_post(wp_slash([
            'ID' => $page->ID,
            'post_name' => 'notre-boutique',
            'post_title' => $page->post_title === 'Nos boutiques' ? 'Notre boutique' : $page->post_title,
            'post_content' => str_replace(
                '<p>Deux boutiques pour sentir, essayer et demander conseil.</p>',
                '<p>Venez sentir les parfums, les essayer et demander conseil.</p>',
                $page->post_content
            ),
        ]));
    }

    // Page « La Maison ».
    if ($maison = get_page_by_path('la-maison')) {
        maj_meta(
            $maison->ID,
            '_yoast_wpseo_metadesc',
            'Univers Sensoriel by K.Elle : une Maison de parfums, de soins et de bien-être née d\'une conviction, le luxe discret pour tous. Boutiques à Metz et Yaoundé.',
            'Univers Sensoriel by K.Elle : une Maison de parfums, de soins et de bien-être née d\'une conviction, le luxe discret pour tous. Boutique à Metz.'
        );
        $texte = str_replace(
            '<p>Deux boutiques, à Metz et à Yaoundé. <a href="/nos-boutiques/">Voir nos boutiques</a></p>',
            '<p>Notre boutique est à Metz. <a href="/notre-boutique/">Adresse et horaires</a></p>',
            $maison->post_content
        );
        if ($texte !== $maison->post_content) {
            wp_update_post(wp_slash(['ID' => $maison->ID, 'post_content' => $texte]));
        }
    }

    maj_traductions(TRADUCTIONS_BOUTIQUE_UNIQUE);
}

/** Textes anglais des nouveaux textes (aussi dans scripts/traductions/en_GB.json, versé par le seed). */
const TRADUCTIONS_BOUTIQUE_UNIQUE = [
    'Notre boutique' => 'Our boutique',
    '&lt;trp-post-container data-trp-post-id=&#039;1365&#039;&gt;Notre boutique&lt;/trp-post-container&gt;' => '&lt;trp-post-container data-trp-post-id=&#039;1365&#039;&gt;Our boutique&lt;/trp-post-container&gt;',
    'Notre boutique à Metz' => 'Our boutique in Metz',
    'Notre boutique est à Metz.' => 'Our boutique is in Metz.',
    'Adresse et horaires' => 'Address and opening hours',
    'Horaires et retrait' => 'Opening hours and collection',
    'Venez sentir les parfums, les essayer et demander conseil.' => 'Come and smell the perfumes, try them and ask for advice.',
    'Extraits de parfum, soins et senteurs pour la maison. En attendant, notre boutique de Metz vous accueille.' => 'Extraits de parfum, skincare and home fragrances. In the meantime, our boutique in Metz welcomes you.',
    'Univers Sensoriel ouvre bientôt sa boutique en ligne : extraits de parfum, soins et senteurs pour la maison. Boutique ouverte à Metz.' => 'Univers Sensoriel is opening its online boutique soon: extraits de parfum, skincare and home fragrances. Boutique open in Metz.',
    'Univers Sensoriel by K.Elle : une Maison de parfums, de soins et de bien-être née d\'une conviction, le luxe discret pour tous. Boutique à Metz.' => 'Univers Sensoriel by K.Elle: a House of perfume, skincare and wellbeing born of one belief, discreet luxury for all. Boutique in Metz.',
    'Venez sentir les parfums et demander conseil dans notre boutique de Metz, 57 rue des Allemands. Retrait gratuit des commandes passées sur le site.' => 'Come and smell the perfumes and ask for advice in our boutique in Metz, 57 rue des Allemands. Free collection of orders placed on the site.',
];

/** Ajoute des traductions anglaises dans TranslatePress, sans toucher à une traduction déjà saisie. */
function maj_traductions(array $traductions): void
{
    if (! class_exists('TRP_Translate_Press') || ! class_exists('TRP_Query')) {
        return;
    }
    global $wpdb;
    $requete = \TRP_Translate_Press::get_trp_instance()->get_component('query');
    $table = $requete->get_table_name('en_GB');
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        return;
    }
    // Chaque texte aussi sous sa forme échappée (« d'une » / « d&#039;une ») : TranslatePress compare le HTML tel quel.
    foreach ($traductions as $fr => $en) {
        $traductions[esc_html($fr)] ??= $en;
    }
    $connus = array_flip((array) $wpdb->get_col("SELECT original FROM `$table`"));
    if ($nouveaux = array_values(array_filter(array_keys($traductions), fn ($t) => ! isset($connus[$t])))) {
        $requete->insert_strings($nouveaux, 'en_GB');
    }
    foreach ($traductions as $fr => $en) {
        $wpdb->query($wpdb->prepare(
            "UPDATE `$table` SET translated = %s, status = %d WHERE original = %s AND (translated IS NULL OR translated = '' OR status = 0)",
            $en,
            \TRP_Query::HUMAN_REVIEWED,
            $fr,
        ));
    }
}
