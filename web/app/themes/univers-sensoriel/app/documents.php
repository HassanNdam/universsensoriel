<?php

/**
 * Documentation et certifications : catalogues, certificats, engagements du fabricant.
 *
 * Les documents se gèrent dans Univers Sensoriel › Documentation (app/reglages.php) : un fichier par ligne, sa rubrique,
 * une couverture facultative et une case « Afficher sur le site ». La page /documentation-et-certifications/ les range
 * par rubrique (resources/views/page-documentation-et-certifications.blade.php).
 *
 * Premier remplissage : importer_documents() reprend la liste préparée par scripts/catalogue/documentation.py
 * (documents.json, PDF et couvertures), par le seed en local et par app/mises-a-jour.php en ligne.
 */

namespace App;

/** Documents publiés, rangés par rubrique (dans l'ordre de RUBRIQUES_DOCUMENTS) ; les rubriques vides sont retirées. */
function documents(): array
{
    $rubriques = array_fill_keys(array_keys(RUBRIQUES_DOCUMENTS), []);
    foreach (lignes('documents', ['fichier']) as $ligne) {
        // Case jamais enregistrée (ligne plus ancienne que la case) : le document est affiché.
        if (isset($ligne['afficher']) && ! $ligne['afficher']) {
            continue;
        }
        $fichier = (int) (is_array($ligne['fichier']) ? ($ligne['fichier']['ID'] ?? 0) : $ligne['fichier']);
        $chemin = $fichier ? get_attached_file($fichier) : '';
        if (! $chemin || ! is_file($chemin)) {
            continue;
        }
        $rubrique = isset($rubriques[$ligne['rubrique'] ?? '']) ? $ligne['rubrique'] : 'catalogues';
        $couverture = $ligne['couverture'] ?? 0;
        $rubriques[$rubrique][] = [
            'titre' => trim((string) ($ligne['titre'] ?? '')) ?: get_the_title($fichier),
            'precision' => trim((string) ($ligne['description'] ?? '')),
            'url' => wp_get_attachment_url($fichier),
            'format' => strtoupper(pathinfo($chemin, PATHINFO_EXTENSION)),
            'taille' => size_format(filesize($chemin), 1),
            'couverture' => is_array($couverture) ? (int) ($couverture['ID'] ?? 0) : (int) $couverture,
        ];
    }

    return array_filter($rubriques);
}

/** Titre d'une rubrique (onglet Rubriques), celui d'origine s'il a été vidé. */
function titre_rubrique(string $rubrique): string
{
    return texte('documents_rubrique_' . $rubrique) ?: (RUBRIQUES_DOCUMENTS[$rubrique] ?? '');
}

/**
 * Importe dans la médiathèque les documents de documents.json ($dossier : PDF et couvertures, chemins relatifs du JSON),
 * puis, une fois tous importés, remplit la liste de l'administration si elle est encore vide (jamais écrasée).
 * Un fichier déjà importé ne l'est pas deux fois (import_media, app/import-produits.php).
 * $secondes > 0 : s'arrête après ce temps. Renvoie le nombre de documents restant à importer.
 */
function importer_documents(array $liste, string $dossier, int $secondes = 0): int
{
    $debut = microtime(true);
    // Pas d'aperçu des PDF (Imagick lirait chaque catalogue) : la page montre la couverture choisie.
    add_filter('fallback_intermediate_image_sizes', '__return_empty_array', 99);
    $lignes = [];
    foreach ($liste as $rang => $document) {
        if ($secondes > 0 && microtime(true) - $debut > $secondes) {
            remove_filter('fallback_intermediate_image_sizes', '__return_empty_array', 99);

            return count($liste) - $rang;
        }
        $fichier = import_media($dossier, $document['fichier'], '');
        if (! $fichier) {
            continue;
        }
        if (get_the_title($fichier) !== $document['titre']) {
            wp_update_post(['ID' => $fichier, 'post_title' => $document['titre']]);
        }
        $couverture = $document['couverture'] ? import_media($dossier, $document['couverture'], '') : 0;
        $lignes[] = [
            'titre' => $document['titre'],
            'rubrique' => $document['rubrique'],
            'fichier' => $fichier,
            'couverture' => $couverture ?: '',
            'description' => $document['description'],
            'afficher' => empty($document['afficher']) ? 0 : 1,
        ];
    }
    remove_filter('fallback_intermediate_image_sizes', '__return_empty_array', 99);
    if ($lignes && ! (int) get_option('options_documents', 0) && function_exists('update_field')) {
        update_field('field_us_documents', $lignes, 'option');
    }

    return 0;
}
