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
        <p class="surtitre">Paroles de clientes et de clients</p>
        <h1>{{ get_the_title() }}</h1>
        <p class="rayon-intro">Des avis reproduits tels quels, avec leur date. Peu nombreux pour l'instant : nous préférons quelques avis vrais à une longue liste.</p>
      </div>
    </header>

    @if ($liste)
      <section class="conteneur avis-page" aria-label="Tous les avis">
        <div class="avis-grille">
          @foreach ($liste as $avis)
            @include('partials.carte-avis', ['avis' => $avis, 'lienProduit' => true])
          @endforeach
        </div>
      </section>
    @endif

    <div class="conteneur page-texte">
      <div class="prose">
        @php(the_content())
      </div>
    </div>
  @endwhile
@endsection
