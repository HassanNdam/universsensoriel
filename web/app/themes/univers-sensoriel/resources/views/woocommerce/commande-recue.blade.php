{{--
  Page « commande reçue ». Données préparées par App\confirmation_commande() (app/commande.php).
  $commande vaut null quand le lien n'est plus valable : seul le remerciement s'affiche.
--}}
@php
  $echec = $commande['echec'] ?? false;
  $etapes = $commande && $commande['retrait']
    ? ['Commande reçue', 'Préparation', 'Prête en boutique']
    : ['Commande reçue', 'Préparation', 'Expédition'];
@endphp

<div class="confirmation">
  <header class="confirmation-tete {{ $echec ? 'confirmation-tete-echec' : '' }}">
    <div class="conteneur">
      <span class="confirmation-sceau" aria-hidden="true">{!! App\icone($echec ? 'fermer' : 'coche', 34) !!}</span>

      @if ($echec)
        <h1>Paiement refusé</h1>
        <p class="chapo">Votre banque n'a pas validé le paiement. Rien n'a été débité.</p>
        <p class="confirmation-actions">
          <a class="bouton bouton-clair" href="{{ esc_url($commande['url_paiement']) }}">Réessayer le paiement</a>
        </p>
      @elseif ($commande)
        <h1>Merci{{ $commande['prenom'] ? ', ' . $commande['prenom'] : '' }}.</h1>
        <p class="chapo">
          Commande n° {{ $commande['numero'] }} enregistrée.
          @if ($commande['courriel'])
            Confirmation envoyée à {{ $commande['courriel'] }}.
          @endif
        </p>
      @else
        <h1>Merci.</h1>
        <p class="chapo">Votre commande a été reçue.</p>
      @endif

      @if ($commande && ! $echec)
        <ol class="confirmation-etapes" aria-label="Avancement de la commande">
          @foreach ($etapes as $rang => $etape)
            <li @class(['est-faite' => $rang === 0]) @if ($rang === 0) aria-current="step" @endif>
              <span class="confirmation-etape-point" aria-hidden="true">{!! $rang === 0 ? App\icone('coche', 14) : $rang + 1 !!}</span>
              {{ $etape }}
            </li>
          @endforeach
        </ol>
      @endif
    </div>
  </header>

  @if ($commande)
    <div class="conteneur confirmation-corps">
      <dl class="confirmation-resume">
        <div><dt>Numéro</dt><dd>{{ $commande['numero'] }}</dd></div>
        <div><dt>Date</dt><dd>{{ $commande['date'] }}</dd></div>
        <div><dt>Total</dt><dd>{!! $commande['total'] !!}</dd></div>
        @if ($commande['paiement'])
          <div><dt>Paiement</dt><dd>{{ $commande['paiement'] }}</dd></div>
        @endif
      </dl>

      @if ($crochets['avant'])
        <div class="confirmation-message">{!! $crochets['avant'] !!}</div>
      @endif

      <div class="confirmation-grille">
        <section class="confirmation-bloc" aria-labelledby="confirmation-articles">
          <h2 id="confirmation-articles">Votre commande</h2>
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

      @if ($crochets['apres'])
        <div class="confirmation-message">{!! $crochets['apres'] !!}</div>
      @endif

      @unless ($echec)
        <p class="confirmation-actions">
          <a class="bouton" href="{{ esc_url(wc_get_page_permalink('shop')) }}">Continuer mes achats</a>
          @if ($commande['url_suivi'])
            <a class="lien-or" href="{{ esc_url($commande['url_suivi']) }}">Suivre ma commande {!! App\icone('fleche', 16) !!}</a>
          @endif
        </p>
      @endunless
    </div>
  @endif
</div>
