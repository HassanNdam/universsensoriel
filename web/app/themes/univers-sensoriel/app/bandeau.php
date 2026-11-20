<?php

/**
 * Bandeau du haut : messages permanents qui défilent (livraison, retrait…) et, au-dessus, l'événement du moment
 * (Black Friday, Noël…) avec son ambiance : couleurs, illustrations animées, particules, compte à rebours.
 *
 * Tout se règle dans Univers Sensoriel › Bandeau et événements (App\REGLAGES, app/reglages.php).
 * Gabarit : resources/views/partials/bandeau.blade.php ; styles : resources/css/bandeau.css.
 */

namespace App;

/** Messages permanents, repères remplacés, sans les vides. */
function bandeau_messages(): array
{
    return array_values(array_filter([texte('bandeau_1'), texte('bandeau_2'), texte('bandeau_3')]));
}

/**
 * Événement à afficher aujourd'hui, ou null.
 * Retenu : affiché, premier jour passé (ou vide), dernier jour pas encore fini (ou vide) ; le plus bas de la liste l'emporte.
 * Aperçu : une personne qui peut modifier les pages ajoute ?evenement=<ambiance> à l'adresse.
 */
function evenement(): ?array
{
    static $retenu = false;
    if ($retenu !== false) {
        return $retenu;
    }
    $lignes = array_values(array_filter(lignes('evenements', ['titre', 'message']), fn ($ligne) => isset(AMBIANCES[$ligne['theme'] ?? ''])));

    $apercu = isset($_GET['evenement']) && current_user_can('edit_pages') ? sanitize_key(wp_unslash($_GET['evenement'])) : '';
    if ($apercu !== '' && isset(AMBIANCES[$apercu])) {
        $choisi = array_values(array_filter($lignes, fn ($ligne) => $ligne['theme'] === $apercu));
        $ligne = end($choisi) ?: ['theme' => $apercu, 'titre' => explode(' : ', AMBIANCES[$apercu])[0], 'message' => 'Aperçu de l\'ambiance', 'compte' => false];

        return $retenu = evenement_prepare($ligne);
    }

    $jour = wp_date('Ymd');
    $retenu = null;
    foreach ($lignes as $ligne) {
        $debut = preg_replace('/\D/', '', (string) ($ligne['debut'] ?? ''));
        $fin = preg_replace('/\D/', '', (string) ($ligne['fin'] ?? ''));
        if (! empty($ligne['actif']) && ($debut === '' || $debut <= $jour) && ($fin === '' || $jour <= $fin)) {
            $retenu = evenement_prepare($ligne);
        }
    }

    return $retenu;
}

/** Ligne d'événement prête pour le gabarit : lien complet, fin du compte à rebours. */
function evenement_prepare(array $ligne): array
{
    $lien = trim((string) ($ligne['lien'] ?? ''));
    if ($lien !== '' && str_starts_with($lien, '/')) {
        $lien = home_url($lien);
    }
    $fin = preg_replace('/\D/', '', (string) ($ligne['fin'] ?? ''));
    $echeance = strlen($fin) === 8 ? \DateTimeImmutable::createFromFormat('YmdHis', $fin . '235959', wp_timezone()) : null;
    $reste = $echeance ? $echeance->getTimestamp() - time() : 0;

    return [
        'theme' => $ligne['theme'],
        'titre' => completer(trim((string) ($ligne['titre'] ?? ''))),
        'message' => completer(trim((string) ($ligne['message'] ?? ''))),
        'lien' => $lien !== '' ? esc_url($lien) : '',
        'lien_texte' => trim((string) ($ligne['lien_texte'] ?? '')),
        // Compte à rebours seulement s'il reste moins de 60 jours : au-delà, il n'apprend rien.
        'echeance' => ! empty($ligne['compte']) && $reste > 0 && $reste < 60 * DAY_IN_SECONDS ? $echeance->format('c') : '',
        'reste' => max(0, $reste),
    ];
}

/** Temps restant découpé pour le premier affichage (le script prend ensuite le relais chaque seconde). */
function compte_a_rebours(int $secondes): array
{
    return [
        'j' => intdiv($secondes, DAY_IN_SECONDS),
        'h' => sprintf('%02d', intdiv($secondes % DAY_IN_SECONDS, HOUR_IN_SECONDS)),
        'm' => sprintf('%02d', intdiv($secondes % HOUR_IN_SECONDS, MINUTE_IN_SECONDS)),
        's' => sprintf('%02d', $secondes % MINUTE_IN_SECONDS),
    ];
}

/*
|--------------------------------------------------------------------------
| Illustrations (SVG au trait, comme les pictos du site)
|--------------------------------------------------------------------------
| Chaque pièce porte sa classe d'animation : ill-pend (se balance depuis le haut), ill-ondule (depuis le bas),
| ill-scintille, ill-bat (bat comme un cœur), ill-flotte. Couleurs : ill-p1, ill-p2, ill-p3 (remplissages
| de l'ambiance), ill-encre (trait couleur du fond). Le délai décale les pièces entre elles.
*/

function ill_g(string $classe, float $delai, string $contenu): string
{
    return sprintf('<g class="%s" style="animation-delay:%ss">%s</g>', $classe, $delai, $contenu);
}

function ill_etoile(float $x, float $y, float $taille, float $delai = 0, string $plein = 'ill-p1'): string
{
    $e = $taille / 24;

    return ill_g('ill-scintille', $delai, sprintf(
        '<path class="%s" transform="translate(%s %s) scale(%s)" d="M12 2.500c.6 5.400 3.100 8.400 9.500 9.500-6.400 1.100-8.900 4.100-9.500 9.500-.6-5.400-3.100-8.400-9.500-9.500 6.400-1.100 8.900-4.100 9.500-9.500Z"/>',
        $plein, $x - $taille / 2, $y - $taille / 2, $e
    ));
}

function ill_boule(float $x, float $fil, float $r, string $plein, float $delai): string
{
    $haut = $fil + 3.5;
    $cy = $haut + $r;

    return ill_g('ill-pend', $delai, sprintf(
        '<path class="ill-fil" d="M%1$s 0V%2$s"/><rect x="%3$s" y="%2$s" width="5" height="3.500" rx=".6" class="ill-p2"/>'
        . '<circle cx="%1$s" cy="%4$s" r="%5$s" class="%6$s"/><path class="ill-reflet" d="M%7$s %8$sa%9$s %9$s 0 0 1 %10$s -%10$s"/>',
        $x, $fil, $x - 2.5, $cy, $r, $plein, $x - $r * 0.55, $cy - $r * 0.1, $r * 0.55, $r * 0.4
    ));
}

function ill_etiquette(float $x, float $fil, float $e, string $plein, float $delai): string
{
    // Étiquette de prix suspendue, avec un « % ».
    $t = fn ($dx, $dy) => ($x + $dx * $e) . ' ' . ($fil + $dy * $e);

    return ill_g('ill-pend', $delai, sprintf(
        '<path class="ill-fil" d="M%s 0V%s"/><path class="%s" d="M%s L%s V%s H%s V%s Z"/><circle class="ill-encre" cx="%s" cy="%s" r="%s"/>'
        . '<path class="ill-encre" d="M%s L%s"/><circle class="ill-encre" cx="%s" cy="%s" r="%s"/><circle class="ill-encre" cx="%s" cy="%s" r="%s"/>',
        $x, $fil, $plein, $t(0, 0), $t(11, 9), $fil + 31 * $e, $x - 11 * $e, $fil + 9 * $e,
        $x, $fil + 6.5 * $e, 1.8 * $e,
        $t(5, 14), $t(-5, 27),
        $x - 4 * $e, $fil + 16 * $e, 1.7 * $e, $x + 4 * $e, $fil + 25 * $e, 1.7 * $e
    ));
}

function ill_coeur(float $x, float $y, float $e, string $plein, string $classe, float $delai): string
{
    return ill_g($classe, $delai, sprintf(
        '<path class="%s" transform="translate(%s %s) scale(%s)" d="M0 4C-1.500-1-10-1.500-10 5c0 5 7 8 10 12 3-4 10-7 10-12 0-6.500-8.500-6-10-1Z"/>',
        $plein, $x, $y, $e
    ));
}

function ill_coeur_pendu(float $x, float $fil, float $e, string $plein, float $delai): string
{
    return ill_g('ill-pend', $delai, sprintf('<path class="ill-fil" d="M%s 0V%s"/>', $x, $fil + 2 * $e) . ill_coeur($x, $fil, $e, $plein, '', 0));
}

function ill_sapin(float $x): string
{
    $p = fn (array $points) => implode(' ', array_map(fn ($c) => ($x + $c[0]) . ' ' . $c[1], $points));

    return '<path class="ill-p3" d="M' . $p([[0, 12], [-12, 25], [-6, 25], [-16, 38], [-9, 38], [-20, 51], [20, 51], [9, 38], [16, 38], [6, 25], [12, 25]]) . 'Z"/>'
        . '<path class="ill-p2" d="M' . $p([[-3, 51], [-3, 55], [3, 55], [3, 51]]) . '"/>'
        . ill_etoile($x, 9, 11, 0, 'ill-p1')
        . ill_g('ill-scintille', 0.4, sprintf('<circle class="ill-p1" cx="%s" cy="29" r="1.400"/><circle class="ill-p1" cx="%s" cy="43" r="1.400"/>', $x - 5, $x + 7))
        . ill_g('ill-scintille', 1.1, sprintf('<circle class="ill-p2" cx="%s" cy="33" r="1.400"/><circle class="ill-p2" cx="%s" cy="46" r="1.400"/>', $x + 5, $x - 9));
}

function ill_cadeau(float $x, float $y, float $l, float $h, string $plein, float $delai): string
{
    $m = $x + $l / 2;

    return ill_g('ill-flotte', $delai, sprintf(
        '<rect class="%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s" rx=".8"/><path class="ill-encre" d="M%6$s %3$sV%7$s"/>'
        . '<path class="ill-p1" d="M%6$s %3$sc-2-4.500-7-4.500-6.500-1.500.3 1.500 4 1.500 6.500 1.500Zm0 0c2-4.500 7-4.500 6.500-1.500-.3 1.500-4 1.500-6.500 1.500Z"/>',
        $plein, $x, $y, $l, $h, $m, $y + $h
    ));
}

function ill_sac(float $x, float $y, string $plein, float $delai): string
{
    return ill_g('ill-flotte', $delai, sprintf(
        '<path class="ill-fil" d="M%1$s %2$sv-5a7 7 0 0 1 14 0v5"/><path class="%3$s" d="M%4$s %2$sH%5$sl3 %6$sH%7$sZ"/>'
        . '<path class="ill-encre" d="M%8$s %9$sh8"/>',
        $x - 7, $y, $plein, $x - 15, $x + 15, 28, $x - 18, $x - 4, $y + 12
    ));
}

function ill_flute(float $x, float $angle, float $delai): string
{
    return ill_g('ill-ondule', $delai, sprintf(
        '<g transform="rotate(%1$s %2$s 50)"><path class="ill-p1" d="M%3$s 14H%4$sl-1.500 15a3.500 3.500 0 0 1-7 0Z"/>'
        . '<path class="ill-fil" d="M%2$s 32.500V48M%5$s 48h12"/>'
        . '<circle class="ill-encre ill-bulle" cx="%2$s" cy="24" r=".9"/><circle class="ill-encre ill-bulle" cx="%6$s" cy="20" r=".7"/></g>',
        $angle, $x, $x - 5, $x + 5, $x - 6, $x + 1.5
    ));
}

function ill_eclat(float $x, float $y, float $r, string $plein, float $delai): string
{
    $rayons = '';
    for ($i = 0; $i < 8; $i++) {
        $a = M_PI * $i / 4;
        $rayons .= sprintf('M%s %s L%s %s', round($x + cos($a) * $r * 0.35, 2), round($y + sin($a) * $r * 0.35, 2), round($x + cos($a) * $r, 2), round($y + sin($a) * $r, 2));
    }

    return ill_g('ill-eclate', $delai, sprintf('<path class="ill-rayon %s" d="%s"/>', $plein, $rayons));
}

function ill_fleur(float $x, float $haut, string $plein, float $delai): string
{
    $petales = '';
    foreach ([0, 72, 144, 216, 288] as $a) {
        $petales .= sprintf('<ellipse class="%s" cx="%s" cy="%s" rx="3" ry="4.600" transform="rotate(%s %s %s)"/>', $plein, $x, $haut - 4.5, $a, $x, $haut);
    }

    return ill_g('ill-ondule', $delai, sprintf(
        '<path class="ill-fil" d="M%1$s 56Q%2$s %3$s %1$s %4$s"/><path class="ill-p3" d="M%1$s %5$sc4-5 9-5 11-3-3 3-7 4-11 3Z"/>%6$s<circle class="ill-p1" cx="%1$s" cy="%4$s" r="2.200"/>',
        $x, $x - 6, ($haut + 56) / 2, $haut, $haut + 20, $petales
    ));
}

function ill_flacon(float $x, string $plein, float $delai): string
{
    return ill_g('ill-flotte', $delai, sprintf(
        '<rect class="ill-p2" x="%1$s" y="9" width="10" height="9" rx="1"/><rect class="ill-p1" x="%2$s" y="18" width="6" height="4"/>'
        . '<rect class="%3$s" x="%4$s" y="22" width="24" height="30" rx="2.500"/><path class="ill-encre" d="M%5$s 34h12M%5$s 39h8"/>',
        $x - 5, $x - 3, $plein, $x - 12, $x - 6
    ));
}

function ill_noeud(float $x, float $y, float $delai): string
{
    return ill_g('ill-pend', $delai, sprintf(
        '<path class="ill-fil" d="M%1$s 0V%2$s"/><path class="ill-p1" d="M%3$s %4$sL%5$s %6$sL%3$s %7$sZM%8$s %4$sL%9$s %6$sL%8$s %7$sZ"/><rect class="ill-p2" x="%10$s" y="%11$s" width="5" height="6" rx="1"/>',
        $x, $y - 3, $x - 13, $y - 6, $x - 2, $y, $y + 6, $x + 13, $x + 2, $x - 2.5, $y - 3
    ));
}

function ill_goutte(float $x, float $y, float $e, string $plein, float $delai): string
{
    return ill_g('ill-flotte', $delai, sprintf(
        '<path class="%s" transform="translate(%s %s) scale(%s)" d="M0-8.500c3 3.800 5.500 6.700 5.500 10a5.500 5.500 0 0 1-11 0c0-3.300 2.500-6.200 5.500-10Z"/>',
        $plein, $x, $y, $e
    ));
}

/** Les deux grappes d'illustrations d'une ambiance (gauche, droite), dans un repère de 170 × 56. */
function illustrations(string $ambiance): array
{
    return match ($ambiance) {
        'noel' => [
            ill_boule(18, 6, 7, 'ill-p1', 0) . ill_boule(44, 17, 5.5, 'ill-p2', -0.8) . ill_boule(70, 3, 8, 'ill-p4', -1.6)
                . ill_boule(98, 13, 5, 'ill-p1', -2.4) . ill_etoile(128, 18, 9, 0.3) . ill_etoile(152, 36, 6, 1.2, 'ill-p2'),
            ill_etoile(22, 20, 7, 0.8) . ill_cadeau(44, 38, 20, 14, 'ill-p4', 0) . ill_sapin(100) . ill_cadeau(132, 34, 24, 18, 'ill-p1', -1.2) . ill_etoile(160, 14, 8, 0.2),
        ],
        'black-friday' => [
            ill_etiquette(22, 4, 1, 'ill-p1', 0) . ill_etiquette(56, 12, 0.75, 'ill-p2', -1) . ill_etoile(88, 18, 10, 0.4) . ill_etoile(112, 38, 7, 1.3) . ill_etoile(140, 22, 5, 0.9, 'ill-p2'),
            ill_etoile(30, 34, 7, 1) . ill_etoile(52, 16, 10, 0.2) . ill_sac(96, 22, 'ill-p1', 0) . ill_etiquette(140, 6, 0.85, 'ill-p2', -0.6),
        ],
        'nouvel-an' => [
            ill_flute(30, -12, 0) . ill_flute(48, 12, -0.4) . ill_eclat(90, 22, 14, 'ill-p1', 0) . ill_eclat(130, 36, 10, 'ill-p2', 0.8) . ill_etoile(152, 14, 8, 0.4),
            ill_etoile(20, 36, 7, 1.1) . ill_eclat(50, 20, 12, 'ill-p2', 0.4) . ill_eclat(92, 30, 16, 'ill-p1', 1.2) . ill_flute(132, -10, -0.8) . ill_flute(150, 10, -1.2),
        ],
        'saint-valentin' => [
            ill_coeur_pendu(20, 8, 0.8, 'ill-p1', 0) . ill_coeur_pendu(48, 18, 0.6, 'ill-p2', -0.7) . ill_coeur_pendu(76, 4, 0.9, 'ill-p4', -1.4)
                . ill_coeur(110, 26, 0.5, 'ill-p2', 'ill-flotte', 0) . ill_etoile(140, 18, 8, 0.6),
            ill_etoile(26, 34, 7, 0.2) . ill_coeur(60, 18, 0.55, 'ill-p2', 'ill-flotte', -1) . ill_coeur(104, 18, 1.35, 'ill-p1', 'ill-bat', 0) . ill_coeur(142, 26, 0.7, 'ill-p4', 'ill-flotte', -2),
        ],
        'fete-des-meres' => [
            ill_fleur(20, 20, 'ill-p1', 0) . ill_fleur(42, 30, 'ill-p4', -0.9) . ill_fleur(64, 16, 'ill-p2', -1.6) . ill_coeur(104, 24, 0.55, 'ill-p1', 'ill-flotte', 0) . ill_etoile(138, 18, 8, 0.5),
            ill_etoile(24, 30, 7, 0.9) . ill_coeur(58, 20, 0.5, 'ill-p4', 'ill-flotte', -1) . ill_fleur(104, 24, 'ill-p2', -0.4) . ill_fleur(126, 14, 'ill-p1', -1.2) . ill_fleur(148, 28, 'ill-p4', -2),
        ],
        'fete-des-peres' => [
            ill_noeud(24, 18, 0) . ill_etoile(56, 30, 9, 0.6) . ill_goutte(84, 22, 1.1, 'ill-p1', -0.5) . ill_etoile(116, 16, 7, 1.2) . ill_etoile(146, 34, 6, 0.3, 'ill-p2'),
            ill_etoile(28, 18, 8, 0.4) . ill_goutte(58, 34, 0.8, 'ill-p2', -1) . ill_flacon(100, 'ill-p4', 0) . ill_noeud(144, 14, -0.8),
        ],
        'soldes' => [
            ill_etiquette(22, 4, 1, 'ill-p1', 0) . ill_etiquette(54, 14, 0.75, 'ill-p4', -0.9) . ill_etoile(90, 22, 9, 0.5) . ill_etiquette(122, 6, 0.85, 'ill-p1', -1.6),
            ill_etiquette(40, 8, 0.8, 'ill-p4', -0.5) . ill_etoile(72, 30, 8, 1) . ill_sac(110, 22, 'ill-p1', 0) . ill_etoile(150, 16, 7, 0.3),
        ],
        default => [
            ill_etoile(20, 18, 10, 0) . ill_goutte(50, 30, 1, 'ill-p2', -0.6) . ill_etoile(82, 30, 7, 0.8) . ill_etoile(118, 16, 9, 1.4) . ill_etoile(150, 34, 6, 0.4, 'ill-p2'),
            ill_etoile(20, 34, 6, 1) . ill_etoile(52, 16, 9, 0.2) . ill_goutte(92, 28, 1.2, 'ill-p1', -1.2) . ill_etoile(126, 34, 8, 0.6) . ill_etoile(154, 18, 10, 1.6, 'ill-p2'),
        ],
    };
}

/** Particules du fond (neige, confettis, cœurs…) : positions fixes, pas de hasard d'une page à l'autre. */
function particules(int $nombre = 22): string
{
    $html = '';
    for ($i = 0; $i < $nombre; $i++) {
        $html .= sprintf(
            '<i style="--x:%s%%;--y:%s%%;--d:%ss;--r:-%ss;--t:%s;--o:%s"></i>',
            round(fmod($i * 37.3 + 7, 100), 1), round(fmod($i * 53.7 + 11, 80), 1), 5 + ($i * 7) % 6, round(fmod($i * 1.37, 9), 2), 0.6 + (($i * 5) % 7) / 10, 0.5 + (($i * 3) % 5) / 10
        );
    }

    return $html;
}
