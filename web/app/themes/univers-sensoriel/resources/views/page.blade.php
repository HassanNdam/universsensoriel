@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $boutique = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
    @endphp
    @php
      // Commande reçue : la vue woocommerce/commande-recue porte son propre bandeau, sur toute la largeur.
      // Quand WooCommerce affiche autre chose à cette adresse (vérification de l'e-mail), la page garde sa mise en page habituelle.
      $confirmation = '';
      if (function_exists('App\est_confirmation_commande') && App\est_confirmation_commande()) {
          ob_start();
          the_content();
          $confirmation = ob_get_clean();
      }
      $pleineLargeur = str_contains($confirmation, 'class="confirmation"');
    @endphp
    @if ($pleineLargeur)
      {!! $confirmation !!}
    @else
      <header class="rayon-tete {{ $boutique ? 'rayon-tete-courte' : '' }}">
        <div class="conteneur">
          @include('partials.fil')
          <h1>{{ get_the_title() }}</h1>
        </div>
      </header>
      <div class="conteneur {{ $boutique ? 'page-boutique' : 'page-texte' }}">
        <div class="{{ $boutique ? 'boutique-contenu' : 'prose' }}">
          @if ($confirmation)
            {!! $confirmation !!}
          @else
            @php(the_content())
          @endif
        </div>
      </div>
    @endif
  @endwhile
@endsection
