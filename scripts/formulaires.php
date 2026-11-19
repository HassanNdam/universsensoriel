<?php

/**
 * Formulaires Forminator : contact et lettre d'information.
 *
 * Chaque formulaire est décrit ici (champs, réglages, e-mails) puis créé une fois dans Forminator.
 * Son identifiant est rangé dans l'option « us_formulaires » : le thème le relit avec App\formulaire().
 * Une empreinte de la description évite de réécrire un formulaire inchangé ; quand la description
 * change ici, le formulaire est mis à jour au prochain `composer seed`. US_SEED_FORCE=1 réécrit tout.
 *
 * Lancer seul : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/formulaires.php --user=admin
 */
if (! class_exists('Forminator_API')) {
    WP_CLI::warning('Forminator est inactif : formulaires non créés.');

    return;
}

$destinataire = function_exists('App\\coordonnees') ? App\coordonnees()['courriel'] : get_option('admin_email');
// Les e-mails partent dans l'habillage du thème (app/mails.php) : logo, coordonnées et pied y sont déjà.
// Un <h1> en tête du message devient le grand titre de l'e-mail ; sans lui, c'est l'objet.

// Réglages communs : aucun habillage Forminator (le thème s'en charge), envoi sans rechargement, piège à robots.
$reglages = [
    'form-type' => 'default',
    'form-style' => 'default',
    'form-substyle' => 'none',
    'cform-color-option' => 'theme',
    'fields-style' => 'open',
    'enable-ajax' => 'true',
    'validation' => 'on_submit',
    'validation-inline' => true,
    'honeypot' => 'true',
    'akismet-protection' => true,
    'store_submissions' => '1',
    'submission-behaviour' => 'behaviour-thankyou',
    'autoclose' => 'false',
    'submission-indicator' => 'show',
    'indicator-label' => 'Envoi en cours…',
    'form-expire' => 'no_expire',
    'use-custom-css' => 'false',
];

$formulaires = [
    'contact' => [
        'nom' => 'Contact',
        'champs' => [
            ['wrapper_id' => 'wrapper-contact-1', 'fields' => [
                [
                    'element_id' => 'name-1', 'type' => 'name', 'cols' => '6', 'required' => 'true',
                    'field_label' => 'Nom et prénom', 'placeholder' => '',
                    'required_message' => 'Indiquez votre nom.',
                ],
                [
                    'element_id' => 'email-1', 'type' => 'email', 'cols' => '6', 'required' => 'true',
                    'field_label' => 'Adresse e-mail', 'placeholder' => '', 'validation' => true,
                    'required_message' => 'Indiquez votre adresse e-mail.',
                    'validation_text' => 'Cette adresse e-mail n\'est pas valide.',
                ],
            ]],
            ['wrapper_id' => 'wrapper-contact-2', 'fields' => [
                [
                    'element_id' => 'select-1', 'type' => 'select', 'cols' => '12', 'required' => 'true',
                    'field_label' => 'Votre demande concerne', 'value_type' => 'single',
                    'placeholder' => 'Choisir un sujet',
                    'required_message' => 'Choisissez un sujet.',
                    'options' => [
                        ['label' => 'Un conseil pour choisir', 'value' => 'Conseil', 'limit' => '', 'key' => 'contact-conseil'],
                        ['label' => 'Le suivi d\'une commande', 'value' => 'Suivi de commande', 'limit' => '', 'key' => 'contact-suivi'],
                        ['label' => 'Un retour ou un échange', 'value' => 'Retour ou échange', 'limit' => '', 'key' => 'contact-retour'],
                        ['label' => 'Un partenariat ou la presse', 'value' => 'Partenariat ou presse', 'limit' => '', 'key' => 'contact-presse'],
                        ['label' => 'Autre chose', 'value' => 'Autre', 'limit' => '', 'key' => 'contact-autre'],
                    ],
                ],
            ]],
            ['wrapper_id' => 'wrapper-contact-3', 'fields' => [
                [
                    'element_id' => 'textarea-1', 'type' => 'textarea', 'cols' => '12', 'required' => 'true',
                    'field_label' => 'Votre message', 'input_type' => 'paragraph', 'placeholder' => '',
                    'description' => 'Pour une commande, pensez à indiquer son numéro.',
                    'limit' => '2000', 'limit_type' => 'characters',
                    'required_message' => 'Écrivez votre message.',
                ],
            ]],
        ],
        'reglages' => [
            'thankyou-message' => 'Merci, votre message est bien parti. Nous vous répondons en général sous 24 heures ouvrées.',
            'submitData' => [
                'custom-submit-text' => 'Envoyer le message',
                'custom-invalid-form-message' => 'Certains champs sont à compléter ou à corriger.',
            ],
        ],
        'courriels' => [
            [
                'slug' => 'notification-contact-maison',
                'label' => 'Message reçu (pour la Maison)',
                'email-recipients' => 'default',
                'recipients' => $destinataire,
                'email-subject' => 'Contact · {select-1} · {name-1}',
                'email-editor' => '<h1>Nouveau message</h1>{all_fields}<p>Répondre à cet e-mail écrit directement à {email-1}.</p>',
                'replyto-email' => '{email-1}',
                'from-name' => 'Univers Sensoriel',
                'email-attachment' => 'false',
                'fill-empty-fields' => 'true',
                'type' => 'default',
            ],
            [
                'slug' => 'notification-contact-accuse',
                'label' => 'Accusé de réception (pour la personne)',
                'email-recipients' => 'default',
                'recipients' => '{email-1}',
                'email-subject' => 'Nous avons bien reçu votre message',
                'email-editor' => '<h1>Message bien reçu</h1><p>Bonjour {name-1},</p><p>Votre message nous est bien parvenu. Nous vous répondons en général sous 24 heures ouvrées.</p><p>Vous nous avez écrit :</p><blockquote>{textarea-1}</blockquote>',
                'replyto-email' => $destinataire,
                'from-name' => 'Univers Sensoriel',
                'email-attachment' => 'false',
                'type' => 'default',
            ],
        ],
    ],

    'newsletter' => [
        'nom' => 'Lettre d\'information',
        'champs' => [
            ['wrapper_id' => 'wrapper-lettre-1', 'fields' => [
                [
                    'element_id' => 'email-1', 'type' => 'email', 'cols' => '12', 'required' => 'true',
                    'field_label' => 'Votre adresse e-mail', 'placeholder' => 'Votre adresse e-mail', 'validation' => true,
                    'required_message' => 'Indiquez votre adresse e-mail.',
                    'validation_text' => 'Cette adresse e-mail n\'est pas valide.',
                ],
            ]],
        ],
        'reglages' => [
            'thankyou-message' => 'Merci, vous êtes inscrit·e. À très vite dans votre boîte de réception.',
            'submitData' => [
                'custom-submit-text' => 'S\'inscrire',
                'custom-invalid-form-message' => 'Vérifiez votre adresse e-mail.',
            ],
        ],
        'courriels' => [
            [
                'slug' => 'notification-lettre-bienvenue',
                'label' => 'Bienvenue (pour la personne inscrite)',
                'email-recipients' => 'default',
                'recipients' => '{email-1}',
                'email-subject' => 'Bienvenue dans la lettre d\'Univers Sensoriel',
                'email-editor' => '<h1>Bienvenue</h1><p>Bonjour,</p><p>Votre inscription à notre lettre est enregistrée. Vous recevrez nos nouveautés, nos conseils parfum et nos offres, sans excès.</p><p>Pour vous désinscrire, il suffit de répondre à cet e-mail.</p>',
                'replyto-email' => $destinataire,
                'from-name' => 'Univers Sensoriel',
                'email-attachment' => 'false',
                'type' => 'default',
            ],
        ],
    ],
];

$identifiants = (array) get_option('us_formulaires', []);

foreach ($formulaires as $cle => $f) {
    $parametres = array_merge($reglages, $f['reglages'], ['formName' => $f['nom']]);
    $empreinte = md5(wp_json_encode([$f['champs'], $parametres, $f['courriels']]));
    $id = (int) ($identifiants[$cle] ?? 0);

    if ($id && is_wp_error(Forminator_API::get_form($id))) {
        $id = 0; // supprimé dans l'admin : on le recrée
    }
    if (! $id) {
        $id = Forminator_API::add_form($f['nom'], $f['champs'], $parametres);
        if (is_wp_error($id)) {
            WP_CLI::warning("formulaire « {$f['nom']} » : " . $id->get_error_message());

            continue;
        }
        $identifiants[$cle] = (int) $id;
        WP_CLI::log("  formulaire créé : {$f['nom']} (n° $id)");
    } elseif (! getenv('US_SEED_FORCE') && get_post_meta($id, '_us_empreinte', true) === $empreinte) {
        continue;
    }

    // La création ne prend pas les e-mails : on repasse toujours par une mise à jour complète.
    $maj = Forminator_API::update_form($id, $f['champs'], $parametres, 'publish', $f['courriels']);
    if (is_wp_error($maj)) {
        WP_CLI::warning("formulaire « {$f['nom']} » : " . $maj->get_error_message());

        continue;
    }
    update_post_meta($id, '_us_empreinte', $empreinte);
    WP_CLI::log("  formulaire à jour : {$f['nom']} → [forminator_form id=\"$id\"]");
}

update_option('us_formulaires', $identifiants, false);
