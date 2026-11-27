@php
  $menu = App\menu_principal();
  $boutique = function_exists('wc_get_page_permalink');
  // Nouveautés et promotions : une entrée du menu, avec son étiquette « New » (app/offres.php).
  $nouveautes = $boutique ? App\nouveautes() : null;
  $offres = $boutique ? App\nombre_offres() : 0;
  $recents = $nouveautes ? App\produits_nouveautes(5) : [];
  // Nom de l'entrée : celui de la catégorie (Produits › Catégories) ; les autres libellés : Textes du site › Nouveautés, Promotions.
  $nomNouveautes = $nouveautes ? $nouveautes[2]->name : App\texte('offres_titre');
@endphp

@include('partials.bandeau')

<header class="entete" data-entete>
  <div class="entete-barre conteneur">
    <button class="entete-bouton entete-burger" type="button" aria-controls="menu-mobile" aria-expanded="false" data-ouvrir="menu-mobile">
      {!! App\icone('menu', 26) !!}
      <span class="sr-only">Ouvrir le menu</span>
    </button>

    <a class="entete-logo" href="{{ home_url('/') }}" aria-label="Univers Sensoriel, accueil">
      <img src="{{ App\image('logo-univers-sensoriel-compact-clair.webp') }}" alt="Univers Sensoriel by K.Elle" width="1452" height="656">
    </a>

    <nav class="entete-nav" aria-label="Menu principal">
      <ul>
        @foreach ($menu as $entree)
          <li class="entete-entree" data-mega>
            <a href="{{ $entree['url'] }}" aria-haspopup="true">{{ $entree['titre'] }}</a>
            <div class="mega">
              <div class="mega-fond conteneur">
                <div class="mega-colonne">
                  <p class="surtitre">{{ $entree['accroche'] }}</p>
                  <ul>
                    @foreach ($entree['rayons'] as $rayon)
                      <li><a href="{{ $rayon['url'] }}">{{ $rayon['titre'] }}</a></li>
                    @endforeach
                    <li><a class="mega-tout" href="{{ $entree['url'] }}">Tout voir {!! App\icone('fleche', 16) !!}</a></li>
                  </ul>
                </div>
                @if ($entree['explorer'])
                  <div class="mega-colonne">
                    <p class="surtitre">Par famille olfactive</p>
                    <ul class="mega-deux">
                      @foreach ($entree['explorer'] as $lien)
                        <li><a href="{{ $lien['url'] }}">{{ $lien['titre'] }}</a></li>
                      @endforeach
                    </ul>
                  </div>
                @endif
                <a class="mega-visuel" href="{{ $entree['url'] }}" tabindex="-1" aria-hidden="true">
                  {!! App\media($entree['visuel']) !!}
                  <span>{{ $entree['titre'] }}</span>
                </a>
              </div>
            </div>
          </li>
        @endforeach
        @if ($nouveautes || $offres)
          <li class="entete-entree entete-nouveautes" data-mega>
            <a href="{{ $nouveautes[0] ?? App\lien_offres() }}" aria-haspopup="true">{{ $nomNouveautes }} @if ($nouveautes && ($etiquette = App\texte('nouveautes_etiquette'))) <span class="etiquette-new" aria-hidden="true">{{ $etiquette }}</span>@endif</a>
            <div class="mega">
              <div class="mega-fond conteneur">
                @if ($recents)
                  <div class="mega-colonne">
                    <p class="surtitre">{{ App\texte('nouveautes_menu') }}</p>
                    <ul>
                      @foreach (array_slice($recents, 0, 5) as $recent)
                        <li><a href="{{ $recent->get_permalink() }}">{{ $recent->get_name() }}</a></li>
                      @endforeach
                      <li><a class="mega-tout" href="{{ $nouveautes[0] }}">{{ App\texte('nouveautes_bouton') }} {!! App\icone('fleche', 16) !!}</a></li>
                    </ul>
                  </div>
                @endif
                @if ($offres)
                  <div class="mega-colonne">
                    <p class="surtitre">{{ App\texte('offres_titre') }}</p>
                    <ul>
                      <li><a href="{{ App\lien_offres() }}">{{ App\texte('offres_nombre', ['nombre' => $offres, 's' => $offres > 1 ? 's' : '']) }}</a></li>
                      <li><a class="mega-tout" href="{{ App\lien_offres() }}">{{ App\texte('offres_bouton') }} {!! App\icone('fleche', 16) !!}</a></li>
                    </ul>
                  </div>
                @endif
                @if ($recents)
                  <a class="mega-visuel mega-visuel-produit" href="{{ $nouveautes[0] }}" tabindex="-1" aria-hidden="true">
                    {!! App\image_produit($recents[0], 'medium_large') !!}
                    <span>{{ $nomNouveautes }}</span>
                  </a>
                @endif
              </div>
            </div>
          </li>
        @endif
        <li class="entete-entree"><a href="{{ home_url('/la-maison/') }}">La Maison</a></li>
      </ul>
    </nav>

    <div class="entete-outils">
      <button class="entete-reglages" type="button" aria-controls="langue-pays" aria-expanded="false" data-ouvrir="langue-pays">
        @include('partials.langue-pays-libelle')
        <span class="sr-only">Langue et pays</span>
      </button>
      <button class="entete-bouton" type="button" aria-controls="recherche" aria-expanded="false" data-ouvrir="recherche">
        {!! App\icone('recherche', 24) !!}
        <span class="sr-only">Rechercher</span>
      </button>
      @if ($boutique)
        <a class="entete-bouton entete-compte" href="{{ wc_get_page_permalink('myaccount') }}">
          {!! App\icone('compte', 24) !!}
          <span class="sr-only">Mon compte</span>
        </a>
        <a class="entete-bouton entete-panier" href="{{ wc_get_cart_url() }}">
          {!! App\icone('panier', 24) !!}
          {!! App\compteur_panier() !!}
          <span class="sr-only">Panier</span>
        </a>
      @endif
    </div>
  </div>

  <div class="recherche" id="recherche" hidden>
    <form class="conteneur" role="search" method="get" action="{{ home_url('/') }}">
      <label class="sr-only" for="champ-recherche">Rechercher un produit</label>
      {!! App\icone('recherche', 22) !!}
      <input id="champ-recherche" type="search" name="s" placeholder="Un parfum, une note, un soin…" autocomplete="off">
      <input type="hidden" name="post_type" value="product">
      <button class="bouton bouton-clair" type="submit">Rechercher</button>
      <button class="entete-bouton" type="button" data-fermer="recherche">{!! App\icone('fermer', 22) !!}<span class="sr-only">Fermer</span></button>
    </form>
  </div>
</header>

<div class="tiroir tiroir-gauche" id="menu-mobile" hidden>
  <div class="tiroir-voile" data-fermer="menu-mobile"></div>
  <div class="tiroir-panneau tiroir-sombre" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="tiroir-tete">
      <img src="{{ App\image('logo-univers-sensoriel-compact-clair.webp') }}" alt="Univers Sensoriel" width="1452" height="656" loading="lazy">
      <button class="entete-bouton" type="button" data-fermer="menu-mobile">{!! App\icone('fermer', 26) !!}<span class="sr-only">Fermer le menu</span></button>
    </div>
    <nav aria-label="Menu mobile">
      @if ($nouveautes)
        <a class="mobile-lien mobile-nouveautes" href="{{ $nouveautes[0] }}">{{ $nomNouveautes }} @if ($etiquette = App\texte('nouveautes_etiquette')) <span class="etiquette-new" aria-hidden="true">{{ $etiquette }}</span>@endif</a>
      @endif
      @if ($offres)
        <a class="mobile-lien" href="{{ App\lien_offres() }}">{{ App\texte('offres_titre') }}</a>
      @endif
      @foreach ($menu as $entree)
        <details class="mobile-univers">
          <summary>{{ $entree['titre'] }} {!! App\icone('chevron', 20) !!}</summary>
          <ul>
            <li><a href="{{ $entree['url'] }}">Tout voir</a></li>
            @foreach ($entree['rayons'] as $rayon)
              <li><a href="{{ $rayon['url'] }}">{{ $rayon['titre'] }}</a></li>
            @endforeach
          </ul>
        </details>
      @endforeach
      <a class="mobile-lien" href="{{ home_url('/journal/') }}">Le journal</a>
      <a class="mobile-lien" href="{{ home_url('/la-maison/') }}">La Maison</a>
      <a class="mobile-lien" href="{{ home_url('/notre-boutique/') }}">{{ App\boutiques_titre() }}</a>
      <a class="mobile-lien" href="{{ home_url('/contact/') }}">Contact</a>
      @if ($boutique)
        <a class="mobile-lien" href="{{ wc_get_page_permalink('myaccount') }}">Mon compte</a>
      @endif
      <button class="mobile-lien mobile-reglages" type="button" aria-controls="langue-pays" aria-expanded="false" data-ouvrir="langue-pays">
        Langue et pays @include('partials.langue-pays-libelle')
      </button>
    </nav>
  </div>
</div>

@include('partials.langue-pays')
