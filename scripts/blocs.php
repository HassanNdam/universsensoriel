<?php

/**
 * Passe les pages et les articles en blocs Gutenberg (paragraphes, titres, listes, boutons, questions…),
 * pour qu'ils se modifient bloc par bloc dans l'éditeur au lieu d'un seul bloc de HTML.
 *
 * - Fichiers sources (scripts/pages/*.html, scripts/blog/*.html) : réécrits en blocs, une seule fois.
 * - Pages et articles du site : leur contenu actuel est converti (les retouches faites dans l'administration
 *   sont gardées), puis l'empreinte du seed est remise à jour pour qu'ils ne passent pas pour « modifiés ».
 * - Un contenu qui contient déjà des blocs n'est pas touché : le script peut être relancé sans risque.
 *
 * Lancer : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/blocs.php --user=admin
 */

/** Codes courts posés seuls dans la page → blocs du thème. */
const US_BLOCS_CODES = ['us_boutiques' => 'us/boutiques', 'us_editeur' => 'us/editeur', 'us_hebergeur' => 'us/hebergeur'];

/** Montants de livraison écrits en dur → codes courts lus dans WooCommerce › Expédition. */
const US_BLOCS_MONTANTS = [
    'dès 49 €' => 'dès [us_seuil] €',
    'Dès 49 €' => 'Dès [us_seuil] €',
    '4,90 €' => '[us_forfait] €',
];

function us_bloc(string $nom, string $html, array $attributs = []): string
{
    $json = $attributs ? ' ' . wp_json_encode($attributs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

    return "<!-- wp:$nom$json -->\n$html\n<!-- /wp:$nom -->";
}

function us_bloc_theme(string $nom, array $donnees = []): string
{
    $attributs = ['name' => $nom, 'mode' => 'preview'];
    if ($donnees) {
        $attributs['data'] = [];
        foreach ($donnees as $cle => $valeur) {
            $attributs['data'][$cle] = $valeur;
            $attributs['data']['_' . $cle] = 'field_us_bloc_' . substr($nom, 3) . '_' . $cle;
        }
    }

    return "<!-- wp:$nom " . wp_json_encode($attributs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' /-->';
}

function us_interieur(DOMNode $noeud): string
{
    $html = '';
    foreach ($noeud->childNodes as $enfant) {
        $html .= $noeud->ownerDocument->saveHTML($enfant);
    }

    return trim($html);
}

/** Paragraphe de boutons (liens .bouton, code [us_whatsapp]) → bloc Boutons ou Bouton WhatsApp ; sinon null. */
function us_boutons(DOMElement $p): ?string
{
    $html = us_interieur($p);
    if (preg_match('#^\[us_whatsapp(?:\s+message="([^"]*)")?\](.*?)\[/us_whatsapp\]\s*(?:<a class="bouton[^"]*" href="([^"]*)">(.*?)</a>)?$#s', $html, $m)) {
        return us_bloc_theme('us/whatsapp', array_filter([
            'libelle' => html_entity_decode($m[2], ENT_QUOTES), 'message' => html_entity_decode($m[1], ENT_QUOTES), 'style' => 'plein',
            'lien_texte' => html_entity_decode($m[4] ?? '', ENT_QUOTES), 'lien_url' => $m[3] ?? '',
        ], 'strlen'));
    }
    $liens = [];
    foreach ($p->childNodes as $enfant) {
        if ($enfant instanceof DOMText && trim($enfant->textContent) === '') {
            continue;
        }
        if (! $enfant instanceof DOMElement || $enfant->tagName !== 'a' || ! str_contains(' ' . $enfant->getAttribute('class') . ' ', ' bouton ')) {
            return null;
        }
        $liens[] = $enfant;
    }
    if (! $liens) {
        return null;
    }
    $boutons = '';
    foreach ($liens as $a) {
        $contour = str_contains($a->getAttribute('class'), 'bouton-contour');
        $boutons .= us_bloc(
            'button',
            sprintf('<div class="wp-block-button%s"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>', $contour ? ' is-style-outline' : '', esc_attr($a->getAttribute('href')), us_interieur($a)),
            $contour ? ['className' => 'is-style-outline'] : []
        );
    }

    return us_bloc('buttons', '<div class="wp-block-buttons">' . $boutons . '</div>');
}

/** Un nœud de premier niveau → bloc(s). */
function us_noeud_en_bloc(DOMNode $noeud): string
{
    if ($noeud instanceof DOMText) {
        $texte = trim($noeud->textContent);
        if ($texte === '') {
            return '';
        }
        if (preg_match('/^\[(\w+)\]$/', $texte, $m) && isset(US_BLOCS_CODES[$m[1]])) {
            return us_bloc_theme(US_BLOCS_CODES[$m[1]]);
        }

        return us_bloc('paragraph', '<p>' . esc_html($texte) . '</p>');
    }
    if (! $noeud instanceof DOMElement) {
        return '';
    }
    $html = us_interieur($noeud);

    switch ($noeud->tagName) {
        case 'p':
            if (preg_match('/^\[(\w+)\]$/', $html, $m) && isset(US_BLOCS_CODES[$m[1]])) {
                return us_bloc_theme(US_BLOCS_CODES[$m[1]]);
            }

            return us_boutons($noeud) ?? us_bloc('paragraph', "<p>$html</p>");
        case 'h2':
        case 'h3':
        case 'h4':
            $niveau = (int) $noeud->tagName[1];

            return us_bloc('heading', "<h$niveau class=\"wp-block-heading\">$html</h$niveau>", $niveau === 2 ? [] : ['level' => $niveau]);
        case 'ul':
        case 'ol':
            if ($noeud->getAttribute('class') !== '') {
                break;
            }
            $elements = '';
            foreach ($noeud->childNodes as $li) {
                if ($li instanceof DOMElement && $li->tagName === 'li') {
                    $elements .= us_bloc('list-item', '<li>' . us_interieur($li) . '</li>');
                }
            }
            $balise = $noeud->tagName;

            return us_bloc('list', "<$balise class=\"wp-block-list\">$elements</$balise>", $balise === 'ol' ? ['ordered' => true] : []);
        case 'details':
            $question = '';
            $reponse = '';
            foreach ($noeud->childNodes as $enfant) {
                if ($enfant instanceof DOMElement && $enfant->tagName === 'summary') {
                    $question = us_interieur($enfant);
                } else {
                    $reponse .= us_noeud_en_bloc($enfant);
                }
            }

            return us_bloc('details', "<details class=\"wp-block-details\"><summary>$question</summary>$reponse</details>");
        case 'blockquote':
            $interieur = '';
            foreach ($noeud->childNodes as $enfant) {
                $interieur .= us_noeud_en_bloc($enfant);
            }

            return us_bloc('quote', "<blockquote class=\"wp-block-quote\">$interieur</blockquote>");
    }

    // Tout le reste (encarts, tableaux…) reste tel quel dans un bloc HTML.
    return us_bloc('html', $noeud->ownerDocument->saveHTML($noeud));
}

function us_en_blocs(string $html): string
{
    if (trim($html) === '' || str_contains($html, '<!-- wp:')) {
        return $html;
    }
    $html = strtr($html, US_BLOCS_MONTANTS);
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="us-racine">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $blocs = [];
    foreach ($document->getElementById('us-racine')->childNodes as $noeud) {
        if (($bloc = us_noeud_en_bloc($noeud)) !== '') {
            $blocs[] = $bloc;
        }
    }

    return implode("\n\n", $blocs) . "\n";
}

// 1. Fichiers sources.
$fichiers = array_merge(glob(__DIR__ . '/pages/*.html') ?: [], glob(__DIR__ . '/blog/*.html') ?: []);
$faits = 0;
foreach ($fichiers as $fichier) {
    $avant = (string) file_get_contents($fichier);
    $apres = us_en_blocs($avant);
    if ($apres !== $avant) {
        file_put_contents($fichier, $apres);
        $faits++;
    }
}
WP_CLI::log("  $faits fichiers sources passés en blocs");

// 2. Pages et articles du site.
$contenus = get_posts(['post_type' => ['page', 'post'], 'post_status' => ['publish', 'draft', 'private'], 'numberposts' => -1]);
$faits = 0;
foreach ($contenus as $contenu) {
    $avant = $contenu->post_content;
    if (str_contains($avant, '[forminator_form') || trim($avant) === '') {
        continue; // page Contact (formulaire seul) et pages vides : rien à convertir
    }
    $apres = us_en_blocs($avant);
    if ($apres === $avant) {
        continue;
    }
    wp_update_post(wp_slash(['ID' => $contenu->ID, 'post_content' => $apres]));
    $enregistre = (string) get_post_field('post_content', $contenu->ID);
    // Empreinte du seed : le contenu converti devient la référence, il ne passe pas pour retouché dans l'admin.
    update_post_meta($contenu->ID, '_us_contenu_seed', md5($enregistre));
    if ($contenu->post_type === 'post' && is_file($source = __DIR__ . "/blog/{$contenu->post_name}.html")) {
        $articles = json_decode((string) file_get_contents(__DIR__ . '/blog/articles.json'), true)['articles'] ?? [];
        foreach ($articles as $article) {
            if ($article['slug'] === $contenu->post_name) {
                update_post_meta($contenu->ID, '_us_empreinte', md5(wp_json_encode($article) . trim((string) file_get_contents($source))));
            }
        }
    }
    $faits++;
    WP_CLI::log("  en blocs : {$contenu->post_title}");
}
WP_CLI::log("  $faits pages et articles passés en blocs");
