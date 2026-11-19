<?php

/**
 * Éditeur de blocs (Gutenberg) des pages et des articles.
 *
 * - Blocs du thème (catégorie « Univers Sensoriel ») : Nos boutiques, Coordonnées, Bouton WhatsApp,
 *   Éditeur du site, Hébergeur. Ce sont des blocs Secure Custom Fields décrits dans resources/blocks/<nom>/block.json,
 *   rendus par rendu_bloc() : leur contenu est lu dans Univers Sensoriel › Coordonnées, jamais recopié dans la page.
 * - Valeurs au fil du texte (dans un paragraphe) : codes courts [us_seuil], [us_forfait] (livraison, lus dans
 *   WooCommerce › Expédition), [us_contact champ="courriel|telephone"] (app/coordonnees.php).
 * - Blocs proposés : ceux qui ont un style dans le thème (BLOCS_AUTORISES), pour que la page reste à la charte.
 * - Questions fréquentes : chaque bloc « Détails » d'une page devient une question des données Google (FAQPage).
 */

namespace App;

/** Blocs de WordPress proposés dans les pages et les articles, en plus des blocs du thème (us/…). */
const BLOCS_AUTORISES = [
    'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/details',
    'core/buttons', 'core/button', 'core/image', 'core/separator', 'core/table', 'core/shortcode',
];

add_filter('block_categories_all', function (array $categories) {
    return array_merge([['slug' => 'univers-sensoriel', 'title' => 'Univers Sensoriel', 'icon' => null]], $categories);
});

add_action('init', function () {
    foreach (glob(get_theme_file_path('resources/blocks/*/block.json')) ?: [] as $bloc) {
        register_block_type(dirname($bloc));
    }
});

add_filter('allowed_block_types_all', function ($autorises, $contexte) {
    $type = $contexte->post?->post_type ?? '';
    if (! in_array($type, ['page', 'post'], true)) {
        return $autorises;
    }
    $theme = array_keys(array_filter(
        \WP_Block_Type_Registry::get_instance()->get_all_registered(),
        fn ($bloc) => str_starts_with($bloc->name, 'us/')
    ));

    return array_merge(BLOCS_AUTORISES, $theme);
}, 10, 2);

/**
 * Rendu des blocs du thème (appelé par Secure Custom Fields, sur le site comme dans l'éditeur).
 * $bloc['name'] : us/boutiques, us/coordonnees, us/whatsapp, us/editeur, us/hebergeur.
 */
function rendu_bloc(array $bloc, string $contenu = '', bool $apercu = false): void
{
    $html = match ($bloc['name'] ?? '') {
        'us/boutiques' => do_shortcode('[us_boutiques]'),
        'us/editeur' => do_shortcode('[us_editeur]'),
        'us/hebergeur' => do_shortcode('[us_hebergeur]'),
        'us/coordonnees' => bloc_coordonnees((array) (get_field('elements') ?: ['courriel', 'telephone', 'whatsapp'])),
        'us/whatsapp' => bloc_whatsapp(),
        default => '',
    };
    if ($html === '' && $apercu) {
        $html = '<p class="bloc-vide">Rien à afficher : à remplir dans Univers Sensoriel › Coordonnées.</p>';
    }
    echo $html;
}

/** Liste des moyens de nous joindre : e-mail, téléphone, WhatsApp (ceux qui sont choisis et remplis). */
function bloc_coordonnees(array $elements): string
{
    $lignes = [
        'courriel' => ['E-mail', contact_lien('courriel')],
        'telephone' => ['Téléphone', contact_lien('telephone')],
        'whatsapp' => ['WhatsApp', coordonnees()['whatsapp'] ? sprintf('<a href="%s" rel="noopener" target="_blank">%s</a>', esc_url(lien_whatsapp()), esc_html(texte('pied_whatsapp'))) : ''],
    ];
    $html = '';
    foreach (array_intersect_key($lignes, array_flip($elements)) as [$libelle, $valeur]) {
        $html .= $valeur ? sprintf('<li>%s : %s</li>', esc_html($libelle), $valeur) : '';
    }

    return $html ? '<ul>' . $html . '</ul>' : '';
}

/** Bouton WhatsApp (message prérempli), suivi au besoin d'un second bouton en contour. */
function bloc_whatsapp(): string
{
    $libelle = trim((string) get_field('libelle')) ?: texte('pied_whatsapp');
    $message = trim((string) get_field('message')) ?: 'wa_message';
    $classe = get_field('style') === 'contour' ? 'bouton bouton-contour' : 'bouton';
    $html = sprintf('<a class="%s" href="%s" rel="noopener" target="_blank">%s</a>', $classe, esc_url(lien_whatsapp($message)), esc_html($libelle));
    $second = trim((string) get_field('lien_texte'));
    $url = trim((string) get_field('lien_url'));
    if ($second !== '' && $url !== '') {
        $html .= sprintf(' <a class="bouton bouton-contour" href="%s">%s</a>', esc_url(str_starts_with($url, '/') ? home_url($url) : $url), esc_html($second));
    }

    return '<p class="boutons">' . $html . '</p>';
}

add_action('acf/init', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }
    acf_add_local_field_group([
        'key' => 'group_us_bloc_whatsapp',
        'title' => 'Bouton WhatsApp',
        'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'us/whatsapp']]],
        'fields' => [
            ['key' => 'field_us_bloc_whatsapp_libelle', 'name' => 'libelle', 'label' => 'Texte du bouton', 'type' => 'text', 'placeholder' => 'Écrire sur WhatsApp'],
            ['key' => 'field_us_bloc_whatsapp_message', 'name' => 'message', 'label' => 'Message prérempli', 'type' => 'text', 'instructions' => 'Vide : le message prérempli des Coordonnées.'],
            ['key' => 'field_us_bloc_whatsapp_style', 'name' => 'style', 'label' => 'Style', 'type' => 'select', 'choices' => ['plein' => 'Plein', 'contour' => 'Contour'], 'default_value' => 'plein'],
            ['key' => 'field_us_bloc_whatsapp_lien_texte', 'name' => 'lien_texte', 'label' => 'Second bouton : texte', 'type' => 'text', 'instructions' => 'Facultatif, affiché à côté en contour.'],
            ['key' => 'field_us_bloc_whatsapp_lien_url', 'name' => 'lien_url', 'label' => 'Second bouton : adresse', 'type' => 'text', 'placeholder' => '/contact/'],
        ],
    ]);
    acf_add_local_field_group([
        'key' => 'group_us_bloc_coordonnees',
        'title' => 'Coordonnées',
        'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'us/coordonnees']]],
        'fields' => [
            [
                'key' => 'field_us_bloc_coordonnees_elements', 'name' => 'elements', 'label' => 'À afficher', 'type' => 'checkbox',
                'choices' => ['courriel' => 'E-mail', 'telephone' => 'Téléphone', 'whatsapp' => 'WhatsApp'],
                'default_value' => ['courriel', 'telephone', 'whatsapp'], 'layout' => 'horizontal',
            ],
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| Valeurs au fil du texte
|--------------------------------------------------------------------------
*/
add_shortcode('us_seuil', fn () => esc_html(seuil_affiche()));
add_shortcode('us_forfait', fn () => esc_html(forfait_affiche()));

/*
|--------------------------------------------------------------------------
| Questions fréquentes : données structurées FAQPage
|--------------------------------------------------------------------------
| Chaque bloc « Détails » (question + réponse) d'une page devient une question pour Google, dès qu'il y en a deux.
*/
function questions_page(int $id): array
{
    $questions = [];
    $parcourir = function (array $blocs) use (&$parcourir, &$questions) {
        foreach ($blocs as $bloc) {
            if ($bloc['blockName'] === 'core/details' && preg_match('#<summary>(.*?)</summary>#s', $bloc['innerHTML'], $m)) {
                $reponse = trim(wp_strip_all_tags(do_shortcode(implode('', array_map('render_block', $bloc['innerBlocks'])))));
                if ($reponse !== '') {
                    $questions[] = ['question' => trim(wp_strip_all_tags($m[1])), 'reponse' => preg_replace('/\s+/', ' ', $reponse)];
                }
            } elseif (! empty($bloc['innerBlocks'])) {
                $parcourir($bloc['innerBlocks']);
            }
        }
    };
    $parcourir(parse_blocks((string) get_post_field('post_content', $id)));

    return $questions;
}

add_action('wp_head', function () {
    if (! is_page() || ! has_block('core/details', get_queried_object_id())) {
        return;
    }
    $questions = questions_page(get_queried_object_id());
    if (count($questions) < 2) {
        return;
    }
    $donnees = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($q) => [
            '@type' => 'Question', 'name' => $q['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['reponse']],
        ], $questions),
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
}, 30);
