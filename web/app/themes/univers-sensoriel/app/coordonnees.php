<?php

/**
 * Coordonnées dans le texte des pages : des codes courts lisent Univers Sensoriel › Coordonnées (app/reglages.php),
 * pour qu'un numéro ou une adresse ne soit écrit qu'à un seul endroit.
 *
 * - [us_contact champ="courriel"]             adresse e-mail cliquable ; champ="telephone" : numéro cliquable ;
 * - [us_whatsapp message="…"]Texte[/us_whatsapp]  bouton vers WhatsApp, message prérempli ;
 * - [us_boutiques]                            cartes des boutiques : enseigne, adresse, horaires, téléphones ;
 * - [us_editeur]                              liste « éditeur du site » des mentions légales ;
 * - [us_hebergeur]                            paragraphe de l'hébergeur.
 *
 * L'adresse e-mail reçoit aussi les messages du formulaire de contact : quand elle change, les formulaires
 * Forminator sont mis à jour (voir en bas du fichier).
 */

namespace App;

/** Lien cliquable vers une coordonnée : « courriel » ou « telephone ». */
function contact_lien(string $champ): string
{
    $c = coordonnees();

    return match ($champ) {
        'courriel', 'email' => $c['courriel'] ? sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($c['courriel'])) : '',
        'telephone' => $c['telephone'] ? sprintf('<a href="%s">%s</a>', esc_url(lien_telephone($c['telephone']), ['tel']), esc_html($c['telephone'])) : '',
        default => '',
    };
}

add_shortcode('us_contact', fn ($atts) => contact_lien((string) shortcode_atts(['champ' => 'courriel'], $atts)['champ']));

add_shortcode('us_whatsapp', function ($atts, $contenu = ''): string {
    $atts = shortcode_atts(['message' => 'wa_message', 'classe' => 'bouton'], $atts);

    return sprintf(
        '<a class="%s" href="%s" rel="noopener" target="_blank">%s</a>',
        esc_attr($atts['classe']), esc_url(lien_whatsapp((string) $atts['message'])), esc_html(trim((string) $contenu) ?: texte('pied_whatsapp'))
    );
});

add_shortcode('us_boutiques', function (): string {
    $html = '';
    $boutiques = coordonnees()['boutiques'];
    foreach ($boutiques as $b) {
        // Carte : coordonnées d'un côté, horaires de l'autre (côte à côte quand la boutique est seule).
        $html .= '<div><div><h3>' . esc_html(boutique_nom($b)) . '</h3>';
        if ($lieu = boutique_lieu($b)) {
            $html .= '<p>' . esc_html($lieu) . '</p>';
        }
        if ($numeros = array_filter([$b['telephone'], $b['telephone_2']])) {
            $html .= '<p>' . implode('<br>', array_map(fn ($n) => sprintf('<a href="%s">%s</a>', esc_url(lien_telephone($n), ['tel']), esc_html($n)), $numeros)) . '</p>';
        }
        $html .= ($b['mention'] ? '<p>' . esc_html($b['mention']) . '</p>' : '') . '</div>';
        $horaires = '';
        foreach ($b['horaires'] as $h) {
            if (! empty($h['jours'])) {
                $horaires .= sprintf('<li><span>%s</span> %s</li>', esc_html($h['jours']), esc_html($h['heures'] ?? ''));
            }
        }
        $html .= ($horaires ? '<ul class="horaires">' . $horaires . '</ul>' : '') . '</div>';
    }

    return $html ? '<div class="cartes' . (count($boutiques) === 1 ? ' cartes-une' : '') . '">' . $html . '</div>' : '';
});

add_shortcode('us_editeur', function (): string {
    $lignes = [
        esc_html(texte('societe_nom')),
        texte('societe_forme') ? 'Forme juridique : ' . esc_html(texte('societe_forme')) : '',
        texte('societe_adresse') ? 'Siège : ' . esc_html(texte('societe_adresse')) : '',
        texte('societe_siret') ? 'SIRET : ' . esc_html(texte('societe_siret')) : '',
        texte('societe_tva') ? 'N° de TVA : ' . esc_html(texte('societe_tva')) : '',
        texte('societe_responsable') ? 'Responsable de la publication : ' . esc_html(texte('societe_responsable')) : '',
        ($courriel = contact_lien('courriel')) ? 'E-mail : ' . $courriel : '',
        ($telephone = contact_lien('telephone')) ? 'Téléphone : ' . $telephone : '',
    ];
    $lignes = array_filter($lignes);

    return $lignes ? '<ul><li>' . implode('</li><li>', $lignes) . '</li></ul>' : '';
});

add_shortcode('us_hebergeur', function (): string {
    $site = esc_url(texte('hebergeur_site'));
    $parties = array_filter([
        esc_html(implode(', ', array_filter([texte('hebergeur_nom'), texte('hebergeur_adresse')]))),
        texte('hebergeur_telephone') ? 'Téléphone : ' . esc_html(texte('hebergeur_telephone')) : '',
    ]);
    $texte = $parties ? implode('. ', $parties) . '.' : '';
    if ($site) {
        $texte .= sprintf(' <a href="%s" rel="noopener" target="_blank">%s</a>', $site, esc_html(preg_replace('#^https?://(www\.)?#', '', untrailingslashit($site))));
    }

    return trim($texte) === '' ? '' : '<p>' . trim($texte) . '</p>';
});

/*
|--------------------------------------------------------------------------
| Formulaires : l'adresse de la Maison suit le réglage
|--------------------------------------------------------------------------
| Forminator garde l'adresse du destinataire dans chaque formulaire. Quand l'adresse e-mail change dans
| Univers Sensoriel › Coordonnées, elle est remplacée dans les e-mails des formulaires du site.
*/
add_action('acf/save_post', function ($objet) {
    if ($objet === 'options') {
        $GLOBALS['us_courriel_avant'] = (string) (get_field('courriel', 'option') ?? reglages_champs()['courriel'][2]);
    }
}, 5);

add_action('acf/save_post', function ($objet) {
    $avant = $GLOBALS['us_courriel_avant'] ?? '';
    $apres = $objet === 'options' ? sanitize_email((string) get_field('courriel', 'option')) : '';
    if (! $avant || ! $apres || $avant === $apres || ! class_exists('Forminator_API')) {
        return;
    }
    foreach ((array) get_option('us_formulaires', []) as $id) {
        $formulaire = \Forminator_API::get_form((int) $id);
        if (is_wp_error($formulaire) || empty($formulaire->notifications)) {
            continue;
        }
        foreach ($formulaire->notifications as &$courriel) {
            foreach (['recipients', 'replyto-email'] as $champ) {
                if (isset($courriel[$champ])) {
                    $courriel[$champ] = str_ireplace($avant, $apres, (string) $courriel[$champ]);
                }
            }
        }
        unset($courriel);
        $formulaire->save();
    }
}, 20);
