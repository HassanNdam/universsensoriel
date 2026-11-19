{{-- Page d'attente affichée aux visiteurs non connectés tant que le site n'est pas en production (app/maintenance.php).
     Page autonome : ni en-tête, ni menu, ni pied de page du site. Reprend l'ouverture de l'accueil : fond sapin,
     titre en or avec ruban et étoile, arche dont les photos de matières se relaient, deux flacons au premier plan.
     $legale : vrai sur une page légale, dont on affiche alors le texte seul dans le même habillage. --}}
@php
  $c = App\coordonnees();
  $lettre = $legale ? '' : App\formulaire('newsletter');
  // Photos de matières qui se relaient dans l'arche (resources/images/site).
  $diapos = ['encens-bois-de-santal-parfum', 'fleur-de-lys-matiere-premiere', 'aloe-vera-feuilles', 'bois-de-cedre-matiere-premiere'];
  // Poussière d'or : position, taille, durée et départ de chaque grain (fixes, pour un rendu identique à chaque visite).
  $grains = [[8, 3, 17, 0], [17, 2, 21, 6], [29, 4, 19, 11], [41, 2, 23, 3], [52, 3, 18, 14], [61, 2, 22, 8], [70, 4, 20, 1], [79, 2, 24, 12], [87, 3, 19, 5], [94, 2, 21, 16]];
@endphp
<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <div class="attente {{ $legale ? 'attente-legale' : '' }}">
      <div class="attente-poussiere" aria-hidden="true">
        @foreach ($grains as [$x, $taille, $duree, $depart])
          <span style="--x: {{ $x }}%; --taille: {{ $taille }}px; --duree: {{ $duree }}s; --depart: -{{ $depart }}s"></span>
        @endforeach
      </div>

      <header class="attente-haut conteneur">
        <a class="attente-logo" href="{{ home_url('/') }}" rel="home">
          <img src="{{ App\image('logo-univers-sensoriel-clair.webp') }}" alt="Univers Sensoriel by K.Elle : beauté, bien-être, art de vivre" width="1452" height="716" fetchpriority="high">
        </a>
      </header>

      @if ($legale)
        <main class="attente-texte conteneur" id="main">
          @while (have_posts()) @php(the_post())
            <h1>{!! get_the_title() !!}</h1>
            <div class="prose">@php(the_content())</div>
            <p><a class="lien-or" href="{{ home_url('/') }}">{!! App\icone('fleche', 18, 'attente-retour') !!} Retour</a></p>
          @endwhile
        </main>
      @else
        <main class="attente-coeur conteneur" id="main">
          <div class="attente-scene" aria-hidden="true">
            <div class="attente-arche">
              <div class="attente-diapos">
                @foreach ($diapos as $diapo)
                  {!! App\visuel($diapo, '', ['loading' => $loop->first ? 'eager' : 'lazy', 'fetchpriority' => $loop->first ? 'high' : 'low', 'sizes' => '(min-width: 980px) 34rem, 80vw']) !!}
                @endforeach
              </div>
            </div>
            <div class="attente-flacon attente-flacon-1">{!! App\visuel('flacon-extrait-de-parfum-homme', '', ['loading' => 'eager']) !!}</div>
            <div class="attente-flacon attente-flacon-2">{!! App\visuel('flacon-extrait-de-parfum-femme', '', ['loading' => 'eager']) !!}</div>
            <span class="attente-etoile">{!! App\icone('etoile', 34) !!}</span>
          </div>

          <div class="attente-annonce">
            <h1>Notre boutique en ligne <span class="titre-or"><span class="titre-or-texte">ouvre bientôt</span><svg class="titre-ruban" viewBox="0 0 320 28" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M6 19C62 6 118 24 176 15S268 3 314 11"/></svg>{!! App\icone('etoile', 24, 'titre-etoile') !!}</span></h1>
            <p class="chapo">Extraits de parfum, soins et senteurs pour la maison. En attendant, nos boutiques de Metz et Yaoundé vous accueillent.</p>

            @if ($lettre)
              <div class="attente-lettre">
                <p class="attente-invite">Recevoir un e-mail à l'ouverture</p>
                <div class="formulaire formulaire-lettre">{!! $lettre !!}</div>
              </div>
            @endif
          </div>
        </main>
      @endif

      <footer class="attente-pied conteneur">
        <p>© {{ date('Y') }} Univers Sensoriel by K.Elle · Site réalisé par <a href="https://hassan-ndam.com" rel="noopener" target="_blank">hassan-ndam.com</a></p>
        <ul>
          <li><a class="attente-picto" href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank" aria-label="Écrire sur WhatsApp">{!! App\icone('whatsapp', 18) !!}</a></li>
          <li><a class="attente-picto" href="mailto:{{ $c['courriel'] }}" aria-label="Écrire à {{ $c['courriel'] }}">{!! App\icone('courriel', 18) !!}</a></li>
          <li><a href="{{ home_url('/mentions-legales/') }}">Mentions légales</a></li>
          <li><a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></li>
        </ul>
      </footer>
    </div>

    @php(wp_footer())
  </body>
</html>
