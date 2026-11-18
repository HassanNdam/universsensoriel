@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $rubrique = get_the_category()[0] ?? null;
      $journal = get_permalink(get_option('page_for_posts')) ?: home_url('/');
      $autres = App\articles_recents(3, [get_the_ID()]);
    @endphp

    <article class="article">
      <header class="article-tete">
        <div class="conteneur">
          <nav class="fil" aria-label="Fil d'Ariane">
            <ol>
              <li><a href="{{ home_url('/') }}">Accueil</a></li>
              <li><a href="{{ $journal }}">Le journal</a></li>
              @if ($rubrique)
                <li><a href="{{ get_category_link($rubrique) }}">{{ $rubrique->name }}</a></li>
              @endif
            </ol>
          </nav>
          <h1>{{ get_the_title() }}</h1>
          <p class="article-meta">
            <time datetime="{{ get_the_date('c') }}">{{ get_the_date('j F Y') }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ App\temps_de_lecture(get_the_ID()) }} min de lecture</span>
          </p>
        </div>
      </header>

      @if (has_post_thumbnail())
        <figure class="article-image conteneur">
          {!! get_the_post_thumbnail(null, 'large', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1100px) 1000px, 92vw']) !!}
        </figure>
      @endif

      <div class="conteneur page-texte article-corps">
        <div class="prose">
          @php(the_content())
        </div>
        <footer class="article-pied">
          <a class="lien-or" href="{{ $journal }}">{!! App\icone('fleche', 16, 'icone-retour') !!} Tous les articles</a>
          <a class="bouton bouton-contour" href="{{ home_url('/boutique/') }}">Voir la boutique</a>
        </footer>
      </div>
    </article>

    @if ($autres)
      <section class="section section-lin">
        <div class="conteneur">
          <header class="section-tete">
            <h2>À lire aussi</h2>
          </header>
          <div class="journal-grille">
            @foreach ($autres as $article)
              @include('partials.carte-article', ['article' => $article])
            @endforeach
          </div>
        </div>
      </section>
    @endif
  @endwhile
@endsection
