{{--
  Page « commande reçue ». Données préparées par App\confirmation_commande() (app/commande.php).
  $commande vaut null quand le lien n'est plus valable : seul le remerciement s'affiche.
--}}
@php
  $echec = $commande['echec'] ?? false;
  $etapes = App\etapes_commande($commande['retrait'] ?? false);
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

      @include('woocommerce.partials.commande-detail')

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
