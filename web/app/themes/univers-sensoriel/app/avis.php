<?php

/**
 * Avis clients : de vrais avis WooCommerce, affichés tels quels.
 * Aucune note moyenne n'est calculée pour la vitrine : avec peu d'avis, elle ne voudrait rien dire.
 */

namespace App;

/**
 * Avis publiés, du plus récent au plus ancien.
 * $args : produit (id), accueil (bool : seulement ceux choisis pour l'accueil), nombre.
 */
function avis(array $args = []): array
{
    $requete = ['type' => 'review', 'status' => 'approve', 'orderby' => 'comment_date', 'order' => 'DESC', 'number' => $args['nombre'] ?? 0];
    if (! empty($args['produit'])) {
        $requete['post_id'] = (int) $args['produit'];
    }
    if (! empty($args['accueil'])) {
        $requete['meta_query'] = [['key' => '_us_accueil', 'value' => '1']];
    }
    $liste = [];
    foreach (get_comments($requete) as $commentaire) {
        $produit = get_post_type($commentaire->comment_post_ID) === 'product' ? wc_get_product($commentaire->comment_post_ID) : null;
        $liste[] = [
            'auteur' => $commentaire->comment_author,
            'texte' => $commentaire->comment_content,
            'note' => (int) get_comment_meta($commentaire->comment_ID, 'rating', true),
            'verifie' => (bool) get_comment_meta($commentaire->comment_ID, 'verified', true),
            'extrait' => (bool) get_comment_meta($commentaire->comment_ID, '_us_extrait', true),
            'ancien' => get_comment_meta($commentaire->comment_ID, '_us_origine', true) === 'ancienne-boutique',
            'date' => $commentaire->comment_date,
            'date_lisible' => date_i18n('F Y', strtotime($commentaire->comment_date)),
            'produit' => $produit,
        ];
    }

    return $liste;
}

/** Note sous forme d'étoiles pleines et vides, avec son équivalent lisible. */
function etoiles(int $note): string
{
    $note = max(0, min(5, $note));

    return sprintf(
        '<span class="etoiles" role="img" aria-label="Note : %d sur 5"><span aria-hidden="true">%s</span><span class="etoiles-vides" aria-hidden="true">%s</span></span>',
        $note, str_repeat('★', $note), str_repeat('★', 5 - $note)
    );
}

/** Vrai si la personne connectée a acheté le produit et peut donc donner son avis. */
function peut_donner_avis(\WC_Product $produit): bool
{
    $utilisateur = wp_get_current_user();

    return $utilisateur->exists() && comments_open($produit->get_id())
        && wc_customer_bought_product($utilisateur->user_email, $utilisateur->ID, $produit->get_id());
}

/** Les avis sur une fiche ne sont pas des commentaires de blog : pas de champ « site web », pas de notification publique. */
add_filter('comment_form_default_fields', function ($champs) {
    unset($champs['url']);

    return $champs;
});

/** Trois derniers articles du journal, pour l'accueil et le bas des articles. */
function articles_recents(int $nombre = 3, array $exclure = []): array
{
    return get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $nombre, 'post__not_in' => $exclure]);
}

/** Temps de lecture d'un article, en minutes (200 mots par minute). */
function temps_de_lecture(int $article): int
{
    return max(1, (int) ceil(str_word_count(wp_strip_all_tags(get_post_field('post_content', $article))) / 200));
}
