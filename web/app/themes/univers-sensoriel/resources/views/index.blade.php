@extends('layouts.app')

@section('content')
  <header class="rayon-tete">
    <div class="conteneur">
      <p class="surtitre">Univers Sensoriel</p>
      <h1>{{ is_search() ? 'Résultats pour « ' . get_search_query() . ' »' : (is_home() ? 'Le journal' : wp_strip_all_tags(get_the_archive_title())) }}</h1>
    </div>
  </header>
  <div class="conteneur page-texte">
    @if (! have_posts())
      <p>Aucun contenu ne correspond pour le moment.</p>
      <p><a class="bouton" href="{{ home_url('/boutique/') }}">Voir la boutique</a></p>
    @endif
    @while (have_posts())
      @php(the_post())
      <article class="resultat">
        <h2><a href="{{ get_permalink() }}">{{ get_the_title() }}</a></h2>
        <p>{{ wp_strip_all_tags(get_the_excerpt()) }}</p>
      </article>
    @endwhile
    {!! get_the_posts_navigation(['prev_text' => 'Plus anciens', 'next_text' => 'Plus récents']) !!}
  </div>
@endsection
