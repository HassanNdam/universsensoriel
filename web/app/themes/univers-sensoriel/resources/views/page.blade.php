@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $boutique = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
    @endphp
    <header class="rayon-tete {{ $boutique ? 'rayon-tete-courte' : '' }}">
      <div class="conteneur">
        @include('partials.fil')
        <p class="surtitre">Univers Sensoriel</p>
        <h1>{{ get_the_title() }}</h1>
      </div>
    </header>
    <div class="conteneur {{ $boutique ? 'page-boutique' : 'page-texte' }}">
      <div class="{{ $boutique ? 'boutique-contenu' : 'prose' }}">
        @php(the_content())
      </div>
    </div>
  @endwhile
@endsection
