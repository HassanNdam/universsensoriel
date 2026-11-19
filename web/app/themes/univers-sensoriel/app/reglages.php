<?php

/**
 * Réglages du site modifiables dans l'administration : menu « Univers Sensoriel » (Secure Custom Fields).
 *
 * REGLAGES ci-dessous est la seule description des réglages : elle fabrique les écrans de l'administration
 * (pages, onglets, champs) et donne à chaque réglage sa valeur d'origine. Ajouter une ligne ici suffit pour
 * avoir un nouveau champ ; le gabarit le lit avec App\texte('cle') ou App\reglage('cle').
 *
 * Valeur lue par le site :
 * - réglage jamais enregistré, ou SCF désactivée → valeur d'origine ci-dessous ;
 * - texte vidé volontairement → rien n'est affiché ;
 * - image vide → image d'origine du thème.
 * `composer seed` (scripts/reglages.php) recopie les valeurs d'origine dans l'administration tant qu'elles n'y sont pas.
 *
 * Dans un texte : {seuil} = montant de la livraison offerte (lu dans WooCommerce › Expédition),
 * {annee} = année en cours. Dans un titre : *mots* = mots en or.
 *
 * Champ : 'cle' => [type, libellé, valeur d'origine, aide, options].
 * Types : texte, zone, courriel, image, choix, rayon, rayons, liste (options = sous-champs).
 */

namespace App;

/** Pictos proposés dans les listes (voir icone() dans app/helpers.php). */
const PICTOS = [
    'livraison' => 'Camion', 'boutique' => 'Boutique', 'cadenas' => 'Cadenas', 'whatsapp' => 'WhatsApp',
    'courriel' => 'Enveloppe', 'goutte' => 'Goutte', 'feuille' => 'Feuille', 'fleur' => 'Fleur',
    'etoile' => 'Étoile', 'coche' => 'Coche', 'plus' => 'Plus', 'sablier' => 'Sablier',
];

const REGLAGES = [
    'univers-sensoriel' => [
        'titre' => 'Coordonnées',
        'onglets' => [
            'Maison' => [
                'marque' => ['texte', 'Nom affiché', 'Univers Sensoriel by K.Elle', 'Pied de page, e-mails des formulaires.'],
                'slogan' => ['texte', 'Devise', 'Beauté · Bien-être · Art de vivre'],
            ],
            'Contact' => [
                'courriel' => ['courriel', 'Adresse e-mail', 'contact@universsensoriel.com', 'Affichée sur le site ; elle reçoit aussi les messages du formulaire de contact.'],
                'telephone' => ['texte', 'Téléphone', '+33 6 05 78 40 11'],
                'whatsapp' => ['texte', 'Numéro WhatsApp', '33605784011', 'Format international, chiffres seuls : 33605784011.'],
                'delai_reponse' => ['texte', 'Délai de réponse', 'Réponse sous 24 heures ouvrées.'],
                'wa_message' => ['texte', 'Message WhatsApp prérempli', 'Bonjour, j\'ai une question.'],
                'wa_conseil' => ['texte', 'Message WhatsApp : demande de conseil', 'Bonjour, j\'aimerais un conseil pour choisir un parfum.'],
                'wa_commande' => ['texte', 'Message WhatsApp : question sur une commande', 'Bonjour, j\'ai une question sur ma commande n° {numero}.', '{numero} = numéro de la commande.'],
            ],
            'Boutiques' => [
                'boutiques' => ['liste', 'Boutiques', [
                    [
                        'ville' => 'Metz', 'pays' => 'France', 'enseigne' => 'Kacy Afro Center', 'adresse' => '57 rue des Allemands, 57000 Metz',
                        'telephone' => '+33 6 41 25 22 71', 'telephone_2' => '+33 7 66 28 66 48',
                        'horaires' => [
                            ['jours' => 'Lundi, mardi et jeudi', 'heures' => '14 h – 19 h'],
                            ['jours' => 'Mercredi', 'heures' => '11 h – 19 h'],
                            ['jours' => 'Vendredi et samedi', 'heures' => '10 h – 19 h'],
                            ['jours' => 'Dimanche', 'heures' => 'Fermé'],
                        ],
                        'mention' => 'Retrait gratuit des commandes passées sur le site.',
                    ],
                    [
                        'ville' => 'Yaoundé', 'pays' => 'Cameroun', 'enseigne' => 'Style Mondial', 'adresse' => '',
                        'telephone' => '+237 56 22 82 25', 'telephone_2' => '', 'horaires' => [], 'mention' => '',
                    ],
                ], 'Affichées dans le pied de page, l\'accueil, la page Contact et la page Nos boutiques.', [
                    'ville' => ['texte', 'Ville'],
                    'pays' => ['texte', 'Pays'],
                    'enseigne' => ['texte', 'Enseigne'],
                    'adresse' => ['texte', 'Adresse'],
                    'telephone' => ['texte', 'Téléphone'],
                    'telephone_2' => ['texte', 'Second téléphone'],
                    'horaires' => ['liste', 'Horaires', [], '', ['jours' => ['texte', 'Jours'], 'heures' => ['texte', 'Heures']]],
                    'mention' => ['texte', 'Mention'],
                ]],
            ],
            'Mentions légales' => [
                'societe_nom' => ['texte', 'Éditeur du site', 'Univers Sensoriel by K.Elle (anciennement Maison du Bien-Être – K-ELLE Sensorielle)', 'Une ligne vide n\'est pas affichée.'],
                'societe_forme' => ['texte', 'Forme juridique', ''],
                'societe_adresse' => ['texte', 'Siège', ''],
                'societe_siret' => ['texte', 'SIRET', ''],
                'societe_tva' => ['texte', 'N° de TVA', ''],
                'societe_responsable' => ['texte', 'Responsable de la publication', ''],
                'hebergeur_nom' => ['texte', 'Hébergeur', 'IONOS SARL'],
                'hebergeur_adresse' => ['texte', 'Adresse de l\'hébergeur', '7 place de la Gare, BP 70109, 57200 Sarreguemines Cedex, France'],
                'hebergeur_telephone' => ['texte', 'Téléphone de l\'hébergeur', '0970 808 911'],
                'hebergeur_site' => ['texte', 'Site de l\'hébergeur', 'https://www.ionos.fr'],
            ],
        ],
    ],
    'us-accueil' => [
        'titre' => 'Accueil',
        'onglets' => [
            'Ouverture' => [
                'ouverture_titre' => ['texte', 'Titre', 'Parfums, soins et rituels pour *éveiller les sens*', 'Les mots entre *étoiles* sont en or.'],
                'ouverture_texte' => ['zone', 'Texte', 'Extraits de parfum longue tenue, soins et senteurs pour la maison.'],
                'ouverture_bouton' => ['texte', 'Bouton (vers les parfums)', 'Découvrir les parfums'],
                'ouverture_lien' => ['texte', 'Lien (vers la beauté)', 'Explorer les soins'],
                'ouverture_image' => ['image', 'Photo de l\'arche', 'encens-bois-de-santal-parfum'],
                'ouverture_flacon_1' => ['image', 'Flacon de gauche', 'flacon-extrait-de-parfum-homme', 'Image détourée (fond transparent).'],
                'ouverture_flacon_2' => ['image', 'Flacon de droite', 'flacon-extrait-de-parfum-femme', 'Image détourée (fond transparent).'],
            ],
            'Essentiels' => [
                'essentiels_titre' => ['texte', 'Titre', 'Nos essentiels'],
                'essentiels' => ['liste', 'Onglets', [
                    ['libelle' => 'Pour elle', 'rayons' => ['parfums-femme'], 'lien' => 'parfums-femme'],
                    ['libelle' => 'Pour lui', 'rayons' => ['parfums-homme'], 'lien' => 'parfums-homme'],
                    ['libelle' => 'Soins', 'rayons' => ['rituels-et-coffrets', 'soin-du-visage'], 'lien' => 'beaute'],
                    ['libelle' => 'Maison', 'rayons' => ['parfums-d-ambiance', 'huiles-essentielles'], 'lien' => 'art-de-vivre'],
                ], 'Chaque onglet montre huit produits des rayons choisis, les produits « mis en avant » d\'abord.', [
                    'libelle' => ['texte', 'Nom de l\'onglet'],
                    'rayons' => ['rayons', 'Rayons présentés'],
                    'lien' => ['rayon', 'Rayon du bouton « Tout voir »'],
                ]],
                'essentiels_bouton' => ['texte', 'Bouton', 'Tout voir'],
            ],
            'Univers' => [
                'univers_titre' => ['texte', 'Titre', 'Nos univers'],
                'univers' => ['liste', 'Tuiles', [
                    ['rayon' => 'parfums', 'texte' => 'Extraits concentrés à 30 %', 'image' => 'extrait-de-parfum-homme-notes-boisees', 'alt' => 'Flacon d\'extrait de parfum posé sur une écorce, entouré de fleurs'],
                    ['rayon' => 'beaute', 'texte' => 'Visage, corps et cheveux', 'image' => 'coffret-soins-visage-peptilux', 'alt' => 'Coffret de soins du visage ouvert sur une table de marbre'],
                    ['rayon' => 'bien-etre', 'texte' => 'Huiles essentielles et végétales', 'image' => 'huile-vegetale-amande-douce', 'alt' => 'Flacon d\'huile d\'amande douce entouré d\'amandes et de fleurs'],
                    ['rayon' => 'art-de-vivre', 'texte' => 'Parfums d\'ambiance', 'image' => 'parfum-d-ambiance-diffuseur-salon', 'alt' => 'Diffuseur de parfum à bâtonnets dans un salon'],
                ], 'Le nom de la tuile est celui du rayon. La photo sert aussi dans le menu.', [
                    'rayon' => ['rayon', 'Rayon'],
                    'texte' => ['texte', 'Texte'],
                    'image' => ['image', 'Photo'],
                    'alt' => ['texte', 'Description de la photo'],
                ]],
            ],
            'La Maison' => [
                'maison_surtitre' => ['texte', 'Surtitre', 'La Maison'],
                'maison_citation' => ['zone', 'Citation', 'Le vrai luxe n\'est pas ce que l\'on voit. C\'est ce que l\'on ressent.'],
                'maison_texte' => ['zone', 'Texte', 'Nous choisissons chaque parfum et chaque soin pour sa qualité, sa tenue et le plaisir qu\'il procure.'],
                'maison_points' => ['liste', 'Points forts', [
                    ['icone' => 'goutte', 'texte' => 'Extraits de parfum concentrés à 30 %'],
                    ['icone' => 'feuille', 'texte' => 'Formules et actifs choisis avec soin'],
                    ['icone' => 'boutique', 'texte' => 'Deux boutiques : Metz et Yaoundé'],
                ], '', ['icone' => ['choix', 'Picto', '', '', PICTOS], 'texte' => ['texte', 'Texte']]],
                'maison_bouton' => ['texte', 'Bouton', 'Notre histoire'],
                'maison_image' => ['image', 'Photo', 'aloe-vera-feuilles'],
                'maison_image_alt' => ['texte', 'Description de la photo', 'Feuilles d\'aloe vera, l\'un des actifs de nos soins'],
            ],
            'Familles olfactives' => [
                'familles_titre' => ['texte', 'Titre', 'Choisir par famille olfactive'],
                'familles_bouton' => ['texte', 'Bouton', 'Lire le guide'],
            ],
            'Collection' => [
                'honneur_rayon' => ['rayon', 'Rayon mis à l\'honneur', 'collection-luxury'],
                'honneur_surtitre' => ['texte', 'Surtitre', 'Collection Luxury'],
                'honneur_titre' => ['texte', 'Titre', 'Les matières les plus précieuses'],
                'honneur_texte' => ['zone', 'Texte', 'Oud, safran, vanille, fleurs rares. Pour elle et pour lui.'],
                'honneur_bouton' => ['texte', 'Bouton', 'Voir la collection'],
                'honneur_image' => ['image', 'Photo', 'parfum-luxury-fleurs-et-epices'],
                'honneur_image_alt' => ['texte', 'Description de la photo', 'Flacon de la collection Luxury posé parmi des fleurs et des épices'],
            ],
            'Bien-être' => [
                'rituels_titre' => ['texte', 'Titre', 'Brumes, huiles et soins du corps'],
                'rituels_rayons' => ['rayons', 'Rayons présentés', ['huiles-vegetales', 'brumes-parfumees', 'corps-et-bain'], 'Quatre produits, les produits « mis en avant » d\'abord.'],
            ],
            'Avis' => [
                'avis_titre' => ['texte', 'Titre', 'Avis clients'],
                'avis_intro' => ['texte', 'Phrase', 'Publiés mot pour mot.'],
                'avis_lien' => ['texte', 'Lien', 'Tous les avis'],
            ],
            'Journal' => [
                'journal_titre' => ['texte', 'Titre', 'Le journal'],
                'journal_bouton' => ['texte', 'Bouton', 'Tous les articles'],
            ],
            'Conseil' => [
                'conseil_titre' => ['texte', 'Titre', 'Sentir, essayer, demander conseil'],
                'conseil_texte' => ['zone', 'Texte', 'En boutique ou par message, nous vous aidons à choisir.'],
                'conseil_bouton' => ['texte', 'Bouton WhatsApp', 'Demander conseil'],
                'conseil_lien_contact' => ['texte', 'Lien vers le contact', 'Formulaire de contact'],
                'conseil_lien_boutiques' => ['texte', 'Lien vers les boutiques', 'Nos boutiques'],
            ],
        ],
    ],
    'us-textes' => [
        'titre' => 'Textes du site',
        'onglets' => [
            'Bandeau' => [
                'bandeau_1' => ['texte', 'Premier message', 'Livraison offerte dès {seuil} €', '{seuil} = montant de la livraison offerte, réglé dans WooCommerce › Réglages › Expédition.'],
                'bandeau_2' => ['texte', 'Second message', 'Retrait gratuit à la boutique de Metz', 'Masqué sur téléphone.'],
            ],
            'Garanties' => [
                'garanties' => ['liste', 'Garanties', [
                    ['icone' => 'livraison', 'titre' => 'Livraison offerte', 'texte' => 'Dès {seuil} € d\'achat en France'],
                    ['icone' => 'boutique', 'titre' => 'Retrait en boutique', 'texte' => 'Gratuit, à Metz'],
                    ['icone' => 'cadenas', 'titre' => 'Paiement sécurisé', 'texte' => 'Vos données restent protégées'],
                    ['icone' => 'whatsapp', 'titre' => 'Conseil personnalisé', 'texte' => 'WhatsApp ou formulaire, réponse sous 24 h'],
                ], 'Affichées au-dessus du pied de page et sur chaque fiche produit. {seuil} = montant de la livraison offerte.', [
                    'icone' => ['choix', 'Picto', '', '', PICTOS],
                    'titre' => ['texte', 'Titre'],
                    'texte' => ['texte', 'Texte'],
                ]],
            ],
            'Pied de page' => [
                'lettre_titre' => ['texte', 'Titre de la lettre d\'information', 'Nouveautés et offres, par e-mail'],
                'lettre_mention' => ['texte', 'Mention sous le formulaire', 'Désinscription à tout moment.'],
                'pied_whatsapp' => ['texte', 'Lien WhatsApp', 'Écrire sur WhatsApp'],
                'pied_paiement' => ['texte', 'Mention de paiement', 'Paiement sécurisé'],
                'pied_copyright' => ['texte', 'Ligne du bas', '© {annee} Univers Sensoriel by K.Elle', '{annee} = année en cours.'],
            ],
            'Fiche produit' => [
                'produit_stock' => ['texte', 'Mention de stock', 'En stock'],
                'produit_concentration' => ['texte', 'Concentration des parfums', 'Extrait de parfum, 30 %'],
                'produit_conseils' => ['zone', 'Conseils d\'utilisation des parfums', 'Deux ou trois vaporisations sur les points de pulsation (poignets, cou), sans frotter. À conserver à l\'abri de la lumière et de la chaleur.'],
                'produit_livraison' => ['zone', 'Livraison', 'Livraison offerte dès {seuil} € d\'achat en France, ou retrait gratuit à la boutique de Metz.'],
                'produit_retours' => ['zone', 'Retours', '14 jours pour changer d\'avis, si le produit n\'a pas été ouvert.'],
                'produit_meme_senteur' => ['texte', 'Titre : même senteur', 'Dans la même senteur'],
                'produit_voisins' => ['texte', 'Titre : autres produits', 'Vous aimerez aussi'],
                'avis_merci' => ['texte', 'Message après un avis', 'Merci ! Votre avis est bien reçu. Il sera publié après relecture.'],
                'avis_mention' => ['texte', 'Mention sous le formulaire d\'avis', 'Publié après relecture, avec votre prénom.'],
            ],
            'Boutique' => [
                'boutique_titre' => ['texte', 'Titre', 'La boutique'],
                'boutique_intro' => ['zone', 'Texte', 'Parfums, soins, bien-être et art de vivre : toute la sélection Univers Sensoriel.', 'Le texte d\'un rayon se modifie dans Produits › Catégories.'],
                'boutique_vide_titre' => ['texte', 'Aucun résultat : titre', 'Aucun produit ne correspond'],
                'boutique_vide_texte' => ['zone', 'Aucun résultat : texte', 'Essayez d\'élargir vos critères, ou demandez-nous conseil : nous vous aidons à trouver.'],
                'boutique_guide' => ['texte', 'Renvoi vers le guide des familles', 'Vous hésitez entre plusieurs familles ?'],
            ],
            'Journal' => [
                'journal_intro' => ['zone', 'Texte', 'Nos conseils pour choisir, appliquer et faire durer.'],
                'journal_vide_titre' => ['texte', 'Aucun article : titre', 'Rien pour le moment'],
                'journal_vide_texte' => ['zone', 'Aucun article : texte', 'Aucun article ne correspond. Revenez bientôt, ou parcourez la boutique.'],
                'journal_suite' => ['texte', 'Titre sous un article', 'À lire aussi'],
            ],
            'Contact' => [
                'contact_mention' => ['texte', 'Mention sous le formulaire', 'Vos données servent uniquement à vous répondre.'],
                'contact_boutiques' => ['texte', 'Titre des boutiques', 'En boutique'],
                'contact_aide_titre' => ['texte', 'Titre de l\'aide', 'Aide'],
                'contact_aide' => ['liste', 'Liens d\'aide', [
                    ['icone' => 'livraison', 'titre' => 'Livraison et retours', 'texte' => 'Délais, frais de port, retours.', 'lien' => '/livraison-et-retours/'],
                    ['icone' => 'plus', 'titre' => 'Questions fréquentes', 'texte' => 'Commande, paiement, tenue des parfums.', 'lien' => '/questions-frequentes/'],
                    ['icone' => 'fleur', 'titre' => 'Guide des familles olfactives', 'texte' => 'Trouver son parfum.', 'lien' => '/guide-des-familles-olfactives/'],
                ], '', [
                    'icone' => ['choix', 'Picto', '', '', PICTOS],
                    'titre' => ['texte', 'Titre'],
                    'texte' => ['texte', 'Texte'],
                    'lien' => ['texte', 'Adresse de la page', '', 'Exemple : /livraison-et-retours/'],
                ]],
            ],
            'Avis clients' => [
                'avis_page_intro' => ['texte', 'Phrase sous le titre', 'Publiés mot pour mot, avec leur date.'],
            ],
            'Commande' => [
                'commande_echec_titre' => ['texte', 'Paiement refusé : titre', 'Paiement refusé'],
                'commande_echec_texte' => ['zone', 'Paiement refusé : texte', 'Votre banque n\'a pas validé le paiement. Rien n\'a été débité.'],
                'commande_aide_titre' => ['texte', 'Titre du bloc d\'aide', 'Une question ?'],
            ],
            'Page introuvable' => [
                'e404_surtitre' => ['texte', 'Surtitre', 'Erreur 404'],
                'e404_titre' => ['texte', 'Titre', 'Cette page s\'est évaporée'],
                'e404_texte' => ['zone', 'Texte', 'Le lien est peut-être ancien.'],
                'e404_bouton' => ['texte', 'Bouton', 'Voir la boutique'],
                'e404_lien' => ['texte', 'Lien', 'Nous écrire'],
            ],
        ],
    ],
    'us-attente' => [
        'titre' => 'Page d\'attente',
        'onglets' => [
            'Affichage' => [
                'attente_mode' => ['choix', 'Page d\'attente', 'auto', 'Les visiteurs non connectés ne voient que cette page. Connectée, vous voyez le site ; « Site caché » dans la barre du haut en donne un aperçu.', [
                    'auto' => 'Automatique : affichée tant que le site n\'est pas en production',
                    'oui' => 'Affichée : le site est caché',
                    'non' => 'Masquée : le site est ouvert au public',
                ]],
            ],
            'Textes' => [
                'attente_titre' => ['texte', 'Titre', 'Notre boutique en ligne *ouvre bientôt*', 'Les mots entre *étoiles* sont en or.'],
                'attente_texte' => ['zone', 'Texte', 'Extraits de parfum, soins et senteurs pour la maison. En attendant, nos boutiques de Metz et Yaoundé vous accueillent.'],
                'attente_invite' => ['texte', 'Invitation à la lettre', 'Recevoir un e-mail à l\'ouverture'],
            ],
            'Google et partage' => [
                'attente_seo_titre' => ['texte', 'Titre', 'Univers Sensoriel : la boutique en ligne ouvre bientôt', '60 caractères au plus.'],
                'attente_seo_description' => ['zone', 'Description', 'Univers Sensoriel ouvre bientôt sa boutique en ligne : extraits de parfum, soins et senteurs pour la maison. Boutiques ouvertes à Metz et Yaoundé.', '120 à 155 caractères.'],
                'attente_seo_image' => ['image', 'Image de partage', '', '1200 × 630 pixels. Vide : image d\'origine.'],
                'attente_seo_alt' => ['texte', 'Description de l\'image', 'Univers Sensoriel : la boutique en ligne ouvre bientôt, extraits de parfum devant un bol d’encens'],
            ],
        ],
    ],
];

/** Tous les réglages à plat : cle => [type, libellé, valeur d'origine, aide, options]. */
function reglages_champs(): array
{
    static $champs = null;
    if ($champs === null) {
        $champs = [];
        foreach (REGLAGES as $page) {
            foreach ($page['onglets'] as $onglet) {
                $champs += $onglet;
            }
        }
    }

    return $champs;
}

/**
 * Valeur d'un réglage (voir REGLAGES pour les noms).
 * Jamais enregistré ou SCF désactivée → valeur d'origine ; image vide → image d'origine.
 */
function reglage(string $cle): mixed
{
    static $valeurs = [];
    if (array_key_exists($cle, $valeurs)) {
        return $valeurs[$cle];
    }
    $champ = reglages_champs()[$cle] ?? null;
    $origine = $champ[2] ?? null;
    $valeur = function_exists('get_field') ? get_field($cle, 'option') : null;
    if (($champ[0] ?? '') === 'image') {
        $valeur = $valeur ?: $origine;
    }
    // Une liste enregistrée vide revient en « false » : la garder vide, et non la remplacer par celle d'origine.
    if (($champ[0] ?? '') === 'liste' && $valeur !== null) {
        $valeur = is_array($valeur) ? $valeur : [];
    }

    return $valeurs[$cle] = $valeur ?? $origine;
}

/** Remplace {seuil}, {annee} et les repères donnés ({numero}…) dans un texte. */
function completer(string $texte, array $reperes = []): string
{
    if (! str_contains($texte, '{')) {
        return $texte;
    }
    $reperes += ['annee' => date('Y')];
    if (str_contains($texte, '{seuil}')) {
        $reperes['seuil'] = seuil_affiche();
    }
    foreach ($reperes as $nom => $valeur) {
        $texte = str_replace('{' . $nom . '}', (string) $valeur, $texte);
    }

    return $texte;
}

/** Texte d'un réglage, repères remplacés. À afficher avec {{ }}. */
function texte(string $cle, array $reperes = []): string
{
    return completer(trim((string) reglage($cle)), $reperes);
}

/** Lignes d'une liste, sans celles dont aucun des champs $requis n'est rempli. */
function lignes(string $cle, array $requis = []): array
{
    return array_values(array_filter((array) reglage($cle), function ($ligne) use ($requis) {
        return is_array($ligne) && (! $requis || array_filter(array_intersect_key($ligne, array_flip($requis))));
    }));
}

/**
 * Titre saisi dans l'administration, prêt pour {!! !!} : les *mots* entre étoiles prennent l'or,
 * le ruban et l'étoile du logo. Le reste est échappé.
 */
function titre_or(string $texte): string
{
    $ruban = '<svg class="titre-ruban" viewBox="0 0 320 28" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M6 19C62 6 118 24 176 15S268 3 314 11"/></svg>';

    return preg_replace(
        '/\*(.+?)\*/u',
        '<span class="titre-or"><span class="titre-or-texte">$1</span>' . $ruban . icone('etoile', 24, 'titre-etoile') . '</span>',
        esc_html(trim($texte))
    );
}

/**
 * Balise <img> d'une image réglable : image de la médiathèque (identifiant) ou visuel d'origine du thème (nom de fichier).
 * Accepte la valeur d'un réglage ou d'une ligne de liste.
 */
function media(mixed $valeur, string $alt = '', array $attributs = []): string
{
    if (is_array($valeur)) {
        $valeur = $valeur['ID'] ?? $valeur['id'] ?? 0;
    }
    if (is_numeric($valeur) && wp_attachment_is_image((int) $valeur)) {
        return wp_get_attachment_image((int) $valeur, 'large', false, $attributs + ['alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async']);
    }

    return is_string($valeur) && $valeur !== '' && ! is_numeric($valeur) ? visuel($valeur, $alt, $attributs) : '';
}

/** Slugs de rayons d'un champ « rayon » ou « rayons » : identifiants choisis dans l'administration, ou slugs d'origine. */
function rayons(mixed $valeur): array
{
    $slugs = [];
    foreach (array_filter((array) $valeur) as $rayon) {
        if (is_numeric($rayon)) {
            $terme = get_term((int) $rayon, 'product_cat');
            $rayon = $terme instanceof \WP_Term ? $terme->slug : '';
        }
        if (is_string($rayon) && $rayon !== '') {
            $slugs[] = $rayon;
        }
    }

    return $slugs;
}

/** Rayon (catégorie de produits) d'un champ « rayon », ou null. */
function rayon(mixed $valeur): ?\WP_Term
{
    $slug = rayons($valeur)[0] ?? '';
    $terme = $slug !== '' && taxonomy_exists('product_cat') ? get_term_by('slug', $slug, 'product_cat') : null;

    return $terme instanceof \WP_Term ? $terme : null;
}

/** Adresse d'un rayon, la boutique à défaut. */
function lien_rayon(mixed $valeur): string
{
    $terme = rayon($valeur);
    $url = $terme ? get_term_link($terme) : '';

    return is_string($url) && $url !== '' ? $url : home_url('/boutique/');
}

/**
 * Montant à partir duquel la livraison est offerte : lu dans WooCommerce › Réglages › Expédition
 * (méthode « Livraison gratuite » avec un minimum de commande). 0 s'il n'y en a pas.
 */
function seuil_livraison(): float
{
    static $seuil = null;
    if ($seuil !== null) {
        return $seuil;
    }
    $seuil = 0.0;
    if (! class_exists('WC_Shipping_Zones')) {
        return $seuil;
    }
    $zones = array_map(fn ($zone) => $zone['shipping_methods'] ?? [], \WC_Shipping_Zones::get_zones());
    $zones[] = (new \WC_Shipping_Zone(0))->get_shipping_methods();
    foreach ($zones as $methodes) {
        foreach ($methodes as $methode) {
            $minimum = (float) wc_format_decimal($methode->get_option('min_amount', 0));
            $avecMinimum = in_array($methode->get_option('requires', ''), ['min_amount', 'either', 'both'], true);
            if ($methode->id === 'free_shipping' && $methode->enabled === 'yes' && $avecMinimum && $minimum > 0) {
                $seuil = $seuil > 0 ? min($seuil, $minimum) : $minimum;
            }
        }
    }

    return $seuil;
}

/** Prix de la livraison à domicile : forfait le plus bas des zones (WooCommerce › Expédition), 0 s'il n'y en a pas. */
function forfait_livraison(): float
{
    static $forfait = null;
    if ($forfait !== null) {
        return $forfait;
    }
    $forfait = 0.0;
    if (! class_exists('WC_Shipping_Zones')) {
        return $forfait;
    }
    foreach (\WC_Shipping_Zones::get_zones() as $zone) {
        foreach ($zone['shipping_methods'] ?? [] as $methode) {
            $cout = (float) wc_format_decimal((string) $methode->get_option('cost', ''));
            if ($methode->id === 'flat_rate' && $methode->enabled === 'yes' && $cout > 0) {
                $forfait = $forfait > 0 ? min($forfait, $cout) : $cout;
            }
        }
    }

    return $forfait;
}

/** Montant tel qu'on l'écrit : « 49 », « 4,90 ». */
function montant(float $valeur): string
{
    return number_format($valeur, floor($valeur) === $valeur ? 0 : 2, ',', ' ');
}

function forfait_affiche(): string
{
    return montant(forfait_livraison());
}

/** Seuil tel qu'on l'écrit : « 49 », « 49,50 ». */
function seuil_affiche(): string
{
    return montant(seuil_livraison());
}

/*
|--------------------------------------------------------------------------
| Écrans de l'administration (Secure Custom Fields)
|--------------------------------------------------------------------------
*/

/** Champ SCF fabriqué depuis une ligne de REGLAGES. $prefixe (« liste__ ») rend la clé d'un sous-champ unique. */
function reglage_scf(string $cle, array $champ, string $prefixe = ''): array
{
    [$type, $libelle] = $champ;
    $origine = $champ[2] ?? '';
    $scf = [
        'key' => 'field_us_' . $prefixe . $cle,
        'name' => $cle,
        'label' => $libelle,
        'instructions' => $champ[3] ?? '',
    ];

    return $scf + match ($type) {
        'zone' => ['type' => 'textarea', 'rows' => 3, 'new_lines' => '', 'default_value' => $origine],
        'courriel' => ['type' => 'email', 'default_value' => $origine],
        'image' => ['type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all'],
        'choix' => ['type' => 'select', 'choices' => $champ[4] ?? [], 'default_value' => $origine, 'ui' => 0],
        'rayon', 'rayons' => [
            'type' => 'taxonomy', 'taxonomy' => 'product_cat', 'field_type' => $type === 'rayons' ? 'multi_select' : 'select',
            'return_format' => 'id', 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1,
        ],
        'liste' => [
            'type' => 'repeater', 'layout' => count($champ[4] ?? []) > 3 ? 'row' : 'table', 'button_label' => 'Ajouter une ligne',
            'sub_fields' => array_map(
                fn ($sous, $definition) => reglage_scf($sous, $definition, $prefixe . $cle . '__'),
                array_keys($champ[4] ?? []), $champ[4] ?? []
            ),
        ],
        default => ['type' => 'text', 'default_value' => $origine],
    };
}

add_action('acf/init', function () {
    if (! function_exists('acf_add_options_page') || ! function_exists('acf_add_local_field_group')) {
        return;
    }
    $parent = array_key_first(REGLAGES);
    foreach (REGLAGES as $slug => $page) {
        $commun = [
            'page_title' => $page['titre'],
            'menu_slug' => $slug,
            'capability' => 'edit_pages',
            'update_button' => 'Enregistrer',
            'updated_message' => 'Réglages enregistrés.',
        ];
        if ($slug === $parent) {
            acf_add_options_page($commun + ['menu_title' => 'Univers Sensoriel', 'icon_url' => 'dashicons-admin-customizer', 'position' => 3, 'redirect' => false]);
        }
        acf_add_options_sub_page($commun + ['menu_title' => $page['titre'], 'parent_slug' => $parent]);

        $champs = [];
        foreach (array_keys($page['onglets']) as $rang => $onglet) {
            $champs[] = ['key' => "field_us_onglet_{$slug}_{$rang}", 'label' => $onglet, 'type' => 'tab', 'placement' => 'left'];
            foreach ($page['onglets'][$onglet] as $cle => $champ) {
                $champs[] = reglage_scf($cle, $champ);
            }
        }
        acf_add_local_field_group([
            'key' => 'group_us_' . str_replace('-', '_', $slug),
            'title' => $page['titre'],
            'fields' => $champs,
            'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => $slug]]],
            'style' => 'seamless',
        ]);
    }
});

// Premier sous-menu : le nom de l'écran (« Coordonnées ») plutôt que celui du menu.
add_action('admin_menu', function () {
    global $submenu;
    $parent = array_key_first(REGLAGES);
    if (isset($submenu[$parent][0][0])) {
        $submenu[$parent][0][0] = REGLAGES[$parent]['titre'];
    }
}, 999);

// Le menu « SCF » (fabrique de champs) est réservé au développement : les champs sont décrits ci-dessus.
add_filter('acf/settings/show_admin', fn () => defined('WP_ENV') && WP_ENV === 'development');
