<?php

/**
 * Recherche par parfum de référence : « Dior », « J'adore », « Baccarat Rouge », « Sauvage Dior »… renvoient
 * aux extraits de parfum Olfazeta qui leur correspondent, ainsi qu'au gel douche et à la crème corps de même senteur
 * (comme les étiquettes de l'ancienne boutique).
 *
 * Les noms de maisons ne sont affichés nulle part sur le site (fiches, rayons, référencement) : ils servent
 * seulement à trouver les produits quand un visiteur les tape dans la recherche.
 *
 * Correspondances : liste des parfums de la Maison (code Chogan => parfum et maison de référence), reliée aux
 * produits par leur numéro (méta _us_reference) et leur genre (W femme, M homme, U mixte).
 * Un numéro absent du catalogue ne renvoie rien.
 */

namespace App;

/** Code Chogan => [parfum de référence, maison]. Liste des parfums de la Maison, novembre 2026. */
const INSPIRATIONS = [
    "006W" => ["Opium", "YSL"],
    "007W" => ["J'adore", "Dior"],
    "010W" => ["Alien", "Thierry Mugler"],
    "011W" => ["Light Blue", "D&G"],
    "014W" => ["Manifesto", "YSL"],
    "019W" => ["Lady Million", "Paco Rabanne"],
    "023W" => ["Hypnotic Poison", "Dior"],
    "024W" => ["Chanel N°5", "Chanel"],
    "025W" => ["For Her", "Narciso Rodriguez"],
    "026W" => ["Flower", "Kenzo"],
    "027W" => ["Trésor", "Lancôme"],
    "028W" => ["Angel", "Thierry Mugler"],
    "029W" => ["L'Eau d'Issey", "Issey Miyake"],
    "039W" => ["Miss Dior Chérie", "Dior"],
    "040W" => ["Hypnose", "Lancôme"],
    "042W" => ["La Vie est Belle", "Lancôme"],
    "047W" => ["Crystal Noir", "Versace"],
    "049W" => ["Dolce", "D&G"],
    "051W" => ["Coco Mademoiselle", "Chanel"],
    "053W" => ["Narciso", "Narciso Rodriguez"],
    "055W" => ["Black Opium", "YSL"],
    "056W" => ["Ange ou Démon", "Givenchy"],
    "057W" => ["Omnia Améthyste", "Bvlgari"],
    "064W" => ["Omnia Indian Garnet", "Bvlgari"],
    "067W" => ["Olympéa", "Paco Rabanne"],
    "070W" => ["The One", "D&G"],
    "071W" => ["Allure", "Chanel"],
    "076W" => ["Acqua di Gioia", "Armani"],
    "080W" => ["Sì", "Armani"],
    "081W" => ["Classique Essence", "JPG"],
    "082W" => ["Signorina", "Ferragamo"],
    "085W" => ["Chance", "Chanel"],
    "089W" => ["Mon Paris", "YSL"],
    "090W" => ["Poison Girl", "Dior"],
    "096W" => ["Gabrielle", "Chanel"],
    "097W" => ["Amo", "Ferragamo"],
    "098W" => ["Joy", "Dior"],
    "115W" => ["Idôle", "Lancôme"],
    "116W" => ["Yes I Am", "Cacharel"],
    "119W" => ["Scandal", "JPG"],
    "120W" => ["La Petite Robe Noire", "Guerlain"],
    "121W" => ["L'Interdit", "Givenchy"],
    "133W" => ["Paradoxe", "Prada"],
    "145W" => ["Devotion", "D&G"],
    "148W" => ["Goddess", "Burberry"],
    "151W" => ["Guilty", "Gucci"],
    "153W" => ["Chloé", "Chloé"],
    "154W" => ["Love Chloé", "Chloé"],
    "156W" => ["Hugo Woman", "Hugo Boss"],
    "158W" => ["Flora", "Gucci"],
    "159W" => ["Burberry Women", "Burberry"],
    "161W" => ["Born in Roma Intense", "Valentino"],
    "001M" => ["One Million", "Paco Rabanne"],
    "002M" => ["Acqua di Giò", "Armani"],
    "003M" => ["Fahrenheit", "Dior"],
    "004M" => ["The One", "D&G"],
    "012M" => ["Eau Sauvage", "Dior"],
    "015M" => ["Roma Uomo", "Laura Biagiotti"],
    "016M" => ["Le Mâle", "JPG"],
    "018M" => ["Déclaration", "Cartier"],
    "020M" => ["La Nuit de l'Homme", "YSL"],
    "021M" => ["Light Blue", "D&G"],
    "022M" => ["Terre d'Hermès", "Hermès"],
    "030M" => ["Black XS", "Paco Rabanne"],
    "032M" => ["Spicebomb", "Viktor & Rolf"],
    "033M" => ["Black Code", "Armani"],
    "037M" => ["Man", "Bvlgari"],
    "038M" => ["Bleu de Chanel", "Chanel"],
    "048M" => ["Allure Homme", "Chanel"],
    "052M" => ["Pasha", "Cartier"],
    "061M" => ["Invictus", "Paco Rabanne"],
    "062M" => ["Intenso", "D&G"],
    "069M" => ["Acqua di Sale", "Profumum Roma"],
    "079M" => ["MYSLF", "YSL"],
    "084M" => ["Dylan Blue", "Versace"],
    "086M" => ["Legend", "Montblanc"],
    "087M" => ["Wanted", "Azzaro"],
    "088M" => ["Man in Black", "Bvlgari"],
    "091M" => ["Chrome", "Azzaro"],
    "140M" => ["Eros", "Versace"],
    "147M" => ["Boss Bottled Absolu", "Hugo Boss"],
    "150M" => ["Hugo", "Hugo Boss"],
    "152M" => ["Guilty", "Gucci"],
    "157M" => ["The Scent", "Hugo Boss"],
    "160M" => ["Burberry for Men", "Burberry"],
    "162M" => ["Born in Roma Intense", "Valentino"],
    "054U" => ["Black Orchid", "Tom Ford"],
    "072U" => ["Patchouli", "Reminiscence"],
    "100U" => ["White Aoud", "Montale"],
    "105U" => ["Intense Café", "Montale"],
    "155U" => ["CK One", "Calvin Klein"],
    "093W" => ["Aventus for Her", "Creed"],
    "122W" => ["Libre", "YSL"],
    "131W" => ["Good Girl", "Carolina Herrera"],
    "132W" => ["My Way", "Armani"],
    "163W" => ["Miutine", "Miu Miu"],
    "068M" => ["Aventus", "Creed"],
    "094M" => ["Sauvage", "Dior"],
    "113M" => ["Sur la Route", "Louis Vuitton"],
    "136M" => ["Dior Homme Intense", "Dior"],
    "164M" => ["Paradigme", "Prada"],
    "044U" => ["Silver Mountain Water", "Creed"],
    "073U" => ["Himalaya", "Creed"],
    "099U" => ["Mandarino di Amalfi", "Tom Ford"],
    "110U" => ["Kirkè", "Tiziana Terenzi"],
    "114U" => ["Ombre Nomade", "Louis Vuitton"],
    "135U" => ["Bois d'Argent", "Dior"],
    "142U" => ["Ombré Leather", "Tom Ford"],
    "109W" => ["J'adore L'Or", "Dior"],
    "123W" => ["Good Girl Gone Bad", "Kilian"],
    "074M" => ["Black Afgano", "Nasomatto"],
    "075M" => ["X for Men", "Clive Christian"],
    "101U" => ["Velvet Amber Skin", "D&G"],
    "102U" => ["Velvet Amber Sun", "D&G"],
    "106U" => ["Fucking Fabulous", "Tom Ford"],
    "111U" => ["Lost Cherry", "Tom Ford"],
    "112U" => ["Neroli Portofino", "Tom Ford"],
    "117U" => ["Tobacco Vanille", "Tom Ford"],
    "118U" => ["Baccarat Rouge 540", "Maison Francis Kurkdjian"],
    "124U" => ["Zeta", "Morph"],
    "125U" => ["Sole di Positano Acqua", "Tom Ford"],
    "126U" => ["Soleil Blanc", "Tom Ford"],
    "127U" => ["Oud Wood", "Tom Ford"],
    "128U" => ["Vanille Fatale", "Tom Ford"],
    "129U" => ["Erba Pura", "Xerjoff"],
    "130U" => ["Megamare", "Orto Parisi"],
    "134U" => ["Bitter Peach", "Tom Ford"],
    "137U" => ["XJ 1861 Naxos", "Xerjoff"],
    "138U" => ["Wood Whisper", "Ojar"],
    "139U" => ["Les Sables Roses", "Louis Vuitton"],
    "141U" => ["Turath", "The Spirit of Dubai"],
    "143U" => ["Vanilla Powder", "Matière Première"],
    "144U" => ["Bianco Latte", "Giardini di Toscana"],
    "146U" => ["Rouge", "Balmain"],
];

/** Autres façons d'écrire une maison ou un parfum (cherchées comme le nom lui-même). */
const INSPIRATIONS_ALIAS = [
    'YSL' => ['Yves Saint Laurent', 'Saint Laurent'],
    'D&G' => ['Dolce Gabbana', 'Dolce and Gabbana', 'DG'],
    'JPG' => ['Jean Paul Gaultier', 'Gaultier'],
    'Bvlgari' => ['Bulgari'],
    'Thierry Mugler' => ['Mugler'],
    'Paco Rabanne' => ['Rabanne'],
    'Armani' => ['Giorgio Armani', 'Emporio Armani'],
    'Hugo Boss' => ['Boss'],
    'Montblanc' => ['Mont Blanc'],
    'Maison Francis Kurkdjian' => ['MFK', 'Kurkdjian', 'Francis Kurkdjian'],
    'Kilian' => ['By Kilian'],
    'Calvin Klein' => ['CK'],
    'Louis Vuitton' => ['LV', 'Vuitton'],
    'Carolina Herrera' => ['Herrera'],
    'Viktor & Rolf' => ['Viktor and Rolf'],
    'Ferragamo' => ['Salvatore Ferragamo'],
    'Narciso Rodriguez' => ['Rodriguez'],
    'Laura Biagiotti' => ['Biagiotti'],
    'Issey Miyake' => ['Miyake'],
    'Chanel N°5' => ['N5', 'No5', 'Numero 5'],
    'One Million' => ['1 Million'],
    'Baccarat Rouge 540' => ['BR540', 'Baccarat'],
    'Spicebomb' => ['Spice Bomb'],
    'MYSLF' => ['Myself'],
    'Acqua di Giò' => ['Aqua di Gio', 'Acqua di Gio'],
    'Acqua di Gioia' => ['Aqua di Gioia'],
    'Eros' => ['Éros'],
    'Silver Mountain Water' => ['Silver Mountain'],
    'Dior Homme Intense' => ['Dior Homme'],
];

/** Mots de la recherche qui ne désignent pas un parfum (« parfum inspiré de Dior » = « Dior »). */
const INSPIRATIONS_MOTS_VIDES = [
    'parfum', 'parfums', 'inspire', 'inspiree', 'inspires', 'inspirees', 'inspiration', 'equivalent', 'equivalence',
    'dupe', 'type', 'comme', 'odeur', 'senteur', 'version', 'facon', 'de', 'du', 'des', 'la', 'le', 'les', 'l', 'd',
    'pour', 'en', 'un', 'une', 'et', 'edp', 'edt', 'extrait', 'ml',
];

/** Mots du genre, ajoutés à chaque parfum : « Dior homme » ne garde que les parfums homme de Dior. */
const INSPIRATIONS_GENRES = [
    'W' => ['femme', 'woman', 'women', 'elle'],
    'M' => ['homme', 'man', 'men', 'lui'],
    'U' => ['mixte', 'unisexe', 'femme', 'homme'],
];

/**
 * Formes d'un texte à comparer : en minuscules, sans accents ni ponctuation, l'apostrophe une fois supprimée
 * (« jadore ») et une fois remplacée par un espace (« j adore »).
 *
 * @return array<int, string[]>
 */
function inspirations_formes(string $texte): array
{
    $texte = strtolower(remove_accents(str_replace(['°', '&', '\\'], '', $texte)));
    $formes = [];
    foreach ([str_replace(["'", '’'], '', $texte), str_replace(["'", '’'], ' ', $texte)] as $variante) {
        $formes[] = array_values(array_filter(preg_split('/[^a-z0-9]+/', $variante) ?: [], 'strlen'));
    }

    return $formes;
}

/** Un mot cherché correspond à un mot du parfum : identique, ou début d'un mot (dès 4 lettres : « bacca », « opiu »). */
function inspirations_mot(string $cherche, array $mots): bool
{
    foreach ($mots as $mot) {
        if ($mot === $cherche || (strlen($cherche) >= 4 && str_starts_with($mot, $cherche))) {
            return true;
        }
    }

    return false;
}

/**
 * Codes de la liste qui répondent à la recherche : tous ses mots trouvés, dont au moins un, autre qu'un mot du genre,
 * dans le parfum ou la maison (« parfum homme » ne vise aucun parfum en particulier).
 *
 * @return array<string, bool> code => vrai si chaque mot est un mot entier du parfum ou de la maison (pas un début de mot)
 */
function inspirations_codes(string $recherche): array
{
    $codes = [];
    $motsGenre = array_merge(...array_values(INSPIRATIONS_GENRES));
    foreach (inspirations_formes($recherche) as $mots) {
        $mots = array_values(array_filter($mots, fn ($m) => ! in_array($m, INSPIRATIONS_MOTS_VIDES, true) && (strlen($m) > 1 || ctype_digit($m))));
        if (! $mots) {
            continue;
        }
        foreach (INSPIRATIONS as $code => [$parfum, $maison]) {
            $noms = [];
            foreach ([$parfum, $maison, ...(INSPIRATIONS_ALIAS[$parfum] ?? []), ...(INSPIRATIONS_ALIAS[$maison] ?? [])] as $texte) {
                foreach (inspirations_formes($texte) as $forme) {
                    array_push($noms, ...$forme);
                }
            }
            $genre = INSPIRATIONS_GENRES[substr($code, 3, 1)] ?? [];
            $tous = array_merge($noms, $genre);
            $trouves = array_filter($mots, fn ($m) => inspirations_mot($m, $tous));
            $parNom = array_filter($mots, fn ($m) => ! in_array($m, $motsGenre, true) && inspirations_mot($m, $noms))
                ?: in_array($mots, inspirations_formes($parfum), true); // « Man » de Bvlgari, tapé tel quel
            if (count($trouves) === count($mots) && $parNom) {
                $exact = ! array_diff($mots, $tous);
                $codes[$code] = ($codes[$code] ?? false) || $exact;
            }
        }
    }

    return $codes;
}

/**
 * Produits publiés de chaque code : l'extrait de parfum du même numéro et du même genre, puis le gel douche et
 * la crème corps de même numéro qui portent son nom. Gardé un jour, recalculé dès qu'un produit change.
 *
 * @return array<string, int[]>
 */
function inspirations_produits(): array
{
    $table = get_transient('us_inspirations');
    if (is_array($table)) {
        return $table;
    }
    $parNumero = [];
    foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
        'meta_query' => [['key' => '_us_reference', 'value' => '', 'compare' => '!=']], 'suppress_filters' => true]) as $id) {
        $parNumero[(string) get_post_meta($id, '_us_reference', true)][] = $id;
    }
    $table = [];
    foreach (array_keys(INSPIRATIONS) as $code) {
        $genre = ['W' => 'femme', 'M' => 'homme'][substr($code, 3, 1)] ?? null;
        $parfums = $soins = [];
        foreach ($parNumero[substr($code, 0, 3)] ?? [] as $id) {
            $rayons = wp_get_post_terms($id, 'product_cat', ['fields' => 'slugs']);
            $genres = taxonomy_exists('pa_genre') ? wp_get_post_terms($id, 'pa_genre', ['fields' => 'slugs']) : [];
            if (in_array('parfums', $rayons, true) && ! in_array('parfums-enfant', $rayons, true)) {
                if (! $genre || in_array($genre, $genres, true) || in_array('mixte', $genres, true)) {
                    $parfums[] = $id;
                }
            } elseif (in_array('corps-et-bain', $rayons, true)) {
                $soins[] = $id;
            }
        }
        // Gel douche et crème : seulement s'ils portent le nom du parfum (l'ancienne boutique avait des numéros recopiés).
        $noms = array_map(fn ($id) => sanitize_title(get_the_title($id)), $parfums);
        $soins = array_filter($soins, fn ($id) => array_filter($noms, fn ($nom) => $nom !== '' && str_contains(sanitize_title(get_the_title($id)), $nom)));
        if ($parfums) {
            $table[$code] = array_values(array_unique(array_merge($parfums, $soins)));
        }
    }
    set_transient('us_inspirations', $table, DAY_IN_SECONDS);

    return $table;
}

add_action('save_post_product', fn () => delete_transient('us_inspirations'));

/**
 * Identifiants des produits qui répondent à une recherche par parfum de référence (dans l'ordre de la liste).
 * $exacts : seulement ceux dont le parfum ou la maison est tapé en entier (« rose » ne vise pas « Sables Roses »).
 */
function inspirations_recherche(string $recherche, bool $exacts = false): array
{
    static $memoire = [];
    $recherche = wp_unslash($recherche);
    if (! isset($memoire[$recherche])) {
        $table = inspirations_produits();
        $memoire[$recherche] = [[], []];
        foreach (inspirations_codes($recherche) as $code => $exact) {
            array_push($memoire[$recherche][0], ...($table[$code] ?? []));
            if ($exact) {
                array_push($memoire[$recherche][1], ...($table[$code] ?? []));
            }
        }
        $memoire[$recherche] = array_map(fn ($ids) => array_values(array_unique($ids)), $memoire[$recherche]);
    }

    return $memoire[$recherche][$exacts ? 1 : 0];
}

/** Recherche de produits : les correspondances s'ajoutent aux résultats habituels (titre, texte, référence)… */
add_filter('posts_search', function ($sql, $query) {
    if ($sql === '' || ! in_array('product', (array) $query->get('post_type'), true)) {
        return $sql;
    }
    $ids = inspirations_recherche((string) $query->get('s'));
    if (! $ids) {
        return $sql;
    }
    global $wpdb;

    return sprintf(' AND ((%s) OR %s.ID IN (%s)) ', preg_replace('/^\s*AND\s*/', '', $sql), $wpdb->posts, implode(',', array_map('intval', $ids)));
}, 20, 2);

/** …et passent en tête quand le tri est « pertinence » (tri par défaut d'une recherche) et le nom tapé en entier. */
add_filter('posts_search_orderby', function ($ordre, $query) {
    $tri = (string) $query->get('orderby');
    if (! in_array($tri, ['', 'relevance'], true) || ! in_array('product', (array) $query->get('post_type'), true)
        || ! ($ids = inspirations_recherche((string) $query->get('s'), true))) {
        return $ordre;
    }
    global $wpdb;
    $tete = sprintf('FIELD(%s.ID, %s) = 0, FIELD(%1$s.ID, %2$s)', $wpdb->posts, implode(',', array_map('intval', $ids)));

    return $ordre ? "$tete, $ordre" : $tete;
}, 20, 2);

/**
 * « Chogan », nom du fabricant, n'est écrit nulle part sur le site mais reste un mot-clé de recherche :
 * il est retiré de la recherche (« chogan sauvage » = « sauvage ») et, tapé seul, affiche toute la boutique.
 * La page de résultats montre toujours ce que le visiteur a tapé.
 */
add_action('pre_get_posts', function ($query) {
    $recherche = (string) $query->get('s');
    if ($recherche === '' || stripos($recherche, 'chogan') === false || ! in_array('product', (array) $query->get('post_type'), true)) {
        return;
    }
    $reste = trim(preg_replace('/\s+/', ' ', preg_replace('/\bchogan\b/i', ' ', $recherche)));
    if ($reste === '') {
        // « chogan » seul : la recherche garde son mot (titre de la page), seul le filtre sur les produits saute.
        $query->set('us_toute_la_boutique', true);

        return;
    }
    if ($query->is_main_query()) {
        $query->set('us_recherche_tapee', $recherche);
    }
    $query->set('s', $reste);
});

add_filter('posts_search', fn ($sql, $query) => $query->get('us_toute_la_boutique') ? '' : $sql, 30, 2);
add_filter('posts_search_orderby', fn ($ordre, $query) => $query->get('us_toute_la_boutique') ? '' : $ordre, 30, 2);

add_filter('get_search_query', function ($texte) {
    global $wp_query;
    $tapee = $wp_query instanceof \WP_Query ? (string) $wp_query->get('us_recherche_tapee') : '';

    return $tapee !== '' ? $tapee : $texte; // get_search_query() échappe ensuite
});
