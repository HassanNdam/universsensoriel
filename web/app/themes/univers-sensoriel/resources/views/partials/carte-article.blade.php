{{-- Carte d'un article du journal. Variables : $article (WP_Post), $titre (balise, h3 par défaut). --}}
@php
  $balise = $titre ?? 'h3';
  $rubrique = get_the_category($article->ID)[0] ?? null;
@endphp
<article class="article-carte">
  <a class="article-carte-image" href="{{ get_permalink($article) }}" tabindex="-1" aria-hidden="true">
    {!! get_the_post_thumbnail($article, 'large', ['loading' => 'lazy', 'sizes' => '(min-width: 900px) 33vw, 92vw', 'alt' => '']) ?: '<span class="visuel-absent">' . App\icone('etoile', 36) . '</span>' !!}
  </a>
  <div class="article-carte-corps">
    <p class="surtitre">{{ $rubrique?->name ?? App\texte('journal_titre') }} <span>· {{ App\temps_de_lecture($article->ID) }} min</span></p>
    <{{ $balise }} class="article-carte-titre"><a href="{{ get_permalink($article) }}">{!! get_the_title($article) !!}</a></{{ $balise }}>
    <p class="article-carte-extrait">{{ get_the_excerpt($article) }}</p>
    <span class="lien-or" aria-hidden="true">Lire l'article {!! App\icone('fleche', 16) !!}</span>
  </div>
</article>
