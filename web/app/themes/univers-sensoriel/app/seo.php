<?php

/**
 * Référencement calculé à l'affichage (Yoast SEO) :
 * titres et descriptions des fiches produit et des familles olfactives, données structurées, plan du site.
 * Les pages et les rayons sont réglés dans scripts/seo.json ; une valeur saisie dans Yoast l'emporte toujours.
 */

namespace App;

if (! defined('WPSEO_VERSION')) {
    return;
}

/** Coupe un texte à la dernière phrase ou au dernier mot avant la limite. */
function raccourcir(string $texte, int $limite): string
{
    $texte = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($texte)));
    if (mb_strlen($texte) <= $limite) {
        return $texte;
    }
    $coupe = mb_substr($texte, 0, $limite);
    $phrase = mb_strrpos($coupe, '. ');
    if ($phrase !== false && $phrase > $limite * 0.6) {
        return mb_substr($coupe, 0, $phrase + 1);
    }

    return rtrim(mb_substr($coupe, 0, (int) mb_strrpos($coupe, ' ')), ' ,;:') . '…';
}

/** Vrai si Yoast n'a pas de valeur saisie pour la fiche (on calcule alors la nôtre). */
function sans_meta_yoast(string $meta): bool
{
    return is_singular('product') && get_post_meta(get_queried_object_id(), $meta, true) === '';
}

/**
 * Titre d'une fiche : « Absolutely – Parfum femme chypré fruité | Univers Sensoriel ».
 * Le mot-clé (type de produit) suit le nom ; la marque saute si le titre dépasse 60 caractères.
 */
function titre_fiche(\WC_Product $produit): string
{
    $infos = fiche($produit);
    $nom = $produit->get_name();
    $type = '';
    if ($infos['parfum'] && $infos['rayon']) {
        $type = match ($infos['rayon']->slug) {
            'parfums-femme' => 'Parfum femme',
            'parfums-homme' => 'Parfum homme',
            'parfums-enfant' => 'Parfum enfant',
            default => 'Extrait de parfum',
        };
        if ($infos['familles']) {
            $type .= ' ' . mb_strtolower(implode(' ', array_map(fn($f) => $f->name, array_slice($infos['familles'], 0, 2))));
        }
    } elseif ($infos['rayon'] && ! str_contains(mb_strtolower($nom), mb_strtolower(explode(' ', $infos['rayon']->name)[0]))) {
        $type = wp_specialchars_decode($infos['rayon']->name);
    }
    $titre = $type ? "$nom – $type" : $nom;
    if (mb_strlen($titre) > 60) {
        $titre = $nom;
    }

    return mb_strlen($titre) <= 41 ? "$titre | Univers Sensoriel" : $titre;
}

function description_fiche(\WC_Product $produit): string
{
    $infos = fiche($produit);
    $brut = fn(string $html) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8')));
    $accroche = $brut($produit->get_short_description());
    $description = $brut($produit->get_description());
    // Accroche trop courte (un simple titre) : on prend la description, qui commence souvent par ce même titre.
    $source = mb_strlen($accroche) >= 90 ? $accroche : (str_starts_with($description, $accroche) ? $description : trim("$accroche. $description"));
    $texte = raccourcir($source, 128);
    $suite = '';
    if ($produit->is_type('variable') && ($min = $produit->get_variation_price('min', true)) !== '') {
        $suite = ' Dès ' . html_entity_decode(wp_strip_all_tags(wc_price($min)), ENT_QUOTES, 'UTF-8') . '.';
    }

    return trim($texte . $suite);
}

$titreProduit = function ($titre) {
    if (sans_meta_yoast('_yoast_wpseo_title') && ($produit = wc_get_product(get_queried_object_id()))) {
        return titre_fiche($produit);
    }
    if (is_tax('pa_famille-olfactive')) {
        $terme = get_queried_object();

        return 'Parfums ' . famille_au_pluriel($terme->name) . ' : ' . $terme->count . ' extraits de parfum | Univers Sensoriel';
    }

    return $titre;
};
$descriptionProduit = function ($description) {
    if (sans_meta_yoast('_yoast_wpseo_metadesc') && ($produit = wc_get_product(get_queried_object_id()))) {
        return description_fiche($produit);
    }
    if (is_tax('pa_famille-olfactive') && ($terme = get_queried_object())) {
        return raccourcir($terme->description, 110) . ' Extraits longue tenue, livraison offerte dès ' . seuil_affiche() . ' €.';
    }

    return $description;
};
foreach (['wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title'] as $filtre) {
    add_filter($filtre, $titreProduit, 20);
}
foreach (['wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description'] as $filtre) {
    add_filter($filtre, $descriptionProduit, 20);
}

/** Organization : coordonnées et boutiques de la Maison. */
add_filter('wpseo_schema_organization', function ($data) {
    $c = coordonnees();
    $data['email'] = $c['courriel'];
    $data['telephone'] = $c['telephone'];
    $data['slogan'] = texte('slogan');
    $data['contactPoint'] = [
        '@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => $c['telephone'],
        'email' => $c['courriel'], 'availableLanguage' => ['French'],
    ];

    return $data;
});

/** Product : la marque est la gamme (Olfazeta, Aurodhea…), le vendeur Univers Sensoriel. */
add_filter('woocommerce_structured_data_product', function ($data, $produit) {
    $infos = fiche($produit);
    if ($infos['gamme']) {
        $data['brand'] = ['@type' => 'Brand', 'name' => $infos['gamme']];
    }
    if ($infos['rayon']) {
        $data['category'] = $infos['rayon']->name;
    }

    return $data;
}, 10, 2);

/** Plan du site : jamais de page technique, même hors production (Yoast n'y tient pas ses « indexables »). */
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
    foreach (['cart', 'checkout', 'myaccount'] as $page) {
        if (function_exists('wc_get_page_id') && ($id = wc_get_page_id($page)) > 0) {
            $ids[] = $id;
        }
    }

    return $ids;
});

/** Les archives d'attributs qui ne servent qu'aux filtres n'entrent pas dans le plan du site. */
add_filter('wpseo_sitemap_exclude_taxonomy', fn($exclure, $taxonomie) => in_array($taxonomie, ['pa_genre', 'pa_gamme', 'pa_format', 'product_tag'], true) ?: $exclure, 10, 2);

/** Rayons vides ou réservés aux brouillons : pas d'indexation. */
add_filter('wpseo_robots', function ($robots) {
    if (is_tax('product_cat') && ($terme = get_queried_object()) && (int) $terme->count === 0) {
        return 'noindex, follow';
    }

    return $robots;
}, 20);

/**
 * Image de partage (Facebook, WhatsApp, LinkedIn, X) : un JPG de 1200 × 630 fabriqué à partir d'une image de la médiathèque
 * et rangé dans uploads/partage/. Un produit détouré est posé entier sur fond ivoire ; une photo est recadrée en paysage.
 * Le fichier est créé à la première demande (ou d'avance par le seed) puis réutilisé tant que l'image d'origine ne change pas.
 *
 * @return array{url: string, width: int, height: int, type: string}|null
 */
function image_partage(int $imageId): ?array
{
    $source = $imageId ? get_attached_file($imageId) : '';
    if (! $source || ! is_file($source) || ! function_exists('imagecreatetruecolor')) {
        return null;
    }
    $envoi = wp_upload_dir();
    $nom = pathinfo($source, PATHINFO_FILENAME) . '-' . substr(md5($imageId . '|' . filemtime($source) . '|' . filesize($source)), 0, 8) . '.jpg';
    $fichier = $envoi['basedir'] . '/partage/' . $nom;
    $resultat = ['url' => $envoi['baseurl'] . '/partage/' . $nom, 'width' => 1200, 'height' => 630, 'type' => 'image/jpeg'];
    if (is_file($fichier)) {
        return $resultat;
    }
    $image = match (wp_check_filetype($source)['type']) {
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
        'image/png' => @imagecreatefrompng($source),
        'image/jpeg' => @imagecreatefromjpeg($source),
        default => false,
    };
    if (! $image || ! wp_mkdir_p(dirname($fichier))) {
        return null;
    }
    [$l, $h] = [imagesx($image), imagesy($image)];
    $toile = imagecreatetruecolor(1200, 630);
    imagefill($toile, 0, 0, imagecolorallocate($toile, 0xF7, 0xF0, 0xE4)); // ivoire de la charte
    if (get_post_meta($imageId, '_us_detoure', true) === '1') {
        $e = min(1100 / $l, 570 / $h); // produit entier, avec une marge
        [$nl, $nh] = [(int) round($l * $e), (int) round($h * $e)];
        imagecopyresampled($toile, $image, intdiv(1200 - $nl, 2), intdiv(630 - $nh, 2), 0, 0, $nl, $nh, $l, $h);
    } else {
        $e = max(1200 / $l, 630 / $h); // photo : on remplit le cadre autour du centre
        [$sl, $sh] = [(int) round(1200 / $e), (int) round(630 / $e)];
        imagecopyresampled($toile, $image, 0, 0, intdiv($l - $sl, 2), intdiv($h - $sh, 2), 1200, 630, $sl, $sh);
    }

    return imagejpeg($toile, $fichier, 86) ? $resultat : null;
}

/** L'image de partage de la page affichée : fiche produit, article, rayon ou famille olfactive (première fiche du rayon). */
function image_partage_courante(): ?array
{
    static $image = false;
    if ($image !== false) {
        return $image;
    }
    $imageId = 0;
    if (is_singular(['product', 'post'])) {
        $imageId = (int) get_post_thumbnail_id(get_queried_object_id());
    } elseif (is_tax(['product_cat', 'pa_famille-olfactive']) && ($terme = get_queried_object())) {
        $imageId = (int) get_term_meta($terme->term_id, 'thumbnail_id', true);
        if (! $imageId) {
            $premier = get_posts([
                'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC',
                'meta_key' => '_thumbnail_id', 'tax_query' => [['taxonomy' => $terme->taxonomy, 'terms' => $terme->term_id]],
            ]);
            $imageId = $premier ? (int) get_post_thumbnail_id($premier[0]) : 0;
        }
    }

    return $image = $imageId ? image_partage($imageId) : null;
}

/** Yoast garde la main quand une image de partage a été choisie dans son onglet « Réseaux sociaux ». */
$partage = function (string $cle) {
    return function ($valeur, $presentation = null) use ($cle) {
        if (($presentation->model->open_graph_image_source ?? '') === 'set-by-user') {
            return $valeur;
        }

        return image_partage_courante()[$cle] ?? $valeur;
    };
};
add_filter('wpseo_opengraph_image', $partage('url'), 20, 2);
add_filter('wpseo_opengraph_image_type', $partage('type'), 20, 2);
add_filter('wpseo_opengraph_image_width', $partage('width'), 20, 2);
add_filter('wpseo_opengraph_image_height', $partage('height'), 20, 2);
add_filter('wpseo_twitter_image', $partage('url'), 20, 2);

/** Une fiche produit se partage comme un produit, pas comme un article. */
add_filter('wpseo_opengraph_type', fn($type) => is_singular('product') ? 'product' : $type);

/**
 * Analyse Yoast dans l'admin : les pages et les fiches sont construites par les gabarits Blade du thème, pas par l'éditeur.
 * Yoast ne voyait donc qu'un contenu vide ou partiel (« 0 mot », « aucune image », « aucun lien »). On lui donne la page
 * publiée telle que Google la lit : le contenu principal (<main>), sans l'en-tête, le menu ni le pied de page.
 * Une modification non enregistrée n'est vue qu'après « Mettre à jour ». scripts/seo-scores.js fait le même calcul en série.
 */
add_action('admin_enqueue_scripts', function ($ecran) {
    $post = get_post();
    if ($ecran !== 'post.php' || ! $post || $post->post_status !== 'publish'
        || ! in_array($post->post_type, ['page', 'product'], true)) {
        return;
    }
    wp_register_script('us-analyse-yoast', false, [], null, true);
    wp_enqueue_script('us-analyse-yoast');
    wp_add_inline_script('us-analyse-yoast', 'window.usAnalyseYoast = ' . wp_json_encode([
        // Jeton signé : la page d'attente (app/maintenance.php) laisse passer cette lecture.
        'url' => add_query_arg('us-analyse', jeton_analyse(), get_permalink($post)),
    ]) . ';' . <<<'JS'
(function () {
    var contenu = null;
    function lire() {
        fetch(window.usAnalyseYoast.url, { credentials: 'omit' })
            .then(function (r) { return r.text(); })
            .then(function (texte) {
                var principal = new DOMParser().parseFromString(texte, 'text/html').querySelector('main');
                if (principal) {
                    principal.querySelectorAll('script, style, noscript, template, form, svg').forEach(function (e) { e.remove(); });
                    contenu = principal.innerHTML;
                }
            })
            .catch(function () { contenu = null; })
            .finally(function () {
                // Ordre imposé par Yoast : « prêt » d'abord, sinon la modification est refusée.
                YoastSEO.app.pluginReady('usPage');
                YoastSEO.app.registerModification('content', function (actuel) { return contenu || actuel; }, 'usPage', 5);
                YoastSEO.app.pluginReloaded('usPage');
            });
    }
    (function attendre(essais) {
        if (window.YoastSEO && YoastSEO.app && YoastSEO.app.registerPlugin) {
            YoastSEO.app.registerPlugin('usPage', { status: 'loading' });
            lire();
        } else if (essais > 0) {
            setTimeout(function () { attendre(essais - 1); }, 300);
        }
    })(60);
})();
JS);
});
