@extends('layouts.app')

@section('content')
  @php
    global $wp_query;
    $rubrique = is_category() ? get_queried_object() : null;
    $titre = is_search() ? 'Résultats pour « ' . get_search_query() . ' »' : ($rubrique ? $rubrique->name : 'Le journal');
    $rubriques = get_categories(['hide_empty' => true]);
    $journal = get_permalink(get_option('page_for_posts')) ?: home_url('/');
  @endphp

  <header class="rayon-tete">
    <div class="conteneur">
      <p class="surtitre">{{ $rubrique ? 'Le journal' : 'Conseils et rituels' }}</p>
      <h1>{{ $titre }}</h1>
      @if (! is_search() && ! is_paged())
        <p class="rayon-intro">{{ $rubrique && $rubrique->description ? wp_strip_all_tags($rubrique->description) : 'Parfum, soin de la peau, cheveux, bien-être : nos conseils pour choisir, appliquer et faire durer.' }}</p>
      @endif
      @if (! is_search() && count($rubriques) > 1)
        <ul class="pastilles pastilles-centre">
          <li><a class="pastille {{ $rubrique ? '' : 'pastille-pleine' }}" href="{{ $journal }}" @if (! $rubrique) aria-current="page" @endif>Tout</a></li>
          @foreach ($rubriques as $r)
            <li><a class="pastille {{ $rubrique && $rubrique->term_id === $r->term_id ? 'pastille-pleine' : '' }}" href="{{ get_category_link($r) }}" @if ($rubrique && $rubrique->term_id === $r->term_id) aria-current="page" @endif>{{ $r->name }}</a></li>
          @endforeach
        </ul>
      @endif
    </div>
  </header>

  <div class="conteneur journal">
    @if (! have_posts())
      <div class="rayon-vide">
        {!! App\icone('etoile', 36) !!}
        <h2>Rien pour le moment</h2>
        <p>Aucun article ne correspond. Revenez bientôt, ou parcourez la boutique.</p>
        <p><a class="bouton" href="{{ home_url('/boutique/') }}">Voir la boutique</a></p>
      </div>
    @else
      <div class="journal-grille {{ ! is_paged() && ! $rubrique && ! is_search() ? 'journal-grille-une' : '' }}">
        @while (have_posts())
          @php(the_post())
          @include('partials.carte-article', ['article' => get_post(), 'titre' => 'h2'])
        @endwhile
      </div>

      @php($pages = paginate_links(['type' => 'array', 'prev_text' => 'Précédent', 'next_text' => 'Suivant', 'mid_size' => 1]))
      @if ($pages)
        <nav class="pagination" aria-label="Pages du journal">
          <ul>
            @foreach ($pages as $page)
              <li>{!! $page !!}</li>
            @endforeach
          </ul>
        </nav>
      @endif
    @endif
  </div>
@endsection
