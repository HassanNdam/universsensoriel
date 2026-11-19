<?php

/**
 * E-mails du site à la charte : commandes et compte (WooCommerce), formulaires (Forminator), notifications de WordPress.
 *
 * Rien n'est copié depuis les extensions. Trois mécanismes, tous dans le thème :
 *
 * 1. L'habillage commun (en-tête au logo, styles, détail de commande, adresses, pied) remplace les gabarits
 *    partagés de WooCommerce par des vues Blade (table PONTS_MAILS). Tout e-mail qui passe par WooCommerce le
 *    reçoit, y compris ceux des extensions (Stripe) et ceux qui seront ajoutés plus tard.
 * 2. Le texte des e-mails listés dans MAILS_REDIGES est écrit ici (mail_message()), court et en français.
 *    Les autres gardent le texte de leur extension, dans le même habillage.
 * 3. Les e-mails envoyés hors de WooCommerce (formulaires, mot de passe de l'administration, notifications)
 *    sont enveloppés dans le même habillage au moment de l'envoi (filtre wp_mail).
 *
 * Objet, titre et « contenu additionnel » de chaque e-mail restent modifiables dans WooCommerce › Réglages › E-mails ;
 * leurs valeurs d'origine sont posées par scripts/mails.php. Styles : resources/css/mail.css (lu tel quel, sans Vite).
 * Essai : php vendor/wp-cli/wp-cli/php/boot-fs.php eval-file scripts/mails-essai.php
 */

namespace App;

if (! function_exists('WC')) {
    return;
}

/** Gabarit partagé de WooCommerce => pont du thème (app/ponts/mail-<nom>.php), qui rend la vue mails/<nom>. */
const PONTS_MAILS = [
    'emails/email-header.php' => 'entete',
    'emails/email-footer.php' => 'pied',
    'emails/email-styles.php' => 'styles',
    'emails/email-order-details.php' => 'commande',
    'emails/email-addresses.php' => 'adresses',
    'emails/email-mobile-messaging.php' => 'vide', // publicité pour l'application WooCommerce, dans « nouvelle commande »
];

/** E-mails dont le texte est écrit par le thème (identifiants de WooCommerce). */
const MAILS_REDIGES = [
    'new_order', 'cancelled_order', 'failed_order',
    'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order',
    'customer_refunded_order', 'customer_cancelled_order', 'customer_failed_order',
    'customer_invoice', 'customer_note',
    'customer_new_account', 'customer_reset_password', 'customer_verify_email',
];

add_filter('wc_get_template', function ($fichier, $nom, $args = []) {
    if (isset(PONTS_MAILS[$nom])) {
        return get_theme_file_path('app/ponts/mail-' . PONTS_MAILS[$nom] . '.php');
    }
    $mail = is_array($args) ? ($args['email'] ?? null) : null;
    if ($mail instanceof \WC_Email && $nom === $mail->template_html && in_array($mail->id, MAILS_REDIGES, true)) {
        return get_theme_file_path('app/ponts/mail-message.php');
    }

    return $fichier;
}, 10, 3);

/*
|--------------------------------------------------------------------------
| Destinataire des e-mails pour la boutique
|--------------------------------------------------------------------------
| Nouvelle commande, commande annulée, paiement échoué… : WooCommerce les envoie à l'adresse d'administration du site.
| Ils partent ici vers l'adresse e-mail des Coordonnées (la même que le formulaire de contact), sauf pour un e-mail
| dont le champ « Destinataire(s) » est rempli dans WooCommerce › Réglages › E-mails : ce champ garde la main.
*/
add_filter('woocommerce_email_classes', function ($mails) {
    foreach ((array) $mails as $mail) {
        if ($mail instanceof \WC_Email && ! $mail->is_customer_email() && isset($mail->form_fields['recipient'])) {
            add_filter('woocommerce_email_recipient_' . $mail->id, __NAMESPACE__ . '\destinataire_boutique', 10, 3);
        }
    }

    return $mails;
}, 20);

function destinataire_boutique($destinataire, $objet = null, $mail = null)
{
    $courriel = coordonnees()['courriel'];
    // Le réglage est lu en base : WC_Email::get_option() remplace un champ vide par l'adresse d'administration.
    $reglages = $mail instanceof \WC_Email ? (array) get_option("woocommerce_{$mail->id}_settings", []) : [];
    $choisi = trim((string) ($reglages['recipient'] ?? ''));

    return $courriel && $choisi === '' ? $courriel : $destinataire;
}

/** Vrai si la commande est retirée en boutique. */
function commande_en_retrait(\WC_Order $commande): bool
{
    foreach ($commande->get_shipping_methods() as $mode) {
        if (in_array($mode->get_method_id(), ['local_pickup', 'pickup_location'], true)) {
            return true;
        }
    }

    return false;
}

/**
 * Texte d'un e-mail rédigé par le thème : salutation, paragraphes (HTML sûr), note citée, bouton.
 * $v : les variables que WooCommerce passe à son gabarit ($order, $user_login, $reset_key…).
 */
function mail_message(\WC_Email $mail, array $v): array
{
    $commande = ($v['order'] ?? null) instanceof \WC_Order ? $v['order'] : null;
    $numero = $commande ? $commande->get_order_number() : '';
    $client = $commande ? esc_html(trim($commande->get_formatted_billing_full_name()) ?: $commande->get_billing_email()) : '';
    $identifiant = '<strong>' . esc_html((string) ($v['user_login'] ?? '')) . '</strong>';
    $compte = wc_get_page_permalink('myaccount');
    $suivi = $commande && $commande->get_customer_id() ? ['Suivre ma commande', $commande->get_view_order_url()] : null;
    $payer = $commande ? ['Régler ma commande', $commande->get_checkout_payment_url()] : null;
    $note = '';

    [$paragraphes, $bouton] = match ($mail->id) {
        // Pour la boutique
        'new_order' => [["Commande de {$client}, {$commande->get_formatted_order_total()}."], ['Ouvrir la commande', $commande->get_edit_order_url()]],
        'cancelled_order' => [["La commande n° {$numero} de {$client} a été annulée."], ['Ouvrir la commande', $commande->get_edit_order_url()]],
        'failed_order' => [["Le paiement de la commande n° {$numero} de {$client} a échoué."], ['Ouvrir la commande', $commande->get_edit_order_url()]],

        // Pour la cliente ou le client : commande
        'customer_on_hold_order' => [['Votre commande est enregistrée. Nous la préparons dès réception du paiement.'], $suivi],
        'customer_processing_order' => [[
            'Votre paiement est accepté, nous préparons votre commande.',
            commande_en_retrait($commande) ? 'Un e-mail vous préviendra dès qu\'elle sera prête en boutique.' : 'Un e-mail vous préviendra de son expédition.',
        ], $suivi],
        'customer_completed_order' => [[commande_en_retrait($commande) ? 'Votre commande est prête : elle vous attend en boutique.' : 'Votre colis est parti.'], $suivi],
        'customer_refunded_order' => [[
            empty($v['partial_refund']) ? 'Votre commande est remboursée.' : 'Un remboursement partiel a été fait sur votre commande.',
            'Le montant revient sur le moyen de paiement utilisé.',
        ], null],
        'customer_cancelled_order' => [['Votre commande a été annulée.'], null],
        'customer_failed_order' => [['Le paiement de votre commande n\'a pas abouti. Vous pouvez le refaire avec un autre moyen de paiement.'], $payer],
        'customer_invoice' => $commande->needs_payment()
            ? [['Votre commande attend son règlement.'], $payer]
            : [['Voici le détail de votre commande.'], $suivi],
        'customer_note' => [['Un message a été ajouté à votre commande :'], $suivi],

        // Compte
        'customer_new_account' => [
            ["Votre compte est créé. Identifiant : {$identifiant}."],
            ! empty($v['password_generated']) && ! empty($v['set_password_url'])
                ? ['Choisir mon mot de passe', $v['set_password_url']]
                : ['Accéder à mon compte', $compte],
        ],
        'customer_reset_password' => [
            ["Un nouveau mot de passe a été demandé pour le compte {$identifiant}.", 'Si la demande ne vient pas de vous, ignorez ce message : rien ne change.'],
            ['Choisir un nouveau mot de passe', add_query_arg(
                ['key' => $v['reset_key'] ?? '', 'id' => $v['user_id'] ?? '', 'login' => rawurlencode((string) ($v['user_login'] ?? ''))],
                wc_get_endpoint_url('lost-password', '', $compte),
            )],
        ],
        'customer_verify_email' => [
            ['Confirmez que <strong>' . esc_html((string) ($v['user_email'] ?? '')) . '</strong> est bien votre adresse : vos commandes passées seront rattachées à votre compte.'],
            ['Confirmer mon adresse', $v['verify_url'] ?? $compte],
        ],
        default => [[], null],
    };

    if ($mail->id === 'customer_note' && ! empty($v['customer_note'])) {
        $note = wpautop(make_clickable(wc_wptexturize_order_note($v['customer_note'])));
    }

    $prenom = $commande ? $commande->get_billing_first_name() : (string) ($v['user_display_name'] ?? '');

    return [
        'bonjour' => $mail->is_customer_email() ? trim('Bonjour ' . $prenom) . ',' : '',
        'paragraphes' => $paragraphes,
        'note' => $note,
        'bouton' => $bouton ? ['libelle' => $bouton[0], 'url' => $bouton[1]] : null,
        'commande' => $commande,
    ];
}

/** Détail d'une commande pour un e-mail : articles, totaux, moyen de paiement. $boutique : e-mail destiné à la Maison. */
function mail_commande(\WC_Order $commande, bool $boutique = false): array
{
    $articles = [];
    foreach ($commande->get_items() as $ligne) {
        $produit = $ligne->get_product();
        $image = $produit ? wp_get_attachment_image_url((int) $produit->get_image_id(), 'thumbnail') : '';
        $articles[] = [
            'nom' => $ligne->get_name(),
            'url' => $produit && $produit->is_visible() ? $produit->get_permalink() : '',
            'image' => $image ?: '',
            'options' => wp_strip_all_tags(wc_display_item_meta($ligne, ['echo' => false, 'before' => '', 'after' => '', 'separator' => ' · ', 'label_before' => '', 'label_after' => ' : ', 'autop' => false])),
            'reference' => $boutique && $produit ? $produit->get_sku() : '',
            'quantite' => $ligne->get_quantity(),
            'total' => $commande->get_formatted_line_subtotal($ligne),
        ];
    }

    // Sous-total, livraison, remises, taxes, remboursements, total : WooCommerce fournit les lignes déjà formatées.
    $totaux = [];
    foreach ($commande->get_order_item_totals() as $cle => $ligne) {
        if ($cle !== 'payment_method') {
            $totaux[] = ['cle' => $cle, 'libelle' => rtrim(trim(wp_strip_all_tags($ligne['label'])), ' :'), 'valeur' => $ligne['value']];
        }
    }

    $date = $commande->get_date_created();

    return [
        'numero' => $commande->get_order_number(),
        'url' => $boutique ? $commande->get_edit_order_url() : '',
        'reperes' => array_filter([
            $date ? date_i18n('j F Y', $date->getOffsetTimestamp()) : '',
            wp_strip_all_tags($commande->get_payment_method_title()),
        ]),
        'articles' => $articles,
        'totaux' => $totaux,
        'note' => $commande->get_customer_note(),
    ];
}

/** Adresses d'une commande pour un e-mail. */
function mail_adresses(\WC_Order $commande, bool $boutique = false): array
{
    $blocs = [[
        'cle' => 'billing',
        'titre' => 'Facturation',
        'adresse' => $commande->get_formatted_billing_address(),
        'lignes' => array_filter([
            $commande->get_billing_phone() ? wc_make_phone_clickable($commande->get_billing_phone()) : '',
            esc_html($commande->get_billing_email()),
        ]),
    ]];

    if (! wc_ship_to_billing_address_only() && $commande->needs_shipping_address() && $commande->get_formatted_shipping_address()) {
        $blocs[] = [
            'cle' => 'shipping',
            'titre' => 'Livraison',
            'adresse' => $commande->get_formatted_shipping_address(),
            'lignes' => array_filter([$commande->get_shipping_phone() ? wc_make_phone_clickable($commande->get_shipping_phone()) : '']),
        ];
    }

    // Les champs ajoutés aux adresses par WooCommerce ou une extension s'accrochent ici.
    foreach ($blocs as &$bloc) {
        $bloc['ajouts'] = sortie_crochet('woocommerce_email_customer_address_section', $bloc['cle'], $commande, $boutique, false);
    }

    return $blocs;
}

/** Ce que le pied et l'en-tête de tous les e-mails affichent. */
function mail_habillage(): array
{
    $c = coordonnees();

    return [
        'marque' => texte('marque') ?: get_bloginfo('name', 'display'),
        'accueil' => home_url('/'),
        'logo' => image('mail/logo-univers-sensoriel-clair.png'),
        'contacts' => array_filter([
            $c['whatsapp'] ? ['WhatsApp', lien_whatsapp()] : null,
            $c['courriel'] ? [$c['courriel'], 'mailto:' . $c['courriel']] : null,
            $c['telephone'] ? [$c['telephone'], lien_telephone($c['telephone'])] : null,
        ]),
        'liens' => [
            ['Livraison et retours', home_url('/livraison-et-retours/')],
            ['Questions fréquentes', home_url('/questions-frequentes/')],
            ['Mon compte', wc_get_page_permalink('myaccount')],
        ],
        'boutiques' => implode(' · ', array_filter(array_map(fn($b) => $b['ville'], $c['boutiques']))),
    ];
}

/** Styles des e-mails : resources/css/mail.css, que WooCommerce recopie dans les balises (les messageries ignorent les feuilles de style). */
function mail_styles(): string
{
    return (string) file_get_contents(get_theme_file_path('resources/css/mail.css'));
}

/*
|--------------------------------------------------------------------------
| E-mails envoyés hors de WooCommerce
|--------------------------------------------------------------------------
| Formulaires, réinitialisation de mot de passe de l'administration, notifications de WordPress et des extensions :
| le message (texte brut ou morceau de HTML) est placé dans l'habillage commun. Un e-mail qui est déjà un document
| HTML complet (ceux de WooCommerce, ou une extension qui a son propre habillage) n'est pas touché.
| Titre affiché : le <h1> placé en tête du message s'il y en a un, sinon l'objet sans le « [Nom du site] » de WordPress.
*/
add_filter('wp_mail', function ($courriel) {
    $message = (string) ($courriel['message'] ?? '');
    if (trim($message) === '' || stripos($message, '<html') !== false) {
        return $courriel;
    }

    $entetes = $courriel['headers'] ?? [];
    $entetes = array_values(array_filter(array_map('trim', is_array($entetes) ? $entetes : preg_split('/\r\n|\r|\n/', (string) $entetes))));
    $html = (bool) preg_grep('#^content-type:\s*text/html#i', $entetes) || apply_filters('wp_mail_content_type', 'text/plain') === 'text/html';

    $titre = trim((string) preg_replace('/^\s*\[[^\]]+\]\s*/', '', (string) ($courriel['subject'] ?? '')));
    if ($html && preg_match('#^\s*<h1[^>]*>(.*?)</h1>#is', $message, $trouve)) {
        $titre = trim(wp_strip_all_tags($trouve[1]));
        $message = substr($message, strlen($trouve[0]));
    }

    try {
        $courriel['message'] = habiller_mail($titre, $html ? $message : make_clickable(esc_html($message)));
    } catch (\Throwable $erreur) {
        // Un e-mail sans habillage vaut mieux qu'un e-mail perdu.
        return $courriel;
    }

    $courriel['headers'] = array_merge(
        array_values(preg_grep('#^content-type:#i', $entetes, PREG_GREP_INVERT)),
        ['Content-Type: text/html; charset=UTF-8'],
    );

    return $courriel;
}, 20);

/** Place un message dans l'habillage commun et recopie les styles dans les balises. */
function habiller_mail(string $titre, string $message): string
{
    $gabarit = new \WC_Email();
    $gabarit->email_type = 'html';

    return $gabarit->style_inline(WC()->mailer()->wrap_message($titre, $message));
}
