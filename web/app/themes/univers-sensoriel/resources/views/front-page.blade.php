@extends('layouts.app')

@section('content')
  @php
    $boutique = function_exists('wc_get_products');
    // Textes, photos et rayons de cette page : Univers Sensoriel › Accueil (app/reglages.php).
    $onglets = [];
    foreach ($boutique ? App\lignes('essentiels', ['libelle']) : [] as $rang => $onglet) {
      $produits = ($slugs = App\rayons($onglet['rayons'] ?? [])) ? App\produits_vedettes($slugs, 8) : [];
      if ($produits) {
        $onglets[$rang] = [$onglet['libelle'], $produits, App\lien_rayon($onglet['lien'] ?: $slugs)];
      }
    }
    $univers = $boutique ? App\univers() : [];
    $familles = $boutique && taxonomy_exists('pa_famille-olfactive')
      ? get_terms(['taxonomy' => 'pa_famille-olfactive', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 8]) : [];
    $matieres = ['floral' => 'fleur-de-lys-matiere-premiere', 'boise' => 'bois-de-cedre-matiere-premiere', 'gourmand' => 'cacao-matiere-premiere', 'epice' => 'gingembre-matiere-premiere'];
    $c = App\coordonnees();
  @endphp

  {{-- 1. Visuel d'ouverture --}}
  <section class="ouverture">
    @include('partials.poussiere')
    <div class="conteneur ouverture-grille">
      <div class="ouverture-texte">
        <h1>{!! App\titre_or(App\texte('ouverture_titre')) !!}</h1>
        <p class="chapo">{{ App\texte('ouverture_texte') }}</p>
        <div class="ouverture-actions">
          <a class="bouton bouton-clair" href="{{ App\lien_rayon('parfums') }}">{{ App\texte('ouverture_bouton') }}</a>
          <a class="lien-or" href="{{ App\lien_rayon('beaute') }}">{{ App\texte('ouverture_lien') }} {!! App\icone('fleche', 18) !!}</a>
        </div>
      </div>
      <div class="ouverture-scene" aria-hidden="true">
        <div class="ouverture-arche">@include('partials.diapos', ['images' => App\diapos(), 'tailles' => '(min-width: 980px) 38rem, 92vw'])</div>
        <div class="ouverture-flacon ouverture-flacon-1">{!! App\media(App\reglage('ouverture_flacon_1'), '', ['loading' => 'eager']) !!}</div>
        <div class="ouverture-flacon ouverture-flacon-2">{!! App\media(App\reglage('ouverture_flacon_2'), '', ['loading' => 'eager']) !!}</div>
        <span class="ouverture-etoile">{!! App\icone('etoile', 34) !!}</span>
      </div>
    </div>
  </section>

  {{-- 2. Sélection à onglets --}}
  @if ($onglets)
    <section class="section selection" data-onglets>
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('essentiels_titre') }}</h2>
          <div class="onglets" role="tablist" aria-label="{{ App\texte('essentiels_titre') }}">
            @foreach ($onglets as $cle => [$libelle])
              <button type="button" role="tab" id="onglet-{{ $cle }}" aria-controls="panneau-{{ $cle }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" @if (! $loop->first) tabindex="-1" @endif>{{ $libelle }}</button>
            @endforeach
          </div>
        </header>
        @foreach ($onglets as $cle => [$libelle, $produits, $url])
          <div class="onglet-panneau" role="tabpanel" id="panneau-{{ $cle }}" aria-labelledby="onglet-{{ $cle }}" @if (! $loop->first) hidden @endif>
            <div class="grille-produits">
              @foreach ($produits as $produit)
                @include('partials.carte-produit', ['produit' => $produit])
              @endforeach
            </div>
            <p class="section-suite"><a class="bouton bouton-contour" href="{{ $url }}">{{ App\texte('essentiels_bouton') }}</a></p>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- 3. Les quatre univers --}}
  @if ($univers)
    <section class="section univers">
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('univers_titre') }}</h2>
        </header>
        <ul class="univers-grille">
          @foreach ($univers as $tuile)
            <li>
              <a class="tuile" href="{{ get_term_link($tuile['terme']) }}">
                <span class="tuile-image">{!! App\media($tuile['image'], $tuile['alt'], ['sizes' => '(min-width: 900px) 25vw, 50vw']) !!}</span>
                <span class="tuile-texte">
                  <strong>{{ $tuile['terme']->name }}</strong>
                  <span>{{ $tuile['texte'] }}</span>
                </span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  {{-- 4. La Maison : bloc sombre --}}
  <section class="maison">
    <div class="maison-image">{!! App\media(App\reglage('maison_image'), App\texte('maison_image_alt')) !!}</div>
    <div class="maison-texte">
      <p class="surtitre surtitre-clair">{{ App\texte('maison_surtitre') }}</p>
      @if ($citation = App\texte('maison_citation'))
        <blockquote>
          <p>{{ $citation }}</p>
        </blockquote>
      @endif
      <p>{{ App\texte('maison_texte') }}</p>
      <ul class="maison-points">
        @foreach (App\lignes('maison_points', ['texte']) as $point)
          <li>{!! App\icone($point['icone'], 22) !!} {{ $point['texte'] }}</li>
        @endforeach
      </ul>
      <a class="bouton bouton-clair" href="{{ home_url('/la-maison/') }}">{{ App\texte('maison_bouton') }}</a>
    </div>
  </section>

  {{-- 5. Familles olfactives --}}
  @if ($familles && ! is_wp_error($familles))
    <section class="section familles">
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('familles_titre') }}</h2>
        </header>
        <div class="familles-grille">
          <ul class="familles-photos">
            @foreach ($matieres as $slug => $image)
              @php($terme = get_term_by('slug', $slug, 'pa_famille-olfactive'))
              @if ($terme && $terme->count)
                <li>
                  <a class="tuile tuile-carree" href="{{ get_term_link($terme) }}">
                    <span class="tuile-image">{!! App\visuel($image, 'Parfums de la famille ' . mb_strtolower($terme->name), ['sizes' => '(min-width: 900px) 20vw, 50vw']) !!}</span>
                    <span class="tuile-texte"><strong>{{ $terme->name }}</strong><span>{{ $terme->count }} parfums</span></span>
                  </a>
                </li>
              @endif
            @endforeach
          </ul>
          <ul class="pastilles">
            @foreach ($familles as $famille)
              <li><a class="pastille" href="{{ get_term_link($famille) }}">{{ $famille->name }} <small>{{ $famille->count }}</small></a></li>
            @endforeach
            <li><a class="pastille pastille-pleine" href="{{ home_url('/guide-des-familles-olfactives/') }}">{{ App\texte('familles_bouton') }}</a></li>
          </ul>
        </div>
      </div>
    </section>
  @endif

  {{-- 6. Collection à l'honneur --}}
  <section class="honneur">
    <div class="conteneur honneur-grille">
      <div class="honneur-image">
        {!! App\media(App\reglage('honneur_image'), App\texte('honneur_image_alt')) !!}
      </div>
      <div class="honneur-texte">
        <p class="surtitre">{{ App\texte('honneur_surtitre') }}</p>
        <h2>{{ App\texte('honneur_titre') }}</h2>
        <p>{{ App\texte('honneur_texte') }}</p>
        <a class="bouton" href="{{ App\lien_rayon(App\reglage('honneur_rayon')) }}">{{ App\texte('honneur_bouton') }}</a>
      </div>
    </div>
  </section>

  {{-- 7. Nouveautés bien-être --}}
  @if ($boutique && ($slugs = App\rayons(App\reglage('rituels_rayons'))) && ($rituels = App\produits_vedettes($slugs, 4)))
    <section class="section">
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('rituels_titre') }}</h2>
        </header>
        <div class="grille-produits">
          @foreach ($rituels as $produit)
            @include('partials.carte-produit', ['produit' => $produit])
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- 8. Avis clients : de vrais avis, reproduits tels quels --}}
  @php($avisAccueil = $boutique ? App\avis(['accueil' => true, 'nombre' => App\AVIS_ACCUEIL]) : [])
  @if (count($avisAccueil) >= 3)
    <section class="section avis-accueil">
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('avis_titre') }}</h2>
          <p class="section-intro">{{ App\texte('avis_intro') }}</p>
        </header>
        @include('partials.avis-carrousel', ['liste' => $avisAccueil, 'lienProduit' => true, 'titre' => App\texte('avis_titre')])
        <p class="avis-note"><a class="lien-or" href="{{ home_url('/avis-clients/') }}">{{ App\texte('avis_lien') }} {!! App\icone('fleche', 16) !!}</a></p>
      </div>
    </section>
  @endif

  {{-- 9. Le journal --}}
  @if ($articles = App\articles_recents(3))
    <section class="section section-blanche">
      <div class="conteneur">
        <header class="section-tete">
          <h2>{{ App\texte('journal_titre') }}</h2>
        </header>
        <div class="journal-grille">
          @foreach ($articles as $article)
            @include('partials.carte-article', ['article' => $article])
          @endforeach
        </div>
        <p class="section-suite"><a class="bouton bouton-contour" href="{{ get_permalink(get_option('page_for_posts')) }}">{{ App\texte('journal_bouton') }}</a></p>
      </div>
    </section>
  @endif

  {{-- 10. Boutiques et conseil --}}
  <section class="rendez-vous">
    <div class="conteneur rendez-vous-grille">
      <div>
        <h2>{{ App\texte('conseil_titre') }}</h2>
        <p>{{ App\texte('conseil_texte') }}</p>
        <div class="ouverture-actions">
          <a class="bouton bouton-clair" href="{{ App\lien_whatsapp('wa_conseil') }}" target="_blank" rel="noopener">{!! App\icone('whatsapp', 20) !!} {{ App\texte('conseil_bouton') }}</a>
          <a class="lien-or" href="{{ home_url('/contact/') }}">{{ App\texte('conseil_lien_contact') }} {!! App\icone('fleche', 18) !!}</a>
          <a class="lien-or" href="{{ home_url('/nos-boutiques/') }}">{{ App\texte('conseil_lien_boutiques') }} {!! App\icone('fleche', 18) !!}</a>
        </div>
      </div>
      <ul class="rendez-vous-boutiques">
        @foreach ($c['boutiques'] as $b)
          <li>
            {!! App\icone('boutique', 28) !!}
            <p><strong>{{ $b['ville'] }}</strong><span>{{ $b['pays'] }} · {{ $b['enseigne'] }}</span>@if ($b['telephone'])<a href="{{ App\lien_telephone($b['telephone']) }}">{{ $b['telephone'] }}</a>@endif</p>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@endsection
