<?php

/**
 * Fonctions d'aide du thème : pictos, coordonnées, menu, données d'une fiche produit.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/** Seuil de livraison offerte (charte) : doit rester égal au réglage WooCommerce › Expédition. */
const SEUIL_LIVRAISON = 49;

/** Pluriel d'une famille olfactive : « Parfums floraux », « Parfums boisés ». */
function famille_au_pluriel(string $nom): string
{
    $bas = mb_strtolower($nom);

    return match ($bas) {
        'floral' => 'floraux',
        'fougère' => 'fougère',
        default => $bas . 's',
    };
}

/** Picto au trait fin (1,2 px), sans remplissage, comme les filets du logo. */
function icone(string $nom, int $taille = 24, string $classe = ''): string
{
    $traces = [
        'recherche' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'compte' => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20.5c.8-4 3.8-6 7.5-6s6.700 2 7.500 6"/>',
        'panier' => '<path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6.500a3 3 0 0 1 6 0V8"/>',
        'menu' => '<path d="M3.500 7h17M3.500 12h17M3.500 17h17"/>',
        'fermer' => '<path d="m5.500 5.500 13 13M18.500 5.500l-13 13"/>',
        'chevron' => '<path d="m8 10 4 4 4-4"/>',
        'fleche' => '<path d="M4 12h16M14 6l6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'moins' => '<path d="M5 12h14"/>',
        'filtres' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
        'livraison' => '<path d="M2.500 6.500h11v10h-11zM13.500 9.500h4l3 3.500v3.500h-7z"/><circle cx="7" cy="17.500" r="1.800"/><circle cx="17" cy="17.500" r="1.800"/>',
        'cadenas' => '<rect x="5" y="10.500" width="14" height="9.500" rx="1"/><path d="M8 10.500V8a4 4 0 0 1 8 0v2.500"/>',
        'boutique' => '<path d="M4 9.500 5.500 4h13L20 9.500M4 9.500V20h16V9.500M4 9.500c0 1.500 1.200 2.500 2.700 2.500s2.600-1 2.600-2.500c0 1.500 1.200 2.500 2.700 2.500s2.700-1 2.700-2.500c0 1.500 1.100 2.500 2.600 2.500S20 11 20 9.500M10 20v-5h4v5"/>',
        'goutte' => '<path d="M12 3.500c3 3.800 5.500 6.700 5.500 10a5.500 5.500 0 0 1-11 0c0-3.300 2.500-6.200 5.500-10Z"/>',
        'feuille' => '<path d="M5 19c0-8 5-13.500 14.500-14.500C19 13 14 19 5 19Zm0 0 8-8"/>',
        'fleur' => '<circle cx="12" cy="12" r="2.200"/><path d="M12 9.800c-2-2.300-1.500-5.300 0-6.300 1.500 1 2 4 0 6.300Zm0 4.400c2 2.300 1.500 5.300 0 6.300-1.500-1-2-4 0-6.300Zm2.200-2.200c2.300-2 5.300-1.500 6.300 0-1 1.500-4 2-6.300 0Zm-4.400 0c-2.300 2-5.300 1.500-6.300 0 1-1.500 4-2 6.300 0Z"/>',
        'echange' => '<path d="M4 8h13l-3-3M20 16H7l3 3"/>',
        'cadeau' => '<path d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7c-1.500-3.500-5.500-3.500-5.500-1.200C6.500 7 9 7 12 7Zm0 0c1.500-3.500 5.500-3.500 5.500-1.200C17.500 7 15 7 12 7Z"/>',
        'whatsapp' => '<path d="M4 20l1.300-4.200A8 8 0 1 1 8.400 19L4 20Z"/><path d="M9.300 8.500c-.5 1.800 2.700 5.500 5.300 6l1.200-1.300-1.800-1.200-.9.700c-.9-.4-1.700-1.200-2.100-2.100l.7-.9-1.100-1.800-1.300.6Z"/>',
        'telephone' => '<path d="M5 4h4l1.500 4.500-2 1.500a11 11 0 0 0 5.500 5.500l1.500-2L20 15v4c0 .6-.4 1-1 1A15 15 0 0 1 4 5c0-.6.400-1 1-1Z"/>',
        'courriel' => '<rect x="3.500" y="6" width="17" height="12" rx="1"/><path d="m4 7 8 6 8-6"/>',
        'pyramide-tete' => '<path d="M12 4 3.500 20h17L12 4Z"/><path d="M9.300 9h5.400" stroke-width="2.400"/>',
        'pyramide-coeur' => '<path d="M12 4 3.500 20h17L12 4Z"/><path d="M7.200 13.500h9.600" stroke-width="2.400"/>',
        'pyramide-fond' => '<path d="M12 4 3.500 20h17L12 4Z"/><path d="M5 18h14" stroke-width="2.400"/>',
        'sablier' => '<path d="M7 3.500h10M7 20.500h10M8 3.500c0 4.500 4 5.500 4 8.500s-4 4-4 8.500M16 3.500c0 4.500-4 5.500-4 8.500s4 4 4 8.500"/>',
        'etoile' => '<path d="M12 2.500c.6 5.400 3.100 8.400 9.500 9.500-6.400 1.100-8.900 4.100-9.500 9.500-.6-5.400-3.100-8.400-9.500-9.500 6.400-1.100 8.900-4.100 9.500-9.500Z"/>',
        'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.500"/><circle cx="12" cy="12" r="3.600"/><circle cx="16.700" cy="7.300" r=".6"/>',
        'facebook' => '<path d="M14 21v-7.500h2.600l.4-3H14V8.700c0-.9.300-1.500 1.600-1.500H17V4.500c-.300 0-1.200-.1-2.200-.1-2.200 0-3.700 1.300-3.700 3.800v2.300H8.500v3h2.600V21"/>',
        'coche' => '<path d="m5 12.500 4.500 4.500L19 7.500"/>',
    ];
    if (! isset($traces[$nom])) {
        return '';
    }

    return sprintf(
        '<svg class="icone %s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
        esc_attr($classe), $taille, $taille, $traces[$nom]
    );
}

/** Adresse d'une image du thème (resources/images). */
function image(string $fichier): string
{
    return Vite::asset("resources/images/{$fichier}");
}

/** Balise <img> d'un visuel du thème, avec dimensions (pas de saut de mise en page). */
function visuel(string $nom, string $alt, array $attributs = []): string
{
    $chemin = get_theme_file_path("resources/images/site/{$nom}.webp");
    $taille = is_file($chemin) ? getimagesize($chemin) : [0, 0];
    $attributs += ['loading' => 'lazy', 'decoding' => 'async'];
    $html = '';
    foreach ($attributs as $cle => $valeur) {
        $html .= sprintf(' %s="%s"', $cle, esc_attr($valeur));
    }

    return sprintf('<img src="%s" alt="%s" width="%d" height="%d"%s>', esc_url(image("site/{$nom}.webp")), esc_attr($alt), $taille[0], $taille[1], $html);
}

/** Coordonnées de la Maison. À tenir à jour ici : elles alimentent pied de page, contact et données structurées. */
function coordonnees(): array
{
    return [
        'courriel' => 'contact@universsensoriel.com',
        'telephone' => '+33 6 05 78 40 11',
        'whatsapp' => '33605784011',
        'boutiques' => [
            [
                'ville' => 'Metz', 'pays' => 'France', 'enseigne' => 'Kacy Afro Center', 'telephone' => '+33 6 41 25 22 71',
                'adresse' => '57 rue des Allemands, 57000 Metz',
                'horaires' => ['Lundi, mardi et jeudi' => '14 h – 19 h', 'Mercredi' => '11 h – 19 h', 'Vendredi et samedi' => '10 h – 19 h', 'Dimanche' => 'Fermé'],
            ],
            ['ville' => 'Yaoundé', 'pays' => 'Cameroun', 'enseigne' => 'Style Mondial', 'telephone' => '+237 56 22 82 25'],
        ],
    ];
}

/**
 * Formulaire Forminator (« contact », « newsletter »), rendu par son shortcode.
 * Les identifiants sont rangés dans l'option « us_formulaires » par scripts/formulaires.php.
 */
function formulaire(string $cle): string
{
    $id = (int) (get_option('us_formulaires', [])[$cle] ?? 0);

    return $id && shortcode_exists('forminator_form') ? do_shortcode('[forminator_form id="' . $id . '"]') : '';
}

function lien_whatsapp(string $message = 'Bonjour, j\'ai une question.'): string
{
    return 'https://wa.me/' . coordonnees()['whatsapp'] . '?text=' . rawurlencode($message);
}

/** Les quatre garanties affichées sous le visuel d'accueil et sur la fiche produit. */
function reassurances(): array
{
    return [
        ['livraison', 'Livraison offerte', 'Dès ' . SEUIL_LIVRAISON . ' € d\'achat en France'],
        ['boutique', 'Retrait en boutique', 'Gratuit, à Metz'],
        ['cadenas', 'Paiement sécurisé', 'Vos données restent protégées'],
        ['whatsapp', 'Conseil personnalisé', 'WhatsApp ou formulaire, réponse sous 24 h'],
    ];
}

/**
 * Menu principal : les quatre univers, leurs rayons publiés et une colonne « à explorer ».
 * Construit depuis les catégories : un rayon ajouté dans l'admin apparaît tout seul.
 */
function menu_principal(): array
{
    static $menu = null;
    if ($menu !== null || ! taxonomy_exists('product_cat')) {
        return $menu ?? [];
    }
    $visuels = [
        'parfums' => ['extrait-de-parfum-homme-notes-boisees', 'Extrait de parfum aux notes boisées'],
        'beaute' => ['coffret-soins-visage-peptilux', 'Coffret de soins du visage'],
        'bien-etre' => ['huile-vegetale-amande-douce', 'Huile végétale d\'amande douce'],
        'art-de-vivre' => ['parfum-d-ambiance-diffuseur-salon', 'Diffuseur de parfum d\'ambiance dans un salon'],
    ];
    $menu = [];
    $univers = get_terms(['taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'meta_key' => 'order', 'orderby' => 'meta_value_num']);
    foreach (is_array($univers) ? $univers : [] as $terme) {
        if (! isset($visuels[$terme->slug])) {
            continue;
        }
        $rayons = get_terms(['taxonomy' => 'product_cat', 'parent' => $terme->term_id, 'hide_empty' => true, 'meta_key' => 'order', 'orderby' => 'meta_value_num']);
        $explorer = [];
        if ($terme->slug === 'parfums' && taxonomy_exists('pa_famille-olfactive')) {
            $familles = get_terms(['taxonomy' => 'pa_famille-olfactive', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 8]);
            foreach (is_array($familles) ? $familles : [] as $famille) {
                $explorer[] = ['titre' => $famille->name, 'url' => get_term_link($famille)];
            }
        }
        $menu[] = [
            'titre' => $terme->name,
            'slug' => $terme->slug,
            'url' => get_term_link($terme),
            'accroche' => get_term_meta($terme->term_id, '_us_accroche', true),
            'rayons' => array_map(fn ($r) => ['titre' => $r->name, 'url' => get_term_link($r), 'nombre' => $r->count], is_array($rayons) ? $rayons : []),
            'explorer' => $explorer,
            'visuel' => $visuels[$terme->slug],
        ];
    }

    return $menu;
}

/** Données d'affichage d'une fiche : sous-titre, numéro, notes, familles, pour qui. */
function fiche(\WC_Product $produit): array
{
    $id = $produit->get_id();
    $termes = fn (string $taxonomie) => taxonomy_exists($taxonomie) ? (wp_get_post_terms($id, $taxonomie) ?: []) : [];
    $familles = $termes('pa_famille-olfactive');
    $genre = $termes('pa_genre');
    $gamme = $termes('pa_gamme');
    $rayons = wp_get_post_terms($id, 'product_cat', ['orderby' => 'parent', 'order' => 'DESC']);
    $rayon = is_array($rayons) && $rayons ? $rayons[0] : null;
    foreach (is_array($rayons) ? $rayons : [] as $r) {
        if ($r->parent && ! str_starts_with($r->slug, 'collection-')) {
            $rayon = $r;
            break;
        }
    }
    $notes = get_post_meta($id, '_us_notes', true);

    return [
        'sous_titre' => (string) get_post_meta($id, '_us_sous_titre', true),
        'reference' => (string) get_post_meta($id, '_us_reference', true),
        'notes' => is_array($notes) ? $notes : [],
        'familles' => is_array($familles) ? $familles : [],
        'genre' => is_array($genre) && $genre ? $genre[0]->name : '',
        'gamme' => is_array($gamme) && $gamme ? $gamme[0]->name : '',
        'rayon' => $rayon,
        'parfum' => $rayon && in_array($rayon->slug, ['parfums-femme', 'parfums-homme', 'collection-mytologik', 'parfums-enfant'], true),
    ];
}

/** Vrai si l'image est un packshot détouré (fond transparent) : le thème lui donne un fond et une ombre. */
function est_detoure(int $imageId): bool
{
    return $imageId && get_post_meta($imageId, '_us_detoure', true) === '1';
}

/** Image d'un produit, avec le nom du produit en texte alternatif quand le visuel est partagé entre fiches. */
function image_produit(\WC_Product $produit, string $taille = 'medium', array $attributs = [], ?int $imageId = null): string
{
    $imageId = $imageId ?? (int) $produit->get_image_id();
    if (! $imageId) {
        return '<span class="visuel-absent" aria-hidden="true">' . icone('etoile', 40) . '</span>';
    }
    $infos = fiche($produit);
    $alt = trim($produit->get_name() . ($infos['sous_titre'] ? ', ' . mb_strtolower($infos['sous_titre']) : ''));
    $attributs += ['alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async', 'class' => est_detoure($imageId) ? 'est-detoure' : 'est-photo'];

    return wp_get_attachment_image($imageId, $taille, false, $attributs);
}

/** Prix court d'une carte : « À partir de 11,90 € » pour un produit à formats. */
function prix_carte(\WC_Product $produit): string
{
    if ($produit->is_type('variable')) {
        $min = $produit->get_variation_price('min', true);

        return $min === '' ? '' : '<span class="prix-des">À partir de</span> ' . wc_price($min);
    }

    return $produit->get_price_html();
}

/** Produits publiés d'un rayon (slug), les produits vedettes d'abord. */
function produits_rayon(string|array $slugs, int $nombre = 8, array $exclure = []): array
{
    return wc_get_products([
        'status' => 'publish', 'limit' => $nombre, 'category' => (array) $slugs, 'exclude' => $exclure,
        'orderby' => 'menu_order', 'order' => 'ASC', 'stock_status' => 'instock',
    ]);
}

function produits_vedettes(string|array $slugs, int $nombre = 8): array
{
    $vedettes = wc_get_products([
        'status' => 'publish', 'limit' => $nombre, 'category' => (array) $slugs, 'featured' => true, 'orderby' => 'menu_order', 'order' => 'ASC',
    ]);
    if (count($vedettes) < $nombre) {
        $vedettes = array_merge($vedettes, produits_rayon($slugs, $nombre - count($vedettes), array_map(fn ($p) => $p->get_id(), $vedettes)));
    }

    return $vedettes;
}

/** Fil d'Ariane : [[titre, url], …], la page courante en dernier (sans lien). */
function fil_ariane(): array
{
    $fil = [['Accueil', home_url('/')]];
    if (function_exists('is_product') && is_product()) {
        $infos = fiche(wc_get_product(get_the_ID()));
        if ($infos['rayon']) {
            foreach (array_reverse(get_ancestors($infos['rayon']->term_id, 'product_cat')) as $parent) {
                $terme = get_term($parent, 'product_cat');
                $fil[] = [$terme->name, get_term_link($terme)];
            }
            $fil[] = [$infos['rayon']->name, get_term_link($infos['rayon'])];
        }
        $fil[] = [get_the_title(), ''];
    } elseif (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        $terme = get_queried_object();
        if ($terme->taxonomy === 'product_cat') {
            foreach (array_reverse(get_ancestors($terme->term_id, 'product_cat')) as $parent) {
                $p = get_term($parent, 'product_cat');
                $fil[] = [$p->name, get_term_link($p)];
            }
        } else {
            $fil[] = ['Parfums', get_term_link('parfums', 'product_cat')];
        }
        $fil[] = [$terme->name, ''];
    } elseif (function_exists('is_shop') && is_shop()) {
        $fil[] = [is_search() ? 'Recherche' : 'Boutique', ''];
    } elseif (is_page()) {
        $fil[] = [get_the_title(), ''];
    }

    return $fil;
}

/**
 * Moyens de paiement affichés dans le pied de page : seulement ceux qui sont activés dans WooCommerce (Stripe).
 * Une carte activée vaut Visa, Mastercard, CB et American Express ; le paiement express vaut Apple Pay et Google Pay.
 * Renvoie [[fichier de resources/images/paiement, nom], …].
 */
function moyens_de_paiement(): array
{
    static $liste = null;
    if ($liste !== null) {
        return $liste;
    }
    $liste = [];
    if (! function_exists('WC') || ! WC()->payment_gateways()) {
        return $liste;
    }
    $actifs = array_filter(WC()->payment_gateways()->payment_gateways(), fn ($passerelle) => $passerelle->enabled === 'yes');
    if (isset($actifs['stripe'])) {
        array_push($liste, ['visa', 'Visa'], ['mastercard', 'Mastercard'], ['cartes-bancaires', 'CB'], ['american-express', 'American Express']);
        if ($actifs['stripe']->get_option('express_checkout', 'no') === 'yes') {
            array_push($liste, ['apple-pay', 'Apple Pay'], ['google-pay', 'Google Pay']);
        }
    }
    foreach (['stripe_klarna' => ['klarna', 'Klarna'], 'stripe_bancontact' => ['bancontact', 'Bancontact']] as $passerelle => $moyen) {
        if (isset($actifs[$passerelle])) {
            $liste[] = $moyen;
        }
    }

    return $liste;
}
