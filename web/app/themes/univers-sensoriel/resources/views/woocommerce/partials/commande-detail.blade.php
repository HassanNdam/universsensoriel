{{--
  Détail d'une commande : articles et totaux à gauche, livraison, facturation et aide à droite.
  Partagé par la page « commande reçue » et le compte client. $commande vient de App\confirmation_commande().
--}}
<div class="commande-detail confirmation-grille">
  <section class="confirmation-bloc" aria-label="Articles commandés">
    <h2>{{ $titre ?? 'Votre commande' }}</h2>
    <ul class="confirmation-articles">
      @foreach ($commande['articles'] as $article)
        <li>
          <span class="confirmation-vignette">{!! $article['image'] !!}</span>
          <span class="confirmation-article">
            @if ($article['url'])
              <a href="{{ esc_url($article['url']) }}">{{ $article['nom'] }}</a>
            @else
              {{ $article['nom'] }}
            @endif
            @if ($article['options'])
              <small>{!! wp_kses_post($article['options']) !!}</small>
            @endif
            <small>Quantité : {{ $article['quantite'] }}</small>
          </span>
          <span class="confirmation-prix">{!! $article['total'] !!}</span>
        </li>
      @endforeach
    </ul>

    <dl class="confirmation-totaux">
      @foreach ($commande['totaux'] as $ligne)
        <div @class(['est-total' => $ligne['cle'] === 'order_total'])>
          <dt>{{ $ligne['libelle'] }}</dt>
          <dd>{!! wp_kses_post($ligne['valeur']) !!}</dd>
        </div>
      @endforeach
    </dl>

    @if ($commande['note'])
      <p class="confirmation-note"><span>Votre note</span> {{ $commande['note'] }}</p>
    @endif
  </section>

  <aside class="confirmation-cote">
    @if ($commande['livraison'])
      <section class="confirmation-carte">
        <h2>{!! App\icone($commande['retrait'] ? 'boutique' : 'livraison', 22) !!} {{ $commande['retrait'] ? 'Retrait' : 'Livraison' }}</h2>
        <p class="confirmation-mode">{{ $commande['livraison'] }}</p>
        @if ($commande['adresse_livraison'])
          <address>{!! wp_kses_post($commande['adresse_livraison']) !!}</address>
        @endif
      </section>
    @endif

    @if ($commande['adresse_facturation'])
      <section class="confirmation-carte">
        <h2>{!! App\icone('courriel', 22) !!} Facturation</h2>
        <address>
          {!! wp_kses_post($commande['adresse_facturation']) !!}
          @if ($commande['telephone'])
            <br>{{ $commande['telephone'] }}
          @endif
          @if ($commande['courriel'])
            <br>{{ $commande['courriel'] }}
          @endif
        </address>
      </section>
    @endif

    <section class="confirmation-carte confirmation-aide">
      <h2>{!! App\icone('whatsapp', 22) !!} Une question ?</h2>
      <p><a class="lien-or" href="{{ App\lien_whatsapp('Bonjour, j\'ai une question sur ma commande n° ' . $commande['numero'] . '.') }}" target="_blank" rel="noopener">Écrire sur WhatsApp {!! App\icone('fleche', 16) !!}</a></p>
    </section>
  </aside>
</div>
