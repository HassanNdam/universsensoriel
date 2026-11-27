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
    'livraison-retrait-metz' => __NAMESPACE__ . '\\maj_livraison_retrait_metz',
    'offres-documentation-2026-11' => __NAMESPACE__ . '\\maj_offres_documentation',
    'nouveautes-2026-11' => __NAMESPACE__ . '\\maj_nouveautes',
];

add_action('init', function (): void {
    $faites = (array) get_option('us_mises_a_jour', []);
    $restantes = array_diff_key(MISES_A_JOUR, array_flip($faites));
    if (! $restantes || ! function_exists('update_field')) {
        return;
    }
    foreach ($restantes as $nom => $fonction) {
        // false : pas encore possible (extension inactive…), elle repassera au prochain chargement.
        if ($fonction() !== false) {
            $faites[] = $nom;
        }
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

/**
 * Zones et tarifs de livraison (WooCommerce › Réglages › Expédition), créés s'ils manquent :
 * - France : point relais ou consigne 8 €, offert dès 79 € ; à domicile 12,90 € ; retrait gratuit chez les partenaires de Metz ;
 * - Europe (pays de vente européens) : point relais 14,90 €, à domicile 19,90 €.
 * Un tarif n'est changé que s'il vaut encore l'ancienne valeur (domicile 4,90 €, offerte dès 49 €).
 * Appelée par la mise à jour « livraison-retrait-metz » et par le seed (installation neuve).
 */
function maj_livraison(): bool
{
    if (! class_exists('WC_Shipping_Zones')) {
        return false;
    }
    $zones = [
        'France' => [['FR'], [
            // L'offre gratuite d'abord : dès le seuil, elle remplace le point relais payant (app/woocommerce.php).
            ['free_shipping', '', ['title' => 'Point relais ou consigne offert', 'requires' => 'min_amount', 'min_amount' => '79']],
            ['flat_rate', 'relais', ['title' => 'Point relais ou consigne', 'tax_status' => 'none', 'cost' => '8']],
            ['flat_rate', 'domicile', ['title' => 'Livraison à domicile', 'tax_status' => 'none', 'cost' => '12.90']],
            ['local_pickup', '', ['title' => 'Retrait chez nos partenaires à Metz', 'tax_status' => 'none', 'cost' => '']],
        ]],
        'Europe' => [['DE', 'BE', 'IT', 'LU', 'NL', 'CH'], [
            ['flat_rate', 'relais', ['title' => 'Point relais ou consigne', 'tax_status' => 'none', 'cost' => '14.90']],
            ['flat_rate', 'domicile', ['title' => 'Livraison à domicile', 'tax_status' => 'none', 'cost' => '19.90']],
        ]],
    ];
    // Anciennes valeurs d'origine remplacées : [méthode, réglage, ancienne valeur, nouvelle].
    $anciennes = [
        ['flat_rate', 'cost', '4.90', '12.90'],
        ['free_shipping', 'min_amount', '49', '79'],
        ['free_shipping', 'title', 'Livraison offerte', 'Point relais ou consigne offert'],
        ['local_pickup', 'title', 'Retrait à la boutique de Metz', 'Retrait chez nos partenaires à Metz'],
    ];
    global $wpdb;
    $existantes = [];
    foreach (\WC_Shipping_Zones::get_zones() as $donnees) {
        $existantes[$donnees['zone_name']] = new \WC_Shipping_Zone($donnees['id']);
    }
    foreach ($zones as $nom => [$pays, $modes]) {
        $zone = $existantes[$nom] ?? null;
        if (! $zone) {
            $zone = new \WC_Shipping_Zone();
            $zone->set_zone_name($nom);
            foreach ($pays as $code) {
                $zone->add_location($code, 'country');
            }
            $zone->save();
        }
        $ordre = 1;
        foreach ($modes as [$type, $mot, $reglages]) {
            $trouve = null;
            foreach ($zone->get_shipping_methods() as $methode) {
                if ($methode->id === $type && ($mot === '' || stripos(remove_accents($methode->get_title()), $mot) !== false)) {
                    $trouve = $methode;
                    break;
                }
            }
            if (! $trouve) {
                $id = $zone->add_shipping_method($type);
                update_option("woocommerce_{$type}_{$id}_settings", $reglages);
                $instance = $id;
            } else {
                $actuels = (array) get_option($trouve->get_instance_option_key(), []);
                foreach ($anciennes as [$methodeType, $cle, $ancienne, $nouvelle]) {
                    if ($trouve->id === $methodeType && isset($actuels[$cle]) && (string) $actuels[$cle] === $ancienne
                        && ($methodeType !== 'flat_rate' || $mot === 'domicile')) {
                        $actuels[$cle] = $nouvelle;
                    }
                }
                update_option($trouve->get_instance_option_key(), $actuels);
                $instance = $trouve->get_instance_id();
            }
            $wpdb->update("{$wpdb->prefix}woocommerce_shipping_zone_methods", ['method_order' => $ordre++], ['instance_id' => $instance]);
        }
    }
    \WC_Cache_Helper::get_transient_version('shipping', true);

    return true;
}

/**
 * 25/11/2026 : retours de la promotrice. Livraison offerte dès 79 € (point relais), point relais et domicile
 * en France, Europe ; retrait chez les partenaires de Metz (Borny, gare) au lieu de « la boutique », avec son numéro ;
 * délais (72 h, retrait sous 24 h) ; engagement qualité (Italie, CPNP).
 */
function maj_livraison_retrait_metz(): bool
{
    if (! maj_livraison()) {
        return false;
    }
    $donnees = json_decode(DONNEES_LIVRAISON, true);

    // Points de retrait : la boutique Kacy Afro Center laisse la place à Borny et à la gare.
    $boutiques = (array) get_field('boutiques', 'option');
    if (count($boutiques) === 1 && ($boutiques[0]['enseigne'] ?? '') === 'Kacy Afro Center') {
        $point = fn ($nom, $adresse) => ['ville' => 'Metz', 'pays' => 'France', 'enseigne' => $nom, 'adresse' => $adresse,
            'telephone' => '+33 6 05 78 40 11', 'telephone_2' => '', 'horaires' => [['jours' => 'Retrait', 'heures' => '8 h – 17 h']],
            'mention' => 'Point de retrait partenaire. Gratuit, sous 24 h en général.'];
        update_field('field_us_boutiques', [
            $point('Borny', 'Place du Marché, 57070 Metz'),
            $point('Gare de Metz', '1 place du Général-de-Gaulle, 57000 Metz'),
        ], 'option');
    }

    // Textes des réglages.
    maj_option('options_conseil_texte', 'En boutique ou par message, nous vous aidons à choisir.', 'Par WhatsApp ou par e-mail, nous vous aidons à choisir.');
    maj_option('options_contact_boutiques', 'En boutique', 'Retrait à Metz');
    maj_option('options_bandeau_2', 'Retrait gratuit à la boutique de Metz', 'Retrait gratuit à Metz, sous 24 h');
    maj_option('options_produit_livraison', 'Livraison offerte dès {seuil} € d\'achat en France, ou retrait gratuit à la boutique de Metz.',
        'Livraison en 72 h en général, offerte en point relais dès {seuil} € d\'achat en France. Retrait gratuit à Metz, sous 24 h.');
    for ($i = 0, $n = (int) get_option('options_maison_points', 0); $i < $n; $i++) {
        maj_option("options_maison_points_{$i}_texte", 'Notre boutique à Metz', 'Retrait gratuit à Metz');
    }
    for ($i = 0, $n = (int) get_option('options_garanties', 0); $i < $n; $i++) {
        if (get_option("options_garanties_{$i}_titre") === 'Retrait en boutique') {
            update_option("options_garanties_{$i}_titre", 'Retrait à Metz');
            maj_option("options_garanties_{$i}_texte", 'Gratuit, à Metz', 'Gratuit, sous 24 h');
        }
    }

    // Pages : seuls les passages d'origine sont remplacés (un texte retouché dans l'éditeur reste tel quel).
    foreach ($donnees['pages'] as $slug => $paires) {
        if (! $page = get_page_by_path($slug)) {
            continue;
        }
        $texte = $page->post_content;
        $nl = str_contains($texte, "\r\n") ? "\r\n" : "\n";
        foreach ($paires as [$ancien, $nouveau]) {
            // Une seule fois : le nouveau texte reprend parfois l'ancien (paragraphe ajouté devant).
            $nouveau = str_replace("\n", $nl, $nouveau);
            if (! str_contains($texte, $nouveau)) {
                $texte = str_replace(str_replace("\n", $nl, $ancien), $nouveau, $texte);
            }
        }
        if ($texte !== $page->post_content) {
            wp_update_post(wp_slash(['ID' => $page->ID, 'post_content' => $texte]));
        }
    }

    // Référencement : page boutique et page commande, puis « dès 49 € » devenu « dès 79 € » partout où il est écrit.
    foreach ($donnees['seo'] as $slug => $champs) {
        if ($page = get_page_by_path($slug)) {
            foreach ($champs as $champ => [$ancien, $nouveau]) {
                maj_meta($page->ID, $champ === 'titre' ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc', $ancien, $nouveau);
            }
        }
    }
    global $wpdb;
    $metas = $wpdb->get_results("SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta}
        WHERE meta_key IN ('_yoast_wpseo_metadesc', '_us_seo_auto_yoast_wpseo_metadesc', '_yoast_wpseo_title', '_us_seo_auto_yoast_wpseo_title')
        AND meta_value LIKE '%dès 49 €%'");
    foreach ($metas as $meta) {
        update_post_meta($meta->post_id, $meta->meta_key, str_replace('dès 49 €', 'dès 79 €', $meta->meta_value));
    }
    $termes = (array) get_option('wpseo_taxonomy_meta', []);
    array_walk_recursive($termes, function (&$valeur) {
        $valeur = is_string($valeur) ? str_replace('dès 49 €', 'dès 79 €', $valeur) : $valeur;
    });
    update_option('wpseo_taxonomy_meta', $termes);
    $ombres = (array) get_option('us_seo_auto_rayons', []);
    array_walk_recursive($ombres, function (&$valeur) {
        $valeur = is_string($valeur) ? str_replace('dès 49 €', 'dès 79 €', $valeur) : $valeur;
    });
    update_option('us_seo_auto_rayons', $ombres, false);
    // Yoast garde une copie des descriptions (« indexables ») : elle sera refaite au prochain affichage.
    $wpdb->query("UPDATE {$wpdb->prefix}yoast_indexable SET description = REPLACE(description, 'dès 49 €', 'dès 79 €') WHERE description LIKE '%dès 49 €%'");

    // Traductions anglaises : les nouveaux textes, et chaque texte traduit « dès 49 € » repris en « dès 79 € ».
    $anglais = $donnees['en'];
    if (class_exists('TRP_Translate_Press')) {
        $table = \TRP_Translate_Press::get_trp_instance()->get_component('query')->get_table_name('en_GB');
        foreach ((array) $wpdb->get_results("SELECT original, translated FROM `$table` WHERE original LIKE '%49 €%' AND translated <> ''") as $ligne) {
            $anglais[str_replace('49 €', '79 €', $ligne->original)] ??= str_replace(['€49', '49 €'], ['€79', '79 €'], $ligne->translated);
        }
    }
    maj_traductions($anglais);

    return true;
}

/** Textes de la mise à jour « livraison-retrait-metz » : pages (ancien => nouveau), SEO et anglais. */
const DONNEES_LIVRAISON = <<<'JSON'
{
 "pages": {
  "livraison-et-retours": [
   [
    "<li><strong>À domicile, en France.</strong> [us_forfait] €, offerte dès [us_seuil] € d'achat.</li>",
    "<li><strong>Point relais ou consigne, en France.</strong> [us_forfait type=\"relais\" zone=\"France\"] €, offert dès [us_seuil] € d'achat. Livraison en 72 h en général.</li>\n<!-- /wp:list-item --><!-- wp:list-item -->\n<li><strong>À domicile, en France.</strong> [us_forfait type=\"domicile\" zone=\"France\"] €. Livraison en 72 h en général.</li>\n<!-- /wp:list-item --><!-- wp:list-item -->\n<li><strong>En Europe.</strong> Allemagne, Belgique, Italie, Luxembourg, Pays-Bas et Suisse : point relais [us_forfait type=\"relais\" zone=\"Europe\"] €, à domicile [us_forfait type=\"domicile\" zone=\"Europe\"] €. Livraison en 3 à 7 jours ouvrés en général.</li>"
   ],
   [
    "<li><strong>Retrait à la boutique de Metz.</strong> Gratuit. Nous vous prévenons dès que la commande est prête.</li>",
    "<li><strong>Retrait chez nos partenaires à Metz.</strong> Gratuit, sous 24 h en général, à Borny ou à la gare. <a href=\"/notre-boutique/\">Adresses et horaires</a></li>"
   ],
   [
    "<p>Vous recevez un e-mail à la commande, puis à l'expédition. Les délais annoncés sont des estimations : ils peuvent varier en raison de circonstances imprévues.</p>",
    "<p>En point relais ou en consigne, nous choisissons le point le plus proche de votre adresse : son adresse vous est envoyée avec le numéro de suivi. Une préférence ? Indiquez-la dans la note de commande.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Vous recevez un e-mail à la commande, puis à l'expédition. Les délais annoncés sont des estimations : ils peuvent varier en raison de circonstances imprévues.</p>"
   ],
   [
    "<p>Tous les produits sont neufs, authentiques et sélectionnés avec exigence.</p>",
    "<p>Tous les produits sont neufs, authentiques et fabriqués en Italie. Chaque cosmétique est conforme au règlement européen et notifié sur le portail européen CPNP, obligatoire pour sa mise sur le marché.</p>"
   ]
  ],
  "conditions-generales-de-vente": [
   [
    "<p>Nous livrons à domicile en France : [us_forfait] €, offert dès [us_seuil] € d'achat. Le retrait à la boutique de Metz est gratuit.</p>",
    "<p>Nous livrons en France en point relais ou en consigne ([us_forfait type=\"relais\" zone=\"France\"] €, offert dès [us_seuil] € d'achat) ou à domicile ([us_forfait type=\"domicile\" zone=\"France\"] €), ainsi qu'en Allemagne, Belgique, Italie, Luxembourg, aux Pays-Bas et en Suisse. Le retrait chez nos partenaires à Metz est gratuit. Tarifs et délais : <a href=\"/livraison-et-retours/\">Livraison et retours</a>.</p>"
   ]
  ],
  "questions-frequentes": [
   [
    "<p>Dès [us_seuil] € d'achat, en France. Le retrait à la boutique de Metz est toujours gratuit.</p>",
    "<p>Dès [us_seuil] € d'achat en France, en point relais ou en consigne. Le retrait chez nos partenaires à Metz est toujours gratuit.</p>"
   ]
  ],
  "notre-boutique": [
   [
    "<p>Venez sentir les parfums, les essayer et demander conseil.</p>",
    "<p>Boutique en ligne ouverte 24 h/24, 7 j/7. Retrait gratuit à Metz, chez nos partenaires de Borny et de la gare.</p>"
   ],
   [
    "<p>Choisissez « Retrait à la boutique de Metz » en validant la commande. Nous vous prévenons dès qu'elle est prête.</p>",
    "<p>Choisissez « Retrait chez nos partenaires à Metz » en validant la commande. Nous vous prévenons dès qu'elle est prête, sous 24 h en général, et convenons avec vous du point de retrait.</p>"
   ]
  ],
  "la-maison": [
   [
    "<p>Notre boutique est à Metz. <a href=\"/notre-boutique/\">Adresse et horaires</a></p>",
    "<p>Boutique en ligne, retrait gratuit à Metz. <a href=\"/notre-boutique/\">Adresses et horaires</a></p>"
   ]
  ],
  "politique-de-confidentialite": [
   [
    "organiser l'expédition ou le retrait en boutique",
    "organiser l'expédition ou le retrait à Metz"
   ]
  ]
 },
 "seo": {
  "notre-boutique": {
   "titre": [
    "Notre boutique à Metz | Univers Sensoriel",
    "Notre boutique et retrait à Metz | Univers Sensoriel"
   ],
   "description": [
    "Venez sentir les parfums et demander conseil dans notre boutique de Metz, 57 rue des Allemands. Retrait gratuit des commandes passées sur le site.",
    "Boutique en ligne ouverte 24 h/24, 7 j/7. Retrait gratuit à Metz, chez nos partenaires de Borny et de la gare, sous 24 h en général."
   ]
  },
  "commande": {
   "description": [
    "Finaliser votre commande Univers Sensoriel : livraison en France ou retrait gratuit à la boutique de Metz, paiement sécurisé.",
    "Finaliser votre commande Univers Sensoriel : point relais, domicile ou retrait gratuit chez nos partenaires à Metz, paiement sécurisé."
   ]
  }
 },
 "en": {
  "Retrait gratuit à Metz": "Free collection in Metz",
  "Par WhatsApp ou par e-mail, nous vous aidons à choisir.": "On WhatsApp or by email, we help you choose.",
  "Retrait à Metz": "Collection in Metz",
  "Gratuit, sous 24 h": "Free, within 24 hours",
  "Retrait gratuit à Metz, sous 24 h": "Free collection in Metz, within 24 hours",
  "Livraison en 72 h en général, offerte en point relais dès 79 € d'achat en France. Retrait gratuit à Metz, sous 24 h.": "Delivery usually within 72 hours, free to a pickup point from €79 in France. Free collection in Metz, within 24 hours.",
  "Gare de Metz": "Metz railway station",
  "Retrait": "Collection",
  "Retrait : 8 h – 17 h": "Collection: 8 am – 5 pm",
  "8 h – 17 h": "8 am – 5 pm",
  "Point de retrait partenaire. Gratuit, sous 24 h en général.": "Partner collection point. Free, usually within 24 hours.",
  "Prête au retrait": "Ready for collection",
  "Adresses et horaires": "Addresses and opening hours",
  "Point relais ou consigne": "Pickup point or locker",
  "Livraison à domicile": "Home delivery",
  "Point relais ou consigne offert": "Free pickup point or locker",
  "Retrait chez nos partenaires à Metz": "Collection from our partners in Metz",
  "Point relais ou consigne, en France.": "Pickup point or locker, in France.",
  "8 €, offert dès 79 € d'achat. Livraison en 72 h en général.": "€8, free from €79. Usually delivered within 72 hours.",
  "À domicile, en France.": "Home delivery, in France.",
  "12,90 €. Livraison en 72 h en général.": "€12.90. Usually delivered within 72 hours.",
  "En Europe.": "In Europe.",
  "Allemagne, Belgique, Italie, Luxembourg, Pays-Bas et Suisse : point relais 14,90 €, à domicile 19,90 €. Livraison en 3 à 7 jours ouvrés en général.": "Germany, Belgium, Italy, Luxembourg, the Netherlands and Switzerland: pickup point €14.90, home delivery €19.90. Usually delivered within 3 to 7 working days.",
  "Retrait chez nos partenaires à Metz.": "Collection from our partners in Metz.",
  "Gratuit, sous 24 h en général, à Borny ou à la gare.": "Free, usually within 24 hours, in Borny or at the railway station.",
  "En point relais ou en consigne, nous choisissons le point le plus proche de votre adresse : son adresse vous est envoyée avec le numéro de suivi. Une préférence ? Indiquez-la dans la note de commande.": "For pickup points and lockers, we choose the one closest to your address and send you its address with the tracking number. Have a preference? Add it in the order note.",
  "Tous les produits sont neufs, authentiques et fabriqués en Italie. Chaque cosmétique est conforme au règlement européen et notifié sur le portail européen CPNP, obligatoire pour sa mise sur le marché.": "All products are new, authentic and made in Italy. Every cosmetic product complies with EU regulations and is notified on the European CPNP portal, which is mandatory before it can be sold.",
  "Nous livrons en France en point relais ou en consigne (8 €, offert dès 79 € d'achat) ou à domicile (12,90 €), ainsi qu'en Allemagne, Belgique, Italie, Luxembourg, aux Pays-Bas et en Suisse. Le retrait chez nos partenaires à Metz est gratuit. Tarifs et délais :": "We deliver in France to a pickup point or locker (€8, free from €79) or to your home (€12.90), as well as to Germany, Belgium, Italy, Luxembourg, the Netherlands and Switzerland. Collection from our partners in Metz is free. Rates and delivery times:",
  "Dès 79 € d'achat en France, en point relais ou en consigne. Le retrait chez nos partenaires à Metz est toujours gratuit.": "From €79 in France, to a pickup point or locker. Collection from our partners in Metz is always free.",
  "Boutique en ligne ouverte 24 h/24, 7 j/7. Retrait gratuit à Metz, chez nos partenaires de Borny et de la gare.": "Online boutique open 24/7. Free collection in Metz, from our partners in Borny and at the railway station.",
  "Choisissez « Retrait chez nos partenaires à Metz » en validant la commande. Nous vous prévenons dès qu'elle est prête, sous 24 h en général, et convenons avec vous du point de retrait.": "Choose “Collection from our partners in Metz” when placing your order. We let you know as soon as it is ready, usually within 24 hours, and agree on the collection point with you.",
  "Boutique en ligne, retrait gratuit à Metz.": "Online boutique, free collection in Metz.",
  "Notre boutique et retrait à Metz": "Our boutique and collection in Metz",
  "Boutique en ligne ouverte 24 h/24, 7 j/7. Retrait gratuit à Metz, chez nos partenaires de Borny et de la gare, sous 24 h en général.": "Online boutique open 24/7. Free collection in Metz, from our partners in Borny and at the railway station, usually within 24 hours.",
  "Finaliser votre commande Univers Sensoriel : point relais, domicile ou retrait gratuit chez nos partenaires à Metz, paiement sécurisé.": "Complete your Univers Sensoriel order: pickup point, home delivery or free collection from our partners in Metz, secure payment.",
  "Livraison en 72 h en général, offerte en point relais dès 79 € d&rsquo;achat en France. Retrait gratuit à Metz, sous 24 h.": "Delivery usually within 72 hours, free to a pickup point from €79 in France. Free collection in Metz, within 24 hours.",
  "Livraison en 72 h en général, offerte en point relais dès 79 € d&#039;achat en France. Retrait gratuit à Metz, sous 24 h.": "Delivery usually within 72 hours, free to a pickup point from €79 in France. Free collection in Metz, within 24 hours.",
  "8 €, offert dès 79 € d&rsquo;achat. Livraison en 72 h en général.": "€8, free from €79. Usually delivered within 72 hours.",
  "8 €, offert dès 79 € d&#039;achat. Livraison en 72 h en général.": "€8, free from €79. Usually delivered within 72 hours.",
  "Nous livrons en France en point relais ou en consigne (8 €, offert dès 79 € d&rsquo;achat) ou à domicile (12,90 €), ainsi qu&rsquo;en Allemagne, Belgique, Italie, Luxembourg, aux Pays-Bas et en Suisse. Le retrait chez nos partenaires à Metz est gratuit. Tarifs et délais :": "We deliver in France to a pickup point or locker (€8, free from €79) or to your home (€12.90), as well as to Germany, Belgium, Italy, Luxembourg, the Netherlands and Switzerland. Collection from our partners in Metz is free. Rates and delivery times:",
  "Nous livrons en France en point relais ou en consigne (8 €, offert dès 79 € d&#039;achat) ou à domicile (12,90 €), ainsi qu&#039;en Allemagne, Belgique, Italie, Luxembourg, aux Pays-Bas et en Suisse. Le retrait chez nos partenaires à Metz est gratuit. Tarifs et délais :": "We deliver in France to a pickup point or locker (€8, free from €79) or to your home (€12.90), as well as to Germany, Belgium, Italy, Luxembourg, the Netherlands and Switzerland. Collection from our partners in Metz is free. Rates and delivery times:",
  "Dès 79 € d&rsquo;achat en France, en point relais ou en consigne. Le retrait chez nos partenaires à Metz est toujours gratuit.": "From €79 in France, to a pickup point or locker. Collection from our partners in Metz is always free.",
  "Dès 79 € d&#039;achat en France, en point relais ou en consigne. Le retrait chez nos partenaires à Metz est toujours gratuit.": "From €79 in France, to a pickup point or locker. Collection from our partners in Metz is always free.",
  "Choisissez « Retrait chez nos partenaires à Metz » en validant la commande. Nous vous prévenons dès qu&rsquo;elle est prête, sous 24 h en général, et convenons avec vous du point de retrait.": "Choose “Collection from our partners in Metz” when placing your order. We let you know as soon as it is ready, usually within 24 hours, and agree on the collection point with you.",
  "Choisissez « Retrait chez nos partenaires à Metz » en validant la commande. Nous vous prévenons dès qu&#039;elle est prête, sous 24 h en général, et convenons avec vous du point de retrait.": "Choose “Collection from our partners in Metz” when placing your order. We let you know as soon as it is ready, usually within 24 hours, and agree on the collection point with you."
 }
}
JSON;

/**
 * 27/11/2026 : nouveautés de la promotrice (fichier « nouveautés produits chogan.xlsx »), 57 fiches.
 * Les fiches et leurs photos sont envoyées avec le site dans uploads/import/nouveautes-2026-11/ (produits.json et
 * photos, préparés par scripts/catalogue/nouveautes.py) : tant que ce dossier n'est pas sur le serveur, rien ne se passe.
 * L'import se fait depuis l'administration, par tranches de 20 secondes (la page se recharge seule jusqu'au bout,
 * voir l'avis ci-dessous), jamais pendant la visite d'un client.
 */
function maj_nouveautes(): bool
{
    $dossier = wp_upload_dir()['basedir'] . '/import/nouveautes-2026-11';
    if (! is_file("$dossier/produits.json") || ! function_exists('wc_get_product')) {
        return false;
    }
    $ligneDeCommande = defined('WP_CLI') && WP_CLI;
    if (! $ligneDeCommande && (! is_admin() || wp_doing_ajax() || ! current_user_can('manage_options'))) {
        return false;
    }
    $reste = importer_produits((array) json_decode((string) file_get_contents("$dossier/produits.json"), true), $dossier, $ligneDeCommande ? 0 : 20);
    update_option('us_import_reste', $reste, false);

    return $reste === 0;
}

/** Import en cours (administration) : avancement, et la page se recharge pour la tranche suivante. */
add_action('admin_notices', function () {
    if (! current_user_can('manage_options') || in_array('nouveautes-2026-11', (array) get_option('us_mises_a_jour', []), true)
        || ! is_file(wp_upload_dir()['basedir'] . '/import/nouveautes-2026-11/produits.json')) {
        return;
    }
    $reste = (int) get_option('us_import_reste', -1);
    printf(
        '<div class="notice notice-info"><p><strong>Import des nouveautés en cours</strong> : %s. Laissez cette page ouverte, elle se recharge toute seule jusqu\'à la fin.</p></div><meta http-equiv="refresh" content="2">',
        $reste > 0 ? esc_html(sprintf(_n('%d fiche restante', '%d fiches restantes', $reste), $reste)) : 'démarrage'
    );
});

/**
 * 27/11/2026 : page Promotions (/promotions/, lien « Voir les offres » du Black Friday), page Documentation et
 * certifications, traductions anglaises des nouveaux libellés.
 */
function maj_offres_documentation(): bool
{
    // Adresse /promotions/ : règles de réécriture à reconstruire (app/offres.php les déclare).
    flush_rewrite_rules(false);

    // Black Friday : « Voir les offres » menait à la boutique entière.
    for ($i = 0, $n = (int) get_option('options_evenements', 0); $i < $n; $i++) {
        if (get_option("options_evenements_{$i}_theme") === 'black-friday') {
            maj_option("options_evenements_{$i}_lien", '/boutique/', '/promotions/');
        }
    }

    // Page Documentation et certifications (le contenu vient de Univers Sensoriel › Documentation).
    if (! get_page_by_path('documentation-et-certifications')) {
        $description = 'Catalogues des produits Univers Sensoriel et certificats de conformité : documents à consulter ou à télécharger, cosmétiques fabriqués en Italie et notifiés au CPNP.';
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'documentation-et-certifications', 'post_title' => 'Documentation et certifications', 'post_content' => '']);
        if ($id && ! is_wp_error($id)) {
            update_post_meta($id, '_yoast_wpseo_metadesc', $description);
            update_post_meta($id, '_us_seo_auto_yoast_wpseo_metadesc', $description);
        }
    }

    maj_traductions(TRADUCTIONS_2026_11);

    return true;
}

/** Libellés anglais des nouveautés, promotions et documents (aussi dans scripts/traductions/en_GB.json). */
const TRADUCTIONS_2026_11 = [
    'Nouveautés' => 'New in',
    'Nouveau' => 'New',
    'Promotions' => 'Offers',
    'Derniers arrivés' => 'Just arrived',
    'Toutes les nouveautés' => 'All new products',
    'Voir les offres' => 'See the offers',
    'Offre' => 'Offer',
    'Choisir la teinte' => 'Choose a shade',
    'Choisir la senteur' => 'Choose a scent',
    'Teinte' => 'Shade',
    'Senteur' => 'Scent',
    'à choisir' => 'to choose',
    'Maquillage' => 'Make-up',
    'Bougies parfumées' => 'Scented candles',
    'Les produits en promotion en ce moment, dans la limite des stocks.' => 'Products on offer right now, while stocks last.',
    'Documentation et certifications' => 'Documentation and certifications',
    'Catalogues des produits et certificats de conformité, à consulter ou à télécharger.' => 'Product catalogues and certificates of conformity, to view or download.',
    'Catalogues des produits' => 'Product catalogues',
    'Certificats de conformité' => 'Certificates of conformity',
    'Fiches techniques et de sécurité' => 'Technical and safety data sheets',
    'Les documents seront bientôt disponibles ici. Pour en recevoir un dès maintenant :' => 'The documents will be available here soon. To receive one now:',
    'nous écrire' => 'write to us',
    'Télécharger (nouvel onglet)' => 'Download (new tab)',
    'Les derniers produits arrivés dans la boutique : parfums, maquillage, soins, bougies et parfums d\'ambiance.' => 'The latest products in the shop: perfumes, make-up, skincare, candles and home fragrances.',
    'Lèvres, teint, yeux : rouges et encres à lèvres, crayons, sticks, fonds de teint et mascaras.' => 'Lips, complexion, eyes: lip colours and inks, pencils, sticks, foundations and mascaras.',
    'Bougies en cire végétale, en 190 et 400 g : jusqu\'à 90 heures de parfum dans la pièce.' => 'Plant-wax candles, in 190 and 400 g: up to 90 hours of fragrance in the room.',
];
