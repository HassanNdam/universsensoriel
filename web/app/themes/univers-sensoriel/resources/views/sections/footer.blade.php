@php
  $c = App\coordonnees();
  $menu = App\menu_principal();
@endphp

<section class="garanties" aria-label="Nos engagements">
  <ul class="conteneur">
    @foreach (App\reassurances() as [$icone, $titre, $texte])
      <li>
        <span class="garantie-picto">{!! App\icone($icone, 26) !!}</span>
        <p><strong>{{ $titre }}</strong><span>{{ $texte }}</span></p>
      </li>
    @endforeach
  </ul>
</section>

<footer class="pied">
  @if ($lettre = App\formulaire('newsletter'))
    <section class="conteneur pied-lettre" aria-labelledby="lettre-titre">
      <div>
        <h2 id="lettre-titre">{{ App\texte('lettre_titre') }}</h2>
      </div>
      <div class="formulaire formulaire-lettre">
        {!! $lettre !!}
        <p class="formulaire-mention">{{ App\texte('lettre_mention') }} <a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></p>
      </div>
    </section>
  @endif

  <div class="conteneur pied-haut">
    <div class="pied-marque">
      <a href="{{ home_url('/') }}" rel="home">
        <img src="{{ App\image('logo-univers-sensoriel-clair.webp') }}" alt="{{ App\texte('marque') }} : {{ mb_strtolower(App\texte('slogan')) }}" width="1452" height="716" loading="lazy">
      </a>
      <a class="lien-or" href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank">{!! App\icone('whatsapp', 20) !!} {{ App\texte('pied_whatsapp') }}</a>
    </div>

    <nav class="pied-colonne" aria-label="La boutique">
      <p class="surtitre">La boutique</p>
      <ul>
        @foreach ($menu as $entree)
          <li><a href="{{ $entree['url'] }}">{{ $entree['titre'] }}</a></li>
        @endforeach
        <li><a href="{{ home_url('/guide-des-familles-olfactives/') }}">Guide des familles olfactives</a></li>
      </ul>
    </nav>

    <nav class="pied-colonne" aria-label="La Maison">
      <p class="surtitre">La Maison</p>
      <ul>
        <li><a href="{{ home_url('/la-maison/') }}">Notre histoire</a></li>
        <li><a href="{{ home_url('/journal/') }}">Le journal</a></li>
        <li><a href="{{ home_url('/avis-clients/') }}">Avis clients</a></li>
        <li><a href="{{ home_url('/notre-boutique/') }}">{{ App\boutiques_titre() }}</a></li>
        <li><a href="{{ home_url('/contact/') }}">Contact</a></li>
        <li><a href="{{ home_url('/devenir-consultante/') }}">Devenir consultante</a></li>
      </ul>
    </nav>

    <nav class="pied-colonne" aria-label="Aide">
      <p class="surtitre">Aide</p>
      <ul>
        <li><a href="{{ home_url('/livraison-et-retours/') }}">Livraison et retours</a></li>
        <li><a href="{{ home_url('/questions-frequentes/') }}">Questions fréquentes</a></li>
        @if (function_exists('wc_get_page_permalink'))
          <li><a href="{{ wc_get_page_permalink('myaccount') }}">Mon compte</a></li>
        @endif
      </ul>
    </nav>

    <div class="pied-colonne">
      <p class="surtitre">{{ App\boutiques_titre() }}</p>
      <ul class="pied-boutiques">
        @foreach ($c['boutiques'] as $b)
          <li><strong>{{ App\boutique_nom($b) }}</strong><br><span>{{ App\boutique_lieu($b) }}</span></li>
        @endforeach
        {{-- Le numéro une seule fois quand c'est le même partout (un seul point, ou plusieurs avec le même contact). --}}
        @if (count($numeros = array_unique(array_filter(array_column($c['boutiques'], 'telephone')))) === 1)
          <li><a href="{{ App\lien_telephone(reset($numeros)) }}">{{ reset($numeros) }}</a></li>
        @endif
      </ul>
    </div>
  </div>

  @if ($moyens = App\moyens_de_paiement())
    <div class="conteneur pied-paiement">
      <p>{!! App\icone('cadenas', 18) !!} {{ App\texte('pied_paiement') }}</p>
      <ul aria-label="Moyens de paiement acceptés">
        @foreach ($moyens as [$fichier, $nom])
          <li><img src="{{ App\image("paiement/{$fichier}.svg") }}" alt="{{ $nom }}" width="38" height="24" loading="lazy" decoding="async"></li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="conteneur pied-bas">
    <button class="pied-reglages" type="button" aria-controls="langue-pays" aria-expanded="false" data-ouvrir="langue-pays">
      {!! App\icone('globe', 18) !!} @include('partials.langue-pays-libelle', ['complet' => true])
      <span class="sr-only">Changer de langue ou de pays</span>
    </button>
    <p>{{ App\texte('pied_copyright') }} · Site réalisé par <a href="https://hassan-ndam.com" rel="noopener" target="_blank">hassan-ndam.com</a></p>
    <ul>
      <li><a href="{{ home_url('/mentions-legales/') }}">Mentions légales</a></li>
      <li><a href="{{ home_url('/conditions-generales-de-vente/') }}">Conditions générales de vente</a></li>
      <li><a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></li>
      @if (function_exists('cmplz_get_option'))
        <li><a href="{{ home_url('/politique-de-cookies/') }}">Cookies</a></li>
        {{-- « button.cmplz-manage-consent » : Complianz rouvre le bandeau au clic. --}}
        <li><button type="button" class="cmplz-manage-consent pied-cookies">Gérer mes cookies</button></li>
      @endif
    </ul>
  </div>
</footer>
