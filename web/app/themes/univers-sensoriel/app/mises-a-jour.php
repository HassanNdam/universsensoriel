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
    'descriptions-partage' => __NAMESPACE__ . '\\maj_descriptions_partage',
    'partage-attente' => __NAMESPACE__ . '\\maj_partage_attente',
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

/**
 * 20/11/2026 : description de partage (méta-description, Open Graph, Twitter) des adresses qui n'en avaient pas.
 * Les mêmes textes sont dans scripts/seo.json (pages, rayons, rubriques) et scripts/traductions/en_GB.json.
 * Une description déjà saisie dans Yoast n'est pas touchée.
 */
function maj_descriptions_partage(): void
{
    if (! class_exists('WPSEO_Taxonomy_Meta')) {
        return;
    }
    $pages = [
        'politique-de-cookies' => ['Politique de cookies | Univers Sensoriel', 'Cookies du site Univers Sensoriel by K.Elle : cookies nécessaires au panier et au compte, mesure d\'audience, durée de conservation et vos choix.'],
        'panier' => ['', 'Votre panier Univers Sensoriel : extraits de parfum, soins et bien-être. Paiement sécurisé, livraison en France ou retrait gratuit à Metz.'],
        'commande' => ['', 'Finaliser votre commande Univers Sensoriel : livraison en France ou retrait gratuit à la boutique de Metz, paiement sécurisé.'],
        'mon-compte' => ['', 'Votre compte Univers Sensoriel : suivi de vos commandes, adresses de livraison et de facturation, détails du compte et mot de passe.'],
    ];
    $termes = [
        'journal/bien-etre' => ['category', 'bien-etre', 'Conseils bien-être du journal Univers Sensoriel : huiles essentielles pour vos rituels, collagène à boire et gestes simples pour prendre soin de soi.'],
        'journal/cheveux' => ['category', 'cheveux', 'Conseils cheveux du journal Univers Sensoriel : routine capillaire en neuf gestes, pellicules et cuir chevelu, pour des cheveux forts et brillants.'],
        'journal/parfum' => ['category', 'parfum', 'Conseils parfum du journal Univers Sensoriel : faire tenir son parfum toute la journée, les bons gestes pour l\'appliquer et le conserver.'],
        'journal/soin-de-la-peau' => ['category', 'soin-de-la-peau', 'Conseils soin de la peau du journal Univers Sensoriel : reconnaître son type de peau, nettoyer son visage, choisir des soins aux peptides.'],
        'accessoires-parfum' => ['product_cat', 'accessoires-parfum', 'Accessoires parfum Univers Sensoriel : de quoi emporter votre extrait de parfum partout et le garder intact, en voyage comme au quotidien.'],
    ];
    $construire = YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
    $depot = YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);

    foreach ($pages as $slug => [$titre, $description]) {
        if (! $page = get_page_by_path($slug)) {
            continue;
        }
        foreach (['_yoast_wpseo_title' => $titre, '_yoast_wpseo_metadesc' => $description] as $meta => $valeur) {
            if ($valeur !== '' && (string) get_post_meta($page->ID, $meta, true) === '') {
                update_post_meta($page->ID, $meta, $valeur);
                update_post_meta($page->ID, '_us_seo_auto' . $meta, $valeur);
            }
        }
        $construire->build_for_id_and_type($page->ID, 'post', $depot->find_by_id_and_type($page->ID, 'post', false));
    }

    // Ombre du seed (scripts/seo.php) : la valeur posée ici ne doit pas passer pour une retouche dans l'admin.
    $ombres = (array) get_option('us_seo_auto_rayons', []);
    foreach ($termes as $cle => [$taxonomie, $slug, $description]) {
        $terme = get_term_by('slug', $slug, $taxonomie);
        if (! $terme) {
            continue;
        }
        $actuel = (array) \WPSEO_Taxonomy_Meta::get_term_meta($terme, $taxonomie);
        if (($actuel['wpseo_desc'] ?? '') === '') {
            \WPSEO_Taxonomy_Meta::set_values($terme->term_id, $taxonomie, array_merge(array_filter($actuel, 'is_string'), ['wpseo_desc' => $description]));
            $ombres[$cle] = array_merge((array) ($ombres[$cle] ?? []), ['wpseo_desc' => $description]);
        }
        $construire->build_for_id_and_type($terme->term_id, 'term', $depot->find_by_id_and_type($terme->term_id, 'term', false));
    }
    update_option('us_seo_auto_rayons', $ombres, false);

    maj_traductions([
        'Politique de cookies' => 'Cookie policy',
        'Cookies du site Univers Sensoriel by K.Elle : cookies nécessaires au panier et au compte, mesure d\'audience, durée de conservation et vos choix.' => 'Cookies on the Univers Sensoriel by K.Elle website: cookies needed for the basket and account, audience measurement, retention period and your choices.',
        'Votre panier Univers Sensoriel : extraits de parfum, soins et bien-être. Paiement sécurisé, livraison en France ou retrait gratuit à Metz.' => 'Your Univers Sensoriel basket: extraits de parfum, skincare and wellbeing. Secure payment, delivery in France or free collection in Metz.',
        'Finaliser votre commande Univers Sensoriel : livraison en France ou retrait gratuit à la boutique de Metz, paiement sécurisé.' => 'Complete your Univers Sensoriel order: delivery in France or free collection from the Metz boutique, secure payment.',
        'Votre compte Univers Sensoriel : suivi de vos commandes, adresses de livraison et de facturation, détails du compte et mot de passe.' => 'Your Univers Sensoriel account: order tracking, delivery and billing addresses, account details and password.',
        'Conseils bien-être du journal Univers Sensoriel : huiles essentielles pour vos rituels, collagène à boire et gestes simples pour prendre soin de soi.' => 'Wellbeing advice from the Univers Sensoriel journal: essential oils for your rituals, drinkable collagen and simple ways to take care of yourself.',
        'Conseils cheveux du journal Univers Sensoriel : routine capillaire en neuf gestes, pellicules et cuir chevelu, pour des cheveux forts et brillants.' => 'Hair advice from the Univers Sensoriel journal: a nine-step hair routine, dandruff and scalp care, for strong and shiny hair.',
        'Conseils parfum du journal Univers Sensoriel : faire tenir son parfum toute la journée, les bons gestes pour l\'appliquer et le conserver.' => 'Perfume advice from the Univers Sensoriel journal: making your perfume last all day, how to apply it and how to store it.',
        'Conseils soin de la peau du journal Univers Sensoriel : reconnaître son type de peau, nettoyer son visage, choisir des soins aux peptides.' => 'Skincare advice from the Univers Sensoriel journal: identifying your skin type, cleansing your face and choosing peptide skincare.',
        'Accessoires parfum Univers Sensoriel : de quoi emporter votre extrait de parfum partout et le garder intact, en voyage comme au quotidien.' => 'Univers Sensoriel perfume accessories: everything to take your extrait de parfum anywhere and keep it intact, travelling or every day.',
    ]);
}

/**
 * 20/11/2026 : la page d'attente partageait le logo (PNG transparent 1452 × 716, rogné dans les aperçus).
 * Le réglage est vidé s'il pointe encore sur le logo : le visuel d'origine reprend sa place
 * (resources/images/partage-bientot-en-ligne.jpg, 1200 × 630). Une autre image choisie n'est pas touchée.
 */
function maj_partage_attente(): void
{
    $id = (int) get_option('options_attente_seo_image');
    if ($id && str_starts_with(basename((string) get_attached_file($id)), 'logo-univers-sensoriel')) {
        update_option('options_attente_seo_image', '');
    }
}
