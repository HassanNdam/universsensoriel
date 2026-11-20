{{-- Page d'attente affichée aux visiteurs non connectés tant que le site n'est pas en production (app/maintenance.php).
     Page autonome : ni en-tête, ni menu, ni pied de page du site. Reprend l'ouverture de l'accueil : fond sapin,
     titre en or avec ruban et étoile, arche dont les photos de matières se relaient, deux flacons au premier plan.
     Textes : Univers Sensoriel › Page d'attente (app/reglages.php).
     $legale : vrai sur une page légale, dont on affiche alors le texte seul dans le même habillage. --}}
@php
  $c = App\coordonnees();
  $lettre = $legale ? '' : App\formulaire('newsletter');
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

    {{-- Même bandeau que le site (événement et messages permanents), réglé au même endroit. --}}
    <div class="attente-cadre">
    @include('partials.bandeau', ['sans_lien' => true])

    <div class="attente {{ $legale ? 'attente-legale' : '' }}">
      @include('partials.poussiere')

      <header class="attente-haut conteneur">
        <a class="attente-logo" href="{{ home_url('/') }}" rel="home">
          <img src="{{ App\image('logo-univers-sensoriel-clair.webp') }}" alt="{{ App\texte('marque') }} : {{ mb_strtolower(App\texte('slogan')) }}" width="1452" height="716" fetchpriority="high">
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
              @include('partials.diapos', ['images' => App\diapos(), 'tailles' => '(min-width: 980px) 34rem, 80vw'])
            </div>
            <div class="attente-flacon attente-flacon-1">{!! App\media(App\reglage('ouverture_flacon_1'), '', ['loading' => 'eager']) !!}</div>
            <div class="attente-flacon attente-flacon-2">{!! App\media(App\reglage('ouverture_flacon_2'), '', ['loading' => 'eager']) !!}</div>
            <span class="attente-etoile">{!! App\icone('etoile', 34) !!}</span>
          </div>

          <div class="attente-annonce">
            <h1>{!! App\titre_or(App\texte('attente_titre')) !!}</h1>
            <p class="chapo">{{ App\texte('attente_texte') }}</p>

            @if ($lettre)
              <div class="attente-lettre">
                <p class="attente-invite">{{ App\texte('attente_invite') }}</p>
                <div class="formulaire formulaire-lettre">{!! $lettre !!}</div>
              </div>
            @endif
          </div>
        </main>
      @endif

      <footer class="attente-pied conteneur">
        <p>{{ App\texte('pied_copyright') }} · Site réalisé par <a href="https://hassan-ndam.com" rel="noopener" target="_blank">hassan-ndam.com</a></p>
        <ul>
          <li><a class="attente-picto" href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank" aria-label="{{ App\texte('pied_whatsapp') }}">{!! App\icone('whatsapp', 18) !!}</a></li>
          <li><a class="attente-picto" href="mailto:{{ $c['courriel'] }}" aria-label="Écrire à {{ $c['courriel'] }}">{!! App\icone('courriel', 18) !!}</a></li>
          <li><a href="{{ home_url('/mentions-legales/') }}">Mentions légales</a></li>
          <li><a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></li>
        </ul>
      </footer>
    </div>
    </div>

    @php(wp_footer())
  </body>
</html>
