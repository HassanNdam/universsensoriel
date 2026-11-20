<?php

/**
 * Réglages du site (menu « Univers Sensoriel » de l'administration, Secure Custom Fields).
 *
 * Recopie dans l'administration la valeur d'origine de chaque réglage décrit dans le thème (App\REGLAGES,
 * app/reglages.php), tant qu'il n'a pas été enregistré : les écrans s'ouvrent ainsi remplis, listes comprises.
 * Un réglage déjà enregistré n'est jamais touché, même avec US_SEED_FORCE (ce sont les textes de la Maison).
 * Une image d'origine est importée dans la médiathèque ; une image vidée dans l'écran reprend celle du thème.
 *
 * Met aussi à jour le texte des pages qui citaient les coordonnées en dur (codes courts [us_…], app/coordonnees.php).
 *
 * Lancer seul : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/reglages.php --user=admin
 */
if (! function_exists('update_field') || ! function_exists('App\\reglages_champs')) {
    WP_CLI::warning('Secure Custom Fields ou le thème est inactif : réglages non recopiés.');

    return;
}

require_once __DIR__ . '/fonctions.php';

// Valeur d'origine telle que SCF l'enregistre : un rayon est un identifiant ; une image d'origine (visuel du thème)
// est importée dans la médiathèque, pour que l'écran montre la photo actuelle et qu'on puisse la remplacer.
$pourScf = function (array $champ, mixed $valeur) use (&$pourScf) {
    switch ($champ[0]) {
        case 'image':
            $fichier = is_string($valeur) && $valeur !== '' ? get_theme_file_path("resources/images/site/$valeur.webp") : '';

            return $fichier && is_file($fichier) ? (us_media($fichier, '', "site/$valeur.webp") ?: '') : '';
        case 'rayon':
        case 'rayons':
            $ids = [];
            foreach ((array) $valeur as $slug) {
                $terme = get_term_by('slug', (string) $slug, 'product_cat');
                if ($terme) {
                    $ids[] = (int) $terme->term_id;
                }
            }

            return $champ[0] === 'rayon' ? ($ids[0] ?? '') : $ids;
        case 'case':
            // « false » n'est pas enregistré : la case reprendrait sa valeur par défaut.
            return $valeur ? 1 : 0;
        case 'liste':
            $lignes = [];
            foreach ((array) $valeur as $ligne) {
                $propre = [];
                foreach ($champ[4] as $sous => $definition) {
                    $propre[$sous] = $pourScf($definition, $ligne[$sous] ?? ($definition[0] === 'liste' ? [] : ''));
                }
                $lignes[] = $propre;
            }

            return $lignes;
        default:
            return $valeur;
    }
};

$recopies = 0;
foreach (App\reglages_champs() as $cle => $champ) {
    if (get_option('options_' . $cle, null) !== null) {
        continue;
    }
    update_field('field_us_' . $cle, $pourScf($champ, $champ[2] ?? ''), 'option');
    $recopies++;
}
WP_CLI::log($recopies ? "  $recopies réglages recopiés dans l'administration" : '  réglages déjà en place');

// Images restées vides (premiers réglages) : la photo d'origine est importée et posée, pour que l'écran montre
// ce que le site affiche déjà. Le rendu ne change pas : une image vide montrait déjà ce visuel.
$images = 0;
foreach (App\reglages_champs() as $cle => $champ) {
    if ($champ[0] === 'image' && get_option('options_' . $cle, null) === '' && ($id = $pourScf($champ, $champ[2] ?? ''))) {
        update_field('field_us_' . $cle, $id, 'option');
        $images++;
    }
}
// Même chose dans les listes (tuiles des univers…) : ligne par ligne, la photo d'origine de même rang.
foreach (App\reglages_champs() as $cle => $champ) {
    $sousImages = $champ[0] === 'liste' ? array_keys(array_filter($champ[4] ?? [], fn ($d) => $d[0] === 'image')) : [];
    $lignes = $sousImages ? get_field($cle, 'option', false) : null;
    if (! $sousImages || ! is_array($lignes)) {
        continue;
    }
    $lignes = (array) get_field($cle, 'option');
    $change = false;
    foreach ($lignes as $rang => &$ligne) {
        foreach ($sousImages as $sous) {
            $origine = $champ[2][$rang][$sous] ?? '';
            if (empty($ligne[$sous]) && $origine && ($id = $pourScf(['image'], $origine))) {
                $ligne[$sous] = $id;
                $change = true;
                $images++;
            }
        }
    }
    unset($ligne);
    if ($change) {
        update_field('field_us_' . $cle, $lignes, 'option');
    }
}
if ($images) {
    WP_CLI::log("  $images images d'origine posées dans les réglages");
}

// Pages qui citaient les coordonnées en dur : seuls ces passages sont remplacés par les codes courts,
// le reste du texte (retouché ou non dans l'administration) n'est pas touché.
$courriel = '<a href="mailto:contact@universsensoriel.com">contact@universsensoriel.com</a>';
$telephone = '<a href="tel:+33605784011">+33 6 05 78 40 11</a>';
$remplacements = [
    'mentions-legales' => [
        '#<ul>\s*<li>Univers Sensoriel by K\.Elle.*?</ul>#s' => '[us_editeur]',
        '#<p>IONOS SARL.*?</p>#s' => '[us_hebergeur]',
    ],
    'conditions-generales-de-vente' => [],
    'politique-de-confidentialite' => [],
    'devenir-consultante' => [
        '#<a class="bouton" href="https://wa\.me/33605784011[^"]*">([^<]*)</a>#' => '[us_whatsapp message="Bonjour, j\'aimerais en savoir plus sur le programme consultante."]$1[/us_whatsapp]',
    ],
    'nos-boutiques' => [
        '#<div class="cartes">.*?</div>\s*</div>#s' => '[us_boutiques]',
    ],
];
foreach ($remplacements as $slug => $motifs) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    if (! $page) {
        continue;
    }
    $texte = (string) preg_replace(array_keys($motifs), array_values($motifs), $page->post_content);
    $texte = str_replace([$courriel, $telephone], ['[us_contact champ="courriel"]', '[us_contact champ="telephone"]'], $texte);
    if ($texte !== $page->post_content) {
        wp_update_post(wp_slash(['ID' => $page->ID, 'post_content' => $texte]));
        WP_CLI::log("  page mise à jour (coordonnées lues dans les réglages) : {$page->post_title}");
    }
}
