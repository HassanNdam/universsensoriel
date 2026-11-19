@extends('layouts.app')

@section('content')
  @php
    $boutique = function_exists('wc_get_products');
    $lien = fn (string $slug) => $boutique && ! is_wp_error($u = get_term_link($slug, 'product_cat')) ? $u : home_url('/boutique/');
    $onglets = $boutique ? [
      'elle' => ['Pour elle', App\produits_vedettes('parfums-femme', 8), $lien('parfums-femme')],
      'lui' => ['Pour lui', App\produits_vedettes('parfums-homme', 8), $lien('parfums-homme')],
      'soins' => ['Soins', App\produits_vedettes(['rituels-et-coffrets', 'soin-du-visage'], 8), $lien('beaute')],
      'maison' => ['Maison', App\produits_vedettes(['parfums-d-ambiance', 'huiles-essentielles'], 8), $lien('art-de-vivre')],
    ] : [];
    $univers = [
      ['parfums', 'Parfums', 'Extraits concentrés à 30 %', 'extrait-de-parfum-homme-notes-boisees', 'Flacon d\'extrait de parfum posé sur une écorce, entouré de fleurs'],
      ['beaute', 'Beauté', 'Visage, corps et cheveux', 'coffret-soins-visage-peptilux', 'Coffret de soins du visage ouvert sur une table de marbre'],
      ['bien-etre', 'Bien-être', 'Huiles essentielles et végétales', 'huile-vegetale-amande-douce', 'Flacon d\'huile d\'amande douce entouré d\'amandes et de fleurs'],
      ['art-de-vivre', 'Art de vivre', 'Parfums d\'ambiance', 'parfum-d-ambiance-diffuseur-salon', 'Diffuseur de parfum à bâtonnets dans un salon'],
    ];
    $familles = $boutique && taxonomy_exists('pa_famille-olfactive')
      ? get_terms(['taxonomy' => 'pa_famille-olfactive', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 8]) : [];
    $matieres = ['floral' => 'fleur-de-lys-matiere-premiere', 'boise' => 'bois-de-cedre-matiere-premiere', 'gourmand' => 'cacao-matiere-premiere', 'epice' => 'gingembre-matiere-premiere'];
    $c = App\coordonnees();
  @endphp

  {{-- 1. Visuel d'ouverture --}}
  <section class="ouverture">
    <div class="conteneur ouverture-grille">
      <div class="ouverture-texte">
        <h1>Parfums, soins et rituels pour <span class="titre-or"><span class="titre-or-texte">éveiller les sens</span><svg class="titre-ruban" viewBox="0 0 320 28" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M6 19C62 6 118 24 176 15S268 3 314 11"/></svg>{!! App\icone('etoile', 24, 'titre-etoile') !!}</span></h1>
        <p class="chapo">Extraits de parfum longue tenue, soins et senteurs pour la maison.</p>
        <div class="ouverture-actions">
          <a class="bouton bouton-clair" href="{{ $lien('parfums') }}">Découvrir les parfums</a>
          <a class="lien-or" href="{{ $lien('beaute') }}">Explorer les soins {!! App\icone('fleche', 18) !!}</a>
        </div>
      </div>
      <div class="ouverture-scene" aria-hidden="true">
        <div class="ouverture-arche">{!! App\visuel('encens-bois-de-santal-parfum', '', ['loading' => 'eager', 'fetchpriority' => 'high']) !!}</div>
        <div class="ouverture-flacon ouverture-flacon-1">{!! App\visuel('flacon-extrait-de-parfum-homme', '', ['loading' => 'eager']) !!}</div>
        <div class="ouverture-flacon ouverture-flacon-2">{!! App\visuel('flacon-extrait-de-parfum-femme', '', ['loading' => 'eager']) !!}</div>
        <span class="ouverture-etoile">{!! App\icone('etoile', 34) !!}</span>
      </div>
    </div>
  </section>

  {{-- 2. Sélection à onglets --}}
  @if ($onglets)
    <section class="section selection" data-onglets>
      <div class="conteneur">
        <header class="section-tete">
          <h2>Nos essentiels</h2>
          <div class="onglets" role="tablist" aria-label="Sélection par univers">
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
            <p class="section-suite"><a class="bouton bouton-contour" href="{{ $url }}">Tout voir</a></p>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- 3. Les quatre univers --}}
  <section class="section univers">
    <div class="conteneur">
      <header class="section-tete">
        <h2>Nos univers</h2>
      </header>
      <ul class="univers-grille">
        @foreach ($univers as [$slug, $nom, $texte, $image, $alt])
          <li>
            <a class="tuile" href="{{ $lien($slug) }}">
              <span class="tuile-image">{!! App\visuel($image, $alt, ['sizes' => '(min-width: 900px) 25vw, 50vw']) !!}</span>
              <span class="tuile-texte">
                <strong>{{ $nom }}</strong>
                <span>{{ $texte }}</span>
              </span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  </section>

  {{-- 4. La Maison : bloc sombre --}}
  <section class="maison">
    <div class="maison-image">{!! App\visuel('aloe-vera-feuilles', 'Feuilles d\'aloe vera, l\'un des actifs de nos soins') !!}</div>
    <div class="maison-texte">
      <p class="surtitre surtitre-clair">La Maison</p>
      <blockquote>
        <p>Le vrai luxe n'est pas ce que l'on voit. C'est ce que l'on ressent.</p>
      </blockquote>
      <p>Nous choisissons chaque parfum et chaque soin pour sa qualité, sa tenue et le plaisir qu'il procure.</p>
      <ul class="maison-points">
        <li>{!! App\icone('goutte', 22) !!} Extraits de parfum concentrés à 30 %</li>
        <li>{!! App\icone('feuille', 22) !!} Formules et actifs choisis avec soin</li>
        <li>{!! App\icone('boutique', 22) !!} Deux boutiques : Metz et Yaoundé</li>
      </ul>
      <a class="bouton bouton-clair" href="{{ home_url('/la-maison/') }}">Notre histoire</a>
    </div>
  </section>

  {{-- 5. Familles olfactives --}}
  @if ($familles && ! is_wp_error($familles))
    <section class="section familles">
      <div class="conteneur">
        <header class="section-tete">
          <h2>Choisir par famille olfactive</h2>
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
            <li><a class="pastille pastille-pleine" href="{{ home_url('/guide-des-familles-olfactives/') }}">Lire le guide</a></li>
          </ul>
        </div>
      </div>
    </section>
  @endif

  {{-- 6. Collection à l'honneur --}}
  <section class="honneur">
    <div class="conteneur honneur-grille">
      <div class="honneur-image">
        {!! App\visuel('parfum-luxury-fleurs-et-epices', 'Flacon de la collection Luxury posé parmi des fleurs et des épices') !!}
      </div>
      <div class="honneur-texte">
        <p class="surtitre">Collection Luxury</p>
        <h2>Les matières les plus précieuses</h2>
        <p>Oud, safran, vanille, fleurs rares. Pour elle et pour lui.</p>
        <a class="bouton" href="{{ $lien('collection-luxury') }}">Voir la collection</a>
      </div>
    </div>
  </section>

  {{-- 7. Nouveautés bien-être --}}
  @if ($boutique && ($rituels = App\produits_vedettes(['huiles-vegetales', 'brumes-parfumees', 'corps-et-bain'], 4)))
    <section class="section">
      <div class="conteneur">
        <header class="section-tete">
          <h2>Brumes, huiles et soins du corps</h2>
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
          <h2>Avis clients</h2>
          <p class="section-intro">Publiés mot pour mot.</p>
        </header>
        <div class="avis-grille">
          @foreach ($avisAccueil as $avis)
            @include('partials.carte-avis', ['avis' => $avis, 'lienProduit' => true])
          @endforeach
        </div>
        <p class="avis-note"><a class="lien-or" href="{{ home_url('/avis-clients/') }}">Tous les avis {!! App\icone('fleche', 16) !!}</a></p>
      </div>
    </section>
  @endif

  {{-- 9. Le journal --}}
  @if ($articles = App\articles_recents(3))
    <section class="section section-blanche">
      <div class="conteneur">
        <header class="section-tete">
          <h2>Le journal</h2>
        </header>
        <div class="journal-grille">
          @foreach ($articles as $article)
            @include('partials.carte-article', ['article' => $article])
          @endforeach
        </div>
        <p class="section-suite"><a class="bouton bouton-contour" href="{{ get_permalink(get_option('page_for_posts')) }}">Tous les articles</a></p>
      </div>
    </section>
  @endif

  {{-- 10. Boutiques et conseil --}}
  <section class="rendez-vous">
    <div class="conteneur rendez-vous-grille">
      <div>
        <h2>Sentir, essayer, demander conseil</h2>
        <p>En boutique ou par message, nous vous aidons à choisir.</p>
        <div class="ouverture-actions">
          <a class="bouton bouton-clair" href="{{ App\lien_whatsapp('Bonjour, j\'aimerais un conseil pour choisir un parfum.') }}" target="_blank" rel="noopener">{!! App\icone('whatsapp', 20) !!} Demander conseil</a>
          <a class="lien-or" href="{{ home_url('/contact/') }}">Formulaire de contact {!! App\icone('fleche', 18) !!}</a>
          <a class="lien-or" href="{{ home_url('/nos-boutiques/') }}">Nos boutiques {!! App\icone('fleche', 18) !!}</a>
        </div>
      </div>
      <ul class="rendez-vous-boutiques">
        @foreach ($c['boutiques'] as $b)
          <li>
            {!! App\icone('boutique', 28) !!}
            <p><strong>{{ $b['ville'] }}</strong><span>{{ $b['pays'] }} · {{ $b['enseigne'] }}</span><a href="tel:{{ preg_replace('/\s+/', '', $b['telephone']) }}">{{ $b['telephone'] }}</a></p>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@endsection
