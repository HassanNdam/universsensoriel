{{--
  Page Contact. Le contenu de la page (admin) n'appelle que le shortcode Forminator :
  [forminator_form id="…"]. Tout le reste (coordonnées, boutiques, liens d'aide) est ici
  et se nourrit de App\coordonnees().
--}}
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $c = App\coordonnees();
    @endphp
    <header class="rayon-tete">
      <div class="conteneur">
        @include('partials.fil')
        <h1>{{ get_the_title() }}</h1>
        <p class="rayon-intro">Réponse sous 24 heures ouvrées.</p>
      </div>
    </header>

    <div class="conteneur contact-grille">
      <section class="contact-formulaire" aria-label="Formulaire de contact">
        <div class="formulaire">
          @php(the_content())
        </div>
        <p class="formulaire-mention">Vos données servent uniquement à vous répondre. <a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></p>
      </section>

      <aside class="contact-direct" aria-label="Nous joindre directement">
        <ul class="contact-moyens">
          <li>
            <span class="contact-picto">{!! App\icone('whatsapp', 22) !!}</span>
            <p>
              <strong>WhatsApp</strong>
              <a href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank">{{ $c['telephone'] }}</a>
            </p>
          </li>
          <li>
            <span class="contact-picto">{!! App\icone('courriel', 22) !!}</span>
            <p>
              <strong>E-mail</strong>
              <a href="mailto:{{ $c['courriel'] }}">{{ $c['courriel'] }}</a>
            </p>
          </li>
        </ul>

        <div class="contact-boutiques">
          <p class="surtitre surtitre-clair">En boutique</p>
          <ul>
            @foreach ($c['boutiques'] as $b)
              <li>
                <strong>{{ $b['ville'] }}</strong>
                <span>{{ $b['enseigne'] }} · {{ $b['pays'] }}</span>
                <a href="tel:{{ preg_replace('/[^+\d]/', '', $b['telephone']) }}">{{ $b['telephone'] }}</a>
              </li>
            @endforeach
          </ul>
          <a class="lien-or" href="{{ home_url('/nos-boutiques/') }}">Voir nos boutiques {!! App\icone('fleche', 18) !!}</a>
        </div>
      </aside>
    </div>

    <section class="conteneur contact-aide" aria-labelledby="aide-titre">
      <h2 id="aide-titre">Aide</h2>
      <ul>
        <li>
          <a href="{{ home_url('/livraison-et-retours/') }}">
            <span class="contact-picto">{!! App\icone('livraison', 24) !!}</span>
            <strong>Livraison et retours</strong>
            <span>Délais, frais de port, retours.</span>
          </a>
        </li>
        <li>
          <a href="{{ home_url('/questions-frequentes/') }}">
            <span class="contact-picto">{!! App\icone('plus', 24) !!}</span>
            <strong>Questions fréquentes</strong>
            <span>Commande, paiement, tenue des parfums.</span>
          </a>
        </li>
        <li>
          <a href="{{ home_url('/guide-des-familles-olfactives/') }}">
            <span class="contact-picto">{!! App\icone('fleur', 24) !!}</span>
            <strong>Guide des familles olfactives</strong>
            <span>Trouver son parfum.</span>
          </a>
        </li>
      </ul>
    </section>
  @endwhile
@endsection
