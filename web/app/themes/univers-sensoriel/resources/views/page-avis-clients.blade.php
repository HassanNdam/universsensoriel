@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $liste = App\avis();
    @endphp
    <header class="rayon-tete">
      <div class="conteneur">
        @include('partials.fil')
        <h1>{{ get_the_title() }}</h1>
        <p class="rayon-intro">{{ App\texte('avis_page_intro') }}</p>
      </div>
    </header>

    @if ($liste)
      <section class="conteneur avis-page" aria-label="Tous les avis">
        @include('partials.avis-carrousel', ['liste' => $liste, 'lienProduit' => true, 'titre' => 'Tous les avis'])
      </section>
    @endif

    <div class="conteneur page-texte">
      <div class="prose">
        @php(the_content())
      </div>
    </div>
  @endwhile
@endsection
