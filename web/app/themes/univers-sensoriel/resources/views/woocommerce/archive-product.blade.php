@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $objet = get_queried_object();
    $terme = $objet instanceof WP_Term ? $objet : null;
    $recherche = is_search();
    $titre = $recherche ? 'Résultats pour « ' . get_search_query() . ' »' : ($terme ? $terme->name : App\texte('boutique_titre'));
    $surtitre = 'Univers Sensoriel';
    if ($terme && $terme->taxonomy === 'pa_famille-olfactive') {
      $titre = 'Parfums ' . App\famille_au_pluriel($terme->name);
      $surtitre = 'Famille olfactive';
    } elseif ($terme && $terme->parent) {
      $surtitre = get_term($terme->parent)->name;
    }
    $description = $terme ? $terme->description : App\texte('boutique_intro');
    $offres = App\page_offres();
    if ($offres) {
      $titre = App\texte('offres_titre');
      $description = App\texte('offres_intro');
    }

    // Pastilles : les sous-rayons du rayon affiché (ou ses rayons frères), sinon les quatre univers.
    // Nouveautés et Promotions : l'une renvoie à l'autre.
    $pastilles = [];
    $nouveautes = App\nouveautes();
    if ($offres || ($terme && $terme->slug === 'nouveautes')) {
      $pastilles = array_filter([
        $nouveautes ? [$nouveautes[0], $nouveautes[2]->name, ! $offres] : null,
        App\nombre_offres() ? [App\lien_offres(), App\texte('offres_titre'), $offres] : null,
      ]);
    } elseif ($terme && $terme->taxonomy === 'product_cat') {
      $parent = $terme->parent ?: $terme->term_id;
      $pastilles[] = [get_term($parent), 'Tout'];
      foreach (get_terms(['taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => true, 'meta_key' => 'order', 'orderby' => 'meta_value_num']) as $t) {
        $pastilles[] = [$t, $t->name];
      }
    } elseif ($terme) {
      foreach (get_terms(['taxonomy' => $terme->taxonomy, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']) as $t) {
        $pastilles[] = [$t, $t->name];
      }
    } else {
      foreach (App\menu_principal() as $entree) {
        $pastilles[] = [get_term_by('slug', $entree['slug'], 'product_cat'), $entree['titre']];
      }
    }

    $donnees = App\facettes();
    $actifs = App\filtres_actifs();
    $nombreActifs = array_sum(array_map(fn ($v) => is_array($v) ? count($v) : 1, $actifs));
    $base = strtok(get_pagenum_link(1, false), '?');
    $total = (int) $wp_query->found_posts;
    $tri = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';
    $tris = apply_filters('woocommerce_catalog_orderby', []);
  @endphp

  <header class="rayon-tete">
    <div class="conteneur">
      @include('partials.fil')
      <h1>{{ $titre }}</h1>
      @if ($description && ! is_paged())
        <p class="rayon-intro">{{ wp_strip_all_tags($description) }}</p>
      @endif
      @if (count($pastilles) > 2 || (count($pastilles) === 2 && is_string(reset($pastilles)[0])))
        <ul class="pastilles pastilles-centre">
          @foreach ($pastilles as $pastille)
            @php
              // Rayon (terme) ou lien direct [adresse, libellé, actif] (Nouveautés, Promotions).
              [$t, $libelle] = $pastille;
              $actif = is_string($t) ? $pastille[2] : ($terme && $t && ! is_wp_error($t) && $t->term_id === $terme->term_id);
            @endphp
            @continue(! $t || is_wp_error($t))
            <li><a class="pastille {{ $actif ? 'pastille-pleine' : '' }}" href="{{ is_string($t) ? $t : get_term_link($t) }}" @if ($actif) aria-current="page" @endif>{{ $libelle }}</a></li>
          @endforeach
        </ul>
      @endif
    </div>
  </header>

  <div class="conteneur rayon">
    <div class="rayon-barre">
      <button class="bouton bouton-contour bouton-filtres" type="button" aria-controls="filtres" aria-expanded="false" data-ouvrir="filtres">
        {!! App\icone('filtres', 20) !!} Filtrer
        @if ($nombreActifs)<span class="bouton-compteur">{{ $nombreActifs }}</span>@endif
      </button>
      <p class="rayon-total" aria-live="polite">{{ $total }} {{ $total > 1 ? 'produits' : 'produit' }}</p>
      <form class="rayon-tri" method="get" action="{{ $base }}">
        <label for="tri">Trier par</label>
        <select id="tri" name="orderby" data-envoi-auto>
          @foreach ($tris as $cle => $libelle)
            <option value="{{ $cle }}" @selected($tri === $cle)>{{ $libelle }}</option>
          @endforeach
        </select>
        @foreach ($actifs as $cle => $valeurs)
          @foreach ((array) $valeurs as $valeur)
            <input type="hidden" name="{{ $cle }}{{ is_array($valeurs) ? '[]' : '' }}" value="{{ $valeur }}">
          @endforeach
        @endforeach
        @if ($recherche)
          <input type="hidden" name="s" value="{{ get_search_query() }}"><input type="hidden" name="post_type" value="product">
        @endif
        <noscript><button class="bouton bouton-contour" type="submit">Trier</button></noscript>
      </form>
    </div>

    @if ($nombreActifs)
      <ul class="filtres-actifs" aria-label="Filtres appliqués">
        @foreach ($actifs as $cle => $valeurs)
          @foreach ((array) $valeurs as $valeur)
            @php
              $reste = $actifs;
              if (is_array($valeurs)) { $reste[$cle] = array_values(array_diff($valeurs, [$valeur])); } else { unset($reste[$cle]); }
              $libelle = $cle === 'prix' ? App\TRANCHES_PRIX[$valeur] : (collect($donnees['facettes'][$cle]['valeurs'] ?? [])->firstWhere('slug', $valeur)['nom'] ?? $valeur);
            @endphp
            <li><a class="pastille pastille-retirer" href="{{ esc_url(add_query_arg(array_filter($reste), $base)) }}" rel="nofollow">{{ $libelle }} {!! App\icone('fermer', 14) !!}<span class="sr-only">(retirer ce filtre)</span></a></li>
          @endforeach
        @endforeach
        <li><a class="lien-simple" href="{{ $base }}" rel="nofollow">Tout effacer</a></li>
      </ul>
    @endif

    @if (have_posts())
      <div class="grille-produits grille-rayon">
        @while (have_posts())
          @php(the_post())
          @include('partials.carte-produit', ['produit' => wc_get_product(get_the_ID()), 'titre' => 'h2', 'eager' => $wp_query->current_post < 4])
        @endwhile
      </div>

      @php($pages = paginate_links(['type' => 'array', 'prev_text' => 'Précédent', 'next_text' => 'Suivant', 'mid_size' => 1]))
      @if ($pages)
        <nav class="pagination" aria-label="Pages de résultats">
          <ul>
            @foreach ($pages as $page)
              <li>{!! $page !!}</li>
            @endforeach
          </ul>
        </nav>
      @endif
    @else
      <div class="rayon-vide">
        {!! App\icone('etoile', 36) !!}
        <h2>{{ App\texte('boutique_vide_titre') }}</h2>
        <p>{{ App\texte('boutique_vide_texte') }}</p>
        <p><a class="bouton" href="{{ $base }}">Effacer les filtres</a></p>
      </div>
    @endif

    @if ($terme && $terme->taxonomy === 'pa_famille-olfactive')
      <aside class="rayon-note">
        <p>{{ App\texte('boutique_guide') }} <a class="lien-or" href="{{ home_url('/guide-des-familles-olfactives/') }}">Lire le guide des familles olfactives</a></p>
      </aside>
    @endif
  </div>

  {{-- Tiroir des filtres --}}
  <div class="tiroir tiroir-gauche" id="filtres" hidden>
    <div class="tiroir-voile" data-fermer="filtres"></div>
    <form class="tiroir-panneau" method="get" action="{{ $base }}" role="dialog" aria-modal="true" aria-label="Filtrer les produits">
      <div class="tiroir-tete">
        <p class="tiroir-titre">Filtrer</p>
        <button class="entete-bouton" type="button" data-fermer="filtres">{!! App\icone('fermer', 26) !!}<span class="sr-only">Fermer les filtres</span></button>
      </div>
      <div class="tiroir-corps">
        @foreach ($donnees['facettes'] as $cle => $facette)
          <details class="facette" @if ($loop->index < 2 || ! empty($actifs[$cle])) open @endif>
            <summary>{{ $facette['libelle'] }} <span class="facette-signe" aria-hidden="true"></span></summary>
            <ul>
              @foreach ($facette['valeurs'] as $valeur)
                <li>
                  <label class="case">
                    <input type="checkbox" name="{{ $cle }}[]" value="{{ $valeur['slug'] }}" @checked(in_array($valeur['slug'], $actifs[$cle] ?? [], true))>
                    <span>{{ $valeur['nom'] }}</span>
                    <small>{{ $valeur['nombre'] }}</small>
                  </label>
                </li>
              @endforeach
            </ul>
          </details>
        @endforeach
        @if (count($donnees['prix']) > 1)
          <details class="facette" @if (! empty($actifs['prix'])) open @endif>
            <summary>Prix <span class="facette-signe" aria-hidden="true"></span></summary>
            <ul>
              @foreach (App\TRANCHES_PRIX as $tranche => $libelle)
                @continue(empty($donnees['prix'][$tranche]))
                <li>
                  <label class="case case-ronde">
                    <input type="radio" name="prix" value="{{ $tranche }}" @checked(($actifs['prix'] ?? '') === $tranche)>
                    <span>{{ $libelle }}</span>
                    <small>{{ $donnees['prix'][$tranche] }}</small>
                  </label>
                </li>
              @endforeach
            </ul>
          </details>
        @endif
        @if ($tri !== 'menu_order')<input type="hidden" name="orderby" value="{{ $tri }}">@endif
        @if ($recherche)
          <input type="hidden" name="s" value="{{ get_search_query() }}"><input type="hidden" name="post_type" value="product">
        @endif
      </div>
      <div class="tiroir-pied">
        <a class="lien-simple" href="{{ $base }}" rel="nofollow">Tout effacer</a>
        <button class="bouton" type="submit">Voir les produits</button>
      </div>
    </form>
  </div>
@endsection
