{{--
  Page Contact. Le contenu de la page (admin) n'appelle que le shortcode Forminator :
  [forminator_form id="…"]. Coordonnées, boutiques, textes et liens d'aide se modifient dans
  l'administration : Univers Sensoriel › Coordonnées et Textes du site (app/reglages.php).
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
        <p class="rayon-intro">{{ App\texte('delai_reponse') }}</p>
      </div>
    </header>

    <div class="conteneur contact-grille">
      <section class="contact-formulaire" aria-label="Formulaire de contact">
        <div class="formulaire">
          @php(the_content())
        </div>
        <p class="formulaire-mention">{{ App\texte('contact_mention') }} <a href="{{ home_url('/politique-de-confidentialite/') }}">Confidentialité</a></p>
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
          <p class="surtitre surtitre-clair">{{ App\texte('contact_boutiques') }}</p>
          <ul>
            @foreach ($c['boutiques'] as $b)
              <li>
                <strong>{{ $b['ville'] }}</strong>
                <span>{{ $b['enseigne'] }} · {{ $b['pays'] }}</span>
                @if ($b['adresse'])
                  <span>{{ $b['adresse'] }}</span>
                @endif
                @if ($b['telephone'])
                  <a href="{{ App\lien_telephone($b['telephone']) }}">{{ $b['telephone'] }}</a>
                @endif
              </li>
            @endforeach
          </ul>
          <a class="lien-or" href="{{ home_url('/notre-boutique/') }}">Horaires et retrait {!! App\icone('fleche', 18) !!}</a>
        </div>
      </aside>
    </div>

    <section class="conteneur contact-aide" aria-labelledby="aide-titre">
      <h2 id="aide-titre">{{ App\texte('contact_aide_titre') }}</h2>
      <ul>
        @foreach (App\lignes('contact_aide', ['titre']) as $aide)
          <li>
            <a href="{{ str_starts_with((string) $aide['lien'], '/') ? home_url($aide['lien']) : $aide['lien'] }}">
              <span class="contact-picto">{!! App\icone($aide['icone'], 24) !!}</span>
              <strong>{{ $aide['titre'] }}</strong>
              <span>{{ $aide['texte'] }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    </section>
  @endwhile
@endsection
