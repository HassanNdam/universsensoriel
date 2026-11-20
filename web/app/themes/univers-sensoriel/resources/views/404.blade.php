@extends('layouts.app')

@section('content')
  <section class="introuvable">
    <div class="conteneur">
      {!! App\icone('etoile', 44) !!}
      <p class="surtitre surtitre-clair">{{ App\texte('e404_surtitre') }}</p>
      <h1>{{ App\texte('e404_titre') }}</h1>
      <p>{{ App\texte('e404_texte') }}</p>
      <div class="ouverture-actions">
        <a class="bouton bouton-clair" href="{{ home_url('/boutique/') }}">{{ App\texte('e404_bouton') }}</a>
        <a class="lien-or" href="{{ home_url('/contact/') }}">{{ App\texte('e404_lien') }} {!! App\icone('fleche', 18) !!}</a>
      </div>
    </div>
  </section>
@endsection
