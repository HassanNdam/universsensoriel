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
        <p class="surtitre">Nous écrire</p>
        <h1>{{ get_the_title() }}</h1>
        <p class="rayon-intro">Une question sur une commande, un produit ou une livraison ? Besoin d'un conseil pour choisir un parfum ? Nous répondons en général sous 24 heures ouvrées.</p>
      </div>
    </header>

    <div class="conteneur contact-grille">
      <section class="contact-formulaire" aria-labelledby="contact-titre">
        <p class="surtitre">Par écrit</p>
        <h2 id="contact-titre">Envoyez-nous un message</h2>
        <div class="formulaire">
          @php(the_content())
        </div>
        <p class="formulaire-mention">Vos nom, adresse e-mail et message servent uniquement à vous répondre. <a href="{{ home_url('/politique-de-confidentialite/') }}">Politique de confidentialité</a></p>
      </section>

      <aside class="contact-direct" aria-label="Nous joindre directement">
        <p class="surtitre surtitre-clair">En direct</p>
        <h2>Nous joindre autrement</h2>
        <ul class="contact-moyens">
          <li>
            <span class="contact-picto">{!! App\icone('whatsapp', 22) !!}</span>
            <p>
              <strong>WhatsApp</strong>
              <a href="{{ App\lien_whatsapp() }}" rel="noopener" target="_blank">{{ $c['telephone'] }}</a>
              <span>Le moyen le plus rapide de nous joindre.</span>
            </p>
          </li>
          <li>
            <span class="contact-picto">{!! App\icone('courriel', 22) !!}</span>
            <p>
              <strong>E-mail</strong>
              <a href="mailto:{{ $c['courriel'] }}">{{ $c['courriel'] }}</a>
              <span>Pour le suivi d'une commande ou un retour.</span>
            </p>
          </li>
          <li>
            <span class="contact-picto">{!! App\icone('sablier', 22) !!}</span>
            <p>
              <strong>Délai de réponse</strong>
              <span>Sous 24 heures, du lundi au samedi.</span>
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
      <h2 id="aide-titre">La réponse est peut-être déjà là</h2>
      <ul>
        <li>
          <a href="{{ home_url('/livraison-et-retours/') }}">
            <span class="contact-picto">{!! App\icone('livraison', 24) !!}</span>
            <strong>Livraison et retours</strong>
            <span>Délais, frais de port, échange d'un article.</span>
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
            <span>Pour trouver le parfum qui vous ressemble.</span>
          </a>
        </li>
      </ul>
    </section>
  @endwhile
@endsection
