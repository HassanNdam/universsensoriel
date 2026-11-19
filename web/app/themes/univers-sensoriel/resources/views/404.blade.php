@extends('layouts.app')

@section('content')
  <section class="introuvable">
    <div class="conteneur">
      {!! App\icone('etoile', 44) !!}
      <p class="surtitre surtitre-clair">Erreur 404</p>
      <h1>Cette page s'est évaporée</h1>
      <p>Le lien est peut-être ancien.</p>
      <div class="ouverture-actions">
        <a class="bouton bouton-clair" href="{{ home_url('/boutique/') }}">Voir la boutique</a>
        <a class="lien-or" href="{{ home_url('/contact/') }}">Nous écrire {!! App\icone('fleche', 18) !!}</a>
      </div>
    </div>
  </section>
@endsection
