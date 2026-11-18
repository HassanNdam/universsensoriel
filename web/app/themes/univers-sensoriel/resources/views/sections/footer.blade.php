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
        <h2 id="lettre-titre">Nouveautés et offres, par e-mail</h2>
      </div>
      <div class="formulaire formulaire-lettre">
        {!! $lettre !!}
        <p class="formulaire-mention">Désinscription à tout moment. <a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></p>
      </div>
    </section>
  @endif

  <div class="conteneur pied-haut">
    <div class="pied-marque">
      <img src="{{ App\image('logo-univers-sensoriel-clair.webp') }}" alt="Univers Sensoriel by K.Elle : beauté, bien-être, art de vivre" width="1452" height="716" loading="lazy">
      <a class="lien-or" href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank">{!! App\icone('whatsapp', 20) !!} Écrire sur WhatsApp</a>
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
        <li><a href="{{ home_url('/nos-boutiques/') }}">Nos boutiques</a></li>
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
      <p class="surtitre">Nos boutiques</p>
      <ul class="pied-boutiques">
        @foreach ($c['boutiques'] as $b)
          <li><strong>{{ $b['ville'] }}</strong> · {{ $b['pays'] }}<br><span>{{ $b['enseigne'] }}</span></li>
        @endforeach
      </ul>
    </div>
  </div>

  <div class="conteneur pied-bas">
    <p>© {{ date('Y') }} Univers Sensoriel by K.Elle</p>
    <ul>
      <li><a href="{{ home_url('/mentions-legales/') }}">Mentions légales</a></li>
      <li><a href="{{ home_url('/conditions-generales-de-vente/') }}">Conditions générales de vente</a></li>
      <li><a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></li>
    </ul>
  </div>
</footer>
